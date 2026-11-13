<?php



namespace App\Services;



use App\Models\ChatMessage;
use App\Models\ChatReaction;

use App\Models\ChatRoom;

use App\Models\Territory;

use App\Models\User;

use Illuminate\Support\Collection;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;



class ChatService

{

    public const MAX_LENGTH = 2000;

    public const ATTACHMENT_MAX_KB = 10240;



    public function canAccess(User $user, ChatRoom $room): bool

    {

        return match ($room->type) {

            'global' => true,

            'territory' => $user->isAdmin() || (int) $user->territory_id === (int) $room->territory_id,

            'direct' => in_array((int) $user->id, [(int) $room->user_a_id, (int) $room->user_b_id], true),

            default => false,

        };

    }



    public function roomsFor(User $user): Collection

    {

        $rooms = collect([ChatRoom::firstOrCreate(['type' => 'global'])]);



        $territories = $user->isAdmin()

            ? Territory::query()->orderBy('city')->get()

            : Territory::query()->whereKey($user->territory_id)->get();



        foreach ($territories as $territory) {

            $rooms->push(ChatRoom::firstOrCreate(['type' => 'territory', 'territory_id' => $territory->id]));

        }



        $direct = ChatRoom::query()

            ->where('type', 'direct')

            ->where(fn ($q) => $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id))

            ->with(['userA', 'userB'])

            ->get();



        return $rooms->concat($direct)->values();

    }



    public function title(ChatRoom $room, User $viewer): string

    {

        return match ($room->type) {

            'global' => 'Salon gÃ©nÃ©ral',

            'territory' => 'Territoire Â· '.($room->territory?->city ?? '?'),

            'direct' => $this->otherUser($room, $viewer)?->name ?? 'Compte supprimÃ©',

            default => 'Discussion',

        };

    }



    public function otherUser(ChatRoom $room, User $viewer): ?User

    {

        return (int) $room->user_a_id === (int) $viewer->id ? $room->userB : $room->userA;

    }



    public function directWith(User $me, User $other): ChatRoom

    {

        abort_if($me->id === $other->id, 422, 'Impossible de discuter avec soi-mÃªme.');



        [$a, $b] = $me->id < $other->id ? [$me->id, $other->id] : [$other->id, $me->id];



        return ChatRoom::firstOrCreate(['type' => 'direct', 'user_a_id' => $a, 'user_b_id' => $b]);

    }



    public function post(User $user, ChatRoom $room, string $body, string $kind = 'text'): ChatMessage

    {

        abort_unless($this->canAccess($user, $room), 403);



        $body = trim($body);

        abort_if($body === '' || mb_strlen($body) > self::MAX_LENGTH, 422, 'Message vide ou trop long.');



        $message = new ChatMessage(['room_id' => $room->id, 'user_id' => $user->id, 'kind' => $kind, 'body' => $body]);
        $message->created_at = now();
        $message->save();

        return $message;

    }



    
public function postAttachment(User $user, ChatRoom $room, \Illuminate\Http\UploadedFile $file, ?string $caption = null): ChatMessage

    {

        abort_unless($this->canAccess($user, $room), 403);



        $caption = trim((string) $caption);

        abort_if(mb_strlen($caption) > self::MAX_LENGTH, 422, 'Message trop long.');



        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];

        $mime = (string) $file->getMimeType();

        abort_unless(isset($extensions[$mime]), 422, 'Type de fichier non autorisÃ©.');



        $size = (int) $file->getSize();

        abort_if($size > self::ATTACHMENT_MAX_KB * 1024, 422, 'Fichier trop lourd.');



        $original = (string) $file->getClientOriginalName();

        $original = Str::limit(preg_replace('/[\r\n"\\\\\/]+/', '_', $original) ?? 'fichier', 120, '');



        $root = rtrim((string) config('partner_portal.private_root'), '/');

        abort_if($root === '', 500, 'PARTNER_PRIVATE_ROOT non configurÃ©.');



        $dir = $root.'/chat/'.$room->id;

        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {

            abort(500, 'Dossier privÃ© du chat inaccessible.');

        }



        $stored = Str::lower(Str::random(40)).'.'.$extensions[$mime];

        $file->move($dir, $stored);



        $message = new ChatMessage([

            'room_id' => $room->id,

            'user_id' => $user->id,

            'kind' => str_starts_with($mime, 'image/') ? 'image' : 'file',

            'body' => $caption,

            'attachment_path' => $room->id.'/'.$stored,

            'attachment_name' => $original,

            'attachment_mime' => $mime,

            'attachment_size' => $size,

        ]);

        $message->created_at = now();

        $message->save();



        return $message;

    }


    public function startCall(User $user, ChatRoom $room): ChatMessage

    {

        abort_unless($room->type === 'direct', 422, 'Les appels sont rÃ©servÃ©s aux messages privÃ©s.');



        $link = 'https://meet.jit.si/jnuaf-'.Str::lower(Str::random(24));



        return $this->post($user, $room, $link, 'call');

    }



    
    public const EMOJIS = ['í ½í±', 'â¤ï¸', 'í ½í¸‚', 'í ½í¸®', 'í ¼í¾‰'];



    public function canDelete(User $user, ChatMessage $message): bool

    {

        if ($message->user_id === $user->id) { return true; }

        if (! $user->isAdmin()) { return false; }



        $room = $message->room;



        return $room !== null && $room->type === 'territory';

    }



    public function deleteMessage(User $user, ChatMessage $message): void

    {

        abort_unless($this->canDelete($user, $message), 403);

        $message->delete();

    }



    public function toggleReaction(User $user, ChatMessage $message, string $emoji): void

    {

        abort_unless(in_array($emoji, self::EMOJIS, true), 422, 'RÃ©action inconnue.');

        abort_unless($message->trashed() === false, 422, 'Message supprimÃ©.');



        $existing = ChatReaction::query()

            ->where('message_id', $message->id)

            ->where('user_id', $user->id)

            ->where('emoji', $emoji)

            ->first();



        if ($existing) { $existing->delete(); return; }



        ChatReaction::create(['message_id' => $message->id, 'user_id' => $user->id, 'emoji' => $emoji]);

    }



    public function reactionsFor(Collection $messages): array

    {

        if ($messages->isEmpty()) { return []; }



        $rows = ChatReaction::query()

            ->whereIn('message_id', $messages->pluck('id'))

            ->with('user:id,name')

            ->get();



        $out = [];



        foreach ($rows as $row) {

            $out[$row->message_id][$row->emoji][] = [

                'id' => (int) $row->user_id,

                'name' => $row->user?->name ?? 'Compte supprimÃ©',

            ];

        }



        return $out;

    }



    public function markRead(
User $user, ChatRoom $room, int $lastMessageId): void

    {

        DB::table('chat_reads')->updateOrInsert(

            ['room_id' => $room->id, 'user_id' => $user->id],

            ['last_message_id' => $lastMessageId]

        );

    }



    public function unreadCounts(User $user, Collection $rooms): array

    {

        $reads = DB::table('chat_reads')->where('user_id', $user->id)->pluck('last_message_id', 'room_id');

        $counts = [];



        foreach ($rooms as $room) {

            $counts[$room->id] = ChatMessage::query()

                ->where('room_id', $room->id)

                ->where('id', '>', (int) ($reads[$room->id] ?? 0))

                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $user->id))

                ->count();

        }



        return $counts;

    }

}


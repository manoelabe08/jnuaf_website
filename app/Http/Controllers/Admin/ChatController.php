<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\ChatMessage;
use App\Models\ChatReaction;

use App\Models\ChatRoom;

use App\Models\User;

use App\Services\ChatService;

use Illuminate\Contracts\View\View;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;



class ChatController extends Controller

{

    public function show(Request $request, ChatService $chat, ?int $room = null): View

    {

        $user = $request->user();

        $rooms = $chat->roomsFor($user);

        $current = $room !== null ? $rooms->firstWhere('id', $room) : $rooms->first();



        abort_if($current === null, 404);



        $messages = $current->messages()->with('user')->orderByDesc('id')->limit(100)->get()->reverse()->values();

        $lastId = (int) ($messages->last()?->id ?? 0);



        if ($lastId > 0) {

            $chat->markRead($user, $current, $lastId);

        }



        $unread = $chat->unreadCounts($user, $rooms);

        $unread[$current->id] = 0;



        return view('admin.chat.index', [

            'rooms' => $rooms,

            'current' => $current,

            'messages' => $messages,

            'lastId' => $lastId,

            'unread' => $unread,

            'titles' => $rooms->mapWithKeys(fn ($r) => [$r->id => $chat->title($r, $user)])->all(),

            'meId' => (int) $user->id,

            'users' => User::query()->where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name']),

        ]);

    }



    public function poll(Request $request, ChatService $chat, ChatRoom $room): JsonResponse

    {

        $user = $request->user();

        abort_unless($chat->canAccess($user, $room), 403);



        $after = max(0, (int) $request->query('after', 0));

        $messages = $room->messages()->with('user')->where('id', '>', $after)->orderBy('id')->limit(100)->get();



        if ($messages->isNotEmpty()) {

            $chat->markRead($user, $room, (int) $messages->last()->id);

        }



        return response()->json([

            'messages' => $messages->map(fn ($m) => $this->payload($m, $user))->values(),

        ]);

    }



    
public function send(Request $request, ChatService $chat, ChatRoom $room): JsonResponse|RedirectResponse

    {

        $data = $request->validate([

            'body' => ['nullable', 'string', 'max:'.ChatService::MAX_LENGTH],

            'attachment' => ['nullable', 'file', 'max:'.ChatService::ATTACHMENT_MAX_KB, 'mimes:jpg,jpeg,png,webp,pdf'],

        ]);



        if ($request->hasFile('attachment')) {

            $message = $chat->postAttachment($request->user(), $room, $request->file('attachment'), $data['body'] ?? '');

        } else {

            abort_if(trim((string) ($data['body'] ?? '')) === '', 422, 'Message vide.');

            $message = $chat->post($request->user(), $room, (string) $data['body']);

        }



        if ($request->expectsJson()) {

            return response()->json(['message' => $this->payload($message->load('user'), $request->user())]);

        }



        return redirect()->route('admin.chat.show', $room);

    }




    public function direct(Request $request, ChatService $chat): RedirectResponse

    {

        $data = $request->validate([

            'user_id' => ['required', 'integer', 'exists:users,id'],

        ]);



        $room = $chat->directWith($request->user(), User::findOrFail($data['user_id']));



        return redirect()->route('admin.chat.show', $room);

    }



    public function call(Request $request, ChatService $chat, ChatRoom $room): RedirectResponse

    {

        $chat->startCall($request->user(), $room);



        return redirect()->route('admin.chat.show', $room);

    }



    public function unread(Request $request, ChatService $chat): JsonResponse

    {

        $user = $request->user();



        return response()->json(['counts' => $chat->unreadCounts($user, $chat->roomsFor($user))]);

    }



    
    public function destroy(Request $request, ChatService $chat, ChatMessage $message): JsonResponse

    {

        $chat->deleteMessage($request->user(), $message);



        return response()->json(['deleted' => true]);

    }



    public function react(Request $request, ChatService $chat, ChatMessage $message): JsonResponse

    {

        $data = $request->validate(['emoji' => ['required', 'string', 'max:16']]);



        $chat->toggleReaction($request->user(), $message, $data['emoji']);

        $reactions = $chat->reactionsFor(collect([$message->load('user')]));



        return response()->json(['reactions' => $reactions[$message->id] ?? []]);

    }



    
public function attachment(Request $request, ChatService $chat, ChatMessage $message)

    {

        $room = $message->room;

        abort_unless($room && $chat->canAccess($request->user(), $room), 403);

        abort_if(! $message->attachment_path, 404);



        $base = realpath(rtrim((string) config('partner_portal.private_root'), '/').'/chat');

        $real = realpath(rtrim((string) config('partner_portal.private_root'), '/').'/chat/'.$message->attachment_path);

        abort_unless($base && $real && str_starts_with($real, $base.DIRECTORY_SEPARATOR) && is_file($real), 404);



        $name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', (string) $message->attachment_name) ?: 'fichier';

        $isImage = str_starts_with((string) $message->attachment_mime, 'image/');
        $mode = ($isImage && ! $request->boolean('download')) ? 'inline' : 'attachment';



        return response()->file($real, [

            'Content-Type' => $message->attachment_mime ?: 'application/octet-stream',

            'Content-Disposition' => $mode.'; filename="'.$name.'"',

            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",

            'Cache-Control' => 'private, max-age=3600',

        ]);

    }



    private function payload(ChatMessage $message, User $viewer): array

    {

        return [

            'id' => (int) $message->id,

            'mine' => (int) $message->user_id === (int) $viewer->id,

            'author' => $message->user?->name ?? 'Compte supprimé',

            'kind' => $message->kind,

            'body' => $message->body,

            'at' => $message->created_at?->toIso8601String(),

            'attachment' => $message->attachment_path ? [

                'name' => $message->attachment_name,

                'mime' => $message->attachment_mime,

                'size' => (int) $message->attachment_size,

                'image' => str_starts_with((string) $message->attachment_mime, 'image/'),

                'url' => route('admin.chat.attachment', $message),

            ] : null,

        ];

    }


}


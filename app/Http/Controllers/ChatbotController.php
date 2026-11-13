<?php



namespace App\Http\Controllers;



use App\Services\Chatbot\ChatbotEngine;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;



class ChatbotController extends Controller

{

    private function off(): bool

    {

        return is_file(storage_path('app/chatbot.off'));

    }



    private function disabled(): JsonResponse

    {

        return response()->json(['disabled' => true], 503);

    }



    private function mask(string $t): string

    {

        $t = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email]', $t) ?? $t;

        $t = preg_replace('/\+?\d[\d\s().\-]{7,}\d/', '[numero]', $t) ?? $t;



        return $t;

    }



    private function event(string $type, ?int $conversationId = null, ?string $ref = null): void

    {

        DB::table('chatbot_events')->insert([

            'type' => $type,

            'conversation_id' => $conversationId,

            'ref' => $ref ? mb_substr($ref, 0, 80) : null,

            'created_at' => now(),

        ]);

    }



    private function conversation(?string $code, Request $request, ?string $page): object

    {

        if ($code) {

            $c = DB::table('chatbot_conversations')->where('code', strtoupper($code))->first();

            if ($c) {

                return $c;

            }

        }

        do {

            $new = strtoupper(Str::random(8));

        } while (DB::table('chatbot_conversations')->where('code', $new)->exists());

        $now = now();

        $id = DB::table('chatbot_conversations')->insertGetId([

            'code' => $new,

            'status' => 'bot',

            'source_url' => $page ? mb_substr($page, 0, 255) : null,

            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),

            'last_message_at' => $now,

            'purge_after' => $now->copy()->addMonths(6),

            'created_at' => $now,

            'updated_at' => $now,

        ]);

        $this->event('conversation', (int) $id);



        return DB::table('chatbot_conversations')->where('id', $id)->first();

    }



    public function init(): JsonResponse

    {

        if ($this->off()) {

            return $this->disabled();

        }



        return response()->json([

            'welcome' => "Bonjour ! Je suis l'assistant de la JNUAF. Posez-moi votre question ou choisissez un sujet.",

            'privacy' => "Ne saisissez pas de donn\u{e9}es personnelles dans vos questions. Les conversations sont conserv\u{e9}es 6 mois.",

            'options' => DB::table('chatbot_options')->where('active', 1)->orderBy('position')->get(['id', 'label', 'action', 'faq_id', 'target']),

            'suggestions' => DB::table('chatbot_faqs')->where('active', 1)->orderByDesc('hits')->orderBy('id')->limit(4)->get(['id', 'question']),

            'teams' => DB::table('chatbot_teams')->where('active', 1)->orderBy('position')->get(['slug', 'label']),

        ]);

    }



    public function message(Request $request): JsonResponse

    {

        if ($this->off()) {

            return $this->disabled();

        }

        $d = $request->validate([

            'message' => 'required|string|max:500',

            'code' => 'nullable|string|max:16',

            'faq_id' => 'nullable|integer',

            'page' => 'nullable|string|max:255',

        ]);

        $text = trim($d['message']);

        $conv = $this->conversation($d['code'] ?? null, $request, $d['page'] ?? null);



        $faq = null;

        if (! empty($d['faq_id'])) {

            $faq = DB::table('chatbot_faqs')->where('id', $d['faq_id'])->where('active', 1)->first();

        }

        $engine = app(ChatbotEngine::class);

        $res = $faq

            ? ['type' => 'faq', 'faq_id' => (int) $faq->id, 'question' => (string) $faq->question, 'text' => (string) $faq->answer]

            : $engine->answer($text);



        $faqId = null;

        if ($res['type'] === 'none') {

            $reply = "Je n'ai pas trouv\u{e9} de r\u{e9}ponse pr\u{e9}cise. Choisissez une question proche ou contactez l'\u{e9}quipe.";

            $engine->recordUnanswered($this->mask($text));

            $this->event('unanswered', (int) $conv->id);

        } else {

            $reply = (string) $res['text'];

            if ($res['type'] === 'faq') {

                $faqId = (int) $res['faq_id'];

                DB::table('chatbot_faqs')->where('id', $faqId)->increment('hits');

            }

            $this->event('answered', (int) $conv->id, $res['type']);

        }$now = now();

        DB::table('chatbot_messages')->insert([

            ['conversation_id' => $conv->id, 'sender' => 'visitor', 'body' => mb_substr($this->mask($text), 0, 500), 'faq_id' => null, 'created_at' => $now],

            ['conversation_id' => $conv->id, 'sender' => 'bot', 'body' => $reply, 'faq_id' => $faqId, 'created_at' => $now],

        ]);

        DB::table('chatbot_conversations')->where('id', $conv->id)->update([

            'last_message_at' => $now,

            'purge_after' => $now->copy()->addMonths(6),

            'updated_at' => $now,

        ]);



        return response()->json([

            'code' => $conv->code,

            'type' => $res['type'],

            'text' => $reply,

            'faq_id' => $faqId,

            'suggestions' => $res['type'] === 'none' ? ($res['suggestions'] ?? []) : [],

        ]);

    }



    public function feedback(Request $request): JsonResponse

    {

        if ($this->off()) {

            return $this->disabled();

        }

        $d = $request->validate([

            'faq_id' => 'required|integer',

            'helpful' => 'required|boolean',

            'code' => 'nullable|string|max:16',

        ]);

        $key = 'chatbot_fb:' . ($d['code'] ?? $request->ip()) . ':' . $d['faq_id'];

        if (! Cache::add($key, 1, 3600)) {

            return response()->json(['ok' => true, 'duplicate' => true]);

        }

        $col = $d['helpful'] ? 'helpful_yes' : 'helpful_no';

        DB::table('chatbot_faqs')->where('id', $d['faq_id'])->increment($col);

        $conv = ! empty($d['code']) ? DB::table('chatbot_conversations')->where('code', strtoupper($d['code']))->first() : null;

        $this->event($d['helpful'] ? 'feedback_yes' : 'feedback_no', $conv ? (int) $conv->id : null, (string) $d['faq_id']);



        return response()->json(['ok' => true]);

    }



    public function handoff(Request $request): JsonResponse

    {

        if ($this->off()) {

            return $this->disabled();

        }

        $d = $request->validate([

            'code' => 'nullable|string|max:16',

            'first_name' => 'required|string|max:80',

            'email' => 'nullable|email|max:190',

            'phone' => 'nullable|string|max:32',

            'team' => 'required|string|max:40',

            'message' => 'nullable|string|max:500',

            'consent' => 'accepted',

        ]);

        if (empty($d['email']) && empty($d['phone'])) {

            return response()->json(['message' => "Indiquez un e-mail ou un t\u{e9}l\u{e9}phone.", 'errors' => ['email' => ["Indiquez un e-mail ou un t\u{e9}l\u{e9}phone."]]], 422);

        }

        $team = DB::table('chatbot_teams')->where('slug', $d['team'])->where('active', 1)->first();

        if (! $team) {

            return response()->json(['message' => "\u{c9}quipe inconnue."], 422);

        }

        $conv = $this->conversation($d['code'] ?? null, $request, null);

        $now = now();

        DB::table('chatbot_conversations')->where('id', $conv->id)->update([

            'first_name' => $d['first_name'],

            'email' => $d['email'] ?? null,

            'phone' => $d['phone'] ?? null,

            'team' => $team->slug,

            'status' => 'waiting',

            'consent_at' => $now,

            'waiting_since' => $now,

            'last_message_at' => $now,

            'purge_after' => $now->copy()->addMonths(6),

            'updated_at' => $now,

        ]);

        if (! empty($d['message'])) {

            DB::table('chatbot_messages')->insert(['conversation_id' => $conv->id, 'sender' => 'visitor', 'body' => mb_substr($this->mask($d['message']), 0, 500), 'faq_id' => null, 'created_at' => $now]);

        }

        $this->event('handoff', (int) $conv->id, $team->slug);


        try {

            \Illuminate\Support\Facades\Mail::raw(

                "Nouvelle demande de contact via le chatbot\n\nR\u{e9}f. : " . $conv->code . "\nPr\u{e9}nom : " . $d['first_name'] . "\n\u{c9}quipe : " . $team->label . "\nE-mail : " . ($d['email'] ?? '-') . "\nT\u{e9}l\u{e9}phone : " . ($d['phone'] ?? '-') . "\nMessage : " . ($d['message'] ?? '-') . "\n",

                function ($m) {

                    $m->to('journeedunumeriqueaufeminin@gmail.com')->subject('Chatbot JNUAF : demande de contact');

                }

            );

        } catch (\Throwable $e) {

            report($e);

        }




        $wa = 'https://wa.me/' . $team->whatsapp_number . '?text=' . rawurlencode('Bonjour, je suis ' . $d['first_name'] . " (r\u{e9}f. " . $conv->code . ').');



        return response()->json([

            'ok' => true,

            'code' => $conv->code,

            'text' => "Merci ! Votre demande est transmise \u{e0} " . $team->label . ". Vous pouvez aussi nous \u{e9}crire sur WhatsApp.",

            'whatsapp' => $wa,

        ]);

    }

}


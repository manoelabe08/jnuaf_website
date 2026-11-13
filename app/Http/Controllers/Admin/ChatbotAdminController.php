<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;



class ChatbotAdminController extends Controller

{

    private function guard(Request $request): void

    {

        abort_unless($request->user()?->isAdmin(), 404);

    }



    public function index(Request $request)

    {

        $this->guard($request);

        $d7 = now()->subDays(7);

        $d30 = now()->subDays(30);

        $ev = fn (string $type) => DB::table('chatbot_events')->where('type', $type)->where('created_at', '>=', $d30)->count();

        $answered = $ev('answered');

        $unans = $ev('unanswered');

        $yes = $ev('feedback_yes');

        $no = $ev('feedback_no');



        $stats = [

            'enabled' => ! is_file(storage_path('app/chatbot.off')),

            'conv7' => DB::table('chatbot_conversations')->where('created_at', '>=', $d7)->count(),

            'conv30' => DB::table('chatbot_conversations')->where('created_at', '>=', $d30)->count(),

            'questions30' => DB::table('chatbot_messages')->where('sender', 'visitor')->where('created_at', '>=', $d30)->count(),

            'rate' => ($answered + $unans) > 0 ? (int) round($answered * 100 / ($answered + $unans)) : null,

            'helpful' => ($yes + $no) > 0 ? (int) round($yes * 100 / ($yes + $no)) : null,

            'open' => DB::table('chatbot_unanswered')->where('status', 'open')->count(),

            'learned' => DB::table('chatbot_faqs')->where('source', 'learned')->count(),

            'waiting' => DB::table('chatbot_conversations')->where('status', 'waiting')->count(),

            'faqs' => DB::table('chatbot_faqs')->where('active', 1)->count(),

        ];

        $top = DB::table('chatbot_faqs')->where('active', 1)->orderByDesc('hits')->orderBy('id')->limit(8)->get(['id', 'question', 'hits', 'helpful_yes', 'helpful_no']);

        $unanswered = DB::table('chatbot_unanswered')->where('status', 'open')->orderByDesc('occurrences')->orderByDesc('last_seen_at')->limit(30)->get();

        $requests = DB::table('chatbot_conversations')->where('status', 'waiting')->orderBy('waiting_since')->get(['id', 'code', 'first_name', 'email', 'phone', 'team', 'waiting_since']);

        $teams = DB::table('chatbot_teams')->pluck('label', 'slug');



        return view('admin.chatbot.index', compact('stats', 'top', 'unanswered', 'requests', 'teams'));

    }



    public function answer(Request $request, int $id)

    {

        $this->guard($request);

        $row = DB::table('chatbot_unanswered')->where('id', $id)->where('status', 'open')->first();

        abort_unless($row, 404);

        $d = $request->validate([

            'question' => 'required|string|max:255',

            'answer' => 'required|string|max:2000',

            'keywords' => 'nullable|string|max:500',

            'category' => 'nullable|string|max:60',

        ]);

        $now = now();

        $faqId = DB::table('chatbot_faqs')->insertGetId([

            'category' => $d['category'] ?? null,

            'question' => $d['question'],

            'answer' => $d['answer'],

            'keywords' => $d['keywords'] ?? null,

            'variants' => $row->normalized,

            'active' => 1,

            'hits' => 0,

            'helpful_yes' => 0,

            'helpful_no' => 0,

            'source' => 'learned',

            'created_at' => $now,

            'updated_at' => $now,

        ]);

        DB::table('chatbot_unanswered')->where('id', $id)->update(['status' => 'answered', 'faq_id' => $faqId, 'sample' => null, 'updated_at' => $now]);



        return redirect()->route('admin.chatbot.index')->with('status', "R\u{e9}ponse enregistr\u{e9}e : le chatbot la r\u{e9}utilisera.");

    }



    public function ignore(Request $request, int $id)

    {

        $this->guard($request);

        DB::table('chatbot_unanswered')->where('id', $id)->where('status', 'open')->update(['status' => 'ignored', 'sample' => null, 'updated_at' => now()]);



        return redirect()->route('admin.chatbot.index')->with('status', "Question ignor\u{e9}e.");

    }



    public function close(Request $request, int $id)

    {

        $this->guard($request);

        DB::table('chatbot_conversations')->where('id', $id)->where('status', 'waiting')->update(['status' => 'closed', 'closed_at' => now(), 'updated_at' => now()]);



        return redirect()->route('admin.chatbot.index')->with('status', "Demande marqu\u{e9}e comme trait\u{e9}e.");

    }



    public function toggle(Request $request)

    {

        $this->guard($request);

        $f = storage_path('app/chatbot.off');

        if (is_file($f)) {

            @unlink($f);

            $msg = "Chatbot activ\u{e9}.";

        } else {

            @touch($f);

            $msg = "Chatbot d\u{e9}sactiv\u{e9}.";

        }



        return redirect()->route('admin.chatbot.index')->with('status', $msg);

    }

}


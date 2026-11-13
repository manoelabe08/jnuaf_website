<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\Post;

use App\Services\Chatbot\KnowledgeIngest;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;



class ChatbotSourceController extends Controller

{

    private function guard(Request $request): void

    {

        abort_unless($request->user()?->isAdmin(), 404);

    }



    private function fail(int $id, \Throwable $e): void

    {

        DB::table('chatbot_sources')->where('id', $id)->update(['error' => mb_substr($e->getMessage(), 0, 250), 'updated_at' => now()]);

    }



    private function go(int $id, string $msg)

    {

        return redirect()->route('admin.chatbot.sources.show', $id)->with('status', $msg);

    }



    public function index(Request $request)

    {

        $this->guard($request);

        $sources = DB::table('chatbot_sources')->orderByDesc('id')->get();

        $postCount = Post::where('status', 'published')->count();



        return view('admin.chatbot.sources', compact('sources', 'postCount'));

    }



    public function show(Request $request, int $id)

    {

        $this->guard($request);

        $source = DB::table('chatbot_sources')->where('id', $id)->first();

        abort_unless($source, 404);

        $passages = DB::table('chatbot_passages')->where('source_id', $id)->orderBy('position')->limit(40)->get();



        return view('admin.chatbot.source', compact('source', 'passages'));

    }



    public function storePdf(Request $request, KnowledgeIngest $ingest)

    {

        $this->guard($request);

        $d = $request->validate([

            'pdf' => 'required|file|mimes:pdf|max:20480',

            'title' => 'nullable|string|max:255',

        ]);

        $file = $request->file('pdf');

        $title = trim((string) ($d['title'] ?? '')) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $path = $file->store('chatbot-docs', 'local');

        $id = DB::table('chatbot_sources')->insertGetId([

            'type' => 'pdf', 'title' => mb_substr($title, 0, 255), 'file_path' => $path, 'active' => 0,

            'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),

        ]);

        try {

            $items = $ingest->pdfPages(Storage::disk('local')->path($path));

            if ($items === []) {

                throw new \RuntimeException("Aucun texte extractible (PDF scann\u{e9} ?).");

            }

            $n = $ingest->replacePassages($id, $items);

        } catch (\Throwable $e) {

            $this->fail($id, $e);



            return $this->go($id, 'Import impossible : ' . $e->getMessage());

        }



        return $this->go($id, $n . " passages extraits. V\u{e9}rifiez-les puis activez la source.");

    }



    public function storeUrl(Request $request, KnowledgeIngest $ingest)

    {

        $this->guard($request);

        $d = $request->validate([

            'url' => 'required|url|max:500',

            'title' => 'nullable|string|max:255',

        ]);

        $id = DB::table('chatbot_sources')->insertGetId([

            'type' => 'url', 'title' => mb_substr(trim((string) ($d['title'] ?? '')) ?: $d['url'], 0, 255), 'url' => $d['url'], 'active' => 0,

            'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),

        ]);

        try {

            [$t, $text] = $ingest->htmlText($ingest->fetch($d['url']));

            if (trim($text) === '') {

                throw new \RuntimeException('Aucun texte trouv' . "\u{e9}" . ' sur la page.');

            }

            if (trim((string) ($d['title'] ?? '')) === '' && $t !== '') {

                DB::table('chatbot_sources')->where('id', $id)->update(['title' => mb_substr($t, 0, 255)]);

            }

            $n = $ingest->replacePassages($id, [[null, $text]]);

        } catch (\Throwable $e) {

            $this->fail($id, $e);



            return $this->go($id, 'Import impossible : ' . $e->getMessage());

        }



        return $this->go($id, $n . " passages extraits. V\u{e9}rifiez-les puis activez la source.");

    }public function syncPosts(Request $request, KnowledgeIngest $ingest)

    {

        $this->guard($request);

        $ids = [];

        $n = 0;

        foreach (Post::where('status', 'published')->get() as $p) {

            $ids[] = $p->id;

            $text = $p->title . "\n\n" . ($p->excerpt ?? '') . "\n\n" . $ingest->postText((string) $p->content);

            $src = DB::table('chatbot_sources')->where('type', 'post')->where('post_id', $p->id)->first();

            if (! $src) {

                $sid = DB::table('chatbot_sources')->insertGetId([

                    'type' => 'post', 'title' => mb_substr($p->title, 0, 255), 'url' => url('actualites/' . $p->slug), 'post_id' => $p->id,

                    'active' => 1, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),

                ]);

            } else {

                $sid = $src->id;

                DB::table('chatbot_sources')->where('id', $sid)->update(['title' => mb_substr($p->title, 0, 255), 'url' => url('actualites/' . $p->slug)]);

            }

            $ingest->replacePassages($sid, [[null, $text]]);

            $n++;

        }

        DB::table('chatbot_sources')->where('type', 'post')->whereNotIn('post_id', $ids ?: [0])->update(['active' => 0]);



        return redirect()->route('admin.chatbot.sources')->with('status', $n . " article(s) import\u{e9}(s) ou actualis\u{e9}(s).");

    }



    public function toggle(Request $request, int $id)

    {

        $this->guard($request);

        $s = DB::table('chatbot_sources')->where('id', $id)->first();

        abort_unless($s, 404);

        if ((int) $s->passage_count === 0) {

            return $this->go($id, "Aucun passage : activation impossible.");

        }

        DB::table('chatbot_sources')->where('id', $id)->update(['active' => $s->active ? 0 : 1, 'updated_at' => now()]);



        return $this->go($id, $s->active ? "Source d\u{e9}sactiv\u{e9}e." : "Source activ\u{e9}e : le chatbot peut l'utiliser.");

    }



    public function refresh(Request $request, int $id, KnowledgeIngest $ingest)

    {

        $this->guard($request);

        $s = DB::table('chatbot_sources')->where('id', $id)->first();

        abort_unless($s, 404);

        try {

            if ($s->type === 'url') {

                [$t, $text] = $ingest->htmlText($ingest->fetch((string) $s->url));

                $items = [[null, $text]];

            } elseif ($s->type === 'pdf') {

                $items = $ingest->pdfPages(Storage::disk('local')->path((string) $s->file_path));

            } else {

                $p = Post::find($s->post_id);

                abort_unless($p, 404);

                $items = [[null, $p->title . "\n\n" . ($p->excerpt ?? '') . "\n\n" . $ingest->postText((string) $p->content)]];

            }

            $n = $ingest->replacePassages($id, $items);

        } catch (\Throwable $e) {

            $this->fail($id, $e);



            return $this->go($id, 'Actualisation impossible : ' . $e->getMessage());

        }



        return $this->go($id, $n . " passages actualis\u{e9}s.");

    }

}


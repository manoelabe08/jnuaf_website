<?php



namespace App\Providers;



use App\Models\Post;

use App\Services\Chatbot\KnowledgeIngest;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;

use Illuminate\Support\ServiceProvider;



class ChatbotSyncServiceProvider extends ServiceProvider

{

    public function boot(): void

    {

        Post::saved(function (Post $post) {

            try {

                $this->sync($post);

            } catch (\Throwable $e) {

                report($e);

            }

        });

        Post::deleted(function (Post $post) {

            try {

                DB::table('chatbot_sources')->where('type', 'post')->where('post_id', $post->id)->update(['active' => 0, 'updated_at' => now()]);

            } catch (\Throwable $e) {

                report($e);

            }

        });

    }



    private function sync(Post $post): void

    {

        if (! Schema::hasTable('chatbot_sources')) {

            return;

        }

        $src = DB::table('chatbot_sources')->where('type', 'post')->where('post_id', $post->id)->first();

        if ($post->status !== 'published') {

            if ($src) {

                DB::table('chatbot_sources')->where('id', $src->id)->update(['active' => 0, 'updated_at' => now()]);

            }



            return;

        }

        $ingest = app(KnowledgeIngest::class);

        $text = $post->title . "\n\n" . ($post->excerpt ?? '') . "\n\n" . $ingest->postText((string) $post->content);

        $data = ['title' => mb_substr($post->title, 0, 255), 'url' => url('actualites/' . $post->slug), 'active' => 1, 'updated_at' => now()];

        if ($src) {

            DB::table('chatbot_sources')->where('id', $src->id)->update($data);

            $sid = $src->id;

        } else {

            $sid = DB::table('chatbot_sources')->insertGetId($data + ['type' => 'post', 'post_id' => $post->id, 'created_at' => now()]);

        }

        $ingest->replacePassages($sid, [[null, $text]]);

    }

}


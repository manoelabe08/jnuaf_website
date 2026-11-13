<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\Post;

use Illuminate\Http\Request;

use Illuminate\View\View;



class PostController extends Controller

{

    public function index(): View

    {

        return view('admin.posts.index', ['posts' => Post::orderByDesc('created_at')->paginate(25)]);

    }



    public function create(): View

    {

        return view('admin.posts.form', ['post' => new Post()]);

    }



    public function store(Request $request)

    {

        $data = $this->validated($request);

        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('cover')) { $data['cover_path'] = $request->file('cover')->store('posts', 'public'); }

        Post::create($data);

        return redirect()->route('admin.posts.index')->with('status', "Article cr\u{e9}\u{e9}.");

    }



    public function edit(Post $post): View

    {

        return view('admin.posts.form', ['post' => $post]);

    }



    public function update(Request $request, Post $post)

    {

        $data = $this->validated($request);

        if ($request->hasFile('cover')) { $data['cover_path'] = $request->file('cover')->store('posts', 'public'); }

        $post->update($data);

        return redirect()->route('admin.posts.index')->with('status', "Article mis \u{e0} jour.");

    }



    public function publish(Post $post)

    {

        $post->update(['status' => 'published', 'published_at' => now()]);

        return redirect()->route('admin.posts.index')->with('status', "Article publi\u{e9}.");

    }



    public function unpublish(Post $post)

    {

        $post->update(['status' => 'draft', 'published_at' => null]);

        return redirect()->route('admin.posts.index')->with('status', "Article d\u{e9}publi\u{e9}.");

    }



    public function destroy(Post $post)

    {

        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', "Article supprim\u{e9}.");

    }



    public function bulk(Request $request)

    {

        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'action' => 'required|in:publish,unpublish']);

        $posts = Post::whereIn('id', $data['ids'])->get();

        foreach ($posts as $p) {

            if ($data['action'] === 'publish') { $p->update(['status' => 'published', 'published_at' => now()]); }

            else { $p->update(['status' => 'draft', 'published_at' => null]); }

        }

        return redirect()->route('admin.posts.index')->with('status', "Action appliqu\u{e9}e \u{e0} ".count($posts).' article(s).');

    }



    public function uploadImage(Request $request)

    {

        $data = $request->validate(['image' => 'required|image|max:4096']);

        $path = $request->file('image')->store('posts/content', 'public');

        return response()->json(['url' => url('medias/'.$path)]);

    }



    private function validated(Request $request): array

    {

        $data = $request->validate([

            'title' => 'required|string|max:200',

            'slug' => 'required|string|max:200|unique:posts,slug' . ($request->route('post') ? ',' . $request->route('post')->id : ''),

            'excerpt' => 'nullable|string|max:500',

            'content' => 'required|string',

            'cover' => 'nullable|image|max:4096',

            'category' => 'nullable|string|max:80',

            'tags' => 'nullable|string|max:500',

        ]);

        $data['slug'] = \Illuminate\Support\Str::slug($data['slug'] ?: $data['title']);
        $data['content'] = \App\Support\HtmlCleaner::clean($data['content']);

        return $data;

    }

}


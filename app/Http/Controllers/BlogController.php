<?php



namespace App\Http\Controllers;



use App\Models\Post;

use Illuminate\View\View;



class BlogController extends Controller

{

    public function index(): View

    {

        return view('blog.index', ['posts' => Post::published()->paginate(12)]);

    }



    public function show(string $slug): View

    {

        $post = Post::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return view('blog.show', ['post' => $post]);

    }

}


<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\SecurityEvent;

use Illuminate\Contracts\View\View;

use Illuminate\Http\Request;



class SecurityLogController extends Controller

{

    public function index(Request $request): View

    {

        $events = SecurityEvent::with('user')

            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action').'%'))

            ->latest()

            ->paginate(50)

            ->withQueryString();



        return view('admin.security.index', ['events' => $events]);

    }

}


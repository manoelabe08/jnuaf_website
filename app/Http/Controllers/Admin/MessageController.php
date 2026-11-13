<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Mail\AdminMessage;
use App\Models\CommitteeApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
    private const GROUPS = [
        'committee_accepted' => 'Candidatures retenues',
        'committee_rejected' => 'Candidatures non retenues',
        'committee_new' => 'Candidatures en attente',
    ];

    public function create()
    {
        return view('admin.messages.create', ['groups' => self::GROUPS]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'group' => ['required', 'in:'.implode(',', array_keys(self::GROUPS))],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'test' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('test')) {
            Mail::to($request->user()->email)->send(new AdminMessage($data['subject'], $data['body'], 'test'));

            return back()->with('status', 'Email de test envoyé à '.$request->user()->email);
        }

        $status = match ($data['group']) {
            'committee_accepted' => ApplicationStatus::Accepted,
            'committee_rejected' => ApplicationStatus::Rejected,
            default => ApplicationStatus::New,
        };

        $people = CommitteeApplication::where('status', $status)->get()->unique('email')->take(40);

        $sent = 0;
        foreach ($people as $p) {
            try {
                Mail::to($p->email)->send(new AdminMessage($data['subject'], $data['body'], $p->full_name));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('status', "$sent email(s) envoyé(s).");
    }
}
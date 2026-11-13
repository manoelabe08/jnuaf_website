<?php



namespace App\Http\Controllers;



use App\Mail\TeamMessage;

use App\Services\AdminMailboxService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;

use Illuminate\View\View;



class AdminMailboxController extends Controller

{

    private const MAX_TOTAL_BYTES = 10 * 1024 * 1024;

    private const MAX_RECIPIENTS = 20;

    private const PER_PAGE = 20;

    private const FETCH_LIMIT = 100;



    public function inbox(Request $request, AdminMailboxService $mailbox): View

    {

        return $this->folderView($request, $mailbox, 'inbox');

    }



    public function sent(Request $request, AdminMailboxService $mailbox): View

    {

        return $this->folderView($request, $mailbox, 'sent');

    }



    public function compose(Request $request): View

    {

        return view('admin.mailbox.compose', [

            'prefillTo' => (string) $request->query('to', ''),

            'prefillSubject' => (string) $request->query('subject', ''),

        ]);

    }



    public function send(Request $request, AdminMailboxService $mailbox): RedirectResponse

    {

        $data = $request->validate([

            'to' => ['required', 'string', 'max:2000'],

            'cc' => ['nullable', 'string', 'max:2000'],

            'bcc' => ['nullable', 'string', 'max:2000'],

            'individual' => ['nullable', 'boolean'],

            'subject' => ['required', 'string', 'max:150', 'regex:/^[^\r\n]+$/'],

            'body' => ['required', 'string', 'max:10000'],

            'attachments' => ['nullable', 'array', 'max:5'],

            'attachments.*' => [

                'file',

                'max:10240',

                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,csv,txt',

            ],

        ], [

            'to.required' => 'Indiquez au moins un destinataire.',

            'subject.required' => 'Indiquez un objet.',

            'subject.regex' => 'L\'objet doit tenir sur une seule ligne.',

            'body.required' => 'Écrivez un message.',

            'attachments.max' => 'Cinq pièces jointes au maximum.',

            'attachments.*.max' => 'Chaque pièce jointe doit faire 10 Mo au plus.',

            'attachments.*.mimes' => 'Format de pièce jointe non accepté (PDF, images, documents bureautiques, CSV, TXT).',

            'attachments.*.uploaded' => 'Une pièce jointe n\'a pas pu être téléversée (fichier trop volumineux pour le serveur ?).',

        ]);



        $to = $this->parseEmails($data['to'] ?? '');

        $cc = array_values(array_diff($this->parseEmails($data['cc'] ?? ''), $to));

        $bcc = array_values(array_diff($this->parseEmails($data['bcc'] ?? ''), $to, $cc));

        $all = array_merge($to, $cc, $bcc);



        $invalid = array_values(array_filter(

            $all,

            fn (string $email) => ! filter_var($email, FILTER_VALIDATE_EMAIL)

        ));



        if ($invalid !== []) {

            return back()->withInput()->withErrors([

                'to' => 'Adresse(s) invalide(s) : '.implode(', ', array_slice($invalid, 0, 5)),

            ]);

        }



        if (count($all) > self::MAX_RECIPIENTS) {

            return back()->withInput()->withErrors([

                'to' => 'Vingt destinataires au maximum par envoi.',

            ]);

        }



        $files = [];

        $total = 0;



        foreach ((array) $request->file('attachments', []) as $file) {

            $total += (int) $file->getSize();



            $name = preg_replace(

                '/[^\pL\pN\s._()\-]/u',

                '_',

                basename($file->getClientOriginalName())

            );



            $files[] = [

                'path' => $file->getRealPath(),

                'name' => $name !== '' ? $name : 'document',

                'mime' => $file->getMimeType() ?: 'application/octet-stream',

            ];

        }



        if ($total > self::MAX_TOTAL_BYTES) {

            return back()->withInput()->withErrors([

                'attachments' => 'Les pièces jointes dépassent 10 Mo au total.',

            ]);

        }



        $individual = $request->boolean('individual');



        $batches = $individual

            ? array_map(fn (string $email) => ['to' => [$email], 'cc' => [], 'bcc' => []], $all)

            : [['to' => $to, 'cc' => $cc, 'bcc' => $bcc]];



        $delivered = 0;

        $failed = [];

        $copies = [];



        foreach ($batches as $batch) {

            try {

                $pending = Mail::mailer('team')->to($batch['to']);



                if ($batch['cc'] !== []) {

                    $pending->cc($batch['cc']);

                }



                if ($batch['bcc'] !== []) {

                    $pending->bcc($batch['bcc']);

                }



                $sent = $pending->send(

                    new TeamMessage($data['subject'], $data['body'], $files)

                );



                $delivered += count($batch['to']) + count($batch['cc']) + count($batch['bcc']);



                if ($sent !== null) {

                    $copies[] = $sent->toString();

                }

            } catch (\Throwable $exception) {

                report($exception);



                $failed = array_merge($failed, $batch['to'], $batch['cc'], $batch['bcc']);

            }

        }



        if ($delivered === 0) {

            return back()->withInput()->withErrors([

                'send' => 'L\'envoi a échoué. Aucun message n\'a été envoyé.',

            ]);

        }



        $status = 'Message envoyé à '.$delivered.' destinataire(s).';



        try {

            $mailbox->appendManyToSent($copies);

            $mailbox->disconnect();

        } catch (\Throwable $exception) {

            report($exception);



            $status .= ' La copie dans « Envoyés » n\'a pas pu être enregistrée.';

        }



        if ($failed !== []) {

            $status .= ' Échec pour : '.implode(', ', array_slice($failed, 0, 5)).'.';

        }



        Log::info('Message envoyé depuis la messagerie admin', [

            'user_id' => $request->user()?->id,

            'recipients' => count($all),

            'individual' => $individual,

            'subject' => $data['subject'],

            'attachments' => count($files),

            'failed' => count($failed),

        ]);



        return redirect()

            ->route('admin.mailbox.inbox')

            ->with('status', $status);

    }



    private function folderView(Request $request, AdminMailboxService $mailbox, string $folder): View

    {

        $filter = (string) $request->query('filter', 'all');

        $sort = (string) $request->query('sort', 'date_desc');

        $period = (int) $request->query('period', 0);



        $params = [

            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),

            'filter' => in_array($filter, ['unread', 'attachments'], true) ? $filter : 'all',

            'sort' => in_array($sort, ['date_asc', 'sender', 'subject'], true) ? $sort : 'date_desc',

            'period' => in_array($period, [7, 30, 90], true) ? $period : 0,

        ];



        $messages = [];

        $error = null;

        $total = 0;

        $pages = 1;

        $page = 1;



        try {

            $all = $mailbox->search(

                $folder === 'sent' ? 'Sent' : 'INBOX',

                $params,

                self::FETCH_LIMIT

            );



            $mailbox->disconnect();



            if ($params['filter'] === 'attachments') {

                $all = array_values(array_filter($all, fn (array $m) => $m['attachments'] > 0));

            }



            $all = $this->sortMessages($all, $params['sort'], $folder === 'sent');



            $total = count($all);

            $pages = max(1, (int) ceil($total / self::PER_PAGE));

            $page = min(max(1, (int) $request->query('page', 1)), $pages);

            $messages = array_slice($all, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        } catch (\Throwable $exception) {

            report($exception);



            $error = 'Impossible de charger ce dossier ou d\'appliquer cette recherche.';

        }



        return view('admin.mailbox.inbox', [

            'folder' => $folder,

            'messages' => $messages,

            'error' => $error,

            'params' => $params,

            'total' => $total,

            'page' => $page,

            'pages' => $pages,

            'capped' => $total >= self::FETCH_LIMIT,

        ]);

    }



    private function sortMessages(array $messages, string $sort, bool $isSent): array

    {

        $label = fn (array $m): string => mb_strtolower(

            $isSent

                ? ($m['to_name'] ?: $m['to'])

                : ($m['from_name'] ?: $m['from'])

        );



        usort($messages, function (array $a, array $b) use ($sort, $label) {

            return match ($sort) {

                'date_asc' => $a['timestamp'] <=> $b['timestamp'],

                'sender' => $label($a) <=> $label($b),

                'subject' => mb_strtolower($a['subject']) <=> mb_strtolower($b['subject']),

                default => $b['timestamp'] <=> $a['timestamp'],

            };

        });



        return $messages;

    }



    private function parseEmails(?string $raw): array

    {

        $parts = preg_split('/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];



        return array_values(array_unique(array_map('strtolower', $parts)));

    }

}


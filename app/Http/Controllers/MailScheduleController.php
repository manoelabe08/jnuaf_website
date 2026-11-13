<?php



namespace App\Http\Controllers;



use App\Models\ScheduledMail;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;



class MailScheduleController extends Controller

{

    private const MAX_TOTAL_BYTES = 10 * 1024 * 1024;

    private const MAX_RECIPIENTS = 20;



    public function index()

    {

        return view('admin.mailbox.scheduled', [

            'mails' => ScheduledMail::orderByDesc('scheduled_at')->limit(50)->get(),

            'timezone' => $this->timezone(),

        ]);

    }



    public function store(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'to' => ['required', 'string', 'max:2000'],

            'cc' => ['nullable', 'string', 'max:2000'],

            'bcc' => ['nullable', 'string', 'max:2000'],

            'individual' => ['nullable', 'boolean'],

            'subject' => ['required', 'string', 'max:150', 'regex:/^[^\r\n]+$/'],

            'body' => ['required', 'string', 'max:10000'],

            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i'],

            'attachments' => ['nullable', 'array', 'max:5'],

            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,csv,txt'],

        ], [

            'to.required' => 'Indiquez au moins un destinataire.',

            'subject.required' => 'Indiquez un objet.',

            'subject.regex' => 'L’objet doit tenir sur une seule ligne.',

            'body.required' => 'Écrivez un message.',

            'scheduled_at.required' => 'Choisissez la date et l’heure d’envoi.',

            'scheduled_at.date_format' => 'Date et heure invalides.',

            'attachments.max' => 'Cinq pièces jointes au maximum.',

            'attachments.*.max' => 'Chaque pièce jointe doit faire 10 Mo au plus.',

            'attachments.*.mimes' => 'Format de pièce jointe non accepté.',

        ]);



        $to = $this->parseEmails($data['to']);

        $cc = array_values(array_diff($this->parseEmails($data['cc'] ?? ''), $to));

        $bcc = array_values(array_diff($this->parseEmails($data['bcc'] ?? ''), $to, $cc));

        $all = array_merge($to, $cc, $bcc);



        $invalid = array_values(array_filter($all, fn (string $e) => ! filter_var($e, FILTER_VALIDATE_EMAIL)));

        if ($invalid !== []) {

            return back()->withInput()->withErrors(['to' => 'Adresse(s) invalide(s) : '.implode(', ', array_slice($invalid, 0, 5))]);

        }

        if ($all === [] || count($all) > self::MAX_RECIPIENTS) {

            return back()->withInput()->withErrors(['to' => 'Entre 1 et 20 destinataires par message.']);

        }



        $when = Carbon::createFromFormat('Y-m-d\TH:i', $data['scheduled_at'], $this->timezone())->setTimezone('UTC');

        if ($when->lte(now()->addMinutes(2))) {

            return back()->withInput()->withErrors(['scheduled_at' => 'Choisissez une date et une heure dans le futur (au moins 2 minutes).']);

        }

        if ($when->gt(now()->addDays(90))) {

            return back()->withInput()->withErrors(['scheduled_at' => 'La programmation est limitée à 90 jours.']);

        }



        $uploads = (array) $request->file('attachments', []);

        $total = 0;

        foreach ($uploads as $file) {

            $total += (int) $file->getSize();

        }

        if ($total > self::MAX_TOTAL_BYTES) {

            return back()->withInput()->withErrors(['attachments' => 'Les pièces jointes dépassent 10 Mo au total.']);

        }



        $mail = ScheduledMail::create([

            'user_id' => $request->user()?->id,

            'recipients' => ['to' => $to, 'cc' => $cc, 'bcc' => $bcc],

            'individual' => $request->boolean('individual'),

            'subject' => $data['subject'],

            'body' => $data['body'],

            'status' => 'scheduled',

            'scheduled_at' => $when->format('Y-m-d H:i:s'),

        ]);



        if ($uploads !== []) {

            $root = rtrim((string) config('partner_portal.private_root'), '/');

            $dir = $root.'/scheduled-mails/'.$mail->id;



            if ($root === '' || (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir))) {

                $mail->delete();



                return back()->withInput()->withErrors(['attachments' => 'Stockage privé inaccessible : les pièces jointes n’ont pas pu être conservées.']);

            }



            $stored = [];

            foreach ($uploads as $file) {

                $name = preg_replace('/[^\pL\pN\s._()\-]/u', '_', basename($file->getClientOriginalName()));

                $mime = $file->getMimeType() ?: 'application/octet-stream';

                $ext = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $file->getClientOriginalExtension())) ?: 'bin';

                $storedName = Str::lower(Str::random(40)).'.'.$ext;

                $file->move($dir, $storedName);

                $stored[] = ['stored' => $storedName, 'name' => $name !== '' ? $name : 'document', 'mime' => $mime];

            }

            $mail->update(['attachments' => $stored]);

        }



        Log::info('Message programmé depuis la messagerie admin', [

            'user_id' => $request->user()?->id,

            'scheduled_mail_id' => $mail->id,

            'recipients' => count($all),

            'scheduled_at' => $when->toDateTimeString(),

        ]);



        $local = $when->copy()->setTimezone($this->timezone());



        return redirect()->route('admin.mailbox.scheduled')

            ->with('status', 'Message programmé pour le '.$local->format('d/m/Y à H:i').' ('.$this->timezone().').');

    }



    public function cancel(Request $request, ScheduledMail $scheduledMail): RedirectResponse

    {

        $updated = ScheduledMail::whereKey($scheduledMail->id)->where('status', 'scheduled')->update(['status' => 'cancelled']);



        if ($updated === 1) {

            $this->deleteFiles($scheduledMail);

        }



        return back()->with('status', $updated === 1

            ? 'Envoi annulé.'

            : 'Trop tard : cet envoi a déjà démarré ou n’est plus programmé.');

    }



    private function deleteFiles(ScheduledMail $mail): void

    {

        $dir = rtrim((string) config('partner_portal.private_root'), '/').'/scheduled-mails/'.$mail->id;

        foreach ((array) $mail->attachments as $a) {

            @unlink($dir.'/'.($a['stored'] ?? ''));

        }

        @rmdir($dir);

    }



    private function timezone(): string

    {

        return (string) config('jnuaf_newsletter.timezone', 'Europe/Paris');

    }



    private function parseEmails(?string $raw): array

    {

        $raw = preg_replace_callback('/[^;,\r\n<]*<([^>]+)>/', fn ($m) => $m[1], (string) $raw);

        $found = [];

        foreach (preg_split('/[;,\s]+/', $raw) ?: [] as $part) {

            $part = mb_strtolower(trim($part, " \t\"'<>"));

            if ($part !== '') {

                $found[] = $part;

            }

        }



        return array_values(array_unique($found));

    }

}


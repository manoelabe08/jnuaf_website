<?php



namespace App\Http\Controllers;



use App\Services\AdminMailboxReader;

use Illuminate\Http\RedirectResponse;

use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;



class AdminMailMessageController extends Controller

{

    public function show(AdminMailboxReader $reader, string $folder, int $uid): View|RedirectResponse

    {

        try {

            $mail = $reader->read($this->imapFolder($folder), $uid);

            $reader->disconnect();

        } catch (\Throwable $exception) {

            report($exception);



            return redirect()

                ->route($folder === 'sent' ? 'admin.mailbox.sent' : 'admin.mailbox.inbox')

                ->with('status', 'Impossible d\'ouvrir ce message pour le moment.');

        }



        abort_if($mail === null, 404);



        return view('admin.mailbox.show', [

            'folder' => $folder,

            'uid' => $uid,

            'mail' => $mail,

        ]);

    }



    public function attachment(

        AdminMailboxReader $reader,

        string $folder,

        int $uid,

        int $index

    ): StreamedResponse {

        $file = $reader->attachment($this->imapFolder($folder), $uid, $index);

        $reader->disconnect();



        abort_if($file === null, 404);



        $name = preg_replace(

            '/[^\pL\pN\s._()\-]/u',

            '_',

            basename($file['name'])

        );



        return response()->streamDownload(

            function () use ($file) {

                echo $file['content'];

            },

            $name !== '' ? $name : 'piece-jointe',

            [

                'Content-Type' => 'application/octet-stream',

                'X-Content-Type-Options' => 'nosniff',

            ]

        );

    }



    private function imapFolder(string $folder): string

    {

        return $folder === 'sent' ? 'Sent' : 'INBOX';

    }

}


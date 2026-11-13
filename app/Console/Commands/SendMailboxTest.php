<?php



namespace App\Console\Commands;



use Illuminate\Console\Command;

use Illuminate\Support\Facades\Mail;



class SendMailboxTest extends Command

{

    protected $signature = 'mailbox:send-test {to}';



    protected $description = 'Envoie un message de test depuis team@jnuaf.org';



    public function handle(): int

    {

        $to = (string) $this->argument('to');



        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {

            $this->error('Adresse de destination invalide.');



            return self::FAILURE;

        }



        try {

            Mail::mailer('team')->raw(

                "Ceci est un test d'envoi depuis la messagerie administrative JNUAF.\n\nSi vous lisez ce message, le SMTP de team@jnuaf.org fonctionne.",

                function ($message) use ($to) {

                    $message->from('team@jnuaf.org', 'JNUAF')

                        ->replyTo('team@jnuaf.org', 'JNUAF')

                        ->to($to)

                        ->subject('Test messagerie JNUAF');

                }

            );



            $this->info('Message de test envoyé à '.$to.'.');



            return self::SUCCESS;

        } catch (\Throwable $exception) {

            $this->error('Échec de l\'envoi : '.$exception->getMessage());



            return self::FAILURE;

        }

    }

}


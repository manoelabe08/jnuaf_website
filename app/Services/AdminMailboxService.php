<?php



namespace App\Services;



use Webklex\PHPIMAP\Client;

use Webklex\PHPIMAP\ClientManager;



class AdminMailboxService

{

    protected Client $client;



    public function connect(): void

    {

        $config = config('admin_mailbox.imap');



        if (empty($config['username']) || empty($config['password'])) {

            throw new \RuntimeException(

                'Les identifiants IMAP de la messagerie administrative ne sont pas configurés.'

            );

        }



        $manager = new ClientManager();



        $this->client = $manager->make([

            'host' => $config['host'],

            'port' => $config['port'],

            'encryption' => 'ssl',

            'validate_cert' => (bool) $config['verify_certificate'],

            'username' => $config['username'],

            'password' => $config['password'],

            'protocol' => 'imap',

            'authentication' => 'login',

        ]);



        $this->client->connect();

    }



    public function folders(): array

    {

        $this->connect();



        $folders = [];



        foreach ($this->client->getFolders() as $folder) {

            $folders[] = $folder->full_name;

        }



        return $folders;

    }



    public function inboxPreview(int $limit = 5): array

    {

        return $this->search('INBOX', [], $limit);

    }



    public function sentPreview(int $limit = 20): array

    {

        return $this->search('Sent', [], $limit);

    }



    /**

     * @param array{q?: string, filter?: string, period?: int} $options

     */

    public function search(string $folderName, array $options = [], int $limit = 100): array

    {

        $this->connect();



        $folder = $this->client->getFolder($folderName);



        if ($folder === null) {

            throw new \RuntimeException('Le dossier '.$folderName.' est introuvable.');

        }



        $query = $folder->query()

            ->all()

            ->leaveUnread()

            ->setFetchOrder('desc');



        $term = trim((string) ($options['q'] ?? ''));



        if ($term !== '') {

            $query->whereText($term);

        }



        if (($options['filter'] ?? '') === 'unread') {

            $query->whereUnseen();

        }



        $days = (int) ($options['period'] ?? 0);



        if ($days > 0) {

            $query->whereSince(now()->subDays($days)->startOfDay());

        }



        $messages = $query->limit($limit)->get();



        $result = [];



        foreach ($messages as $message) {

            $from = $this->addresses($message->getFrom());

            $to = $this->addresses($message->getTo());

            $date = $message->getDate()->first();



            $result[] = [

                'uid' => (string) $message->getUid(),

                'from' => $from[0][0] ?? '',

                'from_name' => $from[0][1] ?? '',

                'to' => $to[0][0] ?? '',

                'to_name' => $to[0][1] ?? '',

                'to_count' => count($to),

                'subject' => (string) $message->getSubject(),

                'date' => optional($date)->format('d/m/Y H:i'),

                'timestamp' => $date ? $date->getTimestamp() : 0,

                'attachments' => $message->getAttachments()->count(),

                'seen' => $message->getFlags()->has('seen'),

            ];

        }



        return $result;

    }



    /**

     * @param array<int, string> $rawMessages

     */

    public function appendManyToSent(array $rawMessages): void

    {

        if ($rawMessages === []) {

            return;

        }



        $this->connect();



        $folder = $this->client->getFolder('Sent');



        if ($folder === null) {

            throw new \RuntimeException('Le dossier Sent est introuvable.');

        }



        foreach ($rawMessages as $raw) {

            $folder->appendMessage($raw, ['\\Seen']);

        }

    }



    public function disconnect(): void

    {

        if (isset($this->client)) {

            $this->client->disconnect();

        }

    }



    private function addresses($attribute): array

    {

        $list = [];



        if ($attribute === null) {

            return $list;

        }



        foreach ($attribute->all() as $address) {

            $list[] = [

                (string) ($address->mail ?? ''),

                (string) ($address->personal ?? ''),

            ];

        }



        return $list;

    }

}


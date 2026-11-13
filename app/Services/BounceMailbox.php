<?php



namespace App\Services;



class BounceMailbox extends AdminMailboxService

{

    private function ensure(): void

    {

        if (! isset($this->client) || ! $this->client->isConnected()) {

            $this->connect();

        }

    }



    public function fetch(int $uid, string $folderName = 'INBOX'): ?array

    {

        $this->ensure();

        $folder = $this->client->getFolder($folderName);

        if ($folder === null) {

            throw new \RuntimeException('Dossier introuvable : '.$folderName);

        }

        $message = $folder->query()->leaveUnread()->getMessageByUid($uid);

        if ($message === null) {

            return null;

        }

        $from = $message->getFrom();

        $first = $from ? ($from->all()[0] ?? null) : null;

        $parts = [];

        foreach (array_values($message->getAttachments()->all()) as $attachment) {

            $mime = strtolower((string) $attachment->getMimeType());

            if (str_contains($mime, 'rfc822') || str_contains($mime, 'rfc-822')) {

                continue;

            }

            if (! str_contains($mime, 'delivery-status') && ! str_starts_with($mime, 'text/')) {

                continue;

            }

            $parts[] = [

                'name' => (string) ($attachment->getName() ?: ''),

                'mime' => $mime,

                'content' => mb_substr((string) $attachment->getContent(), 0, 60000),

            ];

        }



        return [

            'subject' => (string) $message->getSubject(),

            'from' => $first ? (string) ($first->mail ?? '') : '',

            'text' => mb_substr(trim((string) $message->getTextBody()), 0, 60000),

            'parts' => $parts,

        ];

    }



    public function markSeen(int $uid, string $folderName = 'INBOX'): void

    {

        $this->ensure();

        $folder = $this->client->getFolder($folderName);

        $message = $folder?->query()->leaveUnread()->getMessageByUid($uid);

        $message?->setFlag('Seen');

    }

}


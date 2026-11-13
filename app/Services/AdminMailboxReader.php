<?php



namespace App\Services;



class AdminMailboxReader extends AdminMailboxService

{

    public function read(string $folderName, int $uid): ?array

    {

        $message = $this->loadMessage($folderName, $uid);



        if ($message === null) {

            return null;

        }



        try {

            $message->setFlag('Seen');

        } catch (\Throwable $exception) {

            report($exception);

        }



        $from = $this->people($message->getFrom());

        $replyTo = $this->people($message->getReplyTo());

        $date = $message->getDate()->first();



        $attachments = [];



        foreach (array_values($message->getAttachments()->all()) as $index => $attachment) {

            $attachments[] = [

                'index' => $index,

                'name' => (string) ($attachment->getName() ?: 'piece-jointe'),

                'mime' => (string) $attachment->getMimeType(),

                'size' => (int) $attachment->getSize(),

            ];

        }



        return [

            'subject' => (string) $message->getSubject(),

            'from' => $from[0]['mail'] ?? '',

            'from_name' => $from[0]['name'] ?? '',

            'reply_to' => $replyTo[0]['mail'] ?? ($from[0]['mail'] ?? ''),

            'to' => $this->people($message->getTo()),

            'cc' => $this->people($message->getCc()),

            'date' => optional($date)->format('d/m/Y à H:i'),

            'body' => $this->bodyText($message),
            'html' => $message->hasHTMLBody() ? mb_substr((string) $message->getHTMLBody(), 0, 400000) : null,

            'attachments' => $attachments,

        ];

    }



    public function attachment(string $folderName, int $uid, int $index): ?array

    {

        $message = $this->loadMessage($folderName, $uid);



        if ($message === null) {

            return null;

        }



        $list = array_values($message->getAttachments()->all());



        if (! isset($list[$index])) {

            return null;

        }



        $attachment = $list[$index];



        return [

            'name' => (string) ($attachment->getName() ?: 'piece-jointe'),

            'content' => (string) $attachment->getContent(),

        ];

    }



    private function loadMessage(string $folderName, int $uid)

    {

        $this->connect();



        $folder = $this->client->getFolder($folderName);



        if ($folder === null) {

            throw new \RuntimeException('Le dossier '.$folderName.' est introuvable.');

        }



        return $folder->query()->getMessageByUid($uid);

    }



    private function people($attribute): array

    {

        $list = [];



        if ($attribute === null) {

            return $list;

        }



        foreach ($attribute->all() as $address) {

            $list[] = [

                'mail' => (string) ($address->mail ?? ''),

                'name' => (string) ($address->personal ?? ''),

            ];

        }



        return $list;

    }



    private function bodyText($message): string

    {

        $text = trim((string) $message->getTextBody());



        if ($text === '' && $message->hasHTMLBody()) {

            $html = (string) $message->getHTMLBody();

            $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';

            $html = preg_replace('#<br\s*/?>|</(p|div|tr|li|h[1-6])>#i', "\n", $html) ?? '';

            $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        }



        return mb_substr($text, 0, 50000);

    }

}


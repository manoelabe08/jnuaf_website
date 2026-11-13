<?php
namespace App\Console\Commands;
use App\Services\NewsletterService;
use Illuminate\Console\Command;
class SendNewsletterBatch extends Command
{
    protected $signature = 'newsletter:send-batch';
    protected $description = 'Transmet un petit lot de newsletters aux abonnés actifs';
    public function handle(NewsletterService $service): int
    {
        $service->dispatchDue();
        $count = $service->processBatch();
        $this->info($count.' email(s) newsletter accepté(s) par le transport pendant ce passage.');
        return self::SUCCESS;
    }
}

<?php
namespace App\Console\Commands;
use App\Services\PartnerPortalService;
use Illuminate\Console\Command;
class MaintainPartnerPortal extends Command
{
    protected $signature = 'partners:maintain';
    protected $description = 'Invalide les accès partenaires expirés et transmet les notifications en attente';
    public function handle(PartnerPortalService $portal): int
    {
        $portal->processMaintenance();
        $this->info('Vérification des accès et traitement des notifications terminés.');
        return self::SUCCESS;
    }
}

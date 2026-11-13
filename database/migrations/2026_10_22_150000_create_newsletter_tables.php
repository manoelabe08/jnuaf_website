<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        $tables = ['newsletters', 'newsletter_campaigns', 'newsletter_attachments', 'newsletter_deliveries'];
        $present = array_filter($tables, fn ($table) => Schema::hasTable($table));
        if (count($present) === count($tables)) {
            // Tables installées par le SQL alternatif : ne pas les recréer.
            foreach ([
                'newsletters' => ['email', 'status', 'sources', 'consent_note', 'consented_at', 'unsubscribed_at'],
                'newsletter_campaigns' => ['title', 'subject', 'body', 'sources', 'status', 'web_expires_at'],
                'newsletter_attachments' => ['newsletter_campaign_id', 'stored_name', 'download_name', 'mime', 'bytes'],
                'newsletter_deliveries' => ['newsletter_campaign_id', 'newsletter_id', 'email', 'token_hash', 'token_ciphertext', 'status', 'attempts'],
            ] as $table => $columns) {
                if (! Schema::hasColumns($table, $columns)) { throw new RuntimeException('Structure newsletter incompatible : '.$table); }
            }
            return;
        }
        if (count($present) > 0) { throw new RuntimeException('Installation newsletter partielle : inspecter les tables avant de poursuivre.'); }
        $sql = <<<'SQL'
CREATE TABLE `newsletters` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `name` VARCHAR(255) NULL,
 `email` VARCHAR(254) NOT NULL,
 `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
 `sources` JSON NOT NULL,
 `consent_note` TEXT NULL,
 `consented_at` TIMESTAMP NULL,
 `unsubscribed_at` TIMESTAMP NULL,
 `created_at` TIMESTAMP NULL,
 `updated_at` TIMESTAMP NULL,
 PRIMARY KEY (`id`), UNIQUE KEY `newsletters_email_unique` (`email`),
 KEY `newsletters_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `newsletter_campaigns` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `title` VARCHAR(150) NOT NULL,
 `subject` VARCHAR(150) NOT NULL,
 `body` LONGTEXT NOT NULL,
 `sources` JSON NOT NULL,
 `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
 `queued_at` TIMESTAMP NULL,
 `finished_at` TIMESTAMP NULL,
 `web_expires_at` TIMESTAMP NULL,
 `created_at` TIMESTAMP NULL,
 `updated_at` TIMESTAMP NULL,
 PRIMARY KEY (`id`), KEY `newsletter_campaigns_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `newsletter_attachments` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `newsletter_campaign_id` BIGINT UNSIGNED NOT NULL,
 `stored_name` VARCHAR(100) NOT NULL,
 `download_name` VARCHAR(180) NOT NULL,
 `mime` VARCHAR(150) NOT NULL,
 `bytes` BIGINT UNSIGNED NOT NULL,
 `created_at` TIMESTAMP NULL,
 `updated_at` TIMESTAMP NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `newsletter_attachments_stored_unique` (`stored_name`),
 CONSTRAINT `newsletter_attachments_campaign_fk` FOREIGN KEY (`newsletter_campaign_id`) REFERENCES `newsletter_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `newsletter_deliveries` (
 `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 `newsletter_campaign_id` BIGINT UNSIGNED NOT NULL,
 `newsletter_id` BIGINT UNSIGNED NOT NULL,
 `email` VARCHAR(254) NOT NULL,
 `token_hash` CHAR(64) NOT NULL,
 `token_ciphertext` TEXT NOT NULL,
 `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
 `attempts` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 `processing_at` TIMESTAMP NULL,
 `sent_at` TIMESTAMP NULL,
 `error` TEXT NULL,
 `created_at` TIMESTAMP NULL,
 `updated_at` TIMESTAMP NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `newsletter_delivery_recipient_unique` (`newsletter_campaign_id`,`email`),
 UNIQUE KEY `newsletter_delivery_token_unique` (`token_hash`),
 KEY `newsletter_deliveries_status_index` (`status`),
 CONSTRAINT `newsletter_deliveries_campaign_fk` FOREIGN KEY (`newsletter_campaign_id`) REFERENCES `newsletter_campaigns` (`id`) ON DELETE CASCADE,
 CONSTRAINT `newsletter_deliveries_subscriber_fk` FOREIGN KEY (`newsletter_id`) REFERENCES `newsletters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        foreach (explode(';', $sql) as $statement) {
            if (trim($statement) !== '') { DB::unprepared($statement); }
        }
    }
    public function down(): void
    {
        throw new RuntimeException('Retour arrière destructif désactivé : sauvegarder et traiter les données newsletter explicitement.');
    }
};

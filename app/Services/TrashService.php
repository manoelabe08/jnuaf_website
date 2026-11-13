<?php



namespace App\Services;



use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;



class TrashService

{

    public const DAYS = 45;



    private const MAX_ROWS = 50000;



    public static function register(): void

    {

        foreach (['Registration', 'CommitteeApplication', 'PartnerInquiry', 'Event', 'Territory'] as $name) {

            $class = '\\App\\Models\\'.$name;

            if (class_exists($class)) {

                $class::deleting(function (Model $model) {

                    self::capture($model);

                });

            }

        }

        foreach (['Registration', 'CommitteeApplication'] as $name) {

            $class = '\\App\\Models\\'.$name;

            if (class_exists($class)) {

                $class::deleted(function (Model $model) {

                    foreach (self::fileSpecs($model->getTable(), $model->getAttributes()) as $spec) {

                        if ($spec['kind'] === 'private') {

                            $abs = self::absolute($spec);

                            if (is_file($abs)) {

                                @unlink($abs);

                            }

                        }

                    }

                });

            }

        }

    }



    private static function privateRoot(): string

    {

        $root = rtrim((string) config('partner_portal.private_root'), '/\\');

        if ($root === '') {

            throw new \RuntimeException('PARTNER_PRIVATE_ROOT non configure.');

        }



        return $root;

    }



    private static function fileSpecs(string $table, array $row): array

    {

        $specs = [];

        if ($table === 'committee_applications') {

            foreach (['cv_path', 'letter_path'] as $f) {

                if (! empty($row[$f]) && ! str_contains((string) $row[$f], '..')) {

                    $specs[] = ['field' => $f, 'kind' => 'local', 'name' => (string) $row[$f]];

                }

            }

            if (! empty($row['photo_path'])) {

                $specs[] = ['field' => 'photo_path', 'kind' => 'private', 'name' => 'committee-photos/'.basename((string) $row['photo_path'])];

            }

        }

        if ($table === 'registrations') {

            foreach (['photo_path', 'cv_path'] as $f) {

                if (! empty($row[$f])) {

                    $specs[] = ['field' => $f, 'kind' => 'private', 'name' => 'registration-files/'.basename((string) $row[$f])];

                }

            }

        }



        return $specs;

    }



    private static function absolute(array $spec): string

    {

        return $spec['kind'] === 'local'

            ? Storage::disk('local')->path($spec['name'])

            : self::privateRoot().'/'.$spec['name'];

    }



    private static function copyFiles(string $table, array $row): array

    {

        $files = [];

        foreach (self::fileSpecs($table, $row) as $spec) {

            $src = self::absolute($spec);

            if (! is_file($src)) {

                continue;

            }

            $token = Str::lower(Str::random(16));

            $dir = self::privateRoot().'/trash-files/'.$token;

            if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {

                throw new \RuntimeException('Dossier de la corbeille inaccessible.');

            }

            $dest = $dir.'/'.basename($src);

            if (! copy($src, $dest)) {

                throw new \RuntimeException('Copie dans la corbeille impossible.');

            }

            chmod($dest, 0600);

            $files[] = $spec + ['trash' => $token.'/'.basename($src)];

        }



        return $files;

    }



    private static function restoreFiles(array $data): void

    {

        foreach (($data['files'] ?? []) as $spec) {

            if (empty($spec['trash']) || empty($spec['kind']) || empty($spec['name'])) {

                continue;

            }

            $dest = self::absolute($spec);

            $src = self::privateRoot().'/trash-files/'.$spec['trash'];

            if (! is_file($dest) && is_file($src)) {

                $dir = dirname($dest);

                if (! is_dir($dir)) {

                    @mkdir($dir, 0700, true);

                }

                @copy($src, $dest);

            }

        }

    }



    private static function dropFiles(object $item): void

    {

        $data = json_decode((string) $item->payload, true);

        if (! is_array($data)) {

            return;

        }

        foreach (($data['files'] ?? []) as $f) {

            if (! empty($f['trash'])) {

                $abs = self::privateRoot().'/trash-files/'.$f['trash'];

                if (is_file($abs)) {

                    @unlink($abs);

                }

                @rmdir(dirname($abs));

            }

        }if ($item->restored_at === null) {

            foreach (($data['children']['registrations'] ?? []) as $row) {

                foreach (self::fileSpecs('registrations', (array) $row) as $spec) {

                    $abs = self::absolute($spec);

                    if (is_file($abs)) {

                        @unlink($abs);

                    }

                }

            }

        }

    }



    public static function capture(Model $model): void

    {

        if (app()->runningInConsole() || request()->is('mon-inscription/*')) {

            return;

        }

        $table = $model->getTable();

        $parent = $model->getAttributes();

        $children = self::tree($table, $parent);

        $files = self::copyFiles($table, $parent);

        $label = '';

        foreach (['title', 'name', 'email', 'organization', 'company'] as $f) {

            $v = $model->getAttribute($f);

            if (is_string($v) && $v !== '') {

                $label = $v;

                break;

            }

        }

        $payload = json_encode(

            ['table' => $table, 'row' => $parent, 'children' => $children, 'files' => $files],

            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR

        );

        DB::table('trash_items')->insert([

            'model' => class_basename($model),

            'original_id' => $model->getKey(),

            'label' => mb_substr($label.' #'.$model->getKey(), 0, 190),

            'payload' => $payload,

            'deleted_by' => auth()->id(),

            'deleted_at' => now(),

            'purge_at' => now()->addDays(self::DAYS),

        ]);

    }



    public static function tree(string $table, array $parentRow): array

    {

        $db = DB::getDatabaseName();

        $edges = array_map(fn ($e) => (array) $e, DB::select('SELECT TABLE_NAME AS t, COLUMN_NAME AS c, REFERENCED_TABLE_NAME AS r, REFERENCED_COLUMN_NAME AS rc FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL', [$db]));

        if ($table === 'events') {

            $cols = DB::select("SELECT DISTINCT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'event_id'", [$db]);

            foreach ($cols as $row) {

                if (! in_array($row->t, ['events', 'trash_items'], true)) {

                    $edges[] = ['t' => $row->t, 'c' => 'event_id', 'r' => 'events', 'rc' => 'id'];

                }

            }

        }

        $out = [];

        $done = [];

        $total = 0;

        $queue = [[$table, [$parentRow]]];

        while ($queue) {

            [$t, $rows] = array_shift($queue);

            foreach ($edges as $e) {

                if ($e['r'] !== $t || $e['t'] === 'trash_items') {

                    continue;

                }

                $key = $e['t'].'.'.$e['c'].'<'.$t;

                if (isset($done[$key])) {

                    continue;

                }

                $done[$key] = true;

                $vals = array_values(array_unique(array_filter(array_column($rows, $e['rc']), fn ($v) => $v !== null)));

                if (! $vals) {

                    continue;

                }

                $found = [];

                foreach (array_chunk($vals, 500) as $chunk) {

                    foreach (DB::table($e['t'])->whereIn($e['c'], $chunk)->get() as $r) {

                        $found[] = (array) $r;

                    }

                }

                if (! $found) {

                    continue;

                }

                $total += count($found);

                if ($total > self::MAX_ROWS) {

                    throw new \RuntimeException('Suppression refusee : trop de donnees liees pour la corbeille.');

                }

                foreach ($found as $r) {

                    $out[$e['t']][md5(json_encode($r))] = $r;

                }

                $queue[] = [$e['t'], $found];

            }

        }



        return array_map('array_values', $out);

    }



    public static function restore(int $trashId): string

    {

        $item = DB::table('trash_items')->where('id', $trashId)->whereNull('restored_at')->first();

        if (! $item) {

            return 'Element introuvable ou deja restaure.';

        }

        $data = json_decode((string) $item->payload, true);

        if (! is_array($data) || empty($data['table']) || empty($data['row']['id'])) {

            return 'Donnees de la corbeille illisibles.';

        }

        try {

            DB::transaction(function () use ($item, $data) {

                if (DB::table($data['table'])->where('id', $data['row']['id'])->exists()) {

                    throw new \RuntimeException('Un enregistrement portant ce numero existe deja.');

                }

                Schema::disableForeignKeyConstraints();

                try {

                    DB::table($data['table'])->insert($data['row']);

                    foreach (($data['children'] ?? []) as $t => $rows) {

                        foreach (array_chunk($rows, 200) as $chunk) {

                            DB::table($t)->insertOrIgnore($chunk);

                        }

                    }

                } finally {

                    Schema::enableForeignKeyConstraints();

                }

                DB::table('trash_items')->where('id', $item->id)->update(['restored_at' => now()]);

            });

            self::restoreFiles($data);

        } catch (\Throwable $e) {

            return 'Restauration impossible : '.$e->getMessage();

        }



        return 'Element restaure.';

    }



    public static function purgeOne(int $id): void

    {

        $item = DB::table('trash_items')->where('id', $id)->first();

        if ($item) {

            self::dropFiles($item);

            DB::table('trash_items')->where('id', $id)->delete();

        }

    }



    public static function purge(bool $all = false): int

    {

        $q = DB::table('trash_items');

        if (! $all) {

            $q->where(function ($w) {

                $w->where('purge_at', '<', now())->orWhere('restored_at', '<', now()->subDays(7));

            });

        }

        foreach ((clone $q)->get(['id', 'payload', 'restored_at']) as $item) {

            self::dropFiles($item);

        }



        return $q->delete();

    }

}


<?php

namespace App\Support;

use App\Models\ExportLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Export CSV compatible Excel (BOM UTF-8, séparateur « ; »), journalisé dans export_logs. */
class CsvExport
{
    /**
     * @param  array<int, string>  $headers
     * @param  callable(mixed): array<int, mixed>  $row
     */
    public static function download(Request $request, string $type, Builder $query, array $headers, callable $row, array $filters = []): StreamedResponse
    {
        ExportLog::create([
            'user_id' => $request->user()->id,
            'type' => $type,
            'filters' => array_filter($filters),
            'row_count' => (clone $query)->count(),
            'ip_address' => $request->ip(),
        ]);

        $filename = $type.'-jnuaf-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $headers, $row) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            $query->chunkById(500, function ($items) use ($out, $row) {
                foreach ($items as $item) {
                    fputcsv($out, $row($item), ';');
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

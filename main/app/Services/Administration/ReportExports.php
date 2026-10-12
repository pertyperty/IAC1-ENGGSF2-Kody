<?php

namespace App\Services\Administration;

use App\Services\Documents\PdfDocuments;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ReportExports
{
    public function download(array $report, string $format): Response
    {
        $filters = $report['filters'];
        $name = 'kody-'.$filters['type'].'-'.$filters['from'].'-'.$filters['to'].'.'.$format;
        $rows = [
            ['Kody platform report', ucfirst($filters['type'])],
            ['Reporting window (Asia/Manila)', $filters['from'].' through '.$filters['to']],
            ['Generated at', $report['generated_at']->toIso8601String()],
            ['Scope', 'Stored records; current lifecycle states; posted ledger units. Not a tax or bank reconciliation statement.'],
            [], ['Metric and unit', 'Total'],
            ...array_map(fn ($row) => [$row['metric'], $row['count']], $report['rows']),
            [], ['Insights', 'Observation'],
            ...array_map(fn ($row) => [$row['title'], $row['detail']], $report['insights']),
        ];
        $mime = match ($format) {
            'pdf' => 'application/pdf', 'csv' => 'text/csv; charset=UTF-8',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
        $bytes = match ($format) {
            'pdf' => app(PdfDocuments::class)->render('reports.pdf', compact('report')),
            'csv' => $this->csv($rows), 'xlsx' => $this->xlsx($rows),
        };

        return response($bytes)->withHeaders(['Content-Type' => $mime, 'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function csv(array $rows): string
    {
        $stream = fopen('php://temp', 'w+');
        try {
            fwrite($stream, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                $safe = array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row);
                fputcsv($stream, $safe, ',', '"', '', "\r\n");
            }
            rewind($stream);

            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'kody-report-');
        try {
            $writer = new Writer;
            $writer->openToFile($path);
            $writer->getCurrentSheet()->setColumnWidth(62, 1);
            $writer->getCurrentSheet()->setColumnWidth(75, 2);
            $normal = (new Style)->withShouldWrapText(true);
            $heading = $normal->withFontBold(true)->withBackgroundColor('EDF3FC');
            foreach ($rows as $index => $row) {
                $style = $index === 0 || $index === 5 || ($row[0] ?? '') === 'Insights' ? $heading : $normal;
                // Strings never become spreadsheet formulas, even if a label changes later.
                $writer->addRow(new Row(array_map(fn ($value) => is_string($value) ? new StringCell($value, $style) : Cell::fromValue($value, $style), $row), $index === 3 ? 60 : 30));
            }
            $writer->close();

            return file_get_contents($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}

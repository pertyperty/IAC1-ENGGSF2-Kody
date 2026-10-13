<?php

namespace App\Services\Administration;

use App\Services\Documents\ExportFiles;
use App\Services\Documents\PdfDocuments;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\HeaderFooter;
use OpenSpout\Writer\XLSX\Options\PageOrientation;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\PaperSize;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;

class ReportExports
{
    public const string SCOPE = 'Private Administrator management report. Asia/Manila calendar dates, inclusive; current lifecycle states at the generation snapshot. Retained records only. Not audited financial statements or a tax/bank reconciliation. No provider payloads, credentials, recipient details or personal identities.';

    public const string FINANCIAL_SCOPE = 'Cash totals use posting dates and reconcile opening + inflows - outflows = closing. Paid operating expenses are included in outflows. Wallet backing and creator earnings are separate liabilities. Monthly cost estimates may include commitments and must not be added to paid expenses. Payment/payout/refund registers show requests created in the window and their current state, not settlement-date revenue. Register extracts include the latest 200 records per source; totals and trends include all matching records. PHP display values are exact decimal text; centavo columns are integers for analysis.';

    public function download(array $report, string $format): Response
    {
        $filters = $report['filters'];
        $name = 'kody-'.$filters['type'].'-'.$filters['from'].'-'.$filters['to'].'-'.($filters['group'] ?? 'day').'.'.$format;
        $mime = match ($format) {
            'pdf' => 'application/pdf', 'csv' => 'text/csv; charset=UTF-8',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
        $bytes = match ($format) {
            'pdf' => app(PdfDocuments::class)->render('reports.pdf', compact('report')),
            'csv' => $this->csv($this->sections($report)), 'xlsx' => $this->xlsx($this->sections($report)),
        };

        return response($bytes)->withHeaders(['Content-Type' => $mime, 'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function sections(array $report): array
    {
        $filters = $report['filters'];
        $sections = [
            ['title' => 'Executive summary', 'note' => self::SCOPE, 'headers' => ['Management indicator', 'Observation'], 'rows' => array_map(fn ($row) => [$row['title'], $row['detail']], $report['insights'])],
            ['title' => 'Recorded metrics', 'note' => 'Counts and exact units are labeled per metric. Current states do not reconstruct historical transitions.', 'headers' => ['Metric and unit', 'Total'], 'rows' => array_map(fn ($row) => [$row['metric'], $row['count']], $report['rows'])],
            ['title' => 'Activity trends', 'note' => ucfirst($report['trends']['group']).' grouping. First/last buckets may be partial; only selected calendar dates count. Completion records can include replays. Cash units are integer PHP centavos.',
                'headers' => ['Period', 'Registrations', 'Assessed completions', 'Article completions', 'Coding attempts', 'XP granted', 'Paid expense centavos', 'Net cash centavos'],
                'rows' => array_map(fn ($row) => array_values($row), $report['trends']['rows'])],
        ];
        if ($report['financials'] !== null) {
            $sections[] = ['title' => 'Financial position', 'note' => self::FINANCIAL_SCOPE, 'headers' => ['Financial measure', 'PHP exact display', 'PHP centavos'],
                'rows' => array_map(fn ($row) => [$row['metric'], $row['display'], $row['minor']], $report['financials']['totals'])];
            foreach ($report['financials']['registers'] as $register) {
                $sections[] = [...$register, 'note' => count($register['rows']).' of '.$register['count'].' matching records. Latest first except monthly estimates. Narrow the date window to inspect additional records.'];
            }
        }
        foreach ($sections as &$section) {
            $section['window'] = ucfirst($filters['type']).' | '.$filters['from'].' through '.$filters['to'].' | Generated '.$report['generated_at']->format('Y-m-d H:i').' Manila';
        }

        return $sections;
    }

    private function csv(array $sections): string
    {
        $stream = fopen('php://temp', 'w+');
        try {
            fwrite($stream, "\xEF\xBB\xBF");
            foreach ($sections as $section) {
                foreach ([[$section['title']], [$section['window']], [$section['note']], $section['headers'], ...$section['rows'], []] as $row) {
                    $safe = array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row);
                    fputcsv($stream, $safe, ',', '"', '', "\r\n");
                }
            }
            rewind($stream);

            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    private function xlsx(array $sections): string
    {
        abort_unless(class_exists(Writer::class), 503, 'Excel export is unavailable until the server Composer dependencies are installed.');

        return app(ExportFiles::class)->within(function (string $directory) use ($sections): string {
            $options = new Options(tempFolder: $directory, pageSetup: new PageSetup(PageOrientation::LANDSCAPE, PaperSize::A4, fitToWidth: 1),
                // OpenSpout writes header/footer XML verbatim; Excel control ampersands need XML escaping.
                headerFooter: new HeaderFooter(oddHeader: '&amp;LKody / private management report', oddFooter: '&amp;LConfidential&amp;RPage &amp;P of &amp;N'),
                properties: new Properties(title: 'Kody management report', creator: 'Kody', application: 'Kody'));
            $writer = new Writer($options);
            $path = $directory.'/report.xlsx';
            $normal = (new Style)->withFontSize(11)->withShouldWrapText(true)->withCellVerticalAlignment(CellVerticalAlignment::CENTER)->withFontColor('20344D');
            $heading = $normal->withFontBold(true)->withBackgroundColor('20344D')->withFontColor('FFFFFF');
            $muted = $normal->withFontSize(10)->withFontColor('53657C');
            $writer->openToFile($path);
            try {
                foreach ($sections as $index => $section) {
                    $sheet = $index === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                    $sheet->setName($section['title']);
                    $sheet->setSheetView(new SheetView(showGridLines: false, freezeRow: 5));
                    $columnCount = count($section['headers']);
                    $widths = match ($index) {
                        0 => [32, 90], 1 => [72, 22], 2 => [18, 18, 20, 20, 18, 15, 23, 23], 3 => [62, 24, 24],
                        default => array_map(fn ($header) => match (true) {
                            $header === 'Record ID' => 40, str_contains($header, 'Manila') => 24,
                            str_contains($header, 'PHP') => 24, $header === 'Classification' => 30, default => 20,
                        }, $section['headers']),
                    };
                    foreach ($widths as $column => $width) {
                        $sheet->setColumnWidth($width, $column + 1);
                    }
                    $options->mergeCells(0, 1, $columnCount - 1, 1, $index);
                    $options->mergeCells(0, 2, $columnCount - 1, 2, $index);
                    $options->mergeCells(0, 3, $columnCount - 1, 3, $index);
                    $this->writeRow($writer, ['KODY / '.strtoupper($section['title'])], $heading->withFontSize(18), 40);
                    $this->writeRow($writer, [$section['window']], $muted, 32);
                    $this->writeRow($writer, [$section['note']], $muted, $index === 3 ? 110 : 72);
                    $this->writeRow($writer, $section['headers'], $heading, 38);
                    foreach ($section['rows'] as $rowIndex => $row) {
                        $style = $rowIndex % 2 === 0 ? $normal : $normal->withBackgroundColor('EFF4FA');
                        $this->writeRow($writer, $row, $style, $index === 0 ? 75 : 38);
                    }
                    if ($section['rows'] === []) {
                        $this->writeRow($writer, ['No retained records in this window.'], $muted, 32);
                    } else {
                        $sheet->setAutoFilter(new AutoFilter(0, 4, $columnCount - 1, 4 + count($section['rows'])));
                    }
                }
            } finally {
                $writer->close();
            }

            return file_get_contents($path);
        });
    }

    private function writeRow(Writer $writer, array $values, Style $style, int $height): void
    {
        // Strings are explicit text cells: creator labels can never become formulas.
        $writer->addRow(new Row(array_map(fn ($value) => is_string($value) ? new StringCell($value, $style) : Cell::fromValue($value, $style), $values), $height));
    }
}

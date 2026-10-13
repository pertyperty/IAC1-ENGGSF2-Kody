<?php

namespace App\Services\Documents;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

class PdfDocuments
{
    public function render(string $view, array $data): string
    {
        abort_unless(in_array($view, ['reports.pdf', 'transactions.receipt-pdf'], true), 500);
        abort_unless(class_exists(Options::class) && class_exists(Dompdf::class), 503, 'PDF export is unavailable until the server Composer dependencies are installed.');
        $cache = storage_path('framework/cache/pdf');
        File::ensureDirectoryExists($cache);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', resource_path('views'));
        $options->set('fontCache', $cache);
        $options->set('tempDir', $cache);
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4');
        $pdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $pdf->render();
        $pdf->getCanvas()->page_text(500, 812, 'Page {PAGE_NUM} / {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [.34, .41, .49]);

        return $pdf->output();
    }
}

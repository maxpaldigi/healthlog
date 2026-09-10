<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;

final class VisitReportPdf
{
    /**
     * @param  array<string, mixed>  $report
     */
    public static function render(array $report): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setChroot(public_path());

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('reports.visit-pdf', [
            'report' => $report,
            'logo' => public_path('logo.png'),
        ])->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return $pdf->output();
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public static function filename(array $report): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', (string) ($report['profile']['name'] ?? 'healthyme')) ?? 'healthyme';
        $from = (string) ($report['range']['from'] ?? 'from');
        $to = (string) ($report['range']['to'] ?? 'to');

        return 'healthyme-'.trim($name, '-').'-'.$from.'-to-'.$to.'.pdf';
    }
}

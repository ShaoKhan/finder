<?php

declare(strict_types=1);

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function generatePdf(string $template, array $data, string $filename = 'document.pdf'): Response
    {
        // Schritt 1: Dompdf konfigurieren
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        // Schritt 2: HTML aus dem Twig-Template rendern
        $html = $this->twig->render($template, $data);

        $dompdf->loadHtml($html);

        // Schritt 3: Seitengröße und Orientierung einstellen
        $dompdf->setPaper('A4', 'portrait');

        // Schritt 4: PDF generieren
        $dompdf->render();

        // Schritt 5: PDF als Response zurückgeben
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }
}

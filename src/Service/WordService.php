<?php
declare(strict_types=1);
namespace App\Service;


use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\Response;

class WordService
{
    /**
     * @throws Exception
     */
    public function generateWord(array $data, string $filename = 'document.docx'): Response
    {
        // Erstelle ein neues Word-Dokument
        $phpWord = new PhpWord();

        // Abschnitt hinzufügen
        $section = $phpWord->addSection();

        // Titel
        $section->addTitle("Upload Report", 1);

        // Tabelle hinzufügen
        $table = $section->addTable();

        // Kopfzeile
        $table->addRow();
        $table->addCell(3000)->addText('Field');
        $table->addCell(6000)->addText('Value');

        // Daten in die Tabelle einfügen
        foreach ($data as $key => $value) {
            $table->addRow();
            $table->addCell(3000)->addText($key);
            $table->addCell(6000)->addText($value);
        }

        // Word-Dokument als temporäre Datei speichern
        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        // Response erstellen und Datei ausliefern
        return new Response(
            file_get_contents($tempFile),
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}

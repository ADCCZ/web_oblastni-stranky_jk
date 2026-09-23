<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Repository\FormTemplateRepository;
use App\Model\Repository\RegistrationRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExcelExportService
{
    private string $tempDir;

    public function __construct(
        private RegistrationRepository $registrationRepository,
        private FormTemplateRepository $formTemplateRepository,
    ) {
    }

    public function setTempDir(string $tempDir): void
    {
        $this->tempDir = $tempDir;
    }

    /**
     * Vygeneruje XLSX soubor s registracemi pro danou akci.
     *
     * @return string|null Cesta k temp souboru, nebo null pri chybe
     */
    public function generateEventRegistrations(int $eventId, ?int $formTemplateId, string $eventTitle = 'Akce'): ?string
    {
        $registrations = $this->registrationRepository->findResponsesByEvent($eventId);
        if (empty($registrations)) {
            return null;
        }

        // Nacti pole sablony (preskoc zobrazovaci typy - info, image, youtube)
        $displayOnlyTypes = ['info', 'image', 'youtube'];
        $fields = [];
        if ($formTemplateId) {
            $templateData = $this->formTemplateRepository->getTemplateWithFields($formTemplateId);
            if ($templateData) {
                foreach ($templateData['fields'] as $field) {
                    if (!in_array($field->field_type, $displayOnlyTypes, true)) {
                        $fields[] = $field;
                    }
                }
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registrace');

        // Hlavicka
        $headers = ['#', 'Jméno', 'Příjmení', 'Datum narození', 'Člen KP', 'Bydliště'];
        foreach ($fields as $field) {
            $headers[] = $field->label;
        }
        $headers[] = 'Poznámka';
        $headers[] = 'Stav';
        $headers[] = 'Datum registrace';

        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, 1], $header);
            $col++;
        }

        // Styl hlavicky
        $headerRange = 'A1:' . $this->columnLetter(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0075B5'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Data
        $statusNames = [
            'pending' => 'Čekající',
            'confirmed' => 'Potvrzená',
            'cancelled' => 'Zrušená',
            'waitlist' => 'Náhradník',
        ];

        $row = 2;
        foreach ($registrations as $i => $reg) {
            $col = 1;
            $sheet->setCellValue([$col++, $row], $i + 1);
            $sheet->setCellValue([$col++, $row], $reg['first_name']);
            $sheet->setCellValue([$col++, $row], $reg['last_name']);
            $sheet->setCellValue([$col++, $row], $reg['birth_date'] ? $reg['birth_date']->format('j. n. Y') : '');
            $sheet->setCellValue([$col++, $row], $reg['is_pathfinder_member'] ? 'Ano' : 'Ne');
            $address = trim(($reg['street'] ?? '') . ', ' . ($reg['city'] ?? '') . ' ' . ($reg['zip'] ?? ''), ', ');
            $sheet->setCellValue([$col++, $row], $address);

            foreach ($fields as $field) {
                $sheet->setCellValue([$col++, $row], $reg['responses'][$field->id] ?? '');
            }

            $sheet->setCellValue([$col++, $row], $reg['note'] ?? '');
            $sheet->setCellValue([$col++, $row], $statusNames[$reg['status']] ?? $reg['status']);
            $sheet->setCellValue([$col++, $row], $reg['created_at']->format('j. n. Y H:i'));

            $row++;
        }

        // Auto-sirka sloupcu
        for ($c = 1; $c <= count($headers); $c++) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        // Uloz do temp souboru
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $eventTitle);
        $filename = 'registrace_' . $safeName . '_' . date('Y-m-d') . '.xlsx';
        $filepath = rtrim($this->tempDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    /**
     * Prevede cislo sloupce na pismeno (1=A, 2=B, ..., 27=AA)
     */
    private function columnLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $mod = ($col - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $col = (int) (($col - $mod) / 26);
        }
        return $letter;
    }
}

<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use app\models\TestResult;
use app\models\TestTemplate;
use app\models\TestQueue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use TCPDF;

class ExportController extends Controller {

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Eksport wyników badań
     */
    public function actionTestResults($format = 'excel', $template_id = null) {
        $query = TestResult::find()->with(['testTemplate', 'resultValues.parameter']);

        if ($template_id) {
            $query->where(['test_template_id' => $template_id]);
        }

        $results = $query->all();

        switch ($format) {
            case 'pdf':
                return $this->exportResultsToPdf($results);
            default:
                return $this->exportResultsToExcel($results);
        }
    }

    /**
     * Eksport szablonów badań
     */
    public function actionTestTemplates($format = 'excel') {
        $templates = TestTemplate::find()->with(['parameters.norms'])->all();

        switch ($format) {
            case 'pdf':
                return $this->exportTemplatesToPdf($templates);
            default:
                return $this->exportTemplatesToExcel($templates);
        }
    }

    /**
     * Eksport kolejki badań
     */
    public function actionTestQueue($format = 'excel') {
        $queue = TestQueue::find()->with('testTemplate')->all();

        switch ($format) {
            case 'pdf':
                return $this->exportQueueToPdf($queue);
            default:
                return $this->exportQueueToExcel($queue);
        }
    }

    /**
     * Eksport wszystkich danych użytkownika
     */
    public function actionFullExport($format = 'excel') {
        $data = [
            'results' => TestResult::find()->with(['testTemplate', 'resultValues.parameter'])->all(),
            'templates' => TestTemplate::find()->with(['parameters.norms'])->all(),
            'queue' => TestQueue::find()->with('testTemplate')->all(),
        ];

        switch ($format) {
            case 'pdf':
                return $this->exportFullDataToPdf($data);
            default:
                return $this->exportFullDataToExcel($data);
        }
    }

    // ==================== EXCEL METHODS ====================

    /**
     * Eksport wyników badań z wartościami - Excel
     */
    private function exportResultsToExcel($results) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Wyniki badań');

        // Podstawowe informacje o wyniku
        $headers = ['ID', 'Szablon badania', 'Data badania', 'Komentarz', 'Nieprawidłowe wartości'];

        // Dodaj kolumny dla parametrów (dynamiczne)
        $allParameters = $this->getAllParametersFromResults($results);
        foreach ($allParameters as $param) {
            $headers[] = $param['name'] . ' (' . $param['unit'] . ')';
            $headers[] = 'Status ' . $param['name'];
        }

        // Ustaw nagłówki
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, 1], $header);
            $col++;
        }

        // Style nagłówka
        $headerRange = 'A1:' . $sheet->getCell([$col - 1, 1])->getCoordinate();
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle($headerRange)->getFill()->getStartColor()->setRGB('E2E8F0');

        // Dane
        $row = 2;
        foreach ($results as $result) {
            $col = 1;

            // Podstawowe dane
            $sheet->setCellValue([$col++, $row], $result->id);
            $sheet->setCellValue([$col++, $row], $result->testTemplate->name);
            $sheet->setCellValue([$col++, $row], $result->test_date);
            $sheet->setCellValue([$col++, $row], $result->comment);
            $sheet->setCellValue([$col++, $row], $result->has_abnormal_values ? 'Tak' : 'Nie');

            // Wartości parametrów
            $resultValues = $this->getResultValuesArray($result);

            foreach ($allParameters as $param) {
                $paramValue = $resultValues[$param['id']] ?? null;

                if ($paramValue) {
                    // Wartość
                    $sheet->setCellValue([$col++, $row], $paramValue['value']);

                    // Status (normalny/nieprawidłowy)
                    $status = $paramValue['is_normal'] ? 'Normalny' : 'Nieprawidłowy';
                    $sheet->setCellValue([$col++, $row], $status);

                    // Kolorowanie nieprawidłowych wartości
                    if (!$paramValue['is_normal']) {
                        $sheet->getStyle([$col - 2, $row])->getFont()->getColor()->setRGB('DC3545');
                        $sheet->getStyle([$col - 2, $row])->getFont()->setBold(true);
                        $sheet->getStyle([$col - 1, $row])->getFont()->getColor()->setRGB('DC3545');
                    }
                } else {
                    // Brak wartości
                    $sheet->setCellValue([$col++, $row], '-');
                    $sheet->setCellValue([$col++, $row], '-');
                }
            }

            $row++;
        }

        // Auto-size wszystkich kolumn
        for ($i = 1; $i < $col; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        return $this->downloadExcel($spreadsheet, 'wyniki-badan-szczegolowe');
    }

    /**
     * Eksport szablonów z parametrami - Excel
     */
    private function exportTemplatesToExcel($templates) {
        $spreadsheet = new Spreadsheet();

        // Arkusz 1: Podstawowe informacje o szablonach
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Szablony - podstawowe');

        $headers = ['ID', 'Nazwa', 'Opis', 'Status', 'Liczba parametrów', 'Utworzono'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet1->setCellValue([$col, 1], $header);
            $col++;
        }

        $sheet1->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet1->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet1->getStyle('A1:F1')->getFill()->getStartColor()->setRGB('E2E8F0');

        $row = 2;
        foreach ($templates as $template) {
            $sheet1->setCellValue([1, $row], $template->id);
            $sheet1->setCellValue([2, $row], $template->name);
            $sheet1->setCellValue([3, $row], $template->description);
            $sheet1->setCellValue([4, $row], $template->status ? 'Aktywny' : 'Nieaktywny');
            $sheet1->setCellValue([5, $row], count($template->parameters));
            $sheet1->setCellValue([6, $row], date('Y-m-d H:i', $template->created_at));
            $row++;
        }

        foreach (range('A', 'F') as $column) {
            $sheet1->getColumnDimension($column)->setAutoSize(true);
        }

        // Arkusz 2: Parametry szablonów
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Parametry szablonów');

        $paramHeaders = ['ID szablonu', 'Nazwa szablonu', 'ID parametru', 'Nazwa parametru', 'Jednostka', 'Typ normy', 'Zakres normalny'];
        $col = 1;
        foreach ($paramHeaders as $header) {
            $sheet2->setCellValue([$col, 1], $header);
            $col++;
        }

        $sheet2->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet2->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet2->getStyle('A1:G1')->getFill()->getStartColor()->setRGB('D4EDDA');

        $row = 2;
        foreach ($templates as $template) {
            foreach ($template->parameters as $parameter) {
                $sheet2->setCellValue([1, $row], $template->id);
                $sheet2->setCellValue([2, $row], $template->name);
                $sheet2->setCellValue([3, $row], $parameter->id);
                $sheet2->setCellValue([4, $row], $parameter->name);
                $sheet2->setCellValue([5, $row], $parameter->unit ?: '-');

                // Informacje o normach
                if (!empty($parameter->norms)) {
                    $norm = $parameter->norms[0]; // Pierwsza norma
                    $sheet2->setCellValue([6, $row], $this->getNormTypeName($norm->type));
                    $sheet2->setCellValue([7, $row], $this->getNormRangeText($norm));
                } else {
                    $sheet2->setCellValue([6, $row], 'Brak norm');
                    $sheet2->setCellValue([7, $row], '-');
                }

                $row++;
            }
        }

        foreach (range('A', 'G') as $column) {
            $sheet2->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadExcel($spreadsheet, 'szablony-badan-szczegolowe');
    }

    private function exportQueueToExcel($queue) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kolejka badań');

        $headers = ['ID', 'Szablon badania', 'Data planowana', 'Status', 'Komentarz', 'Utworzono'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, 1], $header);
            $col++;
        }

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1:F1')->getFill()->getStartColor()->setRGB('E2E8F0');

        $row = 2;
        foreach ($queue as $item) {
            $statusOptions = ['pending' => 'Oczekujące', 'completed' => 'Wykonane', 'cancelled' => 'Anulowane'];

            $sheet->setCellValue([1, $row], $item->id);
            $sheet->setCellValue([2, $row], $item->testTemplate->name);
            $sheet->setCellValue([3, $row], $item->scheduled_date);
            $sheet->setCellValue([4, $row], $statusOptions[$item->status] ?? $item->status);
            $sheet->setCellValue([5, $row], $item->comment);
            $sheet->setCellValue([6, $row], date('Y-m-d H:i', $item->created_at));
            $row++;
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $this->downloadExcel($spreadsheet, 'kolejka-badan');
    }

    private function exportFullDataToExcel($data) {
        $spreadsheet = new Spreadsheet();

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Wyniki badań');
        $this->addResultsToSheet($sheet1, $data['results']);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Szablony badań');
        $this->addTemplatesToSheet($sheet2, $data['templates']);

        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Kolejka badań');
        $this->addQueueToSheet($sheet3, $data['queue']);

        return $this->downloadExcel($spreadsheet, 'pelny-eksport-danych');
    }

    // ==================== PDF METHODS ====================

    /**
     * Eksport wyników badań z wartościami - PDF
     */
    private function exportResultsToPdf($results) {
        $pdf = $this->createPDF('Wyniki badań z wartościami');

        $html = '<h2 style="color: #333; text-align: center; margin-bottom: 20px;">Wyniki badań medycznych</h2>';
        $html .= '<p style="text-align: center; color: #666; margin-bottom: 20px;">Data generowania: ' . date('d.m.Y H:i') . '</p>';

        foreach ($results as $index => $result) {
            // Nowa strona dla każdego wyniku (oprócz pierwszego)
            if ($index > 0) {
                $pdf->AddPage();
            }

            // Podstawowe informacje o wyniku
            $html = '<div style="background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;">';
            $html .= '<h3 style="color: #495057; margin: 0 0 10px 0;">Wynik badania #' . $result->id . '</h3>';
            $html .= '<table cellpadding="5" cellspacing="0" border="0" style="width: 100%; font-size: 10px;">';
            $html .= '<tr><td style="width: 25%; font-weight: bold;">Szablon:</td><td style="width: 75%;">' . htmlspecialchars($result->testTemplate->name) . '</td></tr>';
            $html .= '<tr><td style="width: 25%; font-weight: bold;">Data badania:</td><td style="width: 75%;">' . $result->test_date . '</td></tr>';
            $html .= '<tr><td style="width: 25%; font-weight: bold;">Status:</td><td style="width: 75%;"><span style="color: ' . ($result->has_abnormal_values ? '#dc3545' : '#28a745') . '; font-weight: bold;">' . ($result->has_abnormal_values ? 'Zawiera nieprawidłowe wartości' : 'Wszystkie wartości prawidłowe') . '</span></td></tr>';
            if ($result->comment) {
                $html .= '<tr><td style="width: 25%; font-weight: bold;">Komentarz:</td><td style="width: 75%;">' . htmlspecialchars($result->comment) . '</td></tr>';
            }
            $html .= '</table>';
            $html .= '</div>';

            // Tabela z wartościami parametrów
            $html .= '<h4 style="color: #495057; margin-bottom: 15px;">Wartości parametrów:</h4>';
            $html .= '<table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 9px;">';
            $html .= '<thead>
                    <tr style="background-color: #e9ecef; font-weight: bold;">
                        <th style="width: 35%; text-align: left; border: 1px solid #ddd;">Parametr</th>
                        <th style="width: 15%; text-align: center; border: 1px solid #ddd;">Wartość</th>
                        <th style="width: 10%; text-align: center; border: 1px solid #ddd;">Jednostka</th>
                        <th style="width: 25%; text-align: center; border: 1px solid #ddd;">Zakres normalny</th>
                        <th style="width: 15%; text-align: center; border: 1px solid #ddd;">Status</th>
                    </tr>
                  </thead><tbody>';

            $hasValues = false;
            foreach ($result->resultValues as $resultValue) {
                $hasValues = true;
                $parameter = $resultValue->parameter;
                $norm = $resultValue->norm;

                // Sprawdź czy wartość jest prawidłowa
                $isNormal = true;
                $normalRange = 'Brak norm';

                if ($norm) {
                    $validationResult = $norm->checkValue($resultValue->value);
                    $isNormal = $validationResult['is_normal'];

                    if ($norm->type === 'range') {
                        $normalRange = $norm->min_value . ' - ' . $norm->max_value;
                    } elseif ($norm->type === 'single_threshold') {
                        $normalRange = ($norm->threshold_direction === 'above' ? '≤ ' : '≥ ') . $norm->threshold_value;
                    }
                }

                $statusColor = $isNormal ? '#28a745' : '#dc3545';
                $valueColor = $isNormal ? '#333' : '#dc3545';
                $statusText = $isNormal ? 'Normalny' : 'Nieprawidłowy';

                $html .= '<tr>';
                $html .= '<td style="width: 35%; text-align: left; border: 1px solid #ddd; padding: 6px;">' . htmlspecialchars($parameter->name) . '</td>';
                $html .= '<td style="width: 15%; text-align: center; border: 1px solid #ddd; padding: 6px; color: ' . $valueColor . '; font-weight: ' . ($isNormal ? 'normal' : 'bold') . ';">' . $resultValue->value . '</td>';
                $html .= '<td style="width: 10%; text-align: center; border: 1px solid #ddd; padding: 6px;">' . ($parameter->unit ?: '-') . '</td>';
                $html .= '<td style="width: 25%; text-align: center; border: 1px solid #ddd; padding: 6px; font-size: 8px;">' . $normalRange . '</td>';
                $html .= '<td style="width: 15%; text-align: center; border: 1px solid #ddd; padding: 6px; color: ' . $statusColor . '; font-weight: bold;">' . $statusText . '</td>';
                $html .= '</tr>';
            }

            if (!$hasValues) {
                $html .= '<tr><td colspan="5" style="text-align: center; padding: 20px; color: #666; font-style: italic;">Brak wprowadzonych wartości parametrów</td></tr>';
            }

            $html .= '</tbody></table>';

            // Dodaj informacje o normach jeśli są nieprawidłowe wartości
            $abnormalValues = array_filter($result->resultValues, function ($rv) {
                return $rv->norm && !$rv->norm->checkValue($rv->value)['is_normal'];
            });

            if (!empty($abnormalValues)) {
                $html .= '<div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">';
                $html .= '<h5 style="color: #856404; margin: 0 0 10px 0;">⚠️ Uwagi dotyczące nieprawidłowych wartości:</h5>';
                $html .= '<ul style="margin: 0; padding-left: 20px; color: #856404;">';
                foreach ($abnormalValues as $av) {
                    $validationResult = $av->norm->checkValue($av->value);
                    $html .= '<li style="margin-bottom: 5px;"><strong>' . $av->parameter->name . ':</strong> ';
                    if ($validationResult['type'] === 'low') {
                        $html .= 'Wartość poniżej normy';
                    } elseif ($validationResult['type'] === 'high') {
                        $html .= 'Wartość powyżej normy';
                    } else {
                        $html .= 'Wartość nieprawidłowa';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
                $html .= '</div>';
            }

            $pdf->writeHTML($html, true, false, true, false, '');

            // Reset HTML dla następnej strony
            $html = '';
        }

        return $this->outputPDF($pdf, 'wyniki-badan-szczegolowe');
    }

    private function exportTemplatesToPdf($templates) {
        $pdf = $this->createPDF('Szablony badań');

        $html = '<h2 style="color: #333; text-align: center; margin-bottom: 20px;">Szablony badań medycznych</h2>';
        $html .= '<p style="text-align: center; color: #666; margin-bottom: 20px;">Data generowania: ' . date('d.m.Y H:i') . '</p>';

        $html .= '<table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 9px;">';
        $html .= '<thead>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <th style="width: 8%; text-align: center; border: 1px solid #ddd;">ID</th>
                    <th style="width: 25%; text-align: left; border: 1px solid #ddd;">Nazwa</th>
                    <th style="width: 42%; text-align: left; border: 1px solid #ddd;">Opis</th>
                    <th style="width: 12%; text-align: center; border: 1px solid #ddd;">Status</th>
                    <th style="width: 13%; text-align: center; border: 1px solid #ddd;">Parametry</th>
                </tr>
              </thead><tbody>';

        foreach ($templates as $template) {
            $description = $template->description ? substr(strip_tags($template->description), 0, 80) : '-';
            if (strlen($template->description ?: '') > 80) {
                $description .= '...';
            }

            $name = substr($template->name, 0, 35);
            if (strlen($template->name) > 35) {
                $name .= '...';
            }

            $statusColor = $template->status ? '#28a745' : '#dc3545';
            $statusText = $template->status ? 'Aktywny' : 'Nieaktywny';

            $html .= '<tr>';
            $html .= '<td style="width: 8%; text-align: center; border: 1px solid #ddd; padding: 6px;">' . $template->id . '</td>';
            $html .= '<td style="width: 25%; text-align: left; border: 1px solid #ddd; padding: 6px;">' . htmlspecialchars($name) . '</td>';
            $html .= '<td style="width: 42%; text-align: left; border: 1px solid #ddd; padding: 6px; font-size: 8px;">' . htmlspecialchars($description) . '</td>';
            $html .= '<td style="width: 12%; text-align: center; border: 1px solid #ddd; padding: 6px;"><span style="color: ' . $statusColor . '; font-weight: bold;">' . $statusText . '</span></td>';
            $html .= '<td style="width: 13%; text-align: center; border: 1px solid #ddd; padding: 6px;">' . count($template->parameters) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 15px;">
                <p><strong>Raport wygenerowany przez system MedArchive</strong></p>
                <p>Liczba szablonów: ' . count($templates) . ' | Data: ' . date('d.m.Y H:i') . '</p>
              </div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->outputPDF($pdf, 'szablony-badan');
    }

    private function exportQueueToPdf($queue) {
        $pdf = $this->createPDF('Kolejka badań');

        $html = '<h2 style="color: #333; text-align: center; margin-bottom: 20px;">Kolejka badań</h2>';
        $html .= '<p style="text-align: center; color: #666; margin-bottom: 20px;">Data generowania: ' . date('d.m.Y H:i') . '</p>';

        $html .= '<table cellpadding="8" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 9px;">';
        $html .= '<thead>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <th style="width: 8%; text-align: center; border: 1px solid #ddd;">ID</th>
                    <th style="width: 32%; text-align: left; border: 1px solid #ddd;">Szablon badania</th>
                    <th style="width: 15%; text-align: center; border: 1px solid #ddd;">Data planowana</th>
                    <th style="width: 15%; text-align: center; border: 1px solid #ddd;">Status</th>
                    <th style="width: 30%; text-align: left; border: 1px solid #ddd;">Komentarz</th>
                </tr>
              </thead><tbody>';

        $statusOptions = [
            'pending' => ['text' => 'Oczekujące', 'color' => '#ffc107'],
            'completed' => ['text' => 'Wykonane', 'color' => '#28a745'],
            'cancelled' => ['text' => 'Anulowane', 'color' => '#dc3545']
        ];

        foreach ($queue as $item) {
            $comment = $item->comment ? substr(strip_tags($item->comment), 0, 50) : '-';
            if (strlen($item->comment ?: '') > 50) {
                $comment .= '...';
            }

            $templateName = substr($item->testTemplate->name, 0, 40);
            if (strlen($item->testTemplate->name) > 40) {
                $templateName .= '...';
            }

            $status = $statusOptions[$item->status] ?? ['text' => $item->status, 'color' => '#6c757d'];

            $html .= '<tr>';
            $html .= '<td style="width: 8%; text-align: center; border: 1px solid #ddd; padding: 6px;">' . $item->id . '</td>';
            $html .= '<td style="width: 32%; text-align: left; border: 1px solid #ddd; padding: 6px;">' . htmlspecialchars($templateName) . '</td>';
            $html .= '<td style="width: 15%; text-align: center; border: 1px solid #ddd; padding: 6px;">' . $item->scheduled_date . '</td>';
            $html .= '<td style="width: 15%; text-align: center; border: 1px solid #ddd; padding: 6px;"><span style="color: ' . $status['color'] . '; font-weight: bold;">' . $status['text'] . '</span></td>';
            $html .= '<td style="width: 30%; text-align: left; border: 1px solid #ddd; padding: 6px; font-size: 8px;">' . htmlspecialchars($comment) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 15px;">
                <p><strong>Raport wygenerowany przez system MedArchive</strong></p>
                <p>Liczba pozycji w kolejce: ' . count($queue) . ' | Data: ' . date('d.m.Y H:i') . '</p>
              </div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->outputPDF($pdf, 'kolejka-badan');
    }

    private function exportFullDataToPdf($data) {
        $pdf = $this->createPDF('Pełny eksport danych');

        // Strona 1: Podsumowanie
        $html = '<h1 style="color: #333; text-align: center; margin-bottom: 30px;">Pełny eksport danych medycznych</h1>';
        $html .= '<p style="text-align: center; color: #666; font-size: 12px; margin-bottom: 30px;">Data generowania: ' . date('d.m.Y H:i') . '</p>';

        $html .= '<div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px;">';
        $html .= '<h3 style="color: #495057; margin-bottom: 15px;">Podsumowanie danych:</h3>';
        $html .= '<table cellpadding="10" cellspacing="0" border="0" style="width: 100%;">';
        $html .= '<tr><td style="width: 50%; font-size: 14px;"><strong>📊 Wyniki badań:</strong></td><td style="width: 50%; font-size: 14px; text-align: right;">' . count($data['results']) . '</td></tr>';
        $html .= '<tr><td style="width: 50%; font-size: 14px;"><strong>📋 Szablony badań:</strong></td><td style="width: 50%; font-size: 14px; text-align: right;">' . count($data['templates']) . '</td></tr>';
        $html .= '<tr><td style="width: 50%; font-size: 14px;"><strong>📅 Zaplanowane badania:</strong></td><td style="width: 50%; font-size: 14px; text-align: right;">' . count($data['queue']) . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div style="margin-top: 40px; padding: 15px; border: 1px solid #dee2e6; background-color: #e9ecef;">';
        $html .= '<p style="font-size: 12px; margin: 0; text-align: center;">Ten raport zawiera szczegółowe informacje na kolejnych stronach.</p>';
        $html .= '</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Strona 2: Wyniki badań (skrócone)
        if (!empty($data['results'])) {
            $pdf->AddPage();
            $html = '<h2 style="color: #333; margin-bottom: 20px;">Wyniki badań (ostatnie 25)</h2>';
            $html .= '<table cellpadding="6" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 8px;">';
            $html .= '<thead>
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <th style="width: 8%; text-align: center; border: 1px solid #ddd;">ID</th>
                        <th style="width: 40%; text-align: left; border: 1px solid #ddd;">Szablon</th>
                        <th style="width: 20%; text-align: center; border: 1px solid #ddd;">Data</th>
                        <th style="width: 32%; text-align: left; border: 1px solid #ddd;">Komentarz</th>
                    </tr>
                  </thead><tbody>';

            foreach (array_slice($data['results'], 0, 25) as $result) {
                $comment = substr(strip_tags($result->comment ?: '-'), 0, 35);
                $templateName = substr($result->testTemplate->name, 0, 45);

                $html .= '<tr>';
                $html .= '<td style="width: 8%; text-align: center; border: 1px solid #ddd; padding: 4px;">' . $result->id . '</td>';
                $html .= '<td style="width: 40%; text-align: left; border: 1px solid #ddd; padding: 4px;">' . htmlspecialchars($templateName) . '</td>';
                $html .= '<td style="width: 20%; text-align: center; border: 1px solid #ddd; padding: 4px;">' . $result->test_date . '</td>';
                $html .= '<td style="width: 32%; text-align: left; border: 1px solid #ddd; padding: 4px;">' . htmlspecialchars($comment) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            if (count($data['results']) > 25) {
                $html .= '<p style="margin-top: 15px; font-style: italic; color: #666; text-align: center;">Pokazano pierwsze 25 wyników z ' . count($data['results']) . '. Pełna lista dostępna w eksporcie Excel.</p>';
            }

            $pdf->writeHTML($html, true, false, true, false, '');
        }

        // Strona 3: Szablony badań
        if (!empty($data['templates'])) {
            $pdf->AddPage();
            $html = '<h2 style="color: #333; margin-bottom: 20px;">Szablony badań</h2>';
            $html .= '<table cellpadding="6" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; font-size: 8px;">';
            $html .= '<thead>
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <th style="width: 8%; text-align: center; border: 1px solid #ddd;">ID</th>
                        <th style="width: 30%; text-align: left; border: 1px solid #ddd;">Nazwa</th>
                        <th style="width: 47%; text-align: left; border: 1px solid #ddd;">Opis</th>
                        <th style="width: 15%; text-align: center; border: 1px solid #ddd;">Status</th>
                    </tr>
                  </thead><tbody>';

            foreach ($data['templates'] as $template) {
                $description = substr(strip_tags($template->description ?: '-'), 0, 70);
                $name = substr($template->name, 0, 35);

                $html .= '<tr>';
                $html .= '<td style="width: 8%; text-align: center; border: 1px solid #ddd; padding: 4px;">' . $template->id . '</td>';
                $html .= '<td style="width: 30%; text-align: left; border: 1px solid #ddd; padding: 4px;">' . htmlspecialchars($name) . '</td>';
                $html .= '<td style="width: 47%; text-align: left; border: 1px solid #ddd; padding: 4px;">' . htmlspecialchars($description) . '</td>';
                $html .= '<td style="width: 15%; text-align: center; border: 1px solid #ddd; padding: 4px;">' . ($template->status ? '<span style="color: #28a745;">Aktywny</span>' : '<span style="color: #dc3545;">Nieaktywny</span>') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        }

        return $this->outputPDF($pdf, 'pelny-eksport-danych');
    }

    // ==================== PDF HELPER METHODS ====================

    private function createPDF($title) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Ustawienia dokumentu
        $pdf->SetCreator('MedArchive System');
        $pdf->SetAuthor('MedArchive');
        $pdf->SetTitle($title);
        $pdf->SetSubject('Eksport danych medycznych');

        // Wyłącz nagłówek i stopkę (powodują problemy z marginesami)
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Ustawienia strony - mniejsze marginesy dla większej przestrzeni
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Ustaw font - DejaVu Sans obsługuje polskie znaki
        $pdf->SetFont('dejavusans', '', 10);

        // Dodaj stronę
        $pdf->AddPage();

        return $pdf;
    }

    private function outputPDF($pdf, $filename) {
        $filename .= '-' . date('Y-m-d-H-i-s') . '.pdf';

        // Wyślij PDF do przeglądarki
        $pdf->Output($filename, 'D'); // 'D' = download, 'I' = inline view
        exit;
    }

    // ==================== EXCEL HELPER METHODS ====================

    private function addResultsToSheet($sheet, $results) {
        $headers = ['ID', 'Szablon badania', 'Data badania', 'Komentarz', 'Nieprawidłowe wartości'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, 1], $header);
            $col++;
        }

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($results as $result) {
            $sheet->setCellValue([1, $row], $result->id);
            $sheet->setCellValue([2, $row], $result->testTemplate->name);
            $sheet->setCellValue([3, $row], $result->test_date);
            $sheet->setCellValue([4, $row], $result->comment);
            $sheet->setCellValue([5, $row], $result->has_abnormal_values ? 'Tak' : 'Nie');
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function addTemplatesToSheet($sheet, $templates) {
        $headers = ['ID', 'Nazwa', 'Opis', 'Status', 'Liczba parametrów'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, 1], $header);
            $col++;
        }

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($templates as $template) {
            $sheet->setCellValue([1, $row], $template->id);
            $sheet->setCellValue([2, $row], $template->name);
            $sheet->setCellValue([3, $row], $template->description);
            $sheet->setCellValue([4, $row], $template->status ? 'Aktywny' : 'Nieaktywny');
            $sheet->setCellValue([5, $row], count($template->parameters));
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function addQueueToSheet($sheet, $queue) {
        $headers = ['ID', 'Szablon badania', 'Data planowana', 'Status', 'Komentarz'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, 1], $header);
            $col++;
        }

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($queue as $item) {
            $statusOptions = ['pending' => 'Oczekujące', 'completed' => 'Wykonane', 'cancelled' => 'Anulowane'];

            $sheet->setCellValue([1, $row], $item->id);
            $sheet->setCellValue([2, $row], $item->testTemplate->name);
            $sheet->setCellValue([3, $row], $item->scheduled_date);
            $sheet->setCellValue([4, $row], $statusOptions[$item->status] ?? $item->status);
            $sheet->setCellValue([5, $row], $item->comment);
            $row++;
        }

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function downloadExcel($spreadsheet, $filename) {
        $writer = new Xlsx($spreadsheet);
        $filename .= '-' . date('Y-m-d-H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Pobierz wszystkie unikalne parametry z wyników
     */
    private function getAllParametersFromResults($results) {
        $parameters = [];

        foreach ($results as $result) {
            foreach ($result->resultValues as $resultValue) {
                $param = $resultValue->parameter;
                if (!isset($parameters[$param->id])) {
                    $parameters[$param->id] = [
                        'id' => $param->id,
                        'name' => $param->name,
                        'unit' => $param->unit ?: ''
                    ];
                }
            }
        }

        return array_values($parameters);
    }

    /**
     * Pobierz wartości wyniku jako tablicę
     */
    private function getResultValuesArray($result) {
        $values = [];

        foreach ($result->resultValues as $resultValue) {
            $isNormal = true;
            if ($resultValue->norm) {
                $validationResult = $resultValue->norm->checkValue($resultValue->value);
                $isNormal = $validationResult['is_normal'];
            }

            $values[$resultValue->parameter->id] = [
                'value' => $resultValue->value,
                'is_normal' => $isNormal,
                'unit' => $resultValue->parameter->unit
            ];
        }

        return $values;
    }

    /**
     * Pobierz nazwę typu normy
     */
    private function getNormTypeName($type) {
        $types = [
            'range' => 'Zakres',
            'single_threshold' => 'Próg',
            'multiple_thresholds' => 'Wiele progów'
        ];

        return $types[$type] ?? $type;
    }

    /**
     * Pobierz tekst zakresu normy
     */
    private function getNormRangeText($norm) {
        switch ($norm->type) {
            case 'range':
                return $norm->min_value . ' - ' . $norm->max_value;

            case 'single_threshold':
                return ($norm->threshold_direction === 'above' ? '≤ ' : '≥ ') . $norm->threshold_value;

            case 'multiple_thresholds':
                if ($norm->thresholds_config) {
                    $thresholds = json_decode($norm->thresholds_config, true);
                    return 'Wiele progów (' . count($thresholds) . ')';
                }
                return 'Wiele progów';

            default:
                return 'Nieokreślony';
        }
    }

}

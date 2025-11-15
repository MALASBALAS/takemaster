<?php
header('Content-Type: text/html; charset=utf-8');
// export_pdf.php - Generador de PDF puro PHP
// Soporta: mPDF, TCPDF, wkhtmltopdf

require __DIR__ . '/../src/nav/bootstrap.php';
require __DIR__ . '/../src/nav/db_connection.php';
start_secure_session();

if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    die(json_encode(['error' => 'No autenticado']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido']));
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['trabajos'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Datos inválidos']));
}

// Crear HTML para PDF
$html = '<!DOCTYPE html>';
$html .= '<html>';
$html .= '<head>';
$html .= '<meta charset="UTF-8">';
$html .= '<style>';
$html .= 'body { font-family: Arial, sans-serif; margin: 20px; font-size: 11px; color: #333; }';
$html .= 'h2 { color: #0b69ff; border-bottom: 3px solid #0b69ff; padding-bottom: 10px; font-size: 18px; }';
$html .= 'p { margin: 5px 0; }';
$html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
$html .= 'thead { background-color: #0b69ff; }';
$html .= 'th { color: white; padding: 12px; text-align: left; border: 1px solid #0b69ff; font-weight: bold; }';
$html .= 'td { padding: 10px; border: 1px solid #ddd; }';
$html .= 'tbody tr:nth-child(even) { background-color: #f5f5f5; }';
$html .= '.total-section { margin-top: 20px; padding: 15px; background-color: #e8f4f8; border: 2px solid #0b69ff; }';
$html .= '.total-section h3 { margin-top: 0; color: #0b69ff; }';
$html .= '.total-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ccc; }';
$html .= '.total-label { font-weight: bold; }';
$html .= '.total-value { font-weight: bold; color: #0b69ff; }';
$html .= '</style>';
$html .= '</head>';
$html .= '<body>';

$html .= '<h2>Reporte de Consultas</h2>';
$html .= '<p><strong>Generado:</strong> ' . date('d/m/Y H:i:s') . '</p>';
$html .= '<p><strong>Usuario:</strong> ' . htmlspecialchars($_SESSION['username']) . '</p>';

$html .= '<table>';
$html .= '<thead><tr>';
$html .= '<th>Estudio</th>';
$html .= '<th>Tipo</th>';
$html .= '<th>Fecha</th>';
$html .= '<th style="text-align: center;">Takes</th>';
$html .= '<th style="text-align: center;">CGs</th>';
$html .= '<th style="text-align: right;">Total</th>';
$html .= '</tr></thead>';
$html .= '<tbody>';

foreach ($data['trabajos'] as $trabajo) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($trabajo['estudio'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($trabajo['tipo'] ?? '') . '</td>';
    $html .= '<td>' . htmlspecialchars($trabajo['fecha'] ?? '') . '</td>';
    $html .= '<td style="text-align: center;">' . htmlspecialchars($trabajo['takes'] ?? '0') . '</td>';
    $html .= '<td style="text-align: center;">' . htmlspecialchars($trabajo['cgs'] ?? '0') . '</td>';
    $html .= '<td style="text-align: right;">' . htmlspecialchars($trabajo['total'] ?? '0') . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

if (isset($data['totales'])) {
    $html .= '<div class="total-section">';
    $html .= '<h3>Totales</h3>';
    if (isset($data['totales']['total_takes'])) {
        $html .= '<div class="total-row"><span class="total-label">Takes:</span><span class="total-value">' . htmlspecialchars($data['totales']['total_takes']) . '</span></div>';
    }
    if (isset($data['totales']['total_cgs'])) {
        $html .= '<div class="total-row"><span class="total-label">CGs:</span><span class="total-value">' . htmlspecialchars($data['totales']['total_cgs']) . '</span></div>';
    }
    if (isset($data['totales']['total_ingresos'])) {
        $html .= '<div class="total-row"><span class="total-label">Ingresos:</span><span class="total-value">' . htmlspecialchars($data['totales']['total_ingresos']) . '</span></div>';
    }
    $html .= '</div>';
}

$html .= '</body></html>';

// Generar PDF binario usando clase mínima mejorada
class SimplePDF {
    private $objects = [];
    private $pages = [];
    private $pageCount = 0;
    private $annotations = [];

    public function addPage() {
        $this->pageCount++;
        $this->pages[$this->pageCount] = '';
        $this->annotations[$this->pageCount] = [];
    }

    public function addLink($x, $y, $width, $height, $url) {
        // Guardar anotación de hipervínculo para la página actual
        $this->annotations[$this->pageCount][] = [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'url' => $url
        ];
    }

    public function drawText($x, $y, $text, $fontSize = 12, $bold = false) {
        // PDF coordinates: Y=0 at bottom, Y=842 at top
        // We use $y as given (0-842), no inversion needed
        $font = $bold ? '/F2' : '/F1';
        $this->pages[$this->pageCount] .= "BT\n";
        $this->pages[$this->pageCount] .= "$font $fontSize Tf\n";
        $this->pages[$this->pageCount] .= "$x $y Td\n";
        $this->pages[$this->pageCount] .= "(" . $this->escape($text) . ") Tj\n";
        $this->pages[$this->pageCount] .= "ET\n";
    }

    public function drawLine($x1, $y1, $x2, $y2, $width = 0.5) {
        // Nota: No invertir Y aquí, los valores ya vienen ajustados desde el contexto
        $this->pages[$this->pageCount] .= "$width w\n";
        $this->pages[$this->pageCount] .= "$x1 $y1 m\n";
        $this->pages[$this->pageCount] .= "$x2 $y2 l\n";
        $this->pages[$this->pageCount] .= "S\n";
    }

    public function drawRect($x, $y, $w, $h, $fill = false, $stroke = true) {
        $op = '';
        if ($fill) $op = 'f';
        if ($stroke) $op .= 'S';
        if (!$op) $op = 'n';
        
        $this->pages[$this->pageCount] .= "$x $y $w $h re $op\n";
    }

    public function setDrawColor($r, $g, $b) {
        $this->pages[$this->pageCount] .= ($r/255) . " " . ($g/255) . " " . ($b/255) . " RG\n";
    }

    public function setFillColor($r, $g, $b) {
        $this->pages[$this->pageCount] .= ($r/255) . " " . ($g/255) . " " . ($b/255) . " rg\n";
    }

    public function setTextColor($r, $g, $b) {
        $this->pages[$this->pageCount] .= ($r/255) . " " . ($g/255) . " " . ($b/255) . " rg\n";
    }

    private function escape($str) {
        // Reemplazar € por su representación correcta en PDF
        $str = str_replace('€', 'EUR', $str);
        return addslashes($str);
    }

    public function output() {
        $pdf = "%PDF-1.4\n%âãÏó\n";
        
        $objOffsets = [];
        $objectCount = 0;
        $annotationObjects = [];
        
        // Catalog
        $objOffsets[] = strlen($pdf);
        $pdf .= "1 0 obj\n<</Type/Catalog/Pages 2 0 R>>\nendobj\n";
        
        // Pages
        $objOffsets[] = strlen($pdf);
        $kids = "";
        for ($i = 0; $i < $this->pageCount; $i++) {
            $kids .= ($i + 3) . " 0 R ";
        }
        $pdf .= "2 0 obj\n<</Type/Pages/Kids[" . trim($kids) . "]/Count " . $this->pageCount . ">>\nendobj\n";
        
        // Page objects and streams - con anotaciones
        $currentObj = 3;
        foreach ($this->pages as $pageNum => $content) {
            $streamObj = $currentObj + 1;
            
            // Construir referencias a anotaciones si existen
            $annotRef = "";
            if (isset($this->annotations[$pageNum]) && !empty($this->annotations[$pageNum])) {
                $annotObj = $streamObj + 1;
                $annotRef = "/Annots[$annotObj 0 R]";
                $annotationObjects[$pageNum] = $annotObj;
            }
            
            $objOffsets[] = strlen($pdf);
            $pdf .= $currentObj . " 0 obj\n<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents " . $streamObj . " 0 R" . $annotRef . "/Resources<</Font<</F1 4 0 R/F2 5 0 R>>>>>>\nendobj\n";
            
            $objOffsets[] = strlen($pdf);
            $streamLength = strlen($content);
            $pdf .= $streamObj . " 0 obj\n<</Length $streamLength>>\nstream\n" . $content . "\nendstream\nendobj\n";
            
            // Crear objetos de anotación si existen
            if (isset($annotationObjects[$pageNum])) {
                $annotObj = $annotationObjects[$pageNum];
                $objOffsets[] = strlen($pdf);
                
                $annotationArray = "[";
                foreach ($this->annotations[$pageNum] as $idx => $annot) {
                    $nextObj = $annotObj + $idx + 1;
                    $annotationArray .= "$nextObj 0 R ";
                }
                $annotationArray = trim($annotationArray) . "]";
                
                $pdf .= $annotObj . " 0 obj\n$annotationArray\nendobj\n";
                
                // Crear cada anotación individual
                foreach ($this->annotations[$pageNum] as $idx => $annot) {
                    $linkObj = $annotObj + $idx + 1;
                    $objOffsets[] = strlen($pdf);
                    
                    $rect = "[" . $annot['x'] . " " . ($annot['y'] - $annot['height']) . " " . ($annot['x'] + $annot['width']) . " " . $annot['y'] . "]";
                    $url = $annot['url'];
                    if (strpos($url, 'http') === false) {
                        $url = 'https://' . $url;
                    }
                    
                    $pdf .= $linkObj . " 0 obj\n<</Type/Annot/Subtype/Link/Rect$rect/Border[0 0 0]/A<</S/URI/URI(" . $url . ")>>>>\nendobj\n";
                }
                
                $currentObj = $linkObj + 1;
            } else {
                $currentObj += 2;
            }
        }
        
        // Font F1
        $objOffsets[] = strlen($pdf);
        $pdf .= "4 0 obj\n<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>\nendobj\n";
        
        // Font F2
        $objOffsets[] = strlen($pdf);
        $pdf .= "5 0 obj\n<</Type/Font/Subtype/Type1/BaseFont/Helvetica-Bold>>\nendobj\n";
        
        // xref
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objOffsets) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        
        foreach ($objOffsets as $offset) {
            $pdf .= str_pad($offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        
        $pdf .= "trailer\n<</Size " . (count($objOffsets) + 1) . "/Root 1 0 R>>\nstartxref\n$xrefOffset\n%%EOF";
        
        return $pdf;
    }
}

// Crear PDF con datos
$pdf = new SimplePDF();
$pdf->addPage();

// Encabezado compacto con estilo moderno - PEGADO A LA PARTE SUPERIOR
$pdf->setFillColor(11, 105, 255);
$pdf->drawRect(0, 782, 595, 60, true, false);

$pdf->setTextColor(255, 255, 255);
$pdf->drawText(20, 825, 'REPORTE DE CONSULTAS', 20, true);

$pdf->setTextColor(200, 220, 255);
$pdf->drawText(480, 828, 'TakeMaster', 10);

// Añadir hipervínculo a TakeMaster (x=480, y=828, ancho aprox=50, alto=12)
$pdf->addLink(480, 828, 50, 12, 'testtakemaster.balbe.xyz');

// Información de fecha y usuario en línea compacta
$pdf->setTextColor(220, 220, 220);
$pdf->drawText(20, 794, 'Generado: ' . date('d/m/Y H:i:s') . ' | Usuario: ' . htmlspecialchars($_SESSION['username']), 9);

// Tabla - Encabezado con mejor formato
$y = 765;
$headerHeight = 18;
// Nuevas posiciones: Estudio más ancho, Total a la derecha
$colPositions = [15, 140, 215, 295, 355, 570];

// Fondo encabezado con degradado simulado
$pdf->setFillColor(20, 120, 255);
$pdf->drawRect(15, $y - 9, 560, $headerHeight, true, false);

// Borde encabezado
$pdf->setDrawColor(11, 105, 255);
$pdf->drawLine(15, $y - 9, 575, $y - 9, 1.5); // Top
$pdf->drawLine(15, $y + 9, 575, $y + 9, 1.5); // Bottom
$pdf->drawLine(15, $y - 9, 15, $y + 9, 1); // Left
$pdf->drawLine(575, $y - 9, 575, $y + 9, 1); // Right

// Texto encabezado
$pdf->setTextColor(255, 255, 255);
$pdf->drawText(20, $y, 'Estudio', 11, true);
$pdf->drawText(145, $y, 'Tipo', 11, true);
$pdf->drawText(220, $y, 'Fecha', 11, true);
$pdf->drawText(300, $y, 'Takes', 11, true);
$pdf->drawText(360, $y, 'CGs', 11, true);
$pdf->drawText(520, $y, 'Total', 11, true);

// Columnas divisorias en encabezado
$pdf->setDrawColor(150, 180, 255);
for ($i = 1; $i < count($colPositions); $i++) {
    $pdf->drawLine($colPositions[$i], $y - 9, $colPositions[$i], $y + 9, 0.5);
}

// Datos de la tabla
$pdf->setTextColor(0, 0, 0);
$y = 738;
$minY = 50;
$rowHeight = 18;

// Variables para calcular totales
$total_takes = 0;
$total_cgs = 0;
$total_ingresos = 0;
$rowCount = 0;

foreach ($data['trabajos'] as $trabajo) {
    if ($y < $minY) {
        $pdf->addPage();
        
        // Repetir encabezado en página nueva
        $y = 788;
        $pdf->setFillColor(20, 120, 255);
        $pdf->drawRect(15, $y - 5.5, 560, $headerHeight, true, false);
        
        $pdf->setDrawColor(11, 105, 255);
        $pdf->drawLine(15, $y - 5.5, 575, $y - 5.5, 1.5);
        $pdf->drawLine(15, $y + 5.5, 575, $y + 5.5, 1.5);
        $pdf->drawLine(15, $y - 5.5, 15, $y + 5.5, 1);
        $pdf->drawLine(575, $y - 5.5, 575, $y + 5.5, 1);
        
        $pdf->setTextColor(255, 255, 255);
        $pdf->drawText(20, $y, 'Estudio', 11, true);
        $pdf->drawText(145, $y, 'Tipo', 11, true);
        $pdf->drawText(220, $y, 'Fecha', 11, true);
        $pdf->drawText(300, $y, 'Takes', 11, true);
        $pdf->drawText(360, $y, 'CGs', 11, true);
        $pdf->drawText(520, $y, 'Total', 11, true);
        
        $y = 770;
        $rowCount = 0;
    }
    
    // Fondo alterno para legibilidad
    $rowCount++;
    if ($rowCount % 2 == 0) {
        $pdf->setFillColor(245, 247, 250);
        $pdf->drawRect(15, $y - 5.5, 560, $rowHeight, true, false);
    }
    
    // Línea divisoria
    $pdf->setDrawColor(230, 230, 230);
    $pdf->drawLine(15, $y - 5.5, 575, $y - 5.5, 0.3);
    
    // Líneas divisorias verticales
    for ($i = 1; $i < count($colPositions); $i++) {
        $pdf->drawLine($colPositions[$i], $y - 9, $colPositions[$i], $y + 9, 0.1);
    }
    
    $pdf->setTextColor(0, 0, 0);
    $pdf->drawText(20, $y, substr($trabajo['estudio'] ?? '', 0, 25), 10);
    $pdf->drawText(145, $y, substr($trabajo['tipo'] ?? '', 0, 13), 10);
    $pdf->drawText(220, $y, $trabajo['fecha'] ?? '', 10);
    $pdf->drawText(300, $y, $trabajo['takes'] ?? '0', 10);
    $pdf->drawText(360, $y, $trabajo['cgs'] ?? '0', 10);
    
    // Total alineado a la derecha con símbolo €
    $totalText = ($trabajo['total'] ?? '0');
    // Limpiar símbolos € existentes
    $totalText = str_replace('€', '', trim($totalText));
    // Agregar EUR si no está ya presente
    if (stripos($totalText, 'eur') === false) {
        $totalText = trim($totalText) . ' EUR';
    }
    $pdf->drawText(500, $y, $totalText, 10);
    
    // Acumular totales
    $total_takes += (float)($trabajo['takes'] ?? 0);
    $total_cgs += (float)($trabajo['cgs'] ?? 0);
    // Limpiar símbolo € del total antes de sumar
    $cleanTotal = str_replace('€', '', $trabajo['total'] ?? '0');
    $cleanTotal = str_replace('EUR', '', $cleanTotal);
    $cleanTotal = floatval(trim($cleanTotal));
    $total_ingresos += $cleanTotal;
    
    $y -= $rowHeight;
}

// Línea final de tabla
$pdf->setDrawColor(11, 105, 255);
$pdf->drawLine(15, $y - 3, 575, $y - 3, 1.5);
$pdf->drawLine(15, $y - 3, 15, $y, 1);
$pdf->drawLine(575, $y - 3, 575, $y, 1);

// Totales con mejor diseño
if ($total_ingresos > 0 || $total_takes > 0 || $total_cgs > 0) {
    $y -= 20;
    
    // Fondo totales
    $pdf->setFillColor(11, 105, 255);
    $pdf->drawRect(15, $y - 25, 560, 25, true, false);
    
    // Borde totales
    $pdf->setDrawColor(11, 105, 255);
    $pdf->drawLine(15, $y - 25, 575, $y - 25, 1.5);
    $pdf->drawLine(15, $y, 575, $y, 1.5);
    $pdf->drawLine(15, $y - 25, 15, $y, 1);
    $pdf->drawLine(575, $y - 25, 575, $y, 1);
    
    $pdf->setTextColor(255, 255, 255);
    $pdf->drawText(20, $y - 16, 'TOTALES', 11, true);
    
    // Mostrar totales en una línea
    $takes_text = number_format($total_takes, 0);
    $cgs_text = number_format($total_cgs, 0);
    $ingresos_text = number_format($total_ingresos, 2, ',', '.');
    
    $totalesInfo = "Takes: $takes_text | CGs: $cgs_text | $ingresos_text EUR";
    $pdf->drawText(150, $y - 16, $totalesInfo, 10, true);
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="consulta_' . date('Y-m-d_H-i-s') . '.pdf"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

echo $pdf->output();
exit;

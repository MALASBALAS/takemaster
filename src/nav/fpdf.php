<?php
// FPDF - Generador de PDF puro PHP
// Versión simplificada para generar PDFs binarios reales

class FPDF {
    protected $currentPage;
    protected $pages = [];
    protected $state = 0;
    protected $x, $y;
    protected $w, $h;
    protected $wPt, $hPt;
    protected $fontFamily = '';
    protected $fontStyle = '';
    protected $fontSize = 0;
    protected $fontSizePt = 0;
    protected $textColor = '0 0 0';
    protected $fillColor = '1 1 1';
    protected $lineColor = '0 0 0';
    protected $lineWidth = 0.567;
    protected $fontKey = '';
    protected $core_fonts = ['courier', 'helvetica', 'times', 'symbol', 'zapfdingbats'];
    protected $fonts = [];
    protected $images = [];
    protected $links = [];
    protected $orientation = 'P';
    protected $format = 'A4';
    protected $k = 2.834645669;
    protected $margins = ['l' => 10, 'r' => 10, 't' => 10, 'b' => 10];
    protected $pageBreakTrigger;
    protected $buffer = '';
    protected $aliasNbPages = '{nb}';
    protected $Objects = [];
    protected $objectNumber = 0;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        $this->orientation = strtoupper($orientation);
        $this->format = strtoupper($format);
        
        $formats = [
            'A4' => [210, 297],
            'LETTER' => [216, 279],
            'A3' => [297, 420],
            'A5' => [148, 210]
        ];
        
        if (isset($formats[$this->format])) {
            list($this->w, $this->h) = $formats[$this->format];
        }

        if ($this->orientation == 'L') {
            list($this->w, $this->h) = [$this->h, $this->w];
        }

        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;
        $this->pageBreakTrigger = $this->h - $this->margins['b'];
        $this->SetAutoPageBreak(true, $this->margins['b']);
    }

    public function SetAutoPageBreak($auto, $margin = 0) {
        $this->pageBreakTrigger = $this->h - $margin;
    }

    public function AddPage() {
        if ($this->currentPage > 0) {
            $this->pages[$this->currentPage] = $this->buffer;
        }
        $this->currentPage++;
        $this->buffer = '';
        $this->x = $this->margins['l'];
        $this->y = $this->margins['t'];
        $this->SetFont('Arial', '', 12);
    }

    public function SetFont($family, $style = '', $size = 0) {
        $family = strtolower($family);
        if ($family == 'arial') $family = 'helvetica';
        
        if ($size == 0) $size = $this->fontSizePt;
        
        $this->fontSize = $size;
        $this->fontSizePt = $size * $this->k;
        $this->fontStyle = $style;
        $this->fontFamily = $family;
        $this->SetTextColor(0);
    }

    public function SetTextColor($r, $g = null, $b = null) {
        if (is_null($g)) {
            $this->textColor = ($r / 255) . ' ' . ($r / 255) . ' ' . ($r / 255);
        } else {
            $this->textColor = ($r / 255) . ' ' . ($g / 255) . ' ' . ($b / 255);
        }
    }

    public function SetFillColor($r, $g = null, $b = null) {
        if (is_null($g)) {
            $this->fillColor = ($r / 255) . ' ' . ($r / 255) . ' ' . ($r / 255);
        } else {
            $this->fillColor = ($r / 255) . ' ' . ($g / 255) . ' ' . ($b / 255);
        }
    }

    public function SetLineColor($r, $g = null, $b = null) {
        if (is_null($g)) {
            $this->lineColor = ($r / 255) . ' ' . ($r / 255) . ' ' . ($r / 255);
        } else {
            $this->lineColor = ($r / 255) . ' ' . ($g / 255) . ' ' . ($b / 255);
        }
    }

    public function SetLineWidth($width) {
        $this->lineWidth = $width;
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
        $k = $this->k;
        if ($this->y + $h > $this->pageBreakTrigger) {
            $this->AddPage();
        }

        $s = '';
        
        if ($fill || $border == 1) {
            if ($fill) {
                $op = ($border == 1) ? 'B' : 'f';
                $s .= sprintf('%.2F %.2F %.2F %.2F re %s ', 
                    $this->x * $k, 
                    ($this->h - $this->y) * $k, 
                    $w * $k, 
                    -$h * $k, 
                    $op);
            }
        }

        if ($txt != '') {
            $s .= 'BT ';
            $s .= $this->textColor . ' rg ';
            $s .= sprintf('/F1 %.2F Tf ', $this->fontSizePt);
            $s .= sprintf('%.2F %.2F Td ', $this->x * $k, ($this->h - $this->y) * $k);
            $s .= '(' . $this->_escape($txt) . ') Tj ET ';
        }

        if ($border) {
            $s .= $this->lineColor . ' RG ';
            $s .= sprintf('%.2F w ', $this->lineWidth * $k);
            $s .= sprintf('%.2F %.2F %.2F %.2F re S ', 
                $this->x * $k, 
                ($this->h - $this->y) * $k, 
                $w * $k, 
                -$h * $k);
        }

        $this->buffer .= $s;

        if ($ln > 0) {
            $this->x = $this->margins['l'];
            if ($ln > 1) {
                $this->y += $h;
            }
        } else {
            $this->x += $w;
        }
    }

    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
        $lines = explode("\n", $txt);
        foreach ($lines as $line) {
            $this->Cell($w, $h, substr($line, 0, 100), $border, 1, $align, $fill);
        }
    }

    public function Ln($h = null) {
        $this->x = $this->margins['l'];
        if ($h === null) {
            $h = $this->fontSize;
        }
        $this->y += $h;
    }

    public function GetX() {
        return $this->x;
    }

    public function SetX($x) {
        $this->x = $x;
    }

    public function GetY() {
        return $this->y;
    }

    public function SetY($y) {
        $this->y = $y;
    }

    public function SetXY($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function Output($dest = '', $name = '') {
        $this->pages[$this->currentPage] = $this->buffer;
        
        $pdf = $this->_generate();
        
        if ($dest == 'D') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            echo $pdf;
        } else if ($dest == 'S') {
            return $pdf;
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $name . '"');
            echo $pdf;
        }
    }

    protected function _generate() {
        $pdf = "%PDF-1.3\n";
        
        $objects = [];
        $offsets = [];
        $objNum = 1;
        
        foreach ($this->pages as $page) {
            $objects[$objNum] = "1 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->wPt} {$this->hPt}] /Contents " . ($objNum + 1) . " 0 R /Resources << /Font << /F1 3 0 R >> >> >>\nendobj\n";
            $objNum++;
            
            $content = $page;
            $objects[$objNum] = ($objNum) . " 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj\n";
            $objNum++;
        }
        
        $objects[3] = "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        $pages_kids = '';
        $pageNum = 1;
        for ($i = 1; $i < $objNum; $i += 2) {
            $pages_kids .= "$i 0 R ";
        }
        
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [$pages_kids] /Count " . (($objNum - 3) / 2) . " >>\nendobj\n";
        
        $objects[4] = "4 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        $xref_offset = strlen($pdf);
        
        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $obj;
        }
        
        $xref = "xref\n0 " . (count($objects) + 1) . "\n";
        $xref .= "0000000000 65535 f \n";
        
        foreach ($offsets as $num => $offset) {
            $xref .= str_pad($offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        
        $pdf .= $xref;
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 4 0 R >>\n";
        $pdf .= "startxref\n" . $xref_offset . "\n%%EOF";
        
        return $pdf;
    }

    protected function _escape($s) {
        return str_replace(['(', ')', '\\'], ['\(', '\)', '\\\\'], $s);
    }

    public function Header() {}
    public function Footer() {}
}
?>

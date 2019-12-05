[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg?style=flat-square)](https://php.net/)
# FPDIWithLinks
FPDI Driver supports links annotations

### Installation
`composer require mazanax/fpdi_with_links`

### Example (from [Setasign/FPDI](https://github.com/Setasign/FPDI))
```php
<?php
use MZ\FPDI\FPDIWithLinks;

// setup the autoload function
require __DIR__ . '/vendor/autoload.php';

// initiate FPDI
$pdf = new FPDIWithLinks();
// add a page
$pdf->AddPage();
// set the source file
$pdf->setSourceFile('Fantastic-Speaker.pdf');
// import page 1
$tplId = $pdf->importPage(1);
// use the imported page and place it at point 10,10 with a width of 100 mm
$pdf->useTemplate($tplId, 10, 10, 100);

$pdf->Output();
```

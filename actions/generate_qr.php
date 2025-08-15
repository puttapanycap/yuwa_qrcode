<?php

require '../vendor/autoload.php';

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;

// Get data from the request
$data = $_POST['data'] ?? '1234567890';
$logo = null;
$label = null;

try {

    $writer = new PngWriter();

    // Create QR code
    $qrCode = new QrCode(
        data: $data,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::Low,
        size: 300,
        margin: 10,
        roundBlockSizeMode: RoundBlockSizeMode::Margin,
        foregroundColor: new Color(0, 0, 0),
        backgroundColor: new Color(255, 255, 255)
    );

    // $builder = new Builder()
    //     ->writer(new PngWriter())
    //     ->data($data)
    //     ->encoding(new Encoding('UTF-8'))
    //     ->errorCorrectionLevel(ErrorCorrectionLevel::High)
    //     ->size(300)
    //     ->margin(10)
    //     ->roundBlockSizeMode(RoundBlockSizeMode::Margin);


    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // $logo = $_FILES['logo']['tmp_name'];
        $logo = new Logo(
            path: $_FILES['logo']['tmp_name'],
            resizeToWidth: 55,
            punchoutBackground: true
        );
    }

    if (isset($_POST['label'])) {
        $label = $_POST['label'];
    }

    $result = $writer->write($qrCode, $logo, $label);

    // Output the QR code image
    header('Content-Type: ' . $result->getMimeType());
    echo $result->getString();
} catch (Exception $e) {
    // Handle errors
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error generating QR code: ' . $e->getMessage();
}

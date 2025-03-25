<?php

require '../configs/all.php';
// การเชื่อมต่อฐานข้อมูล

$qrdomain = _URL_."?r=";

function generateShortCode($length = 6) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function shortenURL($longUrl, $pdo) {
  	global $qrdomain;
    // ตรวจสอบว่า URL มีอยู่แล้วหรือไม่
    $stmt = $pdo->prepare("SELECT short_code FROM urls WHERE long_url = :long_url");
    $stmt->execute(['long_url' => $longUrl]);
    $result = $stmt->fetch();

    if ($result) {
        return $qrdomain. $result['short_code']; // คืน short code เดิมถ้าเคยสร้างแล้ว
    }

    // สร้าง short code ใหม่
    $shortCode = generateShortCode();

    // บันทึกลงฐานข้อมูล
    $stmt = $pdo->prepare("INSERT INTO urls (long_url, short_code) VALUES (:long_url, :short_code)");
    $stmt->execute(['long_url' => $longUrl, 'short_code' => $shortCode]);

    return $qrdomain. $shortCode;
}

// รับค่าจาก JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$longUrl = $data['long_url'];

// ย่อ URL และส่งคืนค่า
$shortUrl = shortenURL($longUrl, $pdo);
echo json_encode(['short_url' => $shortUrl]);
?>

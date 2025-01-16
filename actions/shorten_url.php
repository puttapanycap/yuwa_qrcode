<?php

require '../configs/all.php';
// การเชื่อมต่อฐานข้อมูล

$qrdomain = _URL_."?r=";

function generateShortCode($length = 6) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function shortenURL($longUrl, $pdo) {
  	global $qrdomain;
    global $conn;
    // ตรวจสอบว่า URL มีอยู่แล้วหรือไม่

    $sql = "SELECT short_code FROM urls WHERE long_url = '$longUrl' ";
    $query = $conn->query($sql);

    if ($query) {
        $result = $query->fetch_assoc();
        return $qrdomain. $result['short_code']; // คืน short code เดิมถ้าเคยสร้างแล้ว
    }

    // สร้าง short code ใหม่
    $shortCode = generateShortCode();

    // บันทึกลงฐานข้อมูล
    $ins_sql = $pdo->prepare("INSERT INTO urls (long_url, short_code) VALUES (:long_url, :short_code)");
    $stmt->execute($ins_sql);

    return $qrdomain. $shortCode;
}

// รับค่าจาก JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$longUrl = $data['long_url'];

// ย่อ URL และส่งคืนค่า
$shortUrl = shortenURL($longUrl, $pdo);
echo json_encode(['short_url' => $shortUrl]);
?>

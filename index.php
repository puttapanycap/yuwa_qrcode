<?php

require './configs/all.php';

function isTextOrURL($input)
{
    // เช็คว่าเป็น URL หรือไม่
    if (filter_var($input, FILTER_VALIDATE_URL)) {
        return "URL";
    }
    // เช็คว่าเป็นข้อความธรรมดา (ไม่มีฟิลเตอร์เช็ค specific text)
    else {
        return "Text";
    }
}

if (isset($_GET['r'])) {
    // รับค่า short_code จาก URL (เช่น yourdomain.com/?r=abc123)
    $shortCode = $_GET['r'];

    // ค้นหา long_url ที่ตรงกับ short_code ในฐานข้อมูล
    $sql = "SELECT long_url FROM urls WHERE short_code = '$shortCode' ";
    $query = $conn->query($sql);

    if ($query) {
        $row = $query->fetch_assoc();
        if (isTextOrURL($row['long_url']) == "URL") {
            // ถ้าเจอ URL ให้ทำการ Redirect
            $longUrl = $row['long_url'];
            header("Location: " . $longUrl);
            exit();
        } else {
            echo $row['long_url'];
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator with JPEF, PNG AND SVG Download</title>
    <!-- Bootstrap -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <link href="../assets/css/fonts.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="../assets/css/fontawesome.css" rel="stylesheet">

    <link rel="icon" type="image/svg+xml" href="./assets/images/favicon.svg">
    <link rel="icon" type="image/png" href="./assets/images/favicon.png">

    <style>
        *:not(i) {
            font-family: "LINESeedSansTH", Arial, Helvetica, sans-serif;
        }

        #logoPreview {
            border-radius: .5rem;
            width: 100px;
            height: 100px;
            display: none;
            object-fit: contain;
            background-color: var(--bs-gray-400);
        }

        .logo-preview {
            border-radius: .5rem;
            background-color: var(--bs-gray-200);
            padding: 10px;
            display: flex;
            width: 100%;
            justify-content: center;
            align-items: center;
            min-height: 100px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">หน้าหลัก</a></li>
                <li class="breadcrumb-item active" aria-current="page">โปรแกรมสร้าง QR Code</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-center align-items-center flex-column gap-2">
            <h2 class="text-center">โปรแกรมสร้าง QR Code</h2>

            <div class="w-100 row g-4">

                <div class="col-xl-6 col-12">
                    <div class="card rounded-3">
                        <div class="card-body d-flex flex-column w-100">

                            <label class="form-label required" for="qrText">กรอก ข้อความ หรือ Link</label>
                            <textarea id="qrText" class="form-control mb-3" placeholder="Enter Link or Text for QR code" rows="3"></textarea>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="useShortUrl">
                                <label class="form-check-label" for="useShortUrl">ย่อ Link หรือ สร้าง Link สำหรับข้อความ</label>
                                <div class="form-text text-danger" id="basic-addon4">* ต้องเก็บ Link หรือข้อความ ในฐานข้อมูลเพื่อสร้างลิ้งค์</div>
                            </div>

                            <!-- ฟิลด์ Short URL พร้อมปุ่มคัดลอก -->
                            <div id="short-url-group" class="d-none input-group mb-3">
                                <span class="input-group-text"><i class="fa-solid fa-paperclip"></i></span>
                                <input type="text" id="shortUrl" class="form-control" readonly placeholder="Short Link">
                                <button class="btn btn-outline-secondary" type="button" id="copyShortUrl">คัดลอก</button>
                            </div>

                            <!-- Checkbox สำหรับใส่โลโก้ -->
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="useLogo">
                                <label class="form-check-label" for="useLogo">ใส่โลโก้ใน QR Code</label>
                            </div>

                            <!-- ฟิลด์อัพโหลดโลโก้ -->
                            <div id="logoUploadField" class="mb-3 d-none">
                                <label for="logoFile" class="form-label">อัพโหลดโลโก้</label>
                                <input type="file" id="logoFile" class="form-control mb-3" accept="image/*">
                                <div class="logo-preview">
                                    <img id="logoPreview" alt="Logo Preview">
                                </div>
                            </div>

                            <button id="generate" class="btn btn-primary">สร้าง QR Code</button>

                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-12">

                    <div class="card rounded-3">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h3>ผลลัพธ์</h3>
                            <div class="mt-3" style="max-width: 300px;" id="qrcode"></div>
                            <canvas id="qrCanvas" class="d-none"></canvas>

                            <div id="download-buttons" class="d-none w-100 border border-1 border-dashed border-success rounded-3 p-3">
                                <p class="text-center">Downloads</p>
                                <div class="d-flex flex-row justify-content-center align-items-center gap-3">
                                    <button id="download-jpeg" class="btn btn-success">JPEG</button>
                                    <button id="download-png" class="btn btn-success">PNG</button>
                                    <button id="download-svg" class="btn btn-warning">SVG</button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- QRious.js CDN -->
    <script src="./js/qrious.min.js"></script>

    <script>
        var qr;

        // แสดง/ซ่อนฟิลด์อัพโหลดโลโก้
        document.getElementById('useLogo').addEventListener('change', function() {
            var logoUploadField = document.getElementById('logoUploadField');
            if (this.checked) {
                logoUploadField.classList.remove('d-none');
            } else {
                logoUploadField.classList.add('d-none');
                document.getElementById('logoPreview').style.display = 'none';
                document.getElementById('logoFile').value = ''; // ล้างข้อมูลไฟล์ที่เลือก
            }
        });

        // พรีวิวโลโก้หลังจากที่เลือกไฟล์
        document.getElementById('logoFile').addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var logoPreview = document.getElementById('logoPreview');
                    logoPreview.src = e.target.result;
                    logoPreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // ฟังก์ชันสร้าง Short URL
        function shortenURL(longUrl) {
            return new Promise((resolve, reject) => {
                fetch('./actions/shorten_url.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            long_url: longUrl
                        })
                    })
                    .then(response => response.json())
                    .then(data => resolve(data.short_url))
                    .catch(err => reject(err));
            });
        }

        // ตรวจสอบการใช้ Short URL
        document.getElementById('useShortUrl').addEventListener('change', function() {
            var shortUrlGroup = document.getElementById('short-url-group');
            if (this.checked) {
                shortUrlGroup.classList.remove("d-none");
            } else {
                shortUrlGroup.classList.add("d-none");
                document.getElementById('shortUrl').value = '';
            }
        });

        document.getElementById('generate').addEventListener('click', async function() {
            const qrText = document.getElementById('qrText').value.trim(); // ตัดช่องว่างทั้งก่อนและหลัง
            const useShortUrl = document.getElementById('useShortUrl').checked;
            const shortUrlField = document.getElementById('shortUrl');
            let finalUrl = qrText;

            // ตรวจสอบว่ามีการกรอกข้อความหรือ URL หรือไม่
            if (!qrText) {
                alert('กรุณากรอกข้อความหรือ URL เพื่อสร้าง QR Code');
                return; // หยุดการทำงานถ้าไม่มีข้อความ
            }

            // ตรวจสอบว่ามีการใช้โลโก้และเลือกไฟล์โลโก้หรือไม่
            const useLogo = document.getElementById('useLogo').checked;
            const logoFile = document.getElementById('logoFile').files[0];
            if (useLogo && !logoFile) {
                alert('กรุณาอัพโหลดไฟล์โลโก้');
                return;
            }

            // รีเซ็ตสถานะก่อนสร้าง QR Code ใหม่ (ไม่รีเซ็ตโลโก้)
            resetState(false);

            // หากเลือกใช้ Short URL
            if (useShortUrl) {
                try {
                    finalUrl = await shortenURL(qrText);
                    shortUrlField.value = finalUrl;
                } catch (error) {
                    alert("Error generating short URL");
                    return;
                }
            }

            const canvas = document.getElementById('qrCanvas');
            const ctx = canvas.getContext('2d');

            // เคลียร์แคนวาสก่อนเริ่มวาด QR Code และโลโก้ใหม่
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // สร้าง QR Code บนแคนวาส
            if (!qr) {
                qr = new QRious({
                    element: canvas,
                    value: finalUrl,
                    size: 256
                });
            } else {
                qr.set({
                    value: finalUrl,
                });
            }

            // วาด QR Code ลงบนแคนวาส
            ctx.drawImage(qr.canvas, 0, 0);

            // หากใช้โลโก้ ให้โหลดโลโก้แล้ววาดทับ QR Code
            if (useLogo && logoFile) {
                const img = new Image();
                img.onload = function() {
                    // กำหนดขนาดโลโก้ให้ไม่เกิน 25% ของขนาด QR Code
                    const logoMaxSize = canvas.width * 0.18;
                    const logoSize = logoMaxSize; // กำหนดเป็นสี่เหลี่ยมจัตุรัสเสมอ

                    const logoX = (canvas.width - logoSize) / 2;
                    const logoY = (canvas.height - logoSize) / 2;

                    // วาดพื้นหลังสีขาวแบบสี่เหลี่ยมจัตุรัสขอบมน
                    const cornerRadius = 10;
                    ctx.fillStyle = 'white';
                    ctx.beginPath();
                    ctx.moveTo(logoX + cornerRadius, logoY);
                    ctx.arcTo(logoX + logoSize, logoY, logoX + logoSize, logoY + logoSize, cornerRadius);
                    ctx.arcTo(logoX + logoSize, logoY + logoSize, logoX, logoY + logoSize, cornerRadius);
                    ctx.arcTo(logoX, logoY + logoSize, logoX, logoY, cornerRadius);
                    ctx.arcTo(logoX, logoY, logoX + logoSize, logoY, cornerRadius);
                    ctx.closePath();
                    ctx.fill();

                    // วางโลโก้ให้อยู่กึ่งกลางบนพื้นหลังสีขาว
                    const logoAspectRatio = img.width / img.height;
                    let logoWidth, logoHeight;
                    if (logoAspectRatio > 1) {
                        // โลโก้เป็นแนวนอน
                        logoWidth = logoSize;
                        logoHeight = logoSize / logoAspectRatio;
                    } else {
                        // โลโก้เป็นแนวตั้งหรือสี่เหลี่ยม
                        logoWidth = logoSize * logoAspectRatio;
                        logoHeight = logoSize;
                    }

                    const centeredLogoX = (canvas.width - logoWidth) / 2;
                    const centeredLogoY = (canvas.height - logoHeight) / 2;

                    // วางโลโก้บนพื้นหลังที่วาดไว้
                    ctx.drawImage(img, centeredLogoX, centeredLogoY, logoWidth, logoHeight);

                    // แสดง QR Code พร้อมโลโก้หลังจากวาดเสร็จ
                    document.getElementById('qrcode').innerHTML = `<img src="${canvas.toDataURL()}" alt="QR Code">`;
                };

                img.src = URL.createObjectURL(logoFile);
            } else {
                // กรณีที่ไม่มีการใช้โลโก้ ให้แสดง QR Code ทันที
                document.getElementById('qrcode').innerHTML = `<img src="${qr.toDataURL()}" alt="QR Code">`;
            }

            // แสดงปุ่มดาวน์โหลดหลังจากวาดเสร็จ
            document.getElementById('download-buttons').classList.remove("d-none");
        });

        // ฟังก์ชันรีเซ็ตสถานะและเคลียร์ค่าตั้งต้น
        function resetState(resetLogo = true) {
            const canvas = document.getElementById('qrCanvas');
            const ctx = canvas.getContext('2d');

            // เคลียร์แคนวาส
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (resetLogo) {
                // เคลียร์ฟิลด์อัพโหลดโลโก้ (ถ้า resetLogo เป็น true เท่านั้น)
                document.getElementById('logoPreview').style.display = 'none';
                document.getElementById('logoFile').value = '';
            }

            // เคลียร์ฟิลด์ URL
            // document.getElementById('qrText').value = '';
            // document.getElementById('shortUrl').value = '';

            // ซ่อนปุ่มดาวน์โหลด
            document.getElementById('download-buttons').classList.add('d-none');

            // รีเซ็ตค่าของ QRious instance (ถ้ามี)
            if (qr) {
                qr.set({
                    value: ''
                });
            }

            // ลบภาพ QR Code จาก DOM
            document.getElementById('qrcode').innerHTML = '';
        }

        // ฟังก์ชันคัดลอก Short URL ไปยัง Clipboard
        document.getElementById('copyShortUrl').addEventListener('click', function() {
            var shortUrlField = document.getElementById('shortUrl');
            shortUrlField.select();
            document.execCommand('copy');
            alert('Copied to clipboard: ' + shortUrlField.value);
        });
    </script>

    <script>
        // ดาวน์โหลด SVG
        document.getElementById('download-svg').addEventListener('click', function() {
            if (!qr) {
                alert("Please generate a QR code first!");
                return;
            }

            var svg = `
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                    <image href="${qr.toDataURL()}" height="256" width="256"/>
                </svg>
            `;
            var svgBlob = new Blob([svg], {
                type: 'image/svg+xml;charset=utf-8'
            });
            var url = URL.createObjectURL(svgBlob);

            var link = document.createElement('a');
            link.href = url;
            link.download = 'qrcode.svg';
            link.click();

            URL.revokeObjectURL(url);
        });

        // ดาวน์โหลด PNG
        document.getElementById('download-png').addEventListener('click', function() {
            if (!qr) {
                alert("Please generate a QR code first!");
                return;
            }

            var canvas = document.getElementById('qrCanvas');
            var pngUrl = canvas.toDataURL("image/png");

            var link = document.createElement('a');
            link.href = pngUrl;
            link.download = 'qrcode.png';
            link.click();
        });

        // ดาวน์โหลด JPEG
        document.getElementById('download-jpeg').addEventListener('click', function() {
            if (!qr) {
                alert("Please generate a QR code first!");
                return;
            }

            var canvas = document.getElementById('qrCanvas');
            var jpegUrl = canvas.toDataURL("image/jpeg");

            var link = document.createElement('a');
            link.href = jpegUrl;
            link.download = 'qrcode.jpeg';
            link.click();
        });
    </script>
</body>

</html>
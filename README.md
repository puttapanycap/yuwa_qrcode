# yuwa_qrcode

### Create file to configs/all.php

```php
<?php

define("_URL_", "https://localhost/qrcode/") ;

$servername = "localhost";
$username = "username";
$password = "password";
$database = "databasename";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>
```
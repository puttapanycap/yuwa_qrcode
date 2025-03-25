# yuwa_qrcode

### Create file to configs/all.php

```php
<?php
  // Config URL
  define("_URL_", "https://localhost/qrcode/") ;

  // Config Timezone
  $timezone = 'Asia/Bangkok';
  date_default_timezone_set($timezone);

  // Config Database
  $servername = "localhost";
  $username = "username";
  $password = "password";
  $database = "databasename";
  $pdo = new PDO(
      "mysql:host=$servername;dbname=$database;charset=utf8", 
      $username, 
      $password
  );
?>
```
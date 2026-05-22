<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ఇక్కడ మీ ఆన్‌లైన్ MySQL Databases స్క్రీన్ లో ఉన్న వివరాలు మాత్రమే ఇవ్వండి
$host = "sql211.infinityfree.com";      // ఖచ్చితంగా MySQL Hostname ఇవ్వండి
$user = "if0_41986998";                  // మీ MySQL Username
$pass = "Raghu115"; // మీ FTP Password
$dbname = "https://my-poultry-mgmt.rf.gd";   // మీ పూర్తి డేటాబేస్ పేరు

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}
?>

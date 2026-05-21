<?php
// ఆన్‌లైన్ InfinityFree వివరాలు
$host = "sqlxxx.infinityfree.com"; // మీ హోస్ట్‌నేమ్
$user = "if0_xxxxxxx";             // మీ యూజర్‌నేమ్
$pass = "మీ_పాస్‌వర్డ్";             // మీ పాస్‌వర్డ్
$dbname = "if0_xxxxxxx_employees_db"; // మీ డేటాబేస్ పేరు

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}
?>

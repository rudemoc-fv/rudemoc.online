<?php
$db = new PDO('mysql:host=localhost;dbname=u3057141_rudemoc', 'u3057141_rudemoc', 'iloveIC-0n');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

define('MAX_STORAGE', 10 * 1024 * 1024 * 1024); // 10 GB
define('UPLOAD_DIR', 'uploads/');
?>
<?php
$pdo = new PDO("mysql:host=localhost;dbname=projet_film", "root", "root");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
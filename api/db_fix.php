<?php
$pdo = new PDO('mysql:host=database;dbname=app', 'app', '!ChangeMe!');
$pdo->exec("ALTER TABLE mus_match ADD COLUMN IF NOT EXISTS match_day INT DEFAULT NULL");
echo "DB UPDATED";

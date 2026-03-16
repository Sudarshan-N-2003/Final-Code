<?php

$host = "ep-falling-butterfly-a48k52pw-pooler.us-east-1.aws.neon.tech";
$db   = "neondb";
$user = "neondb_owner";
$pass = "npg_cThXwaB1ode4";
$port = "5432";

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;options=endpoint=ep-falling-butterfly-a48k52pw";

    $pdo = new PDO($dsn, $user, $pass);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to Neon successfully!";

} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage());

}

?>
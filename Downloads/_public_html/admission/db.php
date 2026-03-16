<?php
// db.php — PostgreSQL (Neon) connection helper

function get_db() {

    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    /* ------------------------------------------------
       1. TRY DATABASE_URL (Render / Neon style)
    ------------------------------------------------ */

    $databaseUrl = getenv('DATABASE_URL') ?: getenv('NEON_DATABASE_URL');

    if ($databaseUrl) {

        // remove quotes if present
        $databaseUrl = trim($databaseUrl, '"');

        $parts = parse_url($databaseUrl);

        if ($parts === false) {
            throw new Exception("Invalid DATABASE_URL");
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        $db   = ltrim($parts['path'], '/');

    } else {

        /* ------------------------------------------------
           2. FALLBACK (Hostinger manual config)
        ------------------------------------------------ */

        $host = "ep-falling-butterfly-a48k52pw-pooler.us-east-1.aws.neon.tech";
        $port = "5432";
        $db   = "neondb";
        $user = "neondb_owner";
        $pass = "npg_cThXwaB1ode4";
    }

    /* ------------------------------------------------
       3. Extract endpoint id for Neon SNI
    ------------------------------------------------ */

    $endpoint = explode('.', $host)[0]; // ep-falling-butterfly-a48k52pw-pooler
    $endpoint = str_replace('-pooler', '', $endpoint);

    /* ------------------------------------------------
       4. BUILD DSN
    ------------------------------------------------ */

    $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require;options=endpoint={$endpoint}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {

        $pdo = new PDO($dsn, $user, $pass, $options);

    } catch (PDOException $e) {

        throw new Exception("Database connection failed: " . $e->getMessage());
    }

    return $pdo;
}
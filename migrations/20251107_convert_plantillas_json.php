<?php
// Migration script: convert plantillas.contenido to JSON column (if supported)
// and add generated/stored columns useful for indexing/searching.
// Run from CLI: php migrations/20251107_convert_plantillas_json.php

require_once __DIR__ . '/../src/nav/db_connection.php';

echo "Starting migration: convert contenido -> JSON (if supported) and add generated columns...\n";

try {
    $server_info = $conn->server_info; // e.g. '10.11.13-MariaDB-0ubuntu0.24.04.1' or '8.0.32'
    echo "DB server info: $server_info\n";

    // Attempt to modify column type to JSON (MySQL 5.7+/MariaDB 10.2+)
    $alterStatements = [];

    // 1) Try to change column type to JSON. If DB doesn't support JSON column, this might fail.
    $alterStatements[] = "ALTER TABLE plantillas MODIFY contenido JSON";

    // 2) Add generated columns based on JSON content to allow indexing/searching.
    // Generated columns: trabajo_count (number of trabajo rows), trabajo_primero_tipo (tipo of first trabajo row)
    $alterStatements[] = "ALTER TABLE plantillas
        ADD COLUMN IF NOT EXISTS trabajo_count INT GENERATED ALWAYS AS (JSON_LENGTH(contenido, '$.trabajo')) STORED,
        ADD COLUMN IF NOT EXISTS trabajo_primero_tipo VARCHAR(100) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(contenido, '$.trabajo[0].tipo'))) STORED";

    // 3) Add indexes on generated columns
    $alterStatements[] = "CREATE INDEX IF NOT EXISTS idx_plantillas_trabajo_count ON plantillas(trabajo_count)";
    $alterStatements[] = "CREATE INDEX IF NOT EXISTS idx_plantillas_trabajo_primero_tipo ON plantillas(trabajo_primero_tipo)";

    foreach ($alterStatements as $sql) {
        echo "Executing: " . preg_replace('/\s+/', ' ', trim($sql)) . "\n";
        try {
            $conn->query($sql);
            echo "OK\n";
        } catch (mysqli_sql_exception $e) {
            echo "Statement failed: " . $e->getMessage() . "\n";
            // Continue to next statement - we want best-effort migration
        }
    }

    echo "Migration finished (best-effort). Please inspect DB to confirm changes.\n";
    echo "If any statements failed, re-run against a staging DB after reviewing server compatibility.\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
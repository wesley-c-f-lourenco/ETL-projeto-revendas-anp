<?php
/**
 * dashboard_dados.php
 * Retorna dados agregados do banco em JSON para o dashboard.
 * Faz GROUP BY e COUNT no servidor — muito mais eficiente que
 * carregar 59 mil linhas e agregar no browser.
 */

sqlsrv_configure("WarningsReturnAsErrors", 0);
header('Content-Type: application/json; charset=utf-8');

$config   = parse_ini_file(__DIR__ . '/config.ini', true);
$server   = $config['database']['server'];
$user     = $config['database']['user'];
$password = $config['database']['password'];
$db_name  = $config['database']['name'];
$schema   = $config['database']['schema'];
$table    = $config['database']['table'];
$t        = "[$schema].[$table]";

$conn = sqlsrv_connect($server, [
    "Database"               => $db_name,
    "UID"                    => $user,
    "PWD"                    => $password,
    "TrustServerCertificate" => true,
]);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha na conexão.']);
    exit;
}

// Função auxiliar: roda query e retorna array de resultados
function query($conn, $sql) {
    $stmt = sqlsrv_query($conn, $sql);
    $rows = [];
    while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $r;
    }
    return $rows;
}

// ── 1. KPIs ──
$kpis_raw = query($conn, "
    SELECT
        COUNT(*)                    AS total_revendas,
        COUNT(DISTINCT UF)          AS total_estados,
        COUNT(DISTINCT MUNICIPIO)   AS total_municipios,
        COUNT(DISTINCT DISTRIBUIDORA) AS total_distribuidoras
    FROM $t
");

// ── 2. Revendas por estado ──
$por_estado = query($conn, "
    SELECT UF, COUNT(*) AS total
    FROM $t
    WHERE UF IS NOT NULL
    GROUP BY UF
    ORDER BY total DESC
");

// ── 3. Top 10 municípios ──
$top_municipios = query($conn, "
    SELECT TOP 10 MUNICIPIO, UF, COUNT(*) AS total
    FROM $t
    WHERE MUNICIPIO IS NOT NULL
    GROUP BY MUNICIPIO, UF
    ORDER BY total DESC
");

// ── 4. Autorizações por ano ──
$por_ano = query($conn, "
    SELECT YEAR(DATAPUBLICACAO) AS ano, COUNT(*) AS total
    FROM $t
    WHERE DATAPUBLICACAO IS NOT NULL
      AND YEAR(DATAPUBLICACAO) >= 2000
    GROUP BY YEAR(DATAPUBLICACAO)
    ORDER BY ano ASC
");

// ── 5. Market share por distribuidora ──
$por_distribuidora = query($conn, "
    SELECT DISTRIBUIDORA, COUNT(*) AS total
    FROM $t
    WHERE DISTRIBUIDORA IS NOT NULL
    GROUP BY DISTRIBUIDORA
    ORDER BY total DESC
");

sqlsrv_close($conn);

// Corrige encoding em strings
function fix($val) {
    if (!is_string($val)) return $val;
    return mb_check_encoding($val, 'UTF-8')
        ? $val
        : mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
}

function fixRows($rows) {
    return array_map(function($row) {
        $out = [];
        foreach ($row as $k => $v) $out[$k] = fix($v);
        return $out;
    }, $rows);
}

echo json_encode([
    'kpis'              => $kpis_raw[0],
    'por_estado'        => fixRows($por_estado),
    'top_municipios'    => fixRows($top_municipios),
    'por_ano'           => fixRows($por_ano),
    'por_distribuidora' => fixRows($por_distribuidora),
], JSON_UNESCAPED_UNICODE);
<?php
// Trata avisos como avisos, não como erros fatais (necessário no XAMPP)
sqlsrv_configure("WarningsReturnAsErrors", 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Leitura das configurações
$config_path = __DIR__ . '/config.ini';

if (!file_exists($config_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Arquivo config.ini não encontrado.']);
    exit;
}

$config   = parse_ini_file($config_path, true);
$server   = $config['database']['server'];
$user     = $config['database']['user'];
$password = $config['database']['password'];
$db_name  = $config['database']['name'];
$schema   = $config['database']['schema'];
$table    = $config['database']['table'];

// Conexão com SQL Server
$conn = sqlsrv_connect($server, [
    "Database"               => $db_name,
    "UID"                    => $user,
    "PWD"                    => $password,
    "TrustServerCertificate" => true,
]);

if ($conn === false) {
    http_response_code(500);
    $erros = sqlsrv_errors();
    echo json_encode([
        'error'    => 'Falha na conexão com o banco de dados.',
        'detalhes' => $erros[0]['message'] ?? 'Erro desconhecido.',
    ]);
    exit;
}

// Consulta
$sql = "SELECT CNPJ, RAZAOSOCIAL, UF, MUNICIPIO, AUTORIZACAO, DATAPUBLICACAO
        FROM [$schema].[$table]
        ORDER BY RAZAOSOCIAL";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    http_response_code(500);
    $erros = sqlsrv_errors();
    echo json_encode([
        'error'    => 'Erro ao executar a consulta.',
        'detalhes' => $erros[0]['message'] ?? 'Erro desconhecido.',
    ]);
    sqlsrv_close($conn);
    exit;
}

$dados = [];

while ($linha = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Converte data de objeto DateTime para string DD/MM/YYYY
    if ($linha['DATAPUBLICACAO'] instanceof DateTime) {
        $linha['DATAPUBLICACAO'] = $linha['DATAPUBLICACAO']->format('d/m/Y');
    }

    // Corrige encoding: converte de Windows-1252 para UTF-8
    // Necessário porque o CSV foi importado com encoding Latin-1
    foreach ($linha as $chave => $valor) {
        if (is_string($valor)) {
            // Detecta se a string não é UTF-8 válido e converte
            if (!mb_check_encoding($valor, 'UTF-8')) {
                $linha[$chave] = mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
            }
        }
    }

    $dados[] = $linha;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo json_encode(
    ['data' => $dados],
    JSON_UNESCAPED_UNICODE
);
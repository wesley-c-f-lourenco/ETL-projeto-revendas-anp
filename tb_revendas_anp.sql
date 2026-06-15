<?php
/**
 * revendas_anp_selecionar.php
 *
 * Endpoint que consulta a tabela tb_revendas_anp no SQL Server
 * e retorna os dados em formato JSON.
 *
 * Chamado via AJAX pelo index.php.
 * Não deve ser aberto diretamente no navegador para uso normal,
 * mas pode ser testado diretamente para verificar se retorna JSON.
 *
 * Extensão PHP necessária: php_sqlsrv
 * Instalar: https://learn.microsoft.com/pt-br/sql/connect/php/
 */

// ---- Cabeçalho da resposta ----
// Diz ao browser que o conteúdo é JSON e usa UTF-8.
header('Content-Type: application/json; charset=utf-8');

// Permite que o index.php (mesmo servidor) chame este arquivo via AJAX.
// Em produção, restrinja para o domínio correto.
header('Access-Control-Allow-Origin: *');


// ==============================================================
// PARTE 1: Leitura das configurações
// ==============================================================
// parse_ini_file() lê arquivos .ini nativamente no PHP.
// O segundo parâmetro 'true' retorna um array associativo com seções.
// __DIR__ resolve para a pasta onde este arquivo está salvo,
// evitando problemas com caminhos relativos.

$config_path = __DIR__ . '/config.ini';

if (!file_exists($config_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Arquivo config.ini não encontrado.']);
    exit;
}

$config = parse_ini_file($config_path, true);

$server   = $config['database']['server'];
$user     = $config['database']['user'];
$password = $config['database']['password'];
$db_name  = $config['database']['name'];
$schema   = $config['database']['schema'];
$table    = $config['database']['table'];


// ==============================================================
// PARTE 2: Conexão com o SQL Server
// ==============================================================
// sqlsrv_connect() é a função nativa da extensão Microsoft SQLSRV.
// Retorna false se falhar; devemos verificar.

$conn = sqlsrv_connect($server, [
    "Database"               => $db_name,
    "UID"                    => $user,
    "PWD"                    => $password,
    "CharacterSet"           => "UTF-8",
    "TrustServerCertificate" => true,  // necessário em ambientes locais/dev
]);

if ($conn === false) {
    http_response_code(500);
    $erros = sqlsrv_errors();
    echo json_encode([
        'error'   => 'Falha na conexão com o banco de dados.',
        'detalhes' => $erros[0]['message'] ?? 'Erro desconhecido.',
    ]);
    exit;
}


// ==============================================================
// PARTE 3: Consulta ao banco
// ==============================================================
// O desafio pede apenas as colunas:
// CNPJ, RAZAOSOCIAL, UF, MUNICIPIO, AUTORIZACAO, DATAPUBLICACAO

// Usamos colchetes ([]) em volta do schema e tabela para evitar
// conflitos com palavras reservadas do SQL Server.

$sql = "SELECT
            CNPJ,
            RAZAOSOCIAL,
            UF,
            MUNICIPIO,
            AUTORIZACAO,
            DATAPUBLICACAO
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


// ==============================================================
// PARTE 4: Montagem do resultado
// ==============================================================
$dados = [];

// sqlsrv_fetch_array() retorna uma linha por chamada.
// SQLSRV_FETCH_ASSOC = array associativo (coluna => valor).
// Quando não há mais linhas, retorna null e o while termina.

while ($linha = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

    // DATAPUBLICACAO vem como objeto DateTime do PHP (porque a coluna
    // no banco é do tipo DATE). Precisamos converter para string.
    if ($linha['DATAPUBLICACAO'] instanceof DateTime) {
        $linha['DATAPUBLICACAO'] = $linha['DATAPUBLICACAO']->format('d/m/Y');
    }

    $dados[] = $linha;
}

// Libera os recursos (boa prática).
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);


// ==============================================================
// PARTE 5: Retorno JSON
// ==============================================================
// O DataTables espera o formato: {"data": [...]}
// JSON_UNESCAPED_UNICODE evita que 'ã', 'ç' etc. virem '\u00e3'.

echo json_encode(
    ['data' => $dados],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);

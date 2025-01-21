<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_agendamentos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    // Tenta conectar ao MySQL sem especificar o banco de dados
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Cria o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Banco de dados '" . DB_NAME . "' criado/verificado com sucesso!\n";
    
    // Seleciona o banco de dados
    $pdo->exec("USE " . DB_NAME);
    
    // Executa o script de criação das tabelas
    $sql = file_get_contents(__DIR__ . '/../database/update_schema.sql');
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
            echo "Query executada com sucesso.\n";
        }
    }
    
    echo "Estrutura do banco de dados criada com sucesso!\n";
    
} catch (PDOException $e) {
    die("Erro ao criar/atualizar banco de dados: " . $e->getMessage() . "\n");
} 
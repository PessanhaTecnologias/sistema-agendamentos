<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Lê e executa o script de reset
    $sql = file_get_contents(__DIR__ . '/reset_database.sql');
    $pdo->exec($sql);
    echo "Banco de dados resetado com sucesso!\n";
    
    // Lê e executa o script de criação
    $sql = file_get_contents(__DIR__ . '/update_schema.sql');
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
            echo "Query executada com sucesso.\n";
        }
    }
    
    echo "Banco de dados atualizado com sucesso!\n";
    
} catch (PDOException $e) {
    die("Erro ao resetar/atualizar banco de dados: " . $e->getMessage() . "\n");
} 
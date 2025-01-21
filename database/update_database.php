<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/update_schema.sql');
    
    // Divide as queries pelo ponto e vírgula
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // Executa cada query separadamente
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
            echo "Query executada com sucesso.\n";
        }
    }
    
    echo "Banco de dados atualizado com sucesso!\n";
    
} catch (PDOException $e) {
    die("Erro ao atualizar banco de dados: " . $e->getMessage() . "\n");
} 
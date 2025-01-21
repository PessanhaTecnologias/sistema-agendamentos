<?php
require_once __DIR__ . '/config.php';

try {
    // Obtém a conexão do banco de dados
    $pdo = getConnection();
} catch (PDOException $e) {
    // Em produção, você deve logar o erro e mostrar uma mensagem genérica
    error_log("Erro de conexão: " . $e->getMessage());
    die("Desculpe, ocorreu um erro ao conectar ao banco de dados.");
} 
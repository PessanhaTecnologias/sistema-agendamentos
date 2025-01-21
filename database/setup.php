<?php
// Configurações do banco de dados
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Conecta ao MySQL sem selecionar banco
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao MySQL com sucesso!\n";
    
    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/create_database.sql');
    
    // Divide o SQL em comandos individuais
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    // Executa cada comando
    foreach ($commands as $command) {
        if (!empty($command)) {
            $pdo->exec($command);
            echo "Comando SQL executado com sucesso!\n";
        }
    }
    
    echo "\nBanco de dados configurado com sucesso!\n";
    echo "Você pode acessar o sistema em: http://localhost/Projeto/\n";
    echo "Email: admin@exemplo.com\n";
    echo "Senha: admin123\n";
    
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage() . "\n");
} 
<?php
// Carrega as configurações
require_once __DIR__ . '/../config/config.php';

// Carrega a conexão com o banco de dados
require_once __DIR__ . '/../config/database.php';

// Carrega as funções utilitárias
require_once __DIR__ . '/functions.php';

// Define o cabeçalho padrão
header('Content-Type: text/html; charset=utf-8');

// Verifica se o diretório de logs existe e tem permissão de escrita
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
if (!is_writable($logDir)) {
    chmod($logDir, 0777);
}

// Registra manipulador de erros personalizado
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = date('Y-m-d H:i:s') . " - Erro: [$errno] $errstr - $errfile:$errline\n";
    error_log($error, 3, __DIR__ . '/../logs/error.log');
    
    if (ini_get('display_errors')) {
        echo "<div style='color:red;'><b>Erro:</b> $errstr</div>";
    }
    
    return true;
});

// Registra manipulador de exceções não capturadas
set_exception_handler(function($e) {
    $error = date('Y-m-d H:i:s') . " - Exceção não capturada: " . $e->getMessage() . 
            " em " . $e->getFile() . ":" . $e->getLine() . "\n";
    error_log($error, 3, __DIR__ . '/../logs/error.log');
    
    if (ini_get('display_errors')) {
        echo "<div style='color:red;'><b>Erro:</b> " . $e->getMessage() . "</div>";
    } else {
        echo "<div style='color:red;'>Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.</div>";
    }
}); 
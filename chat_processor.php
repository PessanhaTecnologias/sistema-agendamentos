<?php
// Desabilitar output de erros para o navegador
ini_set('display_errors', 0);
error_reporting(0);

// Configurar headers antes de qualquer output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once 'config/database.php';
    require_once 'controllers/ChatController.php';

    // Verifica se recebeu uma mensagem via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verifica se o Content-Type é application/x-www-form-urlencoded ou application/json
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
        $data = [];
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido');
            }
        } else {
            $data = $_POST;
        }

        $telefone = $data['telefone'] ?? 'novo';
        $mensagem = $data['mensagem'] ?? '';
        
        if (empty($mensagem)) {
            throw new Exception('Mensagem vazia');
        }

        if (!isset($pdo)) {
            throw new Exception('Conexão com banco de dados não estabelecida');
        }

        $chatController = new ChatController($pdo);
        $resposta = $chatController->processarMensagem($telefone, $mensagem);
        
        if ($resposta === false || $resposta === null) {
            throw new Exception('Resposta inválida do controlador');
        }

        echo json_encode([
            'status' => 'success',
            'resposta' => $resposta
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Método inválido');

} catch (PDOException $e) {
    error_log("Erro de banco de dados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao acessar o banco de dados. Por favor, tente novamente.'
    ]);
    exit;
} catch (Exception $e) {
    error_log("Erro no chat: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao processar mensagem. Por favor, tente novamente.'
    ]);
    exit;
} 
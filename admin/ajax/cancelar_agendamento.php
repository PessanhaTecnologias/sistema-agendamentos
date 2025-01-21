<?php
require_once '../../config/database.php';
require_once '../auth.php';

// Verifica se o usuário está logado e tem permissão
if (!isset($_SESSION['usuario_id']) || !verificarPermissao('gerente')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

// Verifica se o ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    // Inicia a transação
    $pdo->beginTransaction();
    
    // Busca o agendamento
    $stmt = $pdo->prepare("
        SELECT * FROM agendamentos WHERE id = ? AND status = 'agendado'
    ");
    $stmt->execute([$_GET['id']]);
    $agendamento = $stmt->fetch();
    
    if (!$agendamento) {
        throw new Exception('Agendamento não encontrado ou já cancelado');
    }
    
    // Atualiza o status do agendamento
    $stmt = $pdo->prepare("
        UPDATE agendamentos 
        SET 
            status = 'cancelado',
            updated_at = NOW(),
            atendente_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['usuario_id'], $_GET['id']]);
    
    // Registra o cancelamento no log
    $stmt = $pdo->prepare("
        INSERT INTO log_agendamentos (
            agendamento_id,
            usuario_id,
            acao,
            detalhes
        ) VALUES (?, ?, 'cancelamento', 'Agendamento cancelado via painel administrativo')
    ");
    $stmt->execute([$_GET['id'], $_SESSION['usuario_id']]);
    
    // Se houver uma conversa ativa com o cliente, atualiza o status
    $stmt = $pdo->prepare("
        UPDATE conversas 
        SET status = 'cancelado' 
        WHERE telefone = ? AND status = 'em_andamento'
    ");
    $stmt->execute([$agendamento['telefone']]);
    
    // Confirma a transação
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Agendamento cancelado com sucesso'
    ]);
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $pdo->rollBack();
    
    error_log("Erro ao cancelar agendamento: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao cancelar agendamento: ' . $e->getMessage()
    ]);
} 
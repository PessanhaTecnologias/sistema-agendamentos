<?php
require_once 'auth.php';

// Verifica se o usuário está logado
if (!verificarLogin()) {
    header('Location: login.php');
    exit;
}

// Verifica se foi fornecido um ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: agendamentos.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    // Inicia a transação
    $pdo->beginTransaction();

    // Atualiza o status do agendamento
    $stmt = $pdo->prepare("
        UPDATE agendamentos 
        SET status = 'cancelado',
            atualizado_em = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    // Registra o log
    $stmt = $pdo->prepare("
        INSERT INTO log_agendamentos (agendamento_id, usuario_id, acao, detalhes)
        VALUES (?, ?, 'cancelamento', 'Agendamento cancelado via painel administrativo')
    ");
    $stmt->execute([$id, $_SESSION['usuario_id']]);

    // Confirma a transação
    $pdo->commit();

    // Redireciona com mensagem de sucesso
    header('Location: agendamentos.php?msg=cancelado');
    exit;

} catch (PDOException $e) {
    // Desfaz a transação em caso de erro
    $pdo->rollBack();
    
    // Redireciona com mensagem de erro
    header('Location: agendamentos.php?erro=cancelar');
    exit;
} 
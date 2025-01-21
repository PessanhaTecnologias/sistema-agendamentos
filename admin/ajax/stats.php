<?php
require_once '../../config/database.php';
require_once '../auth.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}

try {
    $stats = [
        'agendamentos_hoje' => 0,
        'conversas_ativas' => 0,
        'ultima_atualizacao' => date('H:i:s')
    ];
    
    // Agendamentos de hoje
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM agendamentos 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $stats['agendamentos_hoje'] = $stmt->fetchColumn();
    
    // Conversas ativas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM conversas 
        WHERE status = 'em_andamento'
    ");
    $stmt->execute();
    $stats['conversas_ativas'] = $stmt->fetchColumn();
    
    // Retorna os dados em JSON
    header('Content-Type: application/json');
    echo json_encode($stats);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar estatísticas']);
} 
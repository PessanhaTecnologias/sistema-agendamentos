<?php
require_once '../../config/database.php';
require_once '../auth.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}

// Verifica se o ID foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID inválido";
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            l.nome as nome_loja,
            l.cidade as cidade_loja,
            u.nome as atendente,
            c.etapa as etapa_conversa,
            c.status as status_conversa
        FROM agendamentos a
        JOIN locais l ON a.local_id = l.id
        LEFT JOIN usuarios u ON a.atendente_id = u.id
        LEFT JOIN conversas c ON a.id = (
            SELECT MAX(id) 
            FROM conversas 
            WHERE telefone = a.telefone
        )
        WHERE a.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$agendamento) {
        echo "Agendamento não encontrado";
        exit;
    }
    
    // Busca o histórico de mensagens
    $stmt = $pdo->prepare("
        SELECT m.*
        FROM mensagens m
        JOIN conversas c ON m.conversa_id = c.id
        WHERE c.telefone = ?
        ORDER BY m.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$agendamento['telefone']]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid p-0">
    <div class="row">
        <div class="col-md-6">
            <h5>Informações do Cliente</h5>
            <table class="table table-sm">
                <tr>
                    <th>Nome:</th>
                    <td><?php echo htmlspecialchars($agendamento['nome_cliente']); ?></td>
                </tr>
                <tr>
                    <th>Telefone:</th>
                    <td><?php echo htmlspecialchars($agendamento['telefone']); ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td><span class="badge bg-<?php echo $agendamento['status'] == 'agendado' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($agendamento['status']); ?></span></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h5>Informações do Agendamento</h5>
            <table class="table table-sm">
                <tr>
                    <th>Loja:</th>
                    <td><?php echo htmlspecialchars($agendamento['nome_loja']); ?></td>
                </tr>
                <tr>
                    <th>Cidade:</th>
                    <td><?php echo htmlspecialchars($agendamento['cidade_loja']); ?></td>
                </tr>
                <tr>
                    <th>Data/Hora:</th>
                    <td><?php echo date('d/m/Y H:i', strtotime($agendamento['data_agendamento'] . ' ' . $agendamento['horario'])); ?></td>
                </tr>
                <tr>
                    <th>Atendente:</th>
                    <td><?php echo $agendamento['atendente'] ? htmlspecialchars($agendamento['atendente']) : 'Não atribuído'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <?php if ($agendamento['observacoes']): ?>
    <div class="row mt-3">
        <div class="col-12">
            <h5>Observações</h5>
            <p class="text-muted"><?php echo nl2br(htmlspecialchars($agendamento['observacoes'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mt-3">
        <div class="col-12">
            <h5>Histórico da Conversa</h5>
            <div class="chat-history" style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($mensagens as $msg): ?>
                <div class="message <?php echo $msg['tipo'] == 'enviada' ? 'text-end' : ''; ?> mb-2">
                    <small class="text-muted"><?php echo date('d/m H:i', strtotime($msg['created_at'])); ?></small>
                    <div class="message-content p-2 rounded <?php echo $msg['tipo'] == 'enviada' ? 'bg-primary text-white' : 'bg-light'; ?>">
                        <?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (verificarPermissao('gerente')): ?>
    <div class="row mt-3">
        <div class="col-12">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" onclick="editarAgendamento(<?php echo $agendamento['id']; ?>)">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <?php if ($agendamento['status'] == 'agendado'): ?>
                <button type="button" class="btn btn-success" onclick="confirmarPresenca(<?php echo $agendamento['id']; ?>)">
                    <i class="fas fa-check"></i> Confirmar Presença
                </button>
                <button type="button" class="btn btn-danger" onclick="cancelarAgendamento(<?php echo $agendamento['id']; ?>)">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
} catch (PDOException $e) {
    error_log("Erro ao buscar detalhes do agendamento: " . $e->getMessage());
    echo "Erro ao buscar detalhes do agendamento";
} 
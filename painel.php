<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar ações de locais
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salvar_local'])) {
        $nome = $_POST['nome'];
        $cidade = $_POST['cidade'];
        $horarios = $_POST['horarios'];
        $id = $_POST['local_id'] ?? null;
        
        if ($id) {
            $stmt = $pdo->prepare("UPDATE locais SET nome = ?, cidade = ?, horarios = ? WHERE id = ?");
            $stmt->execute([$nome, $cidade, $horarios, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO locais (nome, cidade, horarios) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $cidade, $horarios]);
        }
        
        header('Location: locais.php?msg=sucesso');
        exit;
    }
}

// Buscar dados para o dashboard
$agendamentosHoje = getAgendamentosHoje($pdo);
$totalHoje = count($agendamentosHoje);

$stmt = $pdo->query("
    SELECT COUNT(*) as pendentes 
    FROM agendamentos 
    WHERE status = 'pendente' AND DATE(data_agendamento) >= CURDATE()
");
$pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['pendentes'];

// Buscar conversas ativas
$stmt = $pdo->query("
    SELECT c.*, l.nome as nome_local
    FROM conversas c
    LEFT JOIN locais l ON c.loja_id = l.id
    WHERE c.status = 'em_andamento'
    ORDER BY c.updated_at DESC
    LIMIT 5
");
$conversasAtivas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Agendamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Cards de Resumo -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agendamentos Hoje</h5>
                        <h2><?php echo $totalHoje; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Pendentes</h5>
                        <h2><?php echo $pendentes; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Conversas Ativas</h5>
                        <h2><?php echo count($conversasAtivas); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agendamentos de Hoje -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Agendamentos de Hoje</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Cliente</th>
                                <th>Telefone</th>
                                <th>Local</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentosHoje as $agendamento): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($agendamento['horario'])); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['nome_cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($agendamento['nome_local']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $agendamento['status'] === 'confirmado' ? 'success' : 
                                                ($agendamento['status'] === 'pendente' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($agendamento['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="verDetalhes(<?php echo $agendamento['id']; ?>)">
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Conversas Ativas -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Conversas em Andamento</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Telefone</th>
                                <th>Nome</th>
                                <th>Etapa</th>
                                <th>Última Atualização</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conversasAtivas as $conversa): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($conversa['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($conversa['nome_cliente'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($conversa['etapa']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($conversa['updated_at'])); ?></td>
                                    <td>
                                        <a href="ver_conversa.php?id=<?php echo $conversa['id']; ?>" 
                                           class="btn btn-sm btn-info">Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
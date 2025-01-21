<?php
require_once 'auth.php';

// Verifica se o usuário está logado
if (!verificarLogin()) {
    header('Location: login.php');
    exit;
}

// Inicializa variáveis de filtro
$filtros = [
    'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-d'),
    'data_fim' => $_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days')),
    'status' => $_GET['status'] ?? '',
    'local_id' => $_GET['local_id'] ?? '',
    'busca' => $_GET['busca'] ?? ''
];

try {
    // Busca os locais para o filtro
    $stmt = $pdo->prepare("SELECT id, nome FROM locais WHERE ativo = TRUE ORDER BY nome");
    $stmt->execute();
    $locais = $stmt->fetchAll();

    // Monta a query base
    $sql = "
        SELECT a.*, 
               l.nome as local_nome,
               u1.nome as atendente_nome,
               u2.nome as gerente_nome
        FROM agendamentos a
        LEFT JOIN locais l ON a.local_id = l.id
        LEFT JOIN usuarios u1 ON a.atendente_id = u1.id
        LEFT JOIN usuarios u2 ON a.gerente_id = u2.id
        WHERE 1=1
    ";
    $params = [];

    // Aplica os filtros
    if ($filtros['data_inicio']) {
        $sql .= " AND a.data_agendamento >= ?";
        $params[] = $filtros['data_inicio'];
    }
    if ($filtros['data_fim']) {
        $sql .= " AND a.data_agendamento <= ?";
        $params[] = $filtros['data_fim'];
    }
    if ($filtros['status']) {
        $sql .= " AND a.status = ?";
        $params[] = $filtros['status'];
    }
    if ($filtros['local_id']) {
        $sql .= " AND a.local_id = ?";
        $params[] = $filtros['local_id'];
    }
    if ($filtros['busca']) {
        $sql .= " AND (a.nome_cliente LIKE ? OR a.email_cliente LIKE ? OR a.telefone_cliente LIKE ?)";
        $busca = "%{$filtros['busca']}%";
        $params[] = $busca;
        $params[] = $busca;
        $params[] = $busca;
    }

    $sql .= " ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC";

    // Executa a query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll();

} catch (PDOException $e) {
    $erro = "Erro ao carregar agendamentos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Agendamentos - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Gerenciar Agendamentos</h1>
            <a href="novo_agendamento.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Novo Agendamento
            </a>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="agendado" <?= $filtros['status'] === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                            <option value="confirmado" <?= $filtros['status'] === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                            <option value="cancelado" <?= $filtros['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            <option value="concluido" <?= $filtros['status'] === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Local</label>
                        <select name="local_id" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($locais as $local): ?>
                                <option value="<?= $local['id'] ?>" <?= $filtros['local_id'] == $local['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($local['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="busca" class="form-control" value="<?= htmlspecialchars($filtros['busca']) ?>" placeholder="Nome, email ou telefone">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                        <a href="agendamentos.php" class="btn btn-secondary">Limpar</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php else: ?>
            <!-- Lista de Agendamentos -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Cliente</th>
                                    <th>Local</th>
                                    <th>Status</th>
                                    <th>Atendente</th>
                                    <th>Gerente</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($agendamentos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            Nenhum agendamento encontrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($agendamentos as $agendamento): ?>
                                        <tr>
                                            <td>
                                                <?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?><br>
                                                <small class="text-muted"><?= date('H:i', strtotime($agendamento['hora_agendamento'])) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($agendamento['nome_cliente']) ?><br>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($agendamento['email_cliente']) ?><br>
                                                    <?= htmlspecialchars($agendamento['telefone_cliente']) ?>
                                                </small>
                                            </td>
                                            <td><?= htmlspecialchars($agendamento['local_nome']) ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo match($agendamento['status']) {
                                                        'agendado' => 'primary',
                                                        'confirmado' => 'success',
                                                        'cancelado' => 'danger',
                                                        'concluido' => 'secondary',
                                                        default => 'info'
                                                    };
                                                ?>">
                                                    <?= ucfirst($agendamento['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($agendamento['atendente_nome'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($agendamento['gerente_nome'] ?? '-') ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar_agendamento.php?id=<?= $agendamento['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            title="Cancelar"
                                                            onclick="confirmarCancelamento(<?= $agendamento['id'] ?>)">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarCancelamento(id) {
            if (confirm('Tem certeza que deseja cancelar este agendamento?')) {
                window.location.href = `cancelar_agendamento.php?id=${id}`;
            }
        }
    </script>
</body>
</html> 
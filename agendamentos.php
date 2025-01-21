<?php
session_start();
require_once 'config/database.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar filtros
$where = ["1=1"];
$params = [];

if (isset($_GET['data']) && $_GET['data']) {
    $where[] = "DATE(data_agendamento) = ?";
    $params[] = $_GET['data'];
}

if (isset($_GET['cidade']) && $_GET['cidade']) {
    $where[] = "l.cidade = ?";
    $params[] = $_GET['cidade'];
}

if (isset($_GET['local_id']) && $_GET['local_id']) {
    $where[] = "a.local_id = ?";
    $params[] = $_GET['local_id'];
}

// Buscar agendamentos
$sql = "SELECT a.*, l.nome as nome_local, l.cidade
        FROM agendamentos a
        JOIN locais l ON a.local_id = l.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY data_agendamento DESC, horario DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar locais para o filtro
$stmt = $pdo->query("SELECT id, nome, cidade FROM locais WHERE ativo = TRUE ORDER BY cidade, nome");
$locais = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2>Agendamentos</h2>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" name="data" 
                               value="<?php echo $_GET['data'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <select class="form-control" name="cidade">
                            <option value="">Todas</option>
                            <option value="Guarapari">Guarapari</option>
                            <option value="Vila Velha">Vila Velha</option>
                            <option value="Viana">Viana</option>
                            <option value="Serra">Serra</option>
                            <option value="Cariacica">Cariacica</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Local</label>
                        <select class="form-control" name="local_id">
                            <option value="">Todos</option>
                            <?php foreach ($locais as $local): ?>
                                <option value="<?php echo $local['id']; ?>">
                                    <?php echo htmlspecialchars($local['nome'] . ' - ' . $local['cidade']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Agendamentos -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Local</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendamentos as $agendamento): ?>
                        <tr>
                            <td>
                                <?php 
                                    echo date('d/m/Y H:i', strtotime($agendamento['data_agendamento'] . ' ' . $agendamento['horario']));
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($agendamento['nome_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($agendamento['telefone']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($agendamento['nome_local'] . ' - ' . $agendamento['cidade']); ?>
                            </td>
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
                                <button class="btn btn-sm btn-warning" 
                                        onclick="editarStatus(<?php echo $agendamento['id']; ?>)">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
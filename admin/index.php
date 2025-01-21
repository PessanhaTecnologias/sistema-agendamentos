<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Se não estiver logado, redireciona para o login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica permissões
if (!verificarPermissao('atendente')) {
    header('Location: login.php?erro=permissao');
    exit;
}

// Atualiza o cookie de sessão
setcookie('usuario_id', $_SESSION['usuario_id'], 0, '/Projeto/');

// Obtém as estatísticas
$stats = [
    'total_agendamentos' => 0,
    'agendamentos_hoje' => 0,
    'lojas_ativas' => 0,
    'conversas_ativas' => 0,
    'agendamentos_semana' => [],
    'conversoes' => 0
];

try {
    // Total de agendamentos
    $stmt = $pdo->query("SELECT COUNT(*) FROM agendamentos");
    $stats['total_agendamentos'] = $stmt->fetchColumn();
    
    // Agendamentos de hoje
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM agendamentos 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $stats['agendamentos_hoje'] = $stmt->fetchColumn();
    
    // Lojas ativas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM locais WHERE ativo = TRUE");
    $stmt->execute();
    $stats['lojas_ativas'] = $stmt->fetchColumn();
    
    // Conversas ativas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversas WHERE status = 'em_andamento'");
    $stmt->execute();
    $stats['conversas_ativas'] = $stmt->fetchColumn();
    
    // Agendamentos da última semana
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as data, COUNT(*) as total
        FROM agendamentos
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY data
    ");
    $stmt->execute();
    $stats['agendamentos_semana'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Taxa de conversão (agendamentos concluídos / total de conversas)
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(
                (SELECT COUNT(*) FROM agendamentos WHERE status = 'agendado') * 100.0 / 
                NULLIF((SELECT COUNT(*) FROM conversas WHERE status != 'em_andamento'), 0),
                0
            ) as taxa
    ");
    $stmt->execute();
    $stats['conversoes'] = round($stmt->fetchColumn(), 2);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Agendamentos</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="agendamentos.php">
                            <i class="fas fa-calendar"></i> Agendamentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lojas.php">
                            <i class="fas fa-store"></i> Lojas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="conversas.php">
                            <i class="fas fa-comments"></i> Conversas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar"></i> Relatórios
                        </a>
                    </li>
                    <?php if (verificarPermissao('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users"></i> Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="configuracoes.php">
                            <i class="fas fa-cog"></i> Configurações
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Conteúdo principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarDados()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="periodoDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar"></i> Esta semana
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="mudarPeriodo('hoje')">Hoje</a></li>
                            <li><a class="dropdown-item" href="#" onclick="mudarPeriodo('semana')">Esta semana</a></li>
                            <li><a class="dropdown-item" href="#" onclick="mudarPeriodo('mes')">Este mês</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Cards de estatísticas -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total de Agendamentos</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="total_agendamentos">
                                        <?php echo $stats['total_agendamentos']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Agendamentos Hoje</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="agendamentos_hoje">
                                        <?php echo $stats['agendamentos_hoje']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Taxa de Conversão</div>
                                    <div class="row no-gutters align-items-center">
                                        <div class="col-auto">
                                            <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" data-stat="conversoes">
                                                <?php echo $stats['conversoes']; ?>%
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="progress progress-sm mr-2">
                                                <div class="progress-bar bg-info" role="progressbar" 
                                                     style="width: <?php echo min(100, $stats['conversoes']); ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Conversas Ativas</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" data-stat="conversas_ativas">
                                        <?php echo $stats['conversas_ativas']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de agendamentos -->
            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Agendamentos da Semana</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="graficoAgendamentos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimas conversas ativas -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Conversas Ativas</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php
                                try {
                                    $stmt = $pdo->query("
                                        SELECT c.*, l.nome as nome_loja 
                                        FROM conversas c
                                        LEFT JOIN locais l ON c.loja_id = l.id
                                        WHERE c.status = 'em_andamento'
                                        ORDER BY c.updated_at DESC
                                        LIMIT 5
                                    ");
                                    while ($conversa = $stmt->fetch()) {
                                        $nome_cliente = $conversa['nome_cliente'] ? $conversa['nome_cliente'] : 'Cliente Novo';
                                        echo "<a href='conversa.php?id={$conversa['id']}' class='list-group-item list-group-item-action'>";
                                        echo "<div class='d-flex w-100 justify-content-between'>";
                                        echo "<h6 class='mb-1'>" . htmlspecialchars($nome_cliente) . "</h6>";
                                        echo "<small>" . date('H:i', strtotime($conversa['updated_at'])) . "</small>";
                                        echo "</div>";
                                        echo "<p class='mb-1'>Etapa: " . htmlspecialchars($conversa['etapa']) . "</p>";
                                        if ($conversa['nome_loja']) {
                                            echo "<small>Loja: " . htmlspecialchars($conversa['nome_loja']) . "</small>";
                                        }
                                        echo "</a>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Erro ao buscar conversas: " . $e->getMessage());
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de últimos agendamentos -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Últimos Agendamentos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tabelaAgendamentos" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Telefone</th>
                                    <th>Loja</th>
                                    <th>Data/Hora</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->query("
                                        SELECT a.*, l.nome as nome_loja, u.nome as atendente
                                        FROM agendamentos a 
                                        JOIN locais l ON a.local_id = l.id 
                                        LEFT JOIN usuarios u ON a.atendente_id = u.id
                                        ORDER BY a.created_at DESC 
                                        LIMIT 10
                                    ");
                                    while ($row = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nome_cliente']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['telefone']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nome_loja']) . "</td>";
                                        echo "<td>" . date('d/m/Y H:i', strtotime($row['data_agendamento'] . ' ' . $row['horario'])) . "</td>";
                                        echo "<td><span class='badge bg-" . ($row['status'] == 'agendado' ? 'success' : 'warning') . "'>" . 
                                             htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td>";
                                        echo "<button class='btn btn-sm btn-info' onclick='verDetalhes({$row['id']})'><i class='fas fa-eye'></i></button> ";
                                        if (verificarPermissao('gerente')) {
                                            echo "<button class='btn btn-sm btn-primary' onclick='editarAgendamento({$row['id']})'><i class='fas fa-edit'></i></button> ";
                                            echo "<button class='btn btn-sm btn-danger' onclick='cancelarAgendamento({$row['id']})'><i class='fas fa-times'></i></button>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } catch (PDOException $e) {
                                    error_log("Erro ao buscar agendamentos: " . $e->getMessage());
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesAgendamento"></div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle com Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Gráfico de agendamentos
const ctx = document.getElementById('graficoAgendamentos').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($stats['agendamentos_semana'])); ?>,
        datasets: [{
            label: 'Agendamentos',
            data: <?php echo json_encode(array_values($stats['agendamentos_semana'])); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Funções de ação
function verDetalhes(id) {
    fetch(`ajax/agendamento_detalhes.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detalhesAgendamento').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
        });
}

function editarAgendamento(id) {
    window.location.href = `editar_agendamento.php?id=${id}`;
}

function cancelarAgendamento(id) {
    if (confirm('Tem certeza que deseja cancelar este agendamento?')) {
        fetch(`ajax/cancelar_agendamento.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erro ao cancelar agendamento: ' + data.message);
                }
            });
    }
}

function exportarDados() {
    window.location.href = 'exportar_agendamentos.php';
}

function mudarPeriodo(periodo) {
    // Implementar filtro por período
    console.log('Período selecionado:', periodo);
}

// Atualização automática
setInterval(() => {
    fetch('ajax/stats.php')
        .then(response => response.json())
        .then(data => {
            document.querySelector('[data-stat="agendamentos_hoje"]').textContent = data.agendamentos_hoje;
            document.querySelector('[data-stat="conversas_ativas"]').textContent = data.conversas_ativas;
        });
}, 30000);
</script>

</body>
</html> 
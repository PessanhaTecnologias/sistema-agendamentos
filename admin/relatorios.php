<?php
require_once 'auth.php';

// Verifica se o usuário está logado e tem permissão de admin
if (!verificarLogin() || !verificarPermissao('admin')) {
    header('Location: login.php');
    exit;
}

// Período do relatório (padrão: último mês)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

try {
    // Estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_agendamentos,
            COUNT(CASE WHEN status = 'confirmado' THEN 1 END) as confirmados,
            COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados,
            COUNT(CASE WHEN status = 'concluido' THEN 1 END) as concluidos,
            ROUND(AVG(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) * 100, 1) as taxa_confirmacao
        FROM agendamentos
        WHERE data_agendamento BETWEEN ? AND ?
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $estatisticas = $stmt->fetch();

    // Agendamentos por local
    $stmt = $pdo->prepare("
        SELECT 
            l.nome as local,
            COUNT(*) as total,
            COUNT(CASE WHEN a.status = 'confirmado' THEN 1 END) as confirmados,
            COUNT(CASE WHEN a.status = 'cancelado' THEN 1 END) as cancelados,
            ROUND(AVG(CASE WHEN a.status = 'confirmado' THEN 1 ELSE 0 END) * 100, 1) as taxa_confirmacao
        FROM agendamentos a
        JOIN locais l ON a.local_id = l.id
        WHERE a.data_agendamento BETWEEN ? AND ?
        GROUP BY l.id, l.nome
        ORDER BY total DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $por_local = $stmt->fetchAll();

    // Agendamentos por dia da semana
    $stmt = $pdo->prepare("
        SELECT 
            DAYOFWEEK(data_agendamento) as dia_semana,
            COUNT(*) as total
        FROM agendamentos
        WHERE data_agendamento BETWEEN ? AND ?
        GROUP BY DAYOFWEEK(data_agendamento)
        ORDER BY dia_semana
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $por_dia_semana = $stmt->fetchAll();

    // Horários mais populares
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(hora_agendamento) as hora,
            COUNT(*) as total
        FROM agendamentos
        WHERE data_agendamento BETWEEN ? AND ?
        GROUP BY HOUR(hora_agendamento)
        ORDER BY hora
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $por_hora = $stmt->fetchAll();

    // Atendentes mais produtivos
    $stmt = $pdo->prepare("
        SELECT 
            u.nome as atendente,
            COUNT(*) as total_atendimentos,
            COUNT(CASE WHEN a.status = 'confirmado' THEN 1 END) as confirmados,
            ROUND(AVG(CASE WHEN a.status = 'confirmado' THEN 1 ELSE 0 END) * 100, 1) as taxa_confirmacao
        FROM agendamentos a
        JOIN usuarios u ON a.atendente_id = u.id
        WHERE a.data_agendamento BETWEEN ? AND ?
        GROUP BY u.id, u.nome
        ORDER BY total_atendimentos DESC
    ");
    $stmt->execute([$data_inicio, $data_fim]);
    $por_atendente = $stmt->fetchAll();

} catch (PDOException $e) {
    $erro = "Erro ao gerar relatórios: " . $e->getMessage();
}

// Função auxiliar para traduzir dia da semana
function traduzirDiaSemana($dia) {
    return [
        1 => 'Domingo',
        2 => 'Segunda',
        3 => 'Terça',
        4 => 'Quarta',
        5 => 'Quinta',
        6 => 'Sexta',
        7 => 'Sábado'
    ][$dia] ?? '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Relatórios</h1>
            
            <!-- Filtro de período -->
            <form method="GET" class="d-flex gap-2">
                <div>
                    <label class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
                </div>
                <div>
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
                </div>
                <div class="d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php else: ?>
            <!-- Resumo -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Total de Agendamentos</h6>
                            <h2 class="card-text"><?= number_format($estatisticas['total_agendamentos']) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Confirmados</h6>
                            <h2 class="card-text text-success"><?= number_format($estatisticas['confirmados']) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Cancelados</h6>
                            <h2 class="card-text text-danger"><?= number_format($estatisticas['cancelados']) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Taxa de Confirmação</h6>
                            <h2 class="card-text text-primary"><?= number_format($estatisticas['taxa_confirmacao'], 1) ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Agendamentos por Local -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Agendamentos por Local</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Local</th>
                                            <th>Total</th>
                                            <th>Confirmados</th>
                                            <th>Cancelados</th>
                                            <th>Taxa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($por_local as $local): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($local['local']) ?></td>
                                                <td><?= number_format($local['total']) ?></td>
                                                <td class="text-success"><?= number_format($local['confirmados']) ?></td>
                                                <td class="text-danger"><?= number_format($local['cancelados']) ?></td>
                                                <td><?= number_format($local['taxa_confirmacao'], 1) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agendamentos por Dia da Semana -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Agendamentos por Dia da Semana</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoDiaSemana"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Horários Mais Populares -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Horários Mais Populares</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoHorarios"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Desempenho dos Atendentes -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Desempenho dos Atendentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Atendente</th>
                                            <th>Total</th>
                                            <th>Confirmados</th>
                                            <th>Taxa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($por_atendente as $atendente): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($atendente['atendente']) ?></td>
                                                <td><?= number_format($atendente['total_atendimentos']) ?></td>
                                                <td class="text-success"><?= number_format($atendente['confirmados']) ?></td>
                                                <td><?= number_format($atendente['taxa_confirmacao'], 1) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de Dias da Semana
        new Chart(document.getElementById('graficoDiaSemana'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map('traduzirDiaSemana', array_column($por_dia_semana, 'dia_semana'))) ?>,
                datasets: [{
                    label: 'Agendamentos',
                    data: <?= json_encode(array_column($por_dia_semana, 'total')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Gráfico de Horários
        new Chart(document.getElementById('graficoHorarios'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($h) { 
                    return sprintf('%02d:00', $h['hora']); 
                }, $por_hora)) ?>,
                datasets: [{
                    label: 'Agendamentos',
                    data: <?= json_encode(array_column($por_hora, 'total')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 
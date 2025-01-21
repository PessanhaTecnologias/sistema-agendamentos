<?php
if (!isset($_SESSION)) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Define a página atual
$pagina_atual = basename($_SERVER['PHP_SELF']);

// Carrega notificações não lidas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM notificacoes 
        WHERE usuario_id = ? AND lida = FALSE
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $notificacoes_nao_lidas = $stmt->fetchColumn();
} catch (PDOException $e) {
    $notificacoes_nao_lidas = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Painel Administrativo' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= ADMIN_URL ?>/assets/css/admin.css" rel="stylesheet">
    
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link href="<?= $style ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-calendar-check me-2"></i>
                Painel Administrativo
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $pagina_atual === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $pagina_atual === 'agendamentos.php' ? 'active' : '' ?>" href="agendamentos.php">
                            <i class="bi bi-calendar-check"></i> Agendamentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $pagina_atual === 'locais.php' ? 'active' : '' ?>" href="locais.php">
                            <i class="bi bi-geo-alt"></i> Locais
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $pagina_atual === 'conversas.php' ? 'active' : '' ?>" href="conversas.php">
                            <i class="bi bi-chat-dots"></i> Conversas
                        </a>
                    </li>
                    <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $pagina_atual === 'usuarios.php' ? 'active' : '' ?>" href="usuarios.php">
                                <i class="bi bi-people"></i> Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $pagina_atual === 'relatorios.php' ? 'active' : '' ?>" href="relatorios.php">
                                <i class="bi bi-graph-up"></i> Relatórios
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <!-- Alternador de tema -->
                    <li class="nav-item">
                        <button class="nav-link btn btn-link" onclick="alternarTema()">
                            <i class="bi bi-sun-fill" id="tema-icone"></i>
                        </button>
                    </li>
                    
                    <!-- Notificações -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <?php if ($notificacoes_nao_lidas > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $notificacoes_nao_lidas ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Notificações</h6>
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT *
                                    FROM notificacoes
                                    WHERE usuario_id = ?
                                    ORDER BY criado_em DESC
                                    LIMIT 5
                                ");
                                $stmt->execute([$_SESSION['usuario_id']]);
                                $notificacoes = $stmt->fetchAll();
                                
                                if (empty($notificacoes)) {
                                    echo '<div class="dropdown-item text-muted">Nenhuma notificação</div>';
                                } else {
                                    foreach ($notificacoes as $notificacao) {
                                        echo '<a class="dropdown-item' . ($notificacao['lida'] ? '' : ' fw-bold') . '" href="' . 
                                             ($notificacao['link'] ?? '#') . '">' . 
                                             htmlspecialchars($notificacao['titulo']) . '</a>';
                                    }
                                    echo '<div class="dropdown-divider"></div>';
                                    echo '<a class="dropdown-item text-center" href="notificacoes.php">Ver todas</a>';
                                }
                            } catch (PDOException $e) {
                                echo '<div class="dropdown-item text-danger">Erro ao carregar notificações</div>';
                            }
                            ?>
                        </div>
                    </li>
                    
                    <!-- Menu do usuário -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <?php if (!empty($_SESSION['usuario_foto'])): ?>
                                <img src="<?= UPLOAD_DIR . $_SESSION['usuario_foto'] ?>" 
                                     class="rounded-circle me-1" 
                                     width="24" height="24" 
                                     alt="Foto do usuário">
                            <?php else: ?>
                                <i class="bi bi-person-circle"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="perfil.php">
                                    <i class="bi bi-person"></i> Meu Perfil
                                </a>
                            </li>
                            <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item" href="configuracoes.php">
                                        <i class="bi bi-gear"></i> Configurações
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Container principal -->
    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['flash_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>
    </div>
</body>
</html> 
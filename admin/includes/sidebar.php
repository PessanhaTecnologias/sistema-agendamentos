<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'agendamentos.php' ? 'active' : ''; ?>" href="agendamentos.php">
                    <i class="fas fa-calendar-alt"></i>
                    Agendamentos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lojas.php' ? 'active' : ''; ?>" href="lojas.php">
                    <i class="fas fa-store"></i>
                    Lojas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'conversas.php' ? 'active' : ''; ?>" href="conversas.php">
                    <i class="fas fa-comments"></i>
                    Conversas
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Relatórios</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorio_diario.php' ? 'active' : ''; ?>" href="relatorio_diario.php">
                    <i class="fas fa-chart-bar"></i>
                    Relatório Diário
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'relatorio_mensal.php' ? 'active' : ''; ?>" href="relatorio_mensal.php">
                    <i class="fas fa-chart-line"></i>
                    Relatório Mensal
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Configurações</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                    <i class="fas fa-users"></i>
                    Usuários
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : ''; ?>" href="configuracoes.php">
                    <i class="fas fa-cog"></i>
                    Configurações
                </a>
            </li>
        </ul>
    </div>
</nav> 
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <span class="text-muted">© <?php echo date('Y'); ?> Sistema de Agendamentos. Todos os direitos reservados.</span>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/admin.js"></script>

    <!-- Container de notificações toast -->
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php if (isset($_GET['msg'])): ?>
        <script>
            mostrarNotificacao(
                '<?= match($_GET['msg']) {
                    'salvo' => 'Dados salvos com sucesso!',
                    'excluido' => 'Item excluído com sucesso!',
                    'erro' => 'Erro ao processar a solicitação.',
                    'cancelado' => 'Agendamento cancelado com sucesso!',
                    'confirmado' => 'Agendamento confirmado com sucesso!',
                    'concluido' => 'Agendamento concluído com sucesso!',
                    default => $_GET['msg']
                } ?>',
                '<?= isset($_GET['erro']) ? 'danger' : 'success' ?>'
            );
        </script>
    <?php endif; ?>

    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 
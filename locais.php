<?php
session_start();
require_once 'config/database.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar exclusão de local
if (isset($_POST['excluir_local'])) {
    $id = $_POST['local_id'];
    $stmt = $pdo->prepare("UPDATE locais SET ativo = FALSE WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: locais.php?msg=local_excluido');
    exit;
}

// Processar adição/edição de local
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
    
    header('Location: locais.php?msg=local_salvo');
    exit;
}

// Buscar todos os locais ativos
$stmt = $pdo->query("SELECT * FROM locais WHERE ativo = TRUE ORDER BY cidade, nome");
$locais = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Locais - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gerenciamento de Locais</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalLocal">
                Adicionar Local
            </button>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['msg']) {
                        case 'local_salvo':
                            echo 'Local salvo com sucesso!';
                            break;
                        case 'local_excluido':
                            echo 'Local excluído com sucesso!';
                            break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>Horários</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locais as $local): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($local['nome']); ?></td>
                            <td><?php echo htmlspecialchars($local['cidade']); ?></td>
                            <td><?php echo htmlspecialchars($local['horarios']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" 
                                        onclick="editarLocal(<?php echo htmlspecialchars(json_encode($local)); ?>)">
                                    Editar
                                </button>
                                <form method="POST" class="d-inline" 
                                      onsubmit="return confirm('Tem certeza que deseja excluir este local?')">
                                    <input type="hidden" name="local_id" value="<?php echo $local['id']; ?>">
                                    <button type="submit" name="excluir_local" class="btn btn-sm btn-danger">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Adicionar/Editar Local -->
    <div class="modal fade" id="modalLocal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Local</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="local_id" id="local_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" id="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cidade</label>
                            <select class="form-control" name="cidade" id="cidade" required>
                                <option value="Guarapari">Guarapari</option>
                                <option value="Vila Velha">Vila Velha</option>
                                <option value="Viana">Viana</option>
                                <option value="Serra">Serra</option>
                                <option value="Cariacica">Cariacica</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Horários</label>
                            <input type="text" class="form-control" name="horarios" id="horarios" 
                                   placeholder="08:30-16:40" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="salvar_local" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarLocal(local) {
            document.getElementById('local_id').value = local.id;
            document.getElementById('nome').value = local.nome;
            document.getElementById('cidade').value = local.cidade;
            document.getElementById('horarios').value = local.horarios;
            
            new bootstrap.Modal(document.getElementById('modalLocal')).show();
        }
    </script>
</body>
</html> 
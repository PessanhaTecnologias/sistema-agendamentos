<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Processar edição de mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['editar_mensagem'])) {
        $id = $_POST['mensagem_id'];
        $mensagem = $_POST['mensagem'];
        
        $stmt = $pdo->prepare("UPDATE mensagens_padrao SET mensagem = ? WHERE id = ?");
        $stmt->execute([$mensagem, $id]);
        
        header('Location: mensagens.php?msg=mensagem_atualizada');
        exit;
    }
}

// Buscar todas as mensagens padrão
$stmt = $pdo->query("SELECT * FROM mensagens_padrao ORDER BY tipo");
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Array com descrições amigáveis dos tipos de mensagem
$descricoes = [
    'saudacao' => 'Mensagem Inicial',
    'pedir_nome' => 'Solicitar Nome',
    'pedir_telefone' => 'Solicitar Telefone',
    'pedir_cidade' => 'Escolha da Cidade',
    'pedir_loja_guarapari' => 'Lojas de Guarapari',
    'pedir_loja_vila_velha' => 'Lojas de Vila Velha',
    'pedir_loja_viana' => 'Lojas de Viana',
    'pedir_loja_serra' => 'Lojas de Serra',
    'pedir_loja_cariacica' => 'Lojas de Cariacica',
    'pedir_horario' => 'Seleção de Horário',
    'confirmar_agendamento' => 'Confirmação do Agendamento',
    'confirmacao_final' => 'Mensagem Final',
    'opcoes_edicao' => 'Menu de Edição',
    'editar_nome' => 'Editar Nome',
    'editar_telefone' => 'Editar Telefone',
    'editar_cidade' => 'Editar Cidade',
    'voltar_confirmacao' => 'Voltar para Confirmação'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Mensagens - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview {
            white-space: pre-wrap;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h2>Gerenciar Mensagens do Chat</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'mensagem_atualizada'): ?>
            <div class="alert alert-success">
                Mensagem atualizada com sucesso!
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="accordion" id="mensagensAccordion">
                    <?php foreach ($mensagens as $msg): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?php echo $msg['id']; ?>">
                                    <?php echo htmlspecialchars($descricoes[$msg['tipo']] ?? $msg['tipo']); ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $msg['id']; ?>" 
                                 class="accordion-collapse collapse" 
                                 data-bs-parent="#mensagensAccordion">
                                <div class="accordion-body">
                                    <form method="POST" class="mensagem-form">
                                        <input type="hidden" name="mensagem_id" value="<?php echo $msg['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Tipo</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($msg['tipo']); ?>" readonly>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Mensagem</label>
                                            <textarea class="form-control" name="mensagem" 
                                                      rows="4" onkeyup="atualizarPreview(this)"><?php 
                                                echo htmlspecialchars($msg['mensagem']); 
                                            ?></textarea>
                                        </div>

                                        <div class="preview mb-3">
                                            <?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?>
                                        </div>

                                        <div class="mb-3">
                                            <button type="submit" name="editar_mensagem" 
                                                    class="btn btn-primary">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function atualizarPreview(textarea) {
            const preview = textarea.parentElement.nextElementSibling;
            preview.innerHTML = textarea.value.replace(/\n/g, '<br>');
        }
    </script>
</body>
</html> 
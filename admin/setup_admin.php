<?php
require_once __DIR__ . '/../config/config.php';

try {
    // Verifica se o usuário admin existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute(['admin@exemplo.com']);
    $admin = $stmt->fetch();
    
    // Hash da senha admin123
    $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    if ($admin) {
        // Atualiza o usuário existente
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nome = 'Administrador',
                senha = ?,
                tipo = 'admin',
                ativo = TRUE
            WHERE id = ?
        ");
        $stmt->execute([$senha_hash, $admin['id']]);
        echo "Usuário admin atualizado com sucesso!\n";
    } else {
        // Cria um novo usuário admin
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, tipo, ativo)
            VALUES ('Administrador', 'admin@exemplo.com', ?, 'admin', TRUE)
        ");
        $stmt->execute([$senha_hash]);
        echo "Usuário admin criado com sucesso!\n";
    }
    
    echo "\nAcesso ao painel administrativo:\n";
    echo "URL: http://localhost/Projeto/admin/\n";
    echo "Email: admin@exemplo.com\n";
    echo "Senha: admin123\n";
    
} catch (PDOException $e) {
    echo "Erro ao configurar usuário admin: " . $e->getMessage() . "\n";
    exit(1);
} 
<?php
require_once __DIR__ . '/../config/config.php';

try {
    // Obtém a conexão
    $pdo = getConnection();
    
    // Verifica se o usuário admin existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@exemplo.com']);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "Usuário admin não encontrado. Criando...\n";
        
        // Cria o usuário admin
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, tipo) 
            VALUES (?, ?, ?, ?)
        ");
        
        $senha = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt->execute([
            'Administrador',
            'admin@exemplo.com',
            $senha,
            'admin'
        ]);
        
        echo "Usuário admin criado com sucesso!\n";
    } else {
        echo "Usuário admin encontrado. Atualizando senha...\n";
        
        // Atualiza a senha do admin
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET senha = ?, 
                ativo = TRUE 
            WHERE email = ?
        ");
        
        $senha = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt->execute([
            $senha,
            'admin@exemplo.com'
        ]);
        
        echo "Senha do admin atualizada com sucesso!\n";
    }
    
    echo "\nDados de acesso:\n";
    echo "URL: http://localhost/Projeto/admin/\n";
    echo "Email: admin@exemplo.com\n";
    echo "Senha: admin123\n";
    
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage() . "\n");
} 
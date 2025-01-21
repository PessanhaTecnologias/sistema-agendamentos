<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    // Atualiza último acesso
    try {
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET ultimo_acesso = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
    } catch (PDOException $e) {
        error_log("Erro ao atualizar último acesso: " . $e->getMessage());
    }
    
    return true;
}

function fazerLogin($email, $senha) {
    try {
        global $pdo;
        
        // Busca o usuário
        $stmt = $pdo->prepare("
            SELECT id, nome, senha, tipo 
            FROM usuarios 
            WHERE email = ? AND ativo = TRUE 
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        // Verifica se encontrou o usuário e se a senha está correta
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Inicia a sessão se ainda não foi iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Regenera o ID da sessão por segurança
            session_regenerate_id(true);
            
            // Salva os dados do usuário na sessão
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            $_SESSION['ultimo_acesso'] = time();
            
            // Registra o login
            registrarAcesso($usuario['id'], 'login');
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao fazer login: " . $e->getMessage());
        return false;
    }
}

function fazerLogout() {
    // Registra o logout
    if (isset($_SESSION['usuario_id'])) {
        registrarAcesso($_SESSION['usuario_id'], 'logout');
    }
    
    // Limpa e destrói a sessão
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    
    // Redireciona para a página de login
    header('Location: login.php');
    exit;
}

function registrarAcesso($usuario_id, $tipo) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("
            INSERT INTO log_acessos (usuario_id, tipo, ip)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $usuario_id,
            $tipo,
            getClientIP()
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar acesso: " . $e->getMessage());
    }
}

function verificarPermissao($permissao_necessaria) {
    if (!isset($_SESSION['usuario_tipo'])) {
        return false;
    }
    
    $hierarquia = [
        'admin' => 3,
        'gerente' => 2,
        'atendente' => 1
    ];
    
    $nivel_usuario = isset($hierarquia[$_SESSION['usuario_tipo']]) ? $hierarquia[$_SESSION['usuario_tipo']] : 0;
    $nivel_necessario = isset($hierarquia[$permissao_necessaria]) ? $hierarquia[$permissao_necessaria] : 0;
    
    return $nivel_usuario >= $nivel_necessario;
} 
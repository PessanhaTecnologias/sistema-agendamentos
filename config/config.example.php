<?php
// Configurações gerais
define('SITE_URL', 'http://localhost/Projeto');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_agendamentos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações de timezone
date_default_timezone_set(TIMEZONE);

// Configurações de sessão
ini_set('session.gc_maxlifetime', 3600); // 1 hora
ini_set('session.cookie_lifetime', 3600); // 1 hora
session_set_cookie_params(3600, '/', '', false, true);

// Configurações de erro em desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_TIME', 3600); // 1 hora

// Configurações de log
define('LOG_DIR', __DIR__ . '/../logs/');

// Configurações de email
define('SMTP_HOST', 'smtp.exemplo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'naoresponder@exemplo.com');
define('SMTP_PASS', 'sua_senha');
define('SMTP_FROM', 'naoresponder@exemplo.com');
define('SMTP_FROM_NAME', 'Sistema de Agendamentos');

// Configurações de segurança
define('HASH_COST', 12); // Custo do bcrypt
define('TOKEN_EXPIRATION', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5); // Máximo de tentativas de login
define('LOCKOUT_TIME', 900); // 15 minutos 
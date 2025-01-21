<?php
// Status de agendamento
define('STATUS_AGENDADO', 'agendado');
define('STATUS_CONFIRMADO', 'confirmado');
define('STATUS_CANCELADO', 'cancelado');
define('STATUS_CONCLUIDO', 'concluido');

// Status de conversa
define('CONVERSA_EM_ANDAMENTO', 'em_andamento');
define('CONVERSA_FINALIZADA', 'finalizada');
define('CONVERSA_CANCELADA', 'cancelada');

// Tipos de usuário
define('TIPO_ADMIN', 'admin');
define('TIPO_GERENTE', 'gerente');
define('TIPO_ATENDENTE', 'atendente');

// Tipos de log
define('LOG_LOGIN', 'login');
define('LOG_LOGOUT', 'logout');
define('LOG_AGENDAMENTO', 'agendamento');
define('LOG_CANCELAMENTO', 'cancelamento');

// Configurações de paginação
define('ITENS_POR_PAGINA', 10);
define('MAX_PAGINAS_NAVEGACAO', 5);

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_DIR', __DIR__ . '/../uploads');

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache');
define('CACHE_TIME', 3600); // 1 hora

// Configurações de email
define('EMAIL_FROM', 'sistema@exemplo.com');
define('EMAIL_FROM_NAME', 'Sistema de Agendamentos');
define('EMAIL_REPLY_TO', 'naoresponda@exemplo.com');

// Configurações de segurança
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutos
define('SESSION_TIMEOUT', 1800); // 30 minutos
define('PASSWORD_MIN_LENGTH', 8);

// Configurações de horário
define('HORARIO_INICIO', '08:00');
define('HORARIO_FIM', '18:00');
define('INTERVALO_AGENDAMENTO', 30); // minutos

// Configurações de notificação
define('NOTIFICAR_AGENDAMENTO', true);
define('NOTIFICAR_CANCELAMENTO', true);
define('NOTIFICAR_LEMBRETE', true);
define('TEMPO_LEMBRETE', 24); // horas antes do agendamento

// Mensagens do sistema
define('MSG_ERRO_GENERICO', 'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.');
define('MSG_ACESSO_NEGADO', 'Você não tem permissão para acessar esta área.');
define('MSG_SESSAO_EXPIRADA', 'Sua sessão expirou. Por favor, faça login novamente.');
define('MSG_DADOS_INVALIDOS', 'Os dados informados são inválidos.'); 
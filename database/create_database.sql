-- Cria o banco de dados se não existir
CREATE DATABASE IF NOT EXISTS sistema_agendamentos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Usa o banco de dados
USE sistema_agendamentos;

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    tipo ENUM('text', 'number', 'boolean', 'json') NOT NULL DEFAULT 'text',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'gerente', 'atendente') NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    token_reset_senha VARCHAR(100),
    expiracao_token DATETIME,
    ultimo_acesso DATETIME,
    foto VARCHAR(255),
    telefone VARCHAR(20),
    local_id INT,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de locais
CREATE TABLE IF NOT EXISTS locais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,
    endereco TEXT NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(100),
    horario_abertura TIME NOT NULL,
    horario_fechamento TIME NOT NULL,
    intervalo_agendamento INT NOT NULL DEFAULT 30, -- em minutos
    capacidade_simultanea INT NOT NULL DEFAULT 1,
    dias_funcionamento JSON, -- array com dias da semana [1,2,3,4,5]
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Adiciona a foreign key em usuarios após criar a tabela locais
ALTER TABLE usuarios
ADD CONSTRAINT fk_usuario_local
FOREIGN KEY (local_id) REFERENCES locais(id)
ON DELETE SET NULL;

-- Tabela de feriados
CREATE TABLE IF NOT EXISTS feriados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    local_id INT,
    data DATE NOT NULL,
    descricao VARCHAR(100) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (local_id) REFERENCES locais(id) ON DELETE CASCADE
);

-- Tabela de agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    local_id INT NOT NULL,
    nome_cliente VARCHAR(100) NOT NULL,
    email_cliente VARCHAR(100) NOT NULL,
    telefone_cliente VARCHAR(20) NOT NULL,
    data_agendamento DATE NOT NULL,
    hora_agendamento TIME NOT NULL,
    status ENUM('agendado', 'confirmado', 'cancelado', 'concluido') NOT NULL DEFAULT 'agendado',
    observacoes TEXT,
    atendente_id INT,
    gerente_id INT,
    token_cancelamento VARCHAR(100),
    token_confirmacao VARCHAR(100),
    lembrete_enviado BOOLEAN NOT NULL DEFAULT FALSE,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (local_id) REFERENCES locais(id),
    FOREIGN KEY (atendente_id) REFERENCES usuarios(id),
    FOREIGN KEY (gerente_id) REFERENCES usuarios(id)
);

-- Tabela de conversas
CREATE TABLE IF NOT EXISTS conversas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(100) NOT NULL,
    email_cliente VARCHAR(100) NOT NULL,
    telefone_cliente VARCHAR(20) NOT NULL,
    status ENUM('em_andamento', 'finalizada', 'cancelada') NOT NULL DEFAULT 'em_andamento',
    atendente_id INT,
    agendamento_id INT,
    ultima_interacao DATETIME,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (atendente_id) REFERENCES usuarios(id),
    FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id)
);

-- Tabela de mensagens
CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversa_id INT NOT NULL,
    remetente ENUM('cliente', 'atendente', 'sistema') NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('texto', 'imagem', 'arquivo') NOT NULL DEFAULT 'texto',
    arquivo_url VARCHAR(255),
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    data_leitura DATETIME,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE
);

-- Tabela de mensagens padrão
CREATE TABLE IF NOT EXISTS mensagens_padrao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    mensagem TEXT NOT NULL,
    categoria VARCHAR(50),
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de logs de acesso
CREATE TABLE IF NOT EXISTS log_acessos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('login', 'logout', 'falha_login') NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent TEXT,
    detalhes TEXT,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de logs de agendamentos
CREATE TABLE IF NOT EXISTS log_agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT NOT NULL,
    usuario_id INT,
    acao VARCHAR(50) NOT NULL,
    detalhes TEXT,
    dados_anteriores JSON,
    dados_novos JSON,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    data_leitura DATETIME,
    link VARCHAR(255),
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de emails enviados
CREATE TABLE IF NOT EXISTS emails_enviados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destinatario VARCHAR(100) NOT NULL,
    assunto VARCHAR(200) NOT NULL,
    corpo TEXT NOT NULL,
    status ENUM('pendente', 'enviado', 'erro') NOT NULL DEFAULT 'pendente',
    erro TEXT,
    tentativas INT NOT NULL DEFAULT 0,
    proxima_tentativa DATETIME,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insere configurações padrão
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES
('site_titulo', 'Sistema de Agendamentos', 'Título do site', 'text'),
('site_descricao', 'Sistema completo para gestão de agendamentos', 'Descrição do site', 'text'),
('email_remetente', 'naoresponder@exemplo.com', 'Email remetente para notificações', 'text'),
('nome_remetente', 'Sistema de Agendamentos', 'Nome remetente para notificações', 'text'),
('intervalo_lembrete', '24', 'Horas antes para enviar lembrete', 'number'),
('manutencao', 'false', 'Sistema em manutenção', 'boolean'),
('tema_cores', '{"primary":"#007bff","secondary":"#6c757d"}', 'Cores do tema', 'json');

-- Insere mensagens padrão
INSERT INTO mensagens_padrao (titulo, mensagem, categoria) VALUES
('Boas-vindas', 'Olá! Como posso ajudar?', 'atendimento'),
('Agendamento Confirmado', 'Seu agendamento foi confirmado com sucesso!', 'agendamento'),
('Fora do Horário', 'Nosso horário de atendimento é de segunda a sexta, das 8h às 18h.', 'atendimento');

-- Insere locais iniciais
INSERT INTO locais (nome, cidade, estado, endereco, telefone, email, horario_abertura, horario_fechamento, dias_funcionamento) VALUES
('Unidade Centro', 'São Paulo', 'SP', 'Rua Principal, 123 - Centro', '(11) 3333-4444', 'centro@exemplo.com', '08:00', '18:00', '[1,2,3,4,5]'),
('Unidade Zona Sul', 'São Paulo', 'SP', 'Av. Exemplo, 456 - Moema', '(11) 4444-5555', 'zonasul@exemplo.com', '09:00', '19:00', '[1,2,3,4,5,6]');

-- Insere usuário admin
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Administrador', 'admin@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); 
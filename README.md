# Sistema de Agendamentos

Sistema completo para gerenciamento de agendamentos, desenvolvido em PHP com MySQL.

## 🚀 Funcionalidades

- Gerenciamento de agendamentos
- Múltiplos locais de atendimento
- Sistema de chat integrado
- Notificações em tempo real
- Relatórios e estatísticas
- Painel administrativo completo
- Sistema de permissões (Admin, Gerente, Atendente)
- Tema claro/escuro

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Extensões PHP:
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - session

## 🔧 Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/sistema-agendamentos.git
```

2. Configure o banco de dados:
   - Crie um banco de dados MySQL
   - Importe o arquivo `database/create_database.sql`

3. Configure o arquivo de ambiente:
   - Copie `config/config.example.php` para `config/config.php`
   - Ajuste as configurações conforme seu ambiente

4. Configure as permissões:
```bash
chmod 777 uploads/
chmod 777 cache/
chmod 777 logs/
```

5. Acesse o sistema:
   - URL: `http://seu-dominio/admin`
   - Email: `admin@exemplo.com`
   - Senha: `admin123`

## 🗂️ Estrutura do Projeto

```
├── admin/              # Painel administrativo
├── assets/            # Arquivos estáticos (CSS, JS, imagens)
├── cache/             # Cache do sistema
├── config/            # Arquivos de configuração
├── database/          # Scripts do banco de dados
├── includes/          # Arquivos incluídos
├── logs/              # Logs do sistema
└── uploads/           # Arquivos enviados
```

## 🔒 Segurança

- Senhas criptografadas com bcrypt
- Proteção contra SQL Injection
- Proteção contra XSS
- Controle de sessão
- Tokens CSRF
- Validação de dados

## 📱 Responsividade

O sistema é totalmente responsivo, adaptando-se a diferentes tamanhos de tela:
- Desktop
- Tablet
- Smartphone

## 🛠️ Tecnologias Utilizadas

- PHP
- MySQL
- Bootstrap 5
- JavaScript
- Chart.js
- Font Awesome

## 📄 Licença

Este projeto está sob a licença MIT - veja o arquivo [LICENSE.md](LICENSE.md) para detalhes

## ✒️ Autor

* **Seu Nome** - *Desenvolvimento* - [seu-usuario](https://github.com/seu-usuario)

## 🎁 Agradecimentos

* Agradecimento especial a todos que contribuíram para o projeto
* Este projeto foi desenvolvido com o objetivo de facilitar o gerenciamento de agendamentos 
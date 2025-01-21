<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat de Agendamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .chat-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
        }
        .mensagem {
            margin: 10px 0;
            padding: 10px;
            border-radius: 10px;
            white-space: pre-wrap; /* Preserva quebras de linha */
        }
        .recebida {
            background-color: #f1f1f1;
            margin-right: 20%;
        }
        .enviada {
            background-color: #dcf8c6;
            margin-left: 20%;
        }
        #historico-chat {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div id="historico-chat"></div>
        
        <div class="input-group">
            <input type="text" id="mensagem" class="form-control" placeholder="Digite sua mensagem">
            <button class="btn btn-primary" onclick="enviarMensagem()">Enviar</button>
        </div>
    </div>

    <script>
        let historicoChat = document.getElementById('historico-chat');
        let inputMensagem = document.getElementById('mensagem');
        let telefoneAtual = 'novo'; // Começa como 'novo' e será atualizado quando o usuário informar o telefone

        function adicionarMensagem(mensagem, tipo) {
            let div = document.createElement('div');
            div.className = `mensagem ${tipo}`;
            div.textContent = mensagem;
            historicoChat.appendChild(div);
            historicoChat.scrollTop = historicoChat.scrollHeight;
        }

        function enviarMensagem() {
            let mensagem = inputMensagem.value.trim();
            
            if (!mensagem) {
                alert('Por favor, digite uma mensagem');
                return;
            }

            // Adiciona a mensagem do usuário ao chat
            adicionarMensagem(mensagem, 'enviada');
            
            // Limpa o campo de mensagem
            inputMensagem.value = '';

            // Se for um telefone válido, atualiza o telefoneAtual
            if (mensagem.match(/^[0-9]{10,11}$/)) {
                telefoneAtual = mensagem;
            }

            // Envia a mensagem para o servidor
            fetch('chat_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `telefone=${encodeURIComponent(telefoneAtual)}&mensagem=${encodeURIComponent(mensagem)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.resposta) {
                    adicionarMensagem(data.resposta, 'recebida');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                adicionarMensagem('Erro ao processar mensagem. Por favor, tente novamente.', 'recebida');
            });
        }

        // Inicia o chat com a mensagem de saudação
        window.onload = function() {
            fetch('chat_processor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'telefone=novo&mensagem=inicio'
            })
            .then(response => response.json())
            .then(data => {
                if (data.resposta) {
                    adicionarMensagem(data.resposta, 'recebida');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                adicionarMensagem('Erro ao iniciar o chat. Por favor, recarregue a página.', 'recebida');
            });
        };

        // Permite enviar mensagem com Enter
        inputMensagem.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                enviarMensagem();
            }
        });
    </script>
</body>
</html> 
<?php
class ChatController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function processarMensagem($telefone, $mensagem) {
        // Se for uma nova conversa
        if ($telefone === 'novo' && $mensagem === 'inicio') {
            $telefoneTemp = 'temp_' . uniqid();
            $stmt = $this->pdo->prepare("
                INSERT INTO conversas (telefone, etapa, status) 
                VALUES (?, 'saudacao', 'em_andamento')
            ");
            $stmt->execute([$telefoneTemp]);
            
            $conversa = $this->getOuCriarConversa($telefoneTemp);
            $resposta = $this->getMensagemPadrao('saudacao');
            
            if ($conversa) {
                $this->registrarMensagem($conversa['id'], 'enviada', $resposta);
            }
            
            return $resposta;
        }

        // Busca conversa existente
        $conversa = $this->getOuCriarConversa($telefone);
        
        // Registra a mensagem recebida
        if ($conversa) {
            $this->registrarMensagem($conversa['id'], 'recebida', $mensagem);
            
            // Se estamos na etapa do telefone e é um telefone válido
            if ($conversa['etapa'] === 'telefone') {
                $telefoneNormalizado = preg_replace('/[^0-9]/', '', $mensagem);
                if ($this->validarTelefone($telefoneNormalizado)) {
                    // Atualiza o telefone na conversa
                    $stmt = $this->pdo->prepare("
                        UPDATE conversas 
                        SET telefone = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$telefoneNormalizado, $conversa['id']]);
                }
            }
            
            // Processa a resposta baseada na etapa atual
            $resposta = $this->processarEtapa($conversa, $mensagem);
            
            // Registra a resposta enviada
            if ($resposta) {
                $this->registrarMensagem($conversa['id'], 'enviada', $resposta);
            }
            
            return $resposta;
        }
        
        // Se não encontrou conversa, inicia uma nova
        return $this->getMensagemPadrao('saudacao');
    }
    
    private function getOuCriarConversa($telefone) {
        // Busca conversa existente
        $stmt = $this->pdo->prepare("
            SELECT * FROM conversas 
            WHERE (telefone = ? OR telefone LIKE 'temp_%') 
            AND status = 'em_andamento'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$telefone]);
        $conversa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conversa) {
            return $conversa;
        }
        
        // Se não encontrou conversa em andamento, cria uma nova
        $stmt = $this->pdo->prepare("
            INSERT INTO conversas (telefone, etapa, status) 
            VALUES (?, 'saudacao', 'em_andamento')
        ");
        $stmt->execute([$telefone]);
        
        $stmt = $this->pdo->prepare("SELECT * FROM conversas WHERE telefone = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$telefone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function processarEtapa($conversa, $mensagem) {
        if (!$conversa) {
            return $this->getMensagemPadrao('saudacao');
        }

        // Normaliza a mensagem (remove espaços extras e converte para minúsculo)
        $mensagemNormalizada = trim(strtolower($mensagem));
        
        switch ($conversa['etapa']) {
            case 'saudacao':
                // Aceita variações de "sim"
                if (in_array($mensagemNormalizada, ['sim', 'si', 's', 'yes', 'ok', 'confirmar'])) {
                    $this->atualizarEtapa($conversa['id'], 'nome');
                    return $this->getMensagemPadrao('pedir_nome');
                }
                return "Por favor, responda com 'Sim' para continuar.";
                
            case 'nome':
                if (strlen($mensagem) < 3) {
                    return "Por favor, digite seu nome completo.";
                }
                $this->atualizarDadosConversa($conversa['id'], ['nome_cliente' => $mensagem]);
                $this->atualizarEtapa($conversa['id'], 'telefone');
                return $this->getMensagemPadrao('pedir_telefone');
                
            case 'telefone':
                // Remove caracteres não numéricos do telefone
                $telefone = preg_replace('/[^0-9]/', '', $mensagem);
                if ($this->validarTelefone($telefone)) {
                    $this->atualizarDadosConversa($conversa['id'], [
                        'telefone' => $telefone
                    ]);
                    $this->atualizarEtapa($conversa['id'], 'cidade');
                    return $this->getMensagemPadrao('pedir_cidade');
                }
                return "Por favor, informe um telefone válido com DDD (ex: 27999999999)";
                
            case 'cidade':
                $cidades = ['1' => 'Guarapari', '2' => 'Vila Velha', '3' => 'Viana', '4' => 'Serra', '5' => 'Cariacica'];
                if (isset($cidades[$mensagem])) {
                    $this->atualizarDadosConversa($conversa['id'], ['cidade' => $cidades[$mensagem]]);
                    $this->atualizarEtapa($conversa['id'], 'loja');
                    return $this->getMensagemLojas($cidades[$mensagem]);
                }
                return "Por favor, escolha uma cidade digitando um número de 1 a 5.";
                
            case 'loja':
                $lojas = $this->getLojasDisponiveis($conversa['cidade']);
                if (isset($lojas[$mensagem - 1])) { // -1 porque o usuário digita 1, 2, 3...
                    $loja = $lojas[$mensagem - 1];
                    $this->atualizarDadosConversa($conversa['id'], [
                        'loja_id' => $loja['id']
                    ]);
                    $this->atualizarEtapa($conversa['id'], 'horario');
                    return $this->getMensagemPadrao('pedir_horario');
                }
                return "Por favor, escolha uma loja válida digitando o número correspondente.";
                
            case 'horario':
                $horarios = $this->getHorariosDisponiveis($conversa['loja_id']);
                
                if (isset($horarios[$mensagem])) {
                    $horario = $horarios[$mensagem];
                    $data = date('Y-m-d'); // Agenda para hoje
                    
                    $horario_completo = $data . ' ' . $horario . ':00';
                    
                    $this->atualizarDadosConversa($conversa['id'], [
                        'horario_escolhido' => $horario_completo
                    ]);
                    
                    $this->atualizarEtapa($conversa['id'], 'confirmacao');
                    
                    return $this->montarMensagemConfirmacao($conversa);
                }
                
                // Montar mensagem com horários disponíveis
                $mensagem = "Horários disponíveis:\n\n";
                foreach ($horarios as $numero => $hora) {
                    $mensagem .= $numero . "️⃣ " . $hora . "\n";
                }
                $mensagem .= "\nEscolha um número correspondente ao horário desejado.";
                
                return $mensagem;
                
            case 'confirmacao':
                if ($mensagem === '1') {
                    // Confirmar agendamento
                    $this->finalizarAgendamento($conversa);
                    $this->atualizarEtapa($conversa['id'], 'finalizado');
                    return $this->getMensagemPadrao('confirmacao_final');
                } elseif ($mensagem === '2') {
                    // Ir para edição
                    $this->atualizarEtapa($conversa['id'], 'edicao');
                    return $this->getMensagemPadrao('opcoes_edicao');
                }
                return "Por favor, escolha 1 para confirmar ou 2 para editar.";

            case 'edicao':
                switch ($mensagem) {
                    case '1': // Editar nome
                        $this->atualizarEtapa($conversa['id'], 'editar_nome');
                        return $this->getMensagemPadrao('editar_nome');
                    
                    case '2': // Editar telefone
                        $this->atualizarEtapa($conversa['id'], 'editar_telefone');
                        return $this->getMensagemPadrao('editar_telefone');
                    
                    case '3': // Editar cidade
                        $this->atualizarEtapa($conversa['id'], 'cidade');
                        return $this->getMensagemPadrao('editar_cidade');
                    
                    case '4': // Editar loja
                        $this->atualizarEtapa($conversa['id'], 'loja');
                        return $this->getMensagemLojas($conversa['cidade']);
                    
                    case '5': // Editar horário
                        $this->atualizarEtapa($conversa['id'], 'horario');
                        return $this->getHorariosDisponiveis($conversa['loja_id']);
                    
                    case '6': // Voltar para confirmação
                        $this->atualizarEtapa($conversa['id'], 'confirmacao');
                        return $this->montarMensagemConfirmacao($conversa);
                    
                    default:
                        return $this->getMensagemPadrao('opcoes_edicao');
                }

            case 'editar_nome':
                if (strlen($mensagem) < 3) {
                    return "Por favor, digite seu nome completo.";
                }
                $this->atualizarDadosConversa($conversa['id'], ['nome_cliente' => $mensagem]);
                $this->atualizarEtapa($conversa['id'], 'confirmacao');
                return $this->montarMensagemConfirmacao($conversa);

            case 'editar_telefone':
                $telefone = preg_replace('/[^0-9]/', '', $mensagem);
                if ($this->validarTelefone($telefone)) {
                    $this->atualizarDadosConversa($conversa['id'], ['telefone' => $telefone]);
                    $this->atualizarEtapa($conversa['id'], 'confirmacao');
                    return $this->montarMensagemConfirmacao($conversa);
                }
                return "Por favor, informe um telefone válido com DDD (ex: 27999999999)";
        }
        
        // Se chegou aqui, retorna a mensagem inicial
        $this->atualizarEtapa($conversa['id'], 'saudacao');
        return $this->getMensagemPadrao('saudacao');
    }
    
    private function getMensagemPadrao($tipo) {
        $stmt = $this->pdo->prepare("
            SELECT mensagem FROM mensagens_padrao WHERE tipo = ?
        ");
        $stmt->execute([$tipo]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['mensagem'] : '';
    }
    
    private function atualizarEtapa($conversaId, $novaEtapa) {
        $stmt = $this->pdo->prepare("
            UPDATE conversas SET etapa = ? WHERE id = ?
        ");
        $stmt->execute([$novaEtapa, $conversaId]);
    }
    
    private function registrarMensagem($conversaId, $tipo, $mensagem) {
        $stmt = $this->pdo->prepare("
            INSERT INTO mensagens (conversa_id, tipo, mensagem)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$conversaId, $tipo, $mensagem]);
    }
    
    private function validarTelefone($telefone) {
        // Remove qualquer caractere não numérico
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        // Verifica se tem 10 ou 11 dígitos (com DDD)
        return preg_match('/^[0-9]{10,11}$/', $telefone);
    }
    
    private function getMensagemLojas($cidade) {
        // Buscar lojas da cidade no banco de dados
        $stmt = $this->pdo->prepare("
            SELECT id, nome FROM locais 
            WHERE cidade = ? AND ativo = TRUE
            ORDER BY nome
        ");
        $stmt->execute([$cidade]);
        $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mensagem = "Escolha a loja em $cidade:\n\n";
        foreach ($lojas as $i => $loja) {
            $mensagem .= ($i + 1) . "️⃣ " . $loja['nome'] . "\n";
        }
        
        return $mensagem;
    }
    
    private function atualizarDadosConversa($conversaId, $dados) {
        $campos = [];
        $valores = [];
        
        foreach ($dados as $campo => $valor) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $conversaId;
        
        $sql = "UPDATE conversas SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($valores);
    }
    
    private function finalizarAgendamento($conversa) {
        // Criar o agendamento
        $stmt = $this->pdo->prepare("
            INSERT INTO agendamentos (local_id, nome_cliente, telefone, data_agendamento, horario)
            VALUES (?, ?, ?, DATE(?), TIME(?))
        ");
        
        $stmt->execute([
            $conversa['loja_id'],
            $conversa['nome_cliente'],
            $conversa['telefone'],
            $conversa['horario_escolhido'],
            $conversa['horario_escolhido']
        ]);
        
        // Atualizar status da conversa
        $stmt = $this->pdo->prepare("
            UPDATE conversas 
            SET status = 'finalizada'
            WHERE id = ?
        ");
        $stmt->execute([$conversa['id']]);
    }
    
    private function getLojasDisponiveis($cidade) {
        $stmt = $this->pdo->prepare("
            SELECT id, nome 
            FROM locais 
            WHERE cidade = ? AND ativo = TRUE 
            ORDER BY nome
        ");
        $stmt->execute([$cidade]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getDadosCompletos($conversaId) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, l.nome as nome_loja
            FROM conversas c
            LEFT JOIN locais l ON c.loja_id = l.id
            WHERE c.id = ?
        ");
        $stmt->execute([$conversaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getHorariosDisponiveis($lojaId) {
        // Buscar horários da loja
        $stmt = $this->pdo->prepare("SELECT horarios FROM locais WHERE id = ?");
        $stmt->execute([$lojaId]);
        $loja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$loja) {
            return [];
        }

        $periodos = explode(',', $loja['horarios']);
        $horarios = [];
        $contador = 1;

        foreach ($periodos as $periodo) {
            list($inicio, $fim) = explode('-', $periodo);
            $horaInicio = strtotime($inicio);
            $horaFim = strtotime($fim);
            
            // Gerar horários a cada 1 hora
            for ($hora = $horaInicio; $hora < $horaFim; $hora += 3600) {
                $horarios[$contador] = date('H:i', $hora);
                $contador++;
            }
        }

        return $horarios;
    }

    // Função auxiliar para montar mensagem de confirmação
    private function montarMensagemConfirmacao($conversa) {
        $dadosCompletos = $this->getDadosCompletos($conversa['id']);
        $mensagemConfirmacao = $this->getMensagemPadrao('confirmar_agendamento');
        return strtr($mensagemConfirmacao, [
            '{nome}' => $dadosCompletos['nome_cliente'],
            '{telefone}' => $dadosCompletos['telefone'],
            '{cidade}' => $dadosCompletos['cidade'],
            '{loja}' => $dadosCompletos['nome_loja'],
            '{horario}' => date('d/m/Y H:i', strtotime($dadosCompletos['horario_escolhido']))
        ]);
    }
} 
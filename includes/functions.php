<?php
require_once __DIR__ . '/../config/config.php';

function getLocais($pdo) {
    $stmt = $pdo->query("
        SELECT l.*, 
               COUNT(a.id) as total_agendamentos 
        FROM locais l 
        LEFT JOIN agendamentos a ON l.id = a.local_id 
        WHERE l.ativo = TRUE 
        GROUP BY l.id 
        ORDER BY l.cidade, l.nome
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAgendamentosHoje($pdo) {
    $stmt = $pdo->prepare("
        SELECT a.*, l.nome as nome_local, l.cidade
        FROM agendamentos a
        JOIN locais l ON a.local_id = l.id
        WHERE DATE(a.data_agendamento) = CURDATE()
        ORDER BY a.horario
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatarHorario($horarios) {
    $periodos = explode(',', $horarios);
    $formatado = [];
    foreach ($periodos as $periodo) {
        list($inicio, $fim) = explode('-', $periodo);
        $formatado[] = substr($inicio, 0, 5) . ' às ' . substr($fim, 0, 5);
    }
    return implode(' e ', $formatado);
}

// Função para formatar data
function formatarData($data, $formato = 'd/m/Y') {
    return date($formato, strtotime($data));
}

// Função para formatar hora
function formatarHora($hora, $formato = 'H:i') {
    return date($formato, strtotime($hora));
}

// Função para formatar telefone
function formatarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }
    return $telefone;
}

// Função para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para validar telefone
function validarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    return strlen($telefone) === 11;
}

// Função para gerar senha hash
function gerarHash($senha) {
    return password_hash($senha, PASSWORD_DEFAULT);
}

// Função para verificar senha
function verificarSenha($senha, $hash) {
    return password_verify($senha, $hash);
}

// Função para gerar token único
function gerarToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Função para validar data
function validarData($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

// Função para validar hora
function validarHora($hora) {
    $h = DateTime::createFromFormat('H:i', $hora);
    return $h && $h->format('H:i') === $hora;
}

// Função para obter o IP do cliente
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP);
}

// Função para verificar se é uma data futura
function isDataFutura($data) {
    $hoje = new DateTime();
    $data = new DateTime($data);
    return $data > $hoje;
}

// Função para verificar se é horário comercial
function isHorarioComercial($hora) {
    $hora = DateTime::createFromFormat('H:i', $hora);
    $inicio = DateTime::createFromFormat('H:i', '08:00');
    $fim = DateTime::createFromFormat('H:i', '18:00');
    return $hora >= $inicio && $hora <= $fim;
}

// Função para verificar se é dia útil
function isDiaUtil($data) {
    $data = new DateTime($data);
    $diaSemana = $data->format('N');
    return $diaSemana >= 1 && $diaSemana <= 5;
}

// Função para formatar valor monetário
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// Função para limpar string (remove acentos)
function limparString($string) {
    return preg_replace(
        array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/",
              "/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/"),
        explode(" ","a A e E i I o O u U n N c C"),
        $string
    );
} 
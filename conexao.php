<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

try {
    $conexao = new mysqli('localhost', 'root', '', 'loja_guinfo');
    $conexao->set_charset('utf8mb4');
} catch (mysqli_sql_exception $erro) {
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Importe o arquivo banco.sql no phpMyAdmin.');
}

function e(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function formatar_preco(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validar_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }
}

function carregar_itens_carrinho(mysqli $conexao): array {
    $itens = [];
    $carrinho = $_SESSION['carrinho'] ?? [];
    $stmt = $conexao->prepare('SELECT id, nome, preco, estoque, imagem FROM produtos WHERE id = ?');

    foreach ($carrinho as $id => $quantidade) {
        if (!ctype_digit((string)$id) || !is_int($quantidade) || $quantidade < 1) {
            continue;
        }

        $produtoId = (int)$id;
        $stmt->bind_param('i', $produtoId);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($produto = $resultado->fetch_assoc()) {
            $produto['quantidade'] = $quantidade;
            $produto['subtotal'] = (float)$produto['preco'] * $quantidade;
            $itens[] = $produto;
        }
    }

    return $itens;
}

function usuario_e_admin(mysqli $conexao): bool {
    if (!isset($_SESSION['usuario']['id'])) {
        return false;
    }

    $usuarioId = (int)$_SESSION['usuario']['id'];
    $stmt = $conexao->prepare('SELECT 1 FROM administradores WHERE usuario_id = ?');
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();

    return $stmt->get_result()->num_rows === 1;
}

function exigir_admin(mysqli $conexao): void {
    if (!usuario_e_admin($conexao)) {
        http_response_code(403);
        exit('Apenas a conta administradora pode gerenciar o catálogo.');
    }
}

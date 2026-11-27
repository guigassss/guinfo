<?php
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: conta.php?proximo=checkout');
    exit;
}

$erro = '';
$pedidoConcluido = null;

if (isset($_GET['pedido']) && ctype_digit((string)$_GET['pedido'])) {
    $pedidoId = (int)$_GET['pedido'];
    $usuarioId = (int)$_SESSION['usuario']['id'];
    $stmt = $conexao->prepare('SELECT id, total, criado_em FROM pedidos WHERE id = ? AND usuario_id = ?');
    $stmt->bind_param('ii', $pedidoId, $usuarioId);
    $stmt->execute();
    $pedidoConcluido = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $carrinho = $_SESSION['carrinho'] ?? [];
    if (!$carrinho) {
        header('Location: carrinho.php');
        exit;
    }

    $conexao->begin_transaction();
    try {
        $linhas = [];
        $total = 0.0;
        $produtoStmt = $conexao->prepare('SELECT id, nome, preco, estoque FROM produtos WHERE id = ? FOR UPDATE');

        foreach ($carrinho as $id => $quantidade) {
            if (!ctype_digit((string)$id) || !is_int($quantidade) || $quantidade < 1) {
                throw new RuntimeException('Carrinho inválido. Revise os produtos e tente novamente.');
            }

            $produtoId = (int)$id;
            $produtoStmt->bind_param('i', $produtoId);
            $produtoStmt->execute();
            $produto = $produtoStmt->get_result()->fetch_assoc();

            if (!$produto || (int)$produto['estoque'] < $quantidade) {
                throw new RuntimeException('Estoque insuficiente para ' . ($produto['nome'] ?? 'um dos produtos') . '. Ajuste a quantidade no carrinho.');
            }

            $produto['quantidade'] = $quantidade;
            $linhas[] = $produto;
            $total += (float)$produto['preco'] * $quantidade;
        }

        $usuarioId = (int)$_SESSION['usuario']['id'];
        $pedidoStmt = $conexao->prepare('INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)');
        $pedidoStmt->bind_param('id', $usuarioId, $total);
        $pedidoStmt->execute();
        $pedidoId = (int)$conexao->insert_id;

        $estoqueStmt = $conexao->prepare('UPDATE produtos SET estoque = estoque - ? WHERE id = ? AND estoque >= ?');
        $itemStmt = $conexao->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, preco_unitario, quantidade) VALUES (?, ?, ?, ?, ?)');

        foreach ($linhas as $linha) {
            $produtoId = (int)$linha['id'];
            $quantidade = (int)$linha['quantidade'];
            $preco = (float)$linha['preco'];
            $estoqueStmt->bind_param('iii', $quantidade, $produtoId, $quantidade);
            $estoqueStmt->execute();
            if ($estoqueStmt->affected_rows !== 1) {
                throw new RuntimeException('O estoque mudou durante a compra. Revise o carrinho e tente novamente.');
            }

            $nome = (string)$linha['nome'];
            $itemStmt->bind_param('iisdi', $pedidoId, $produtoId, $nome, $preco, $quantidade);
            $itemStmt->execute();
        }

        $conexao->commit();
        $_SESSION['carrinho'] = [];
        header('Location: checkout.php?pedido=' . $pedidoId);
        exit;
    } catch (RuntimeException $excecao) {
        $conexao->rollback();
        $erro = $excecao->getMessage();
    } catch (Throwable $excecao) {
        $conexao->rollback();
        throw $excecao;
    }
}

$itens = $pedidoConcluido ? [] : carregar_itens_carrinho($conexao);
if (!$pedidoConcluido && !$itens) {
    header('Location: carrinho.php');
    exit;
}
$total = array_sum(array_column($itens, 'subtotal'));
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guinfo | Finalizar compra</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
<main class="container">
  <h1 class="page-title"><?= $pedidoConcluido ? 'Compra registrada' : 'Confirmar compra' ?></h1>
  <?php if ($pedidoConcluido): ?>
    <div class="form-feedback success">Pedido #<?= (int)$pedidoConcluido['id'] ?> registrado com sucesso. Total: <?= formatar_preco((float)$pedidoConcluido['total']) ?>. O estoque foi atualizado.</div>
    <p class="checkout-links"><a class="button-link" href="index.php">Continuar comprando</a></p>
  <?php else: ?>
    <?php if ($erro !== ''): ?><div class="form-feedback error"><?= e($erro) ?></div><?php endif ?>
    <p class="checkout-intro">Conectado como <?= e((string)$_SESSION['usuario']['email']) ?>. Revise seu pedido antes de confirmar.</p>
    <div class="catalog-table-wrap">
      <table>
        <thead><tr><th>Produto</th><th>Preço</th><th>Quantidade</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($itens as $item): ?>
          <tr>
            <td><?= e((string)$item['nome']) ?></td>
            <td><?= formatar_preco((float)$item['preco']) ?></td>
            <td><?= (int)$item['quantidade'] ?></td>
            <td><?= formatar_preco((float)$item['subtotal']) ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
        <tfoot><tr><td colspan="3">Total</td><td><?= formatar_preco((float)$total) ?></td></tr></tfoot>
      </table>
    </div>
    <form method="post" class="checkout-confirm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="btn-enviar">Confirmar pedido</button>
      <a href="carrinho.php">Voltar ao carrinho</a>
    </form>
  <?php endif ?>
</main>
<?php require 'footer.php'; ?>
</body>
</html>

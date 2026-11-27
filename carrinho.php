<?php
require_once 'conexao.php';

$mensagens = [
    'adicionado' => 'Produto adicionado ao carrinho.',
    'atualizado' => 'Carrinho atualizado.',
    'removido' => 'Produto removido do carrinho.',
    'limpo' => 'Carrinho esvaziado.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $acao = (string)($_POST['acao'] ?? '');
    $id = isset($_POST['produto_id']) && ctype_digit((string)$_POST['produto_id'])
        ? (int)$_POST['produto_id']
        : 0;
    $carrinho = $_SESSION['carrinho'] ?? [];
    $aviso = 'atualizado';

    if ($acao === 'limpar') {
        $_SESSION['carrinho'] = [];
        $aviso = 'limpo';
    } elseif ($id > 0) {
        $stmt = $conexao->prepare('SELECT estoque FROM produtos WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $produto = $stmt->get_result()->fetch_assoc();
        $quantidadeAtual = isset($carrinho[$id]) ? (int)$carrinho[$id] : 0;

        if ($acao === 'adicionar' || $acao === 'mais') {
            if (!$produto || (int)$produto['estoque'] < $quantidadeAtual + 1) {
                $aviso = 'indisponivel';
            } else {
                $carrinho[$id] = $quantidadeAtual + 1;
                $_SESSION['carrinho'] = $carrinho;
                $aviso = 'adicionado';
            }
        } elseif ($acao === 'menos' && $quantidadeAtual > 1) {
            $carrinho[$id] = $quantidadeAtual - 1;
            $_SESSION['carrinho'] = $carrinho;
        } elseif ($acao === 'menos' && $quantidadeAtual === 1) {
            unset($carrinho[$id]);
            $_SESSION['carrinho'] = $carrinho;
            $aviso = 'removido';
        } elseif ($acao === 'remover') {
            unset($carrinho[$id]);
            $_SESSION['carrinho'] = $carrinho;
            $aviso = 'removido';
        }
    }

    if ($acao === 'adicionar') {
        header('Location: index.php?aviso=' . urlencode($aviso) . '#produtos');
    } else {
        header('Location: carrinho.php?aviso=' . urlencode($aviso));
    }
    exit;
}

$itens = carregar_itens_carrinho($conexao);
$total = array_sum(array_column($itens, 'subtotal'));
$aviso = (string)($_GET['aviso'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guinfo | Carrinho</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
<main class="container">
  <h1 class="page-title">Seu carrinho</h1>
  <?php if (isset($mensagens[$aviso])): ?><div class="form-feedback success"><?= e($mensagens[$aviso]) ?></div><?php endif ?>
  <?php if ($aviso === 'indisponivel'): ?><div class="form-feedback error">Não há estoque suficiente para adicionar esse produto.</div><?php endif ?>

  <?php if (!$itens): ?>
    <div class="cart-empty">
      <div class="empty-icon">🛒</div>
      <h2>Seu carrinho está vazio</h2>
      <p>Escolha algum produto para começar.</p>
      <a class="btn-enviar button-link" href="index.php">Ver produtos</a>
    </div>
  <?php else: ?>
    <div class="catalog-table-wrap">
      <table>
        <thead><tr><th>Produto</th><th>Preço</th><th>Quantidade</th><th>Subtotal</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($itens as $item): ?>
          <tr>
            <td><?= e((string)$item['nome']) ?></td>
            <td><?= formatar_preco((float)$item['preco']) ?></td>
            <td><?= (int)$item['quantidade'] ?></td>
            <td><?= formatar_preco((float)$item['subtotal']) ?></td>
            <td class="cart-actions">
              <?php foreach (['menos' => '−', 'mais' => '+'] as $acao => $rotulo): ?>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="acao" value="<?= e($acao) ?>">
                  <input type="hidden" name="produto_id" value="<?= (int)$item['id'] ?>">
                  <button type="submit" aria-label="<?= $acao === 'mais' ? 'Aumentar' : 'Diminuir' ?> quantidade"><?= e($rotulo) ?></button>
                </form>
              <?php endforeach ?>
              <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="acao" value="remover">
                <input type="hidden" name="produto_id" value="<?= (int)$item['id'] ?>">
                <button type="submit" class="btn-limpar">Remover</button>
              </form>
            </td>
          </tr>
        <?php endforeach ?>
        </tbody>
        <tfoot><tr><td colspan="3">Total</td><td colspan="2"><?= formatar_preco((float)$total) ?></td></tr></tfoot>
      </table>
    </div>
    <div class="cart-page-actions">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="acao" value="limpar">
        <button type="submit" class="btn-clear">Esvaziar carrinho</button>
      </form>
      <a class="button-link" href="checkout.php">Continuar para compra</a>
    </div>
  <?php endif ?>
</main>
<?php require 'footer.php'; ?>
</body>
</html>

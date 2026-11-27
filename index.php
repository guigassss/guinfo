<?php
require_once 'conexao.php';
$resultado = $conexao->query('SELECT id, nome, descricao, preco, categoria, estoque, imagem FROM produtos ORDER BY id');
$aviso = (string)($_GET['aviso'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guinfo | Loja de Informática</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
<main class="container">
  <section class="hero">
    <h1>Guinfo</h1>
    <p id="subtitulo">A sua melhor loja de produtos eletrônicos</p>
  </section>
  <?php if ($aviso === 'adicionado'): ?><div class="form-feedback success">Produto adicionado ao carrinho.</div><?php endif ?>
  <?php if ($aviso === 'indisponivel'): ?><div class="form-feedback error">Não há estoque suficiente para adicionar esse produto.</div><?php endif ?>
  <section class="catalogo" id="produtos">
    <div class="section-head">
      <h2>Produtos</h2>
    </div>
    <div class="vitrine">
      <?php if ($resultado->num_rows === 0): ?>
        <p class="estado-vitrine">Nenhum produto cadastrado.</p>
      <?php else: ?>
        <?php while ($produto = $resultado->fetch_assoc()): ?>
          <article class="produto">
            <?php if (!empty($produto['imagem'])): ?>
              <img class="produto-imagem" src="<?= e((string)$produto['imagem']) ?>" alt="<?= e((string)$produto['nome']) ?>">
            <?php endif; ?>
            <h3><?= e((string)$produto['nome']) ?></h3>
            <p><?= e((string)$produto['descricao']) ?></p>
            <p class="preco"><?= formatar_preco((float)$produto['preco']) ?></p>
            <p>Estoque: <?= (int)$produto['estoque'] ?></p>
            <form method="post" action="carrinho.php">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="acao" value="adicionar">
              <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
              <button type="submit" class="btn-comprar" <?= (int)$produto['estoque'] < 1 ? 'disabled' : '' ?>>
                <?= (int)$produto['estoque'] < 1 ? 'Sem estoque' : 'Adicionar ao carrinho' ?>
              </button>
            </form>
          </article>
        <?php endwhile ?>
      <?php endif ?>
    </div>
  </section>
</main>
<?php require 'footer.php'; ?>
</body>
</html>

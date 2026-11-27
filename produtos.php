<?php
require_once 'conexao.php';
exigir_admin($conexao);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    if (isset($_POST['excluir']) && ctype_digit((string)$_POST['excluir'])) {
        $id = (int)$_POST['excluir'];
        $sql = 'DELETE FROM produtos WHERE id = ?';
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        header('Location: produtos.php?mensagem=Produto+excluido');
        exit;
    }
}

$resultado = $conexao->query('SELECT id, nome, categoria, preco, estoque FROM produtos ORDER BY id');
$mensagem = isset($_GET['mensagem']) ? (string)$_GET['mensagem'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guinfo | Catálogo</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
<main class="container">
  <section>
    <h1 class="page-title">Catálogo completo</h1>
    <?php if ($mensagem !== ''): ?><div class="form-feedback success"><?= e($mensagem) ?></div><?php endif ?>
    <p style="margin-bottom:16px;"><a class="btn-enviar" href="form.php" style="display:inline-block;text-decoration:none;">Novo produto</a></p>
    <div class="catalog-table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if ($resultado->num_rows === 0): ?>
          <tr><td colspan="6">Nenhum produto cadastrado.</td></tr>
        <?php else: ?>
          <?php while ($produto = $resultado->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$produto['id'] ?></td>
              <td><?= e((string)$produto['nome']) ?></td>
              <td><?= e((string)$produto['categoria']) ?></td>
              <td><?= formatar_preco((float)$produto['preco']) ?></td>
              <td><?= (int)$produto['estoque'] ?></td>
              <td>
                <a href="form.php?id=<?= (int)$produto['id'] ?>">Editar</a>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="excluir" value="<?= (int)$produto['id'] ?>">
                  <button type="submit" class="btn-limpar">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endwhile ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php require 'footer.php'; ?>
</body>
</html>

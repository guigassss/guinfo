<?php
require_once 'conexao.php';
exigir_admin($conexao);

$produto = [
    'id' => 0, 'nome' => '', 'categoria' => '', 'descricao' => '',
    'preco' => '', 'estoque' => '', 'imagem' => ''
];
$erros = [];
$sucesso = '';

if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conexao->prepare('SELECT id, nome, categoria, descricao, preco, estoque, imagem FROM produtos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows === 1) {
        $produto = $resultado->fetch_assoc();
    } else {
        $erros[] = 'Produto não encontrado.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $produto['id'] = isset($_POST['id']) && ctype_digit((string)$_POST['id']) ? (int)$_POST['id'] : 0;
    $produto['nome'] = trim((string)($_POST['nome'] ?? ''));
    $produto['categoria'] = trim((string)($_POST['categoria'] ?? ''));
    $produto['descricao'] = trim((string)($_POST['descricao'] ?? ''));
    $produto['preco'] = trim((string)($_POST['preco'] ?? ''));
    $produto['estoque'] = trim((string)($_POST['estoque'] ?? ''));
    $produto['imagem'] = trim((string)($_POST['imagem'] ?? ''));

    if (strlen($produto['nome']) < 3 || strlen($produto['nome']) > 150) $erros[] = 'O nome deve ter entre 3 e 150 caracteres.';
    if (strlen($produto['categoria']) < 2 || strlen($produto['categoria']) > 80) $erros[] = 'Informe uma categoria válida.';
    if (!is_numeric(str_replace(',', '.', $produto['preco'])) || (float)str_replace(',', '.', $produto['preco']) <= 0) $erros[] = 'O preço deve ser maior que zero.';
    if (!ctype_digit($produto['estoque']) || (int)$produto['estoque'] < 0) $erros[] = 'O estoque deve ser um número inteiro maior ou igual a zero.';
    if (strlen($produto['descricao']) > 500) $erros[] = 'A descrição deve ter no máximo 500 caracteres.';
    if (strlen($produto['imagem']) > 255) $erros[] = 'O caminho da imagem é muito longo.';

    if (count($erros) === 0) {
        $preco = (float)str_replace(',', '.', $produto['preco']);
        $estoque = (int)$produto['estoque'];
        if ($produto['id'] > 0) {
            $stmt = $conexao->prepare('UPDATE produtos SET nome=?, categoria=?, descricao=?, preco=?, estoque=?, imagem=? WHERE id=?');
            $stmt->bind_param('sssdisi', $produto['nome'], $produto['categoria'], $produto['descricao'], $preco, $estoque, $produto['imagem'], $produto['id']);
            $stmt->execute();
        } else {
            $stmt = $conexao->prepare('INSERT INTO produtos (nome, categoria, descricao, preco, estoque, imagem) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssdis', $produto['nome'], $produto['categoria'], $produto['descricao'], $preco, $estoque, $produto['imagem']);
            $stmt->execute();
        }
        header('Location: produtos.php?mensagem=' . urlencode('Produto salvo com sucesso'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guinfo | <?= $produto['id'] > 0 ? 'Editar produto' : 'Novo produto' ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
<main class="container">
  <section id="cadastro">
    <h1 class="page-title"><?= $produto['id'] > 0 ? 'Editar produto' : 'Cadastrar produto' ?></h1>
    <?php if ($erros): ?>
      <div class="form-feedback error"><?= e(implode(' ', $erros)) ?></div>
    <?php endif ?>
    <form method="post" class="form-cadastro">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$produto['id'] ?>">

      <label for="nome">Nome do produto *</label>
      <input id="nome" name="nome" required minlength="3" maxlength="150" value="<?= e((string)$produto['nome']) ?>">

      <label for="categoria">Categoria *</label>
      <input id="categoria" name="categoria" required maxlength="80" value="<?= e((string)$produto['categoria']) ?>">

      <label for="descricao">Descrição</label>
      <textarea id="descricao" name="descricao" maxlength="500" rows="4"><?= e((string)$produto['descricao']) ?></textarea>

      <div class="form-row-cep">
        <div>
          <label for="preco">Preço (R$) *</label>
          <input id="preco" name="preco" type="number" required min="0.01" step="0.01" value="<?= e((string)$produto['preco']) ?>">
        </div>
        <div>
          <label for="estoque">Estoque *</label>
          <input id="estoque" name="estoque" type="number" required min="0" step="1" value="<?= e((string)$produto['estoque']) ?>">
        </div>
      </div>

      <label for="imagem">Caminho da imagem</label>
      <input id="imagem" name="imagem" maxlength="255" placeholder="imagem/exemplo.png" value="<?= e((string)$produto['imagem']) ?>">

      <button type="submit" class="btn-enviar"><?= $produto['id'] > 0 ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
      <p style="margin-top:12px;"><a href="produtos.php">Voltar ao catálogo</a></p>
    </form>
  </section>
</main>
<?php require 'footer.php'; ?>
</body>
</html>

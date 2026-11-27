<?php
require_once 'conexao.php';

$erro = '';
$proximo = (($_GET['proximo'] ?? $_POST['proximo'] ?? '') === 'checkout') ? 'checkout.php' : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $acao = (string)($_POST['acao'] ?? '');

    if ($acao === 'sair') {
        unset($_SESSION['usuario']);
        session_regenerate_id(true);
        header('Location: index.php');
        exit;
    }

    if ($acao === 'cadastrar') {
        $nome = trim((string)($_POST['nome'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $senha = (string)($_POST['senha'] ?? '');

        if (strlen($nome) < 3 || strlen($nome) > 120) {
            $erro = 'Informe um nome com pelo menos 3 caracteres.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $erro = 'Informe um e-mail válido.';
        } elseif (strlen($senha) < 8 || strlen($senha) > 72) {
            $erro = 'A senha deve ter entre 8 e 72 caracteres.';
        } else {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conexao->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nome, $email, $senhaHash);

            try {
                $stmt->execute();
                $usuarioId = (int)$conexao->insert_id;
                session_regenerate_id(true);
                $_SESSION['usuario'] = [
                    'id' => $usuarioId,
                    'nome' => $nome,
                    'email' => $email,
                ];
                header('Location: ' . $proximo);
                exit;
            } catch (mysqli_sql_exception $excecao) {
                if ($excecao->getCode() === 1062) {
                    $erro = 'Este e-mail já possui uma conta. Faça login.';
                } else {
                    throw $excecao;
                }
            }
        }
    } elseif ($acao === 'entrar') {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $senha = (string)($_POST['senha'] ?? '');

        $stmt = $conexao->prepare('SELECT id, nome, email, senha_hash FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario || !password_verify($senha, (string)$usuario['senha_hash'])) {
            $erro = 'E-mail ou senha incorretos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id' => (int)$usuario['id'],
                'nome' => (string)$usuario['nome'],
                'email' => (string)$usuario['email'],
            ];
            header('Location: ' . $proximo);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Guinfo | Minha conta</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'header.php'; ?>
<main class="container">
  <h1 class="page-title">Minha conta</h1>
  <?php if ($erro !== ''): ?><div class="form-feedback error"><?= e($erro) ?></div><?php endif ?>

  <?php if (isset($_SESSION['usuario'])): ?>
    <section class="account-panel">
      <h2>Bem-vindo, <?= e((string)$_SESSION['usuario']['nome']) ?></h2>
      <p>Você está conectado como <?= e((string)$_SESSION['usuario']['email']) ?>.</p>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="acao" value="sair">
        <button type="submit" class="btn-enviar">Sair da conta</button>
      </form>
    </section>
  <?php else: ?>
    <div class="account-grid">
      <section>
        <h2>Entrar</h2>
        <form method="post" class="form-cadastro">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="acao" value="entrar">
          <input type="hidden" name="proximo" value="<?= $proximo === 'checkout.php' ? 'checkout' : '' ?>">
          <label for="login-email">E-mail</label>
          <input id="login-email" name="email" type="email" maxlength="190" autocomplete="email" required>
          <label for="login-senha">Senha</label>
          <input id="login-senha" name="senha" type="password" autocomplete="current-password" required>
          <button type="submit" class="btn-enviar">Entrar</button>
        </form>
      </section>

      <section>
        <h2>Criar conta</h2>
        <form method="post" class="form-cadastro">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="acao" value="cadastrar">
          <input type="hidden" name="proximo" value="<?= $proximo === 'checkout.php' ? 'checkout' : '' ?>">
          <label for="cadastro-nome">Nome</label>
          <input id="cadastro-nome" name="nome" maxlength="120" autocomplete="name" required>
          <label for="cadastro-email">E-mail</label>
          <input id="cadastro-email" name="email" type="email" maxlength="190" autocomplete="email" required>
          <label for="cadastro-senha">Senha (mínimo 8 caracteres)</label>
          <input id="cadastro-senha" name="senha" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
          <button type="submit" class="btn-enviar">Criar conta</button>
        </form>
      </section>
    </div>
  <?php endif ?>
</main>
<?php require 'footer.php'; ?>
</body>
</html>

<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
$administrador = usuario_e_admin($conexao);
?>
<header class="header">
  <div class="container header-inner">
    <a class="logo" href="index.php"><span class="logo-mark">G</span> Guinfo</a>
    <nav class="nav" id="navMenu" aria-label="Navegação principal">
      <a class="<?= $paginaAtual === 'index.php' ? 'active' : '' ?>" href="index.php">Início</a>
      <a class="<?= $paginaAtual === 'index.php' || $paginaAtual === 'produtos.php' ? 'active' : '' ?>" href="index.php#produtos">Catálogo</a>
      <?php if ($administrador): ?>
        <a class="<?= $paginaAtual === 'produtos.php' || $paginaAtual === 'form.php' ? 'active' : '' ?>" href="produtos.php">Gerenciar catálogo</a>
      <?php endif ?>
      <a class="<?= $paginaAtual === 'carrinho.php' ? 'active' : '' ?>" href="carrinho.php">Carrinho (<?= array_sum($_SESSION['carrinho'] ?? []) ?>)</a>
      <a class="<?= $paginaAtual === 'conta.php' ? 'active' : '' ?>" href="conta.php">
        <?= isset($_SESSION['usuario']) ? 'Olá, ' . e((string)$_SESSION['usuario']['nome']) : 'Entrar / Cadastrar' ?>
      </a>
    </nav>
    <div class="header-actions">
      <button class="icon-btn" id="themeBtn" type="button" aria-label="Alternar tema"><span id="themeIcon">🌙</span></button>
      <button class="icon-btn hamburger" id="hamburger" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="navMenu">
        <span class="hamburger-box"><span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span></span>
      </button>
    </div>
  </div>
</header>
<div class="nav-overlay" id="navOverlay"></div>

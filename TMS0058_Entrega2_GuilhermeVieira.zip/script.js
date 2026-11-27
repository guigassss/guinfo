const $ = id => document.getElementById(id);
const html = document.documentElement;

const formatarMoeda = valor =>
  valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

/* Tema */
const temaSalvo = localStorage.getItem('guinfo_tema') || 'light';
html.dataset.theme = temaSalvo;
if ($('themeIcon')) $('themeIcon').textContent = temaSalvo === 'dark' ? '☀️' : '🌙';
$('themeBtn')?.addEventListener('click', () => {
  const novo = html.dataset.theme === 'dark' ? 'light' : 'dark';
  html.dataset.theme = novo;
  localStorage.setItem('guinfo_tema', novo);
  if ($('themeIcon')) $('themeIcon').textContent = novo === 'dark' ? '☀️' : '🌙';
});

/* Menu mobile */
const navMenu = $('navMenu');
const hamburger = $('hamburger');
const navOverlay = $('navOverlay');
function fecharMenu() {
  navMenu?.classList.remove('open');
  hamburger?.classList.remove('active');
  navOverlay?.classList.remove('open');
  document.body.classList.remove('nav-open');
  hamburger?.setAttribute('aria-expanded', 'false');
}
hamburger?.addEventListener('click', () => {
  const abre = !navMenu.classList.contains('open');
  navMenu.classList.toggle('open', abre);
  hamburger.classList.toggle('active', abre);
  navOverlay.classList.toggle('open', abre);
  document.body.classList.toggle('nav-open', abre);
  hamburger.setAttribute('aria-expanded', String(abre));
});
navOverlay?.addEventListener('click', fecharMenu);
navMenu?.querySelectorAll('a').forEach(link => link.addEventListener('click', fecharMenu));

/* Carrinho em array */
let carrinho = [];
try {
  carrinho = JSON.parse(localStorage.getItem('guinfo_carrinho') || '[]');
  if (!Array.isArray(carrinho)) carrinho = [];
} catch {
  carrinho = [];
}

function salvarCarrinho() {
  localStorage.setItem('guinfo_carrinho', JSON.stringify(carrinho));
  atualizarContador();
}
function atualizarContador() {
  const qtd = carrinho.reduce((soma, item) => soma + item.qtd, 0);
  if ($('cartCount')) $('cartCount').textContent = String(qtd);
}

function adicionarProduto(produto) {
  const existente = carrinho.find(item => item.id === produto.id);
  if (existente) existente.qtd++;
  else carrinho.push({ id: produto.id, nome: produto.nome, preco: produto.preco, qtd: 1 });
  salvarCarrinho();
  if ($('cartModalBg')?.classList.contains('open')) renderizarCarrinho();
}

function renderizarCarrinho() {
  const corpo = $('cartModalBody');
  if (!corpo) return;

  if (carrinho.length === 0) {
    corpo.innerHTML = '<div class="cart-empty"><div class="empty-icon">🛒</div><h4>Seu carrinho está vazio</h4><p>Adicione produtos para continuar.</p></div>';
    if ($('cartModalFoot')) $('cartModalFoot').style.display = 'none';
    return;
  }
  if ($('cartModalFoot')) $('cartModalFoot').style.display = 'block';

  corpo.innerHTML = carrinho.map((item, indice) => `
    <div class="cart-modal-item">
      <div>
        <div class="item-name">${item.nome}</div>
        <div class="item-details">${formatarMoeda(item.preco)} × ${item.qtd}</div>
        <div class="item-qty">
          <button type="button" data-acao="menos" data-indice="${indice}" aria-label="Diminuir quantidade">−</button>
          <span>${item.qtd}</span>
          <button type="button" data-acao="mais" data-indice="${indice}" aria-label="Aumentar quantidade">+</button>
        </div>
      </div>
      <button type="button" class="item-remove" data-acao="remover" data-indice="${indice}" aria-label="Remover ${item.nome}">🗑</button>
    </div>
  `).join('');

  const total = carrinho.reduce((soma, item) => soma + item.preco * item.qtd, 0);
  if ($('cartTotalValue')) $('cartTotalValue').textContent = formatarMoeda(total);
}

$('cartModalBody')?.addEventListener('click', evento => {
  const botao = evento.target.closest('[data-acao]');
  if (!botao) return;
  const indice = Number(botao.dataset.indice);
  const item = carrinho[indice];
  if (!item) return;
  if (botao.dataset.acao === 'mais') item.qtd++;
  if (botao.dataset.acao === 'menos') item.qtd--;
  if (botao.dataset.acao === 'remover') item.qtd = 0;
  carrinho = carrinho.filter(produto => produto.qtd > 0);
  salvarCarrinho();
  renderizarCarrinho();
});

function abrirCarrinho() {
  if (!$('cartModalBg')) return;
  renderizarCarrinho();
  $('cartModalBg').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function fecharCarrinho() {
  if (!$('cartModalBg')) return;
  $('cartModalBg').classList.remove('open');
  document.body.style.overflow = '';
}
$('cartBtn')?.addEventListener('click', abrirCarrinho);
$('cartModalClose')?.addEventListener('click', fecharCarrinho);
$('cartModalBg')?.addEventListener('click', evento => {
  if (evento.target === $('cartModalBg')) fecharCarrinho();
});
document.addEventListener('keydown', evento => {
  if (evento.key === 'Escape') fecharCarrinho();
});
$('clearCartBtn')?.addEventListener('click', () => {
  if (carrinho.length && confirm('Deseja limpar todo o carrinho?')) {
    carrinho = [];
    salvarCarrinho();
    renderizarCarrinho();
  }
});
$('checkoutBtn')?.addEventListener('click', () => {
  if (!carrinho.length) return;
  const quantidade = carrinho.reduce((soma, item) => soma + item.qtd, 0);
  const total = carrinho.reduce((soma, item) => soma + item.preco * item.qtd, 0);
  alert(`${quantidade} ${quantidade === 1 ? 'item' : 'itens'} finalizado${quantidade === 1 ? '' : 's'}.\nTotal: ${formatarMoeda(total)}`);
});

/* Produtos vindos de produtos.json */
let produtos = [];
async function carregarProdutos() {
  try {
    const resposta = await fetch('produtos.json');
    if (!resposta.ok) throw new Error('Não foi possível carregar produtos.json');
    produtos = await resposta.json();
  } catch (erro) {
    console.error(erro);
    if ($('listaProdutos')) $('listaProdutos').innerHTML = '<p class="estado-vitrine">Erro ao carregar os produtos.</p>';
    if ($('tabelaProdutos')) $('tabelaProdutos').innerHTML = '<tr><td colspan="3">Erro ao carregar os produtos.</td></tr>';
  }

  if ($('listaProdutos')) renderizarProdutos('Todos');
  if ($('tabelaProdutos')) renderizarTabela();
}

function renderizarProdutos(categoria) {
  const lista = $('listaProdutos');
  if (!lista) return;
  const filtrados = categoria === 'Todos'
    ? produtos
    : produtos.filter(produto => produto.categoria === categoria);

  if (!filtrados.length) {
    lista.innerHTML = '<p class="estado-vitrine">Nenhum produto encontrado.</p>';
    return;
  }

  lista.innerHTML = filtrados.map(produto => `
    <article class="produto">
      <img class="produto-imagem" src="${produto.imagem}" alt="${produto.nome}">
      <h3>${produto.nome}</h3>
      <p>${produto.descricao}</p>
      <p class="preco">${formatarMoeda(produto.preco)}</p>
      <button type="button" class="btn-comprar" data-produto-id="${produto.id}">Comprar</button>
    </article>
  `).join('');

  lista.querySelectorAll('.btn-comprar').forEach(botao => {
    botao.addEventListener('click', () => {
      const produto = produtos.find(item => item.id === Number(botao.dataset.produtoId));
      if (produto) adicionarProduto(produto);
    });
  });
}

$('filtros')?.addEventListener('click', evento => {
  const botao = evento.target.closest('.filtro');
  if (!botao) return;
  $('filtros').querySelectorAll('.filtro').forEach(item => item.classList.remove('active'));
  botao.classList.add('active');
  renderizarProdutos(botao.dataset.categoria);
});

function renderizarTabela() {
  if (!produtos.length) return;
  $('tabelaProdutos').innerHTML = produtos.map(produto => `
    <tr><td>${produto.nome}</td><td>${produto.categoria}</td><td>${formatarMoeda(produto.preco)}</td></tr>
  `).join('');
  if ($('totalItens')) $('totalItens').textContent = String(produtos.length);
}

/* Validação do cadastro */
function apenasDigitos(valor) { return valor.replace(/\D/g, ''); }
function validarNome(valor) { return valor.trim().length >= 3 && /^[A-Za-zÀ-ÿ\s]+$/.test(valor.trim()); }
function validarCPF(valor) {
  const cpf = apenasDigitos(valor);
  if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
  let soma = 0;
  for (let i = 0; i < 9; i++) soma += Number(cpf[i]) * (10 - i);
  let digito = (soma * 10) % 11;
  if (digito === 10) digito = 0;
  if (digito !== Number(cpf[9])) return false;
  soma = 0;
  for (let i = 0; i < 10; i++) soma += Number(cpf[i]) * (11 - i);
  digito = (soma * 10) % 11;
  if (digito === 10) digito = 0;
  return digito === Number(cpf[10]);
}
function validarTelefone(valor) {
  const telefone = apenasDigitos(valor);
  return telefone.length >= 10 && telefone.length <= 11;
}
function validarEmail(valor) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valor.trim()); }
function validarCEP(valor) { const cep = apenasDigitos(valor); return cep.length === 8 && !/^0{8}$/.test(cep); }

const form = $('formCadastro');
if (form) {
  $('cpf')?.addEventListener('input', evento => {
    let digitos = apenasDigitos(evento.target.value).slice(0, 11);
    evento.target.value = digitos.length > 9
      ? `${digitos.slice(0,3)}.${digitos.slice(3,6)}.${digitos.slice(6,9)}-${digitos.slice(9)}`
      : digitos.length > 6
        ? `${digitos.slice(0,3)}.${digitos.slice(3,6)}.${digitos.slice(6)}`
        : digitos.length > 3 ? `${digitos.slice(0,3)}.${digitos.slice(3)}` : digitos;
  });
  $('telefone')?.addEventListener('input', evento => {
    let d = apenasDigitos(evento.target.value).slice(0, 11);
    if (d.length > 10) evento.target.value = `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
    else if (d.length > 6) evento.target.value = `(${d.slice(0,2)}) ${d.slice(2,6)}-${d.slice(6)}`;
    else if (d.length > 2) evento.target.value = `(${d.slice(0,2)}) ${d.slice(2)}`;
    else evento.target.value = d;
  });
  $('cep')?.addEventListener('input', evento => {
    const d = apenasDigitos(evento.target.value).slice(0, 8);
    evento.target.value = d.length > 5 ? `${d.slice(0,5)}-${d.slice(5)}` : d;
  });
  $('cep')?.addEventListener('blur', async evento => {
    const cep = apenasDigitos(evento.target.value);
    if (!validarCEP(cep)) return;
    try {
      const resposta = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const dados = await resposta.json();
      if (dados.erro) throw new Error('CEP não encontrado');
      $('rua').value = dados.logradouro || '';
      $('bairro').value = dados.bairro || '';
      $('cidade').value = dados.localidade || '';
      $('estado').value = dados.uf || '';
    } catch (erro) {
      mostrarErroCampo($('cep'), 'CEP não encontrado.');
    }
  });

  form.addEventListener('submit', evento => {
    evento.preventDefault();
    form.querySelectorAll('.invalid').forEach(campo => campo.classList.remove('invalid'));
    let valido = true;

    function verificar(campo, condicao, mensagem) {
      if (!condicao) {
        campo.classList.add('invalid');
        mostrarErroCampo(campo, mensagem);
        valido = false;
      } else removerErroCampo(campo);
    }

    verificar($('name'), validarNome($('name').value), 'Informe um nome válido com pelo menos 3 letras.');
    verificar($('cpf'), validarCPF($('cpf').value), 'CPF inválido. Confira os dígitos.');
    verificar($('telefone'), validarTelefone($('telefone').value), 'Telefone inválido.');
    verificar($('email'), validarEmail($('email').value), 'E-mail inválido.');
    verificar($('cep'), validarCEP($('cep').value), 'Informe um CEP válido.');
    verificar($('rua'), $('rua').value.trim().length > 2, 'Informe a rua.');
    verificar($('numero'), $('numero').value.trim().length > 0, 'Informe o número.');
    verificar($('bairro'), $('bairro').value.trim().length > 1, 'Informe o bairro.');
    verificar($('cidade'), $('cidade').value.trim().length > 1, 'Informe a cidade.');
    verificar($('estado'), /^[A-Za-z]{2}$/.test($('estado').value.trim()), 'Informe a UF com 2 letras.');
    verificar($('senha'), $('senha').value.length >= 8, 'A senha deve ter pelo menos 8 caracteres.');
    verificar($('aceite'), $('aceite').checked, 'Você precisa aceitar os termos.');

    const feedback = $('formFeedback');
    if (!valido) {
      feedback.className = 'form-feedback error';
      feedback.textContent = 'Cadastro não enviado. Corrija os campos destacados.';
      form.querySelector('.invalid')?.focus();
      return;
    }
    feedback.className = 'form-feedback success';
    feedback.textContent = 'Cadastro realizado com sucesso!';
    form.reset();
  });
}

function mostrarErroCampo(campo, mensagem) {
  removerErroCampo(campo);
  const erro = document.createElement('small');
  erro.className = 'erro-msg';
  erro.textContent = mensagem;
  campo.insertAdjacentElement('afterend', erro);
}
function removerErroCampo(campo) {
  const erro = campo?.nextElementSibling;
  if (erro?.classList.contains('erro-msg')) erro.remove();
}

if ($('ano')) $('ano').textContent = new Date().getFullYear();
atualizarContador();
carregarProdutos();

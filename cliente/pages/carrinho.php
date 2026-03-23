<?php
session_start();
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

$usuarioLogado = isset($_SESSION['cliente']);
$clienteNome = $usuarioLogado ? htmlspecialchars($_SESSION['cliente']['nome']) : '';
$nomeUsuario = $clienteNome;

$clienteCompleto = null;
if ($usuarioLogado && isset($_SESSION['cliente']['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['cliente']['id']]);
        $clienteCompleto = $stmt->fetch();
    } catch (Throwable $e) {
        $clienteCompleto = null;
    }
}

$cepCliente = '';
if ($clienteCompleto) {
    $cepCliente = (string)($clienteCompleto['cep'] ?? $clienteCompleto['cep_entrega'] ?? '');
}

$freteGratisValor = getFreteGratisThreshold($pdo);

$pageTitle = 'Carrinho - RARE7';
$basePath = '../';
$currentPage = 'cart';

include '../includes/header.php';
?>
<body class="cart-page">
<?php include '../includes/navbar.php'; ?>

<main class="cart-shell login-fade">
    <section class="cart-hero">
        <div class="cart-hero-content">
            <p class="cart-kicker">Seu carrinho</p>
            <h1 class="cart-hero-title">Finalize sua seleção premium.</h1>
            <p class="cart-hero-description">Revise seus produtos, ajuste quantidades e avance para um checkout elegante, rapido e seguro.</p>
        </div>

        <div class="cart-hero-stats">
            <article class="cart-stat-card">
                <strong id="heroItemsCount">00 itens</strong>
                <span>No carrinho</span>
            </article>
            <article class="cart-stat-card">
                <strong>3x</strong>
                <span>Sem juros</span>
            </article>
            <article class="cart-stat-card">
                <strong>Pix</strong>
                <span>Desconto disponivel</span>
            </article>
        </div>
    </section>

    <section class="cart-layout">
        <div class="cart-main-column">
            <div id="freeShippingBar" class="free-shipping-bar" style="display:none;">
                <div class="shipping-text" id="shippingText">Faltam <strong id="shippingRemaining">R$ 0,00</strong> para frete gratis.</div>
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar" style="width:0%"></div>
                </div>
            </div>

            <div class="cart-products" id="cartItemsContainer">
                <div class="empty-cart">
                    <div class="empty-icon">🛍</div>
                    <h2>Carregando carrinho...</h2>
                </div>
            </div>

            <div class="cart-continue-box">
                <p class="cart-kicker">Continue comprando</p>
                <h3>Adicione mais peças ao seu drop.</h3>
                <a href="../produtos.php" class="cart-link-btn">Ver coleção completa</a>
            </div>
        </div>

        <aside class="cart-side-column">
            <article class="cart-checkout-card">
                <header class="checkout-card-header">
                    <p class="cart-kicker">Resumo do pedido</p>
                    <h4>Seu checkout</h4>
                </header>

                <section class="checkout-section" aria-label="Cupom">
                    <label class="checkout-label" for="cupomInput">Cupom</label>
                    <div class="input-group-mini">
                        <input
                            type="text"
                            id="cupomInput"
                            class="cupom-input"
                            placeholder="Ex: RARE10"
                            maxlength="20"
                            onkeypress="if(event.key==='Enter') aplicarCupom()"
                        >
                        <button type="button" class="btn-mini btn-apply-cupom" onclick="aplicarCupom(event)">Aplicar</button>
                    </div>
                </section>

                <section class="checkout-section" aria-label="Frete">
                    <label class="checkout-label" for="cepInput">Frete</label>
                    <div class="input-group-mini">
                        <input
                            type="text"
                            id="cepInput"
                            class="cep-input"
                            placeholder="Digite seu CEP"
                            maxlength="10"
                            onkeyup="formatarCEPMini(this)"
                            onkeypress="if(event.key==='Enter') calcularFreteMini(event)"
                        >
                        <button type="button" class="btn-mini btn-calc-frete" onclick="calcularFreteMini(event)">Calcular</button>
                    </div>
                    <div id="freteOptionsMini" class="frete-options-mini" style="display:none;"></div>
                </section>

                <div id="appliedInfo" class="applied-info" style="display:none;"></div>

                <div class="checkout-divider" aria-hidden="true"></div>

                <section class="checkout-summary" aria-label="Resumo financeiro">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong id="subtotalValue">R$ 0,00</strong>
                    </div>

                    <div class="summary-row">
                        <span>Frete</span>
                        <strong id="freteValue">Calcular CEP</strong>
                    </div>

                    <div class="summary-row desconto" id="descontoRow" style="display:none;">
                        <span>Desconto</span>
                        <strong id="descontoValue">- R$ 0,00</strong>
                    </div>

                    <div class="summary-row">
                        <span>Pix</span>
                        <strong>Desconto no checkout</strong>
                    </div>

                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <strong id="totalValue">R$ 0,00</strong>
                    </div>
                </section>

                <p class="parcelamento-info" id="parcelamentoInfo">ou em ate 3x sem juros de R$ 0,00</p>

                <button type="button" class="btn-checkout" id="btnCheckout" onclick="finalizarCompra()">Ir para checkout</button>
                <a href="../produtos.php" class="btn-continue-shopping">Continuar comprando</a>
            </article>
        </aside>
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<script>
const __noopLog = () => {};

let carrinho = {
    items: [],
    cupom: null,
    frete: null,
    subtotal: 0,
    desconto: 0,
    freteValor: 0,
    total: 0
};

const CONFIG = {
    freteGratisLimite: <?php echo (float) $freteGratisValor; ?>
};

const CEP_CLIENTE_PADRAO = <?php echo json_encode($cepCliente); ?>;

function updateCartBadge() {
    const cart = localStorage.getItem('dz_cart');
    const items = cart ? JSON.parse(cart) : [];
    const totalItems = items.reduce((sum, item) => sum + (parseInt(item.qty, 10) || 1), 0);

    const badge = document.getElementById('cartBadge');
    if (badge) {
        badge.textContent = totalItems;
        badge.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

function updateHeroStats() {
    const heroCount = document.getElementById('heroItemsCount');
    if (!heroCount) return;
    const totalItens = carrinho.items.reduce((sum, item) => sum + (parseInt(item.quantidade, 10) || 0), 0);
    heroCount.textContent = String(totalItens).padStart(2, '0') + ' itens';
}

document.addEventListener('DOMContentLoaded', function () {
    updateCartBadge();
    carregarCarrinho();
    preencherCepInicial();
});

function normalizarCep(cep) {
    return String(cep || '').replace(/\D/g, '').substring(0, 8);
}

function formatarCepTexto(cep) {
    const digits = normalizarCep(cep);
    if (digits.length <= 5) return digits;
    return digits.substring(0, 5) + '-' + digits.substring(5);
}

function preencherCepInicial() {
    const cepInput = document.getElementById('cepInput');
    if (!cepInput || cepInput.value.trim()) return;

    let cepPrioritario = '';

    try {
        const freteSalvo = localStorage.getItem('dz_frete');
        if (freteSalvo) {
            const frete = JSON.parse(freteSalvo);
            cepPrioritario = frete && frete.cep ? frete.cep : '';
        }
    } catch (_) {
        cepPrioritario = '';
    }

    if (!cepPrioritario && CEP_CLIENTE_PADRAO) {
        cepPrioritario = CEP_CLIENTE_PADRAO;
    }

    if (cepPrioritario) {
        cepInput.value = formatarCepTexto(cepPrioritario);
    }
}

function isAmbienteLocal() {
    return window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
}

function popularCarrinhoDemo() {
    const demoItems = [
        {
            id: 101,
            name: 'Camisa Brasil 2002 - Home',
            price: 349.90,
            qty: 1,
            image: 'images/produtos/produto-1.jpg',
            variacao_id: 1,
            variacao_texto: 'Tamanho: G | Personalizacao: RONALDO 9',
            categoria: 'Selecoes'
        },
        {
            id: 102,
            name: 'Camisa Milan 06/07 - Retro',
            price: 329.90,
            qty: 1,
            image: 'images/produtos/produto-2.jpg',
            variacao_id: 2,
            variacao_texto: 'Tamanho: M | Personalizacao: KAKA 22',
            categoria: 'Retro'
        },
        {
            id: 103,
            name: 'Camisa PSG Black - Special',
            price: 389.90,
            qty: 2,
            image: 'images/produtos/produto-3.jpg',
            variacao_id: 3,
            variacao_texto: 'Tamanho: GG | Personalizacao: MBAPPE 7',
            categoria: 'Clubes'
        }
    ];

    localStorage.setItem('dz_cart', JSON.stringify(demoItems));
}

async function carregarCarrinho() {
    let cartData = localStorage.getItem('dz_cart');
    const rootPrefix = window.location.pathname.includes('/cliente/')
        ? window.location.pathname.split('/cliente/')[0]
        : '';

    if (!cartData || cartData === '[]') {
        mostrarCarrinhoVazio();
        return;
    }

    try {
        const items = JSON.parse(cartData);

        if (!Array.isArray(items) || items.length === 0) {
            mostrarCarrinhoVazio();
            return;
        }

        carrinho.items = items.map(function (item) {
            let imagemAjustada = item.image || null;
            if (imagemAjustada && !imagemAjustada.startsWith('http')) {
                const cleaned = imagemAjustada.replace(/^\.\//, '');

                if (cleaned.startsWith('/')) {
                    imagemAjustada = cleaned;
                } else if (cleaned.startsWith('../../admin/')) {
                    imagemAjustada = rootPrefix + '/' + cleaned.replace(/^\.\.\/\.\.\//, '');
                } else if (cleaned.startsWith('../admin/')) {
                    imagemAjustada = rootPrefix + '/' + cleaned.replace(/^\.\.\//, '');
                } else if (cleaned.startsWith('admin/')) {
                    imagemAjustada = rootPrefix + '/' + cleaned;
                } else if (cleaned.startsWith('../')) {
                    imagemAjustada = rootPrefix + '/cliente/' + cleaned.replace(/^\.\.\//, '');
                } else if (cleaned.startsWith('images/')) {
                    imagemAjustada = rootPrefix + '/cliente/' + cleaned;
                } else {
                    imagemAjustada = rootPrefix + '/cliente/' + cleaned;
                }
            }

            return {
                produto_id: item.id,
                variacao_id: item.variacao_id || null,
                nome: item.name,
                variacao_texto: item.variacao_texto || '',
                preco: parseFloat(item.price) || 0,
                preco_original: parseFloat(item.price) || 0,
                imagem: imagemAjustada,
                estoque: 999,
                quantidade: parseInt(item.qty, 10) || 1,
                tem_promocao: false,
                categoria: item.categoria || 'Selecoes'
            };
        });

        const freteSalvo = localStorage.getItem('dz_frete');
        if (freteSalvo) {
            try {
                carrinho.frete = JSON.parse(freteSalvo);
            } catch (_) {
                carrinho.frete = null;
            }
        }

        const cupomSalvo = localStorage.getItem('dz_cupom');
        if (cupomSalvo) {
            try {
                carrinho.cupom = JSON.parse(cupomSalvo);
            } catch (_) {
                carrinho.cupom = null;
            }
        }

        renderizarCarrinho();
        calcularTotais();
        updateHeroStats();
    } catch (error) {
        console.error('Erro ao carregar carrinho:', error);
        mostrarCarrinhoVazio();
    }
}

function extrairCampoVariacao(texto, campo) {
    if (!texto) return '';
    const regex = new RegExp(campo + '\\s*:\\s*([^|]+)', 'i');
    const match = texto.match(regex);
    return match ? match[1].trim() : '';
}

function renderizarCarrinho() {
    const container = document.getElementById('cartItemsContainer');
    if (!container) return;

    container.innerHTML = carrinho.items.map(function (item, index) {
        const temEstoque = item.quantidade <= item.estoque;
        const tamanho = extrairCampoVariacao(item.variacao_texto, 'tamanho') || '-';
        const personalizacao = extrairCampoVariacao(item.variacao_texto, 'personalizacao') || '-';

        return `
            <article class="cart-item">
                <div class="item-image">
                    ${item.imagem ? `<img src="${item.imagem}" alt="${item.nome}" onerror="this.parentElement.innerHTML='🧵'">` : '🧵'}
                </div>
                <div class="item-details">
                    <span class="item-category">${item.categoria}</span>
                    <h3 class="item-name">${item.nome}</h3>
                    <div class="item-meta">
                        <span>Tamanho: <strong>${tamanho}</strong></span>
                        <span>Personalizacao: <strong>${personalizacao}</strong></span>
                    </div>
                    <p class="item-price">
                        ${item.tem_promocao ? `<span class="price-original">R$ ${formatarDinheiro(item.preco_original)}</span>` : ''}
                        R$ ${formatarDinheiro(item.preco)}
                    </p>
                    ${!temEstoque ? `<div class="stock-warning">Estoque insuficiente (disponivel: ${item.estoque})</div>` : ''}
                    <div class="item-actions-row">
                        <div class="qty-controls">
                            <button class="qty-btn" onclick="alterarQuantidade(${index}, -1)" ${item.quantidade <= 1 ? 'disabled' : ''}>−</button>
                            <span class="qty-number">${item.quantidade}</span>
                            <button class="qty-btn" onclick="alterarQuantidade(${index}, 1)" ${!temEstoque ? 'disabled' : ''}>+</button>
                        </div>
                        <div class="item-secondary-actions">
                            <button class="btn-save-later" onclick="salvarParaDepois(${index})">Salvar para depois</button>
                            <button class="btn-remove" onclick="removerItem(${index})">Remover</button>
                        </div>
                    </div>
                </div>
            </article>
        `;
    }).join('');
}

function salvarParaDepois(index) {
    const item = carrinho.items[index];
    if (!item) return;
    const saved = JSON.parse(localStorage.getItem('dz_saved') || '[]');
    saved.push(item);
    localStorage.setItem('dz_saved', JSON.stringify(saved));
    alert('Item salvo para depois.');
}

function alterarQuantidade(index, delta) {
    const item = carrinho.items[index];
    const novaQtd = item.quantidade + delta;

    if (novaQtd <= 0) {
        removerItem(index);
        return;
    }

    if (novaQtd > item.estoque) {
        alert('Estoque insuficiente. Disponivel: ' + item.estoque);
        return;
    }

    carrinho.items[index].quantidade = novaQtd;
    salvarCarrinhoLocalStorage();
    renderizarCarrinho();
    calcularTotais();
    updateHeroStats();
}

function removerItem(index) {
    if (!confirm('Deseja remover este item do carrinho?')) return;

    carrinho.items.splice(index, 1);

    if (carrinho.items.length === 0) {
        localStorage.removeItem('dz_cart');
        updateCartBadge();
        mostrarCarrinhoVazio();
        return;
    }

    salvarCarrinhoLocalStorage();
    renderizarCarrinho();
    calcularTotais();
    updateHeroStats();
}

function salvarCarrinhoLocalStorage() {
    const simplificado = carrinho.items.map(function (item) {
        return {
            id: item.produto_id,
            name: item.nome,
            price: item.preco,
            qty: item.quantidade,
            image: item.imagem,
            variacao_id: item.variacao_id || null,
            variacao_texto: item.variacao_texto || null
        };
    });

    localStorage.setItem('dz_cart', JSON.stringify(simplificado));
    updateCartBadge();
}

function calcularTotais() {
    carrinho.subtotal = carrinho.items.reduce(function (total, item) {
        return total + (item.preco * item.quantidade);
    }, 0);

    carrinho.desconto = 0;
    if (carrinho.cupom) {
        if (carrinho.cupom.tipo === 'percentual' || carrinho.cupom.tipo === 'porcentagem') {
            carrinho.desconto = (carrinho.subtotal * carrinho.cupom.valor) / 100;
        } else {
            carrinho.desconto = carrinho.cupom.valor;
        }
        carrinho.desconto = Math.min(carrinho.desconto, carrinho.subtotal);
    }

    carrinho.freteValor = carrinho.frete ? (parseFloat(carrinho.frete.valor) || 0) : 0;
    carrinho.total = carrinho.subtotal - carrinho.desconto + carrinho.freteValor;

    atualizarResumo();
}

function atualizarResumo() {
    const subtotalEl = document.getElementById('subtotalValue');
    const descontoRow = document.getElementById('descontoRow');
    const descontoEl = document.getElementById('descontoValue');
    const freteEl = document.getElementById('freteValue');
    const totalEl = document.getElementById('totalValue');
    const parcelamentoEl = document.getElementById('parcelamentoInfo');
    const btnCheckout = document.getElementById('btnCheckout');

    if (subtotalEl) subtotalEl.textContent = 'R$ ' + formatarDinheiro(carrinho.subtotal);

    if (carrinho.cupom && carrinho.desconto > 0) {
        if (descontoRow) descontoRow.style.display = 'flex';
        if (descontoEl) descontoEl.textContent = '- R$ ' + formatarDinheiro(carrinho.desconto);
    } else if (descontoRow) {
        descontoRow.style.display = 'none';
    }

    if (freteEl) {
        if (carrinho.frete) {
            if (carrinho.frete.gratis) {
                freteEl.textContent = 'GRATIS';
            } else {
                freteEl.textContent = 'R$ ' + formatarDinheiro(carrinho.freteValor);
            }
        } else {
            freteEl.textContent = 'Calcular CEP';
        }
    }

    if (totalEl) totalEl.textContent = 'R$ ' + formatarDinheiro(carrinho.total);
    if (parcelamentoEl) parcelamentoEl.textContent = 'ou em ate 3x sem juros de R$ ' + formatarDinheiro(carrinho.total / 3);

    if (btnCheckout) btnCheckout.disabled = carrinho.items.length === 0;

    atualizarProgressoFreteGratis();
    atualizarInfoAplicadas();
}

function atualizarInfoAplicadas() {
    const container = document.getElementById('appliedInfo');
    if (!container) return;

    let html = '';

    if (carrinho.cupom) {
        html += `
            <div class="applied-item applied-item-cupom">
                <div class="applied-item-left"><strong>${carrinho.cupom.codigo}</strong></div>
                <button class="btn-remove-applied" onclick="removerCupom()" title="Remover cupom">×</button>
            </div>
        `;
    }

    if (carrinho.frete) {
        const valorTexto = carrinho.frete.gratis ? 'GRATIS' : `R$ ${formatarDinheiro(carrinho.frete.valor)}`;
        html += `
            <div class="applied-item">
                <div class="applied-item-left"><strong>${carrinho.frete.nome}</strong> - ${valorTexto}</div>
                <button class="btn-remove-applied" onclick="removerFrete()" title="Recalcular frete">×</button>
            </div>
        `;
    }

    if (html) {
        container.innerHTML = html;
        container.style.display = 'flex';
    } else {
        container.style.display = 'none';
    }
}

function atualizarProgressoFreteGratis() {
    const bar = document.getElementById('freeShippingBar');
    const text = document.getElementById('shippingText');
    const progressBar = document.getElementById('progressBar');

    if (!bar || !text || !progressBar) return;

    if (carrinho.subtotal >= CONFIG.freteGratisLimite) {
        text.innerHTML = 'Parabens! Voce ganhou frete gratis.';
        progressBar.style.width = '100%';
        bar.style.display = 'block';
    } else if (carrinho.subtotal > 0) {
        const falta = CONFIG.freteGratisLimite - carrinho.subtotal;
        const porcentagem = (carrinho.subtotal / CONFIG.freteGratisLimite) * 100;
        text.innerHTML = `Faltam <strong>R$ ${formatarDinheiro(falta)}</strong> para frete gratis.`;
        progressBar.style.width = porcentagem + '%';
        bar.style.display = 'block';
    } else {
        bar.style.display = 'none';
    }
}

async function aplicarCupom(event) {
    const codigo = document.getElementById('cupomInput').value.trim().toUpperCase();
    const btnApply = event ? event.currentTarget : document.querySelector('.btn-apply-cupom');

    if (!codigo) {
        alert('Digite um codigo de cupom');
        return;
    }

    const btnText = btnApply.textContent;
    btnApply.disabled = true;
    btnApply.textContent = 'Validando...';

    try {
        const response = await fetch('../api/cupom-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'validate',
                codigo: codigo,
                subtotal: carrinho.subtotal
            })
        });

        const result = await response.json();

        if (result.success) {
            carrinho.cupom = result.data;
            localStorage.setItem('dz_cupom', JSON.stringify(result.data));
            calcularTotais();

            const inputCupom = document.getElementById('cupomInput');
            inputCupom.value = codigo;
            inputCupom.disabled = true;

            btnApply.textContent = '✓';
            btnApply.disabled = true;
        } else {
            alert(result.message);
            btnApply.disabled = false;
            btnApply.textContent = btnText;
        }
    } catch (error) {
        console.error('Erro ao validar cupom:', error);
        alert('Erro ao validar cupom. Tente novamente.');
        btnApply.disabled = false;
        btnApply.textContent = btnText;
    }
}

function removerCupom() {
    carrinho.cupom = null;
    localStorage.removeItem('dz_cupom');

    const inputCupom = document.getElementById('cupomInput');
    const btnApply = document.querySelector('.btn-apply-cupom');

    if (inputCupom) {
        inputCupom.value = '';
        inputCupom.disabled = false;
        inputCupom.placeholder = 'Ex: RARE10';
    }

    if (btnApply) {
        btnApply.textContent = 'Aplicar';
        btnApply.disabled = false;
    }

    calcularTotais();
}

function formatarCEPMini(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5, 8);
    }
    input.value = value;
}

async function calcularFreteMini(event) {
    const cep = document.getElementById('cepInput').value.replace(/\D/g, '');
    if (cep.length !== 8) {
        alert('CEP invalido. Digite um CEP valido.');
        return;
    }

    if (carrinho.items.length === 0) {
        alert('Adicione produtos ao carrinho antes de calcular o frete.');
        return;
    }

    const btnCalc = event ? event.currentTarget : document.querySelector('.btn-calc-frete');
    if (btnCalc) {
        btnCalc.disabled = true;
        btnCalc.textContent = 'Calculando...';
    }

    try {
        const response = await fetch('../api/frete-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'calculate',
                cep: cep,
                subtotal: carrinho.subtotal,
                items: carrinho.items.map(function (item) {
                    return {
                        produto_id: item.produto_id || item.id,
                        variacao_id: item.variacao_id || null,
                        quantidade: item.quantidade || item.qty || 1,
                        preco: item.preco || item.price
                    };
                })
            })
        });

        const result = await response.json();

        if (result.success) {
            mostrarOpcoesFreteMini(result.data.opcoes);
        } else {
            alert(result.message || 'Frete incorreto. Verifique o CEP e tente novamente.');
        }
    } catch (error) {
        console.error('Erro ao calcular frete:', error);
        alert('Erro ao processar frete. Tente novamente.');
    } finally {
        if (btnCalc) {
            btnCalc.disabled = false;
            btnCalc.textContent = 'Calcular';
        }
    }
}

function mostrarOpcoesFreteMini(opcoes) {
    const container = document.getElementById('freteOptionsMini');
    if (!container) return;

    if (!opcoes || opcoes.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.innerHTML = opcoes.map(function (opcao, index) {
        const isGratis = opcao.gratis || opcao.valor === 0;
        const valor = isGratis ? 'GRATIS' : `R$ ${formatarDinheiro(opcao.valor)}`;

        return `
            <div class="frete-option-mini" data-index="${index}" onclick="selecionarFreteMini(${index})">
                <div class="frete-option-info">
                    <div class="frete-nome-mini">${opcao.nome}</div>
                    <div class="frete-prazo-mini">Entrega em ${opcao.prazo_dias || opcao.prazo || '?'} dias uteis</div>
                </div>
                <div class="frete-valor-mini ${isGratis ? 'gratis' : ''}">${valor}</div>
            </div>
        `;
    }).join('');

    container.style.display = 'flex';
    window.freteOpcoesAtual = opcoes;
}

function selecionarFreteMini(index) {
    document.querySelectorAll('.frete-option-mini').forEach(function (el) {
        el.classList.remove('selected');
    });

    const opcaoElement = document.querySelector(`.frete-option-mini[data-index="${index}"]`);
    if (opcaoElement) {
        opcaoElement.classList.add('selected');
    }

    const opcao = window.freteOpcoesAtual[index];
    if (!opcao) return;

    carrinho.frete = {
        id: opcao.id,
        valor: opcao.valor || 0,
        nome: opcao.nome,
        gratis: opcao.gratis || opcao.valor === 0,
        prazo: opcao.prazo_dias || opcao.prazo,
        cep: document.getElementById('cepInput').value.trim()
    };

    localStorage.setItem('dz_frete', JSON.stringify(carrinho.frete));
    calcularTotais();
}

function removerFrete() {
    carrinho.frete = null;
    localStorage.removeItem('dz_frete');

    const cepInput = document.getElementById('cepInput');
    const freteOptions = document.getElementById('freteOptionsMini');

    if (cepInput) cepInput.value = '';
    if (freteOptions) {
        freteOptions.innerHTML = '';
        freteOptions.style.display = 'none';
    }

    calcularTotais();
}

async function finalizarCompra() {
    if (!carrinho.items || carrinho.items.length === 0) {
        alert('Seu carrinho esta vazio.');
        return;
    }

    if (!carrinho.frete) {
        const cepInput = document.getElementById('cepInput');
        alert('Calcule o frete antes de ir para o checkout.');
        if (cepInput) {
            cepInput.focus();
            cepInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }

    try {
        const response = await fetch('../api/carrinho-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'validateStock',
                items: carrinho.items.map(function (item) {
                    return {
                        produto_id: item.produto_id,
                        variacao_id: item.variacao_id,
                        quantidade: item.quantidade,
                        nome: item.nome
                    };
                })
            })
        });

        const result = await response.json();

        if (!result.success) {
            alert('Erro de estoque:\n' + result.message);
            return;
        }
    } catch (error) {
        console.error('Erro ao validar estoque:', error);
        alert('Erro ao validar estoque. Tente novamente.');
        return;
    }

    <?php if (!$usuarioLogado): ?>
    if (confirm('Voce precisa estar logado para finalizar a compra. Deseja fazer login agora?')) {
        sessionStorage.setItem('redirect_after_login', 'carrinho.php');
        window.location.href = 'login.php';
    }
    return;
    <?php endif; ?>

    sessionStorage.setItem('pedido_carrinho', JSON.stringify(carrinho));
    window.location.href = 'checkout.php';
}

function mostrarCarrinhoVazio() {
    const container = document.getElementById('cartItemsContainer');
    if (!container) return;

    container.innerHTML = `
        <div class="empty-cart">
            <div class="empty-icon">🛒</div>
            <h2>Seu carrinho esta vazio</h2>
            <p>Adicione produtos para comecar suas compras.</p>
            <a href="../produtos.php" class="btn-continue">Comecar a comprar</a>
        </div>
    `;

    const btnCheckout = document.getElementById('btnCheckout');
    if (btnCheckout) btnCheckout.disabled = true;
    updateHeroStats();
}

function formatarDinheiro(valor) {
    return parseFloat(valor).toFixed(2).replace('.', ',');
}
</script>
</body>
</html>

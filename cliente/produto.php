<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

require_once 'config.php';
require_once 'conexao.php';
require_once 'cms_data_provider.php';

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

$usuarioLogado = isset($_SESSION['cliente']);
$nomeUsuario = $usuarioLogado ? htmlspecialchars($_SESSION['cliente']['nome']) : '';

$freteGratisValor = getFreteGratisThreshold($pdo);
$produtoId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : 0;

if ($produtoId <= 0) {
    header('Location: produtos.php');
    exit;
}

$query = "SELECT p.*, c.nome AS categoria, c.id AS categoria_id, c.menu_group AS category_menu_group,
                 CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento
          FROM produtos p
          LEFT JOIN categorias c ON p.categoria_id = c.id
          LEFT JOIN home_featured_products fp ON p.id = fp.product_id AND fp.section_key = 'launches'
          WHERE p.id = ? AND p.status = 'ativo'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $produtoId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produto = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$produto) {
    header('Location: produtos.php');
    exit;
}

function productImageUrl(?string $fileName): string
{
    $name = trim((string) ($fileName ?? ''));
    if ($name === '') {
        return '';
    }

    $safeName = basename($name);
    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'produtos' . DIRECTORY_SEPARATOR . $safeName;

    if (!is_file($absolutePath)) {
        return '';
    }

    return '../admin/assets/images/produtos/' . rawurlencode($safeName);
}

$produtosRelacionados = [];
if ($produto['categoria_id']) {
    $queryRelacionados = "SELECT p.*, c.menu_group AS category_menu_group,
                          CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento
                          FROM produtos p
                          LEFT JOIN categorias c ON p.categoria_id = c.id
                          LEFT JOIN home_featured_products fp ON p.id = fp.product_id AND fp.section_key = 'launches'
                          WHERE p.categoria_id = ? AND p.id != ? AND p.status = 'ativo'
                          ORDER BY RAND()
                          LIMIT 6";
    $stmtRel = mysqli_prepare($conn, $queryRelacionados);
    mysqli_stmt_bind_param($stmtRel, 'ii', $produto['categoria_id'], $produtoId);
    mysqli_stmt_execute($stmtRel);
    $resultRel = mysqli_stmt_get_result($stmtRel);
    while ($row = mysqli_fetch_assoc($resultRel)) {
        $produtosRelacionados[] = $row;
    }
    mysqli_stmt_close($stmtRel);
}

$produtosCombos = [];
if (!empty($produto['marca'])) {
    $queryCombos = "SELECT p.*, c.menu_group AS category_menu_group,
                    CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento
                    FROM produtos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN home_featured_products fp ON p.id = fp.product_id AND fp.section_key = 'launches'
                    WHERE p.marca = ? AND p.id != ? AND p.status = 'ativo'
                    ORDER BY RAND()
                    LIMIT 6";
    $stmtCombos = mysqli_prepare($conn, $queryCombos);
    mysqli_stmt_bind_param($stmtCombos, 'si', $produto['marca'], $produtoId);
    mysqli_stmt_execute($stmtCombos);
    $resultCombos = mysqli_stmt_get_result($stmtCombos);
    while ($row = mysqli_fetch_assoc($resultCombos)) {
        $produtosCombos[] = $row;
    }
    mysqli_stmt_close($stmtCombos);
}

$variacoes = [];
$variacoesPorTipo = [];
$checkTableVariacoes = mysqli_query($conn, "SHOW TABLES LIKE 'produto_variacoes'");
if ($checkTableVariacoes && mysqli_num_rows($checkTableVariacoes) > 0) {
    $queryVariacoes = "SELECT * FROM produto_variacoes
                       WHERE produto_id = ? AND ativo = 1
                       ORDER BY tipo, id ASC";
    $stmtVar = mysqli_prepare($conn, $queryVariacoes);
    mysqli_stmt_bind_param($stmtVar, 'i', $produtoId);
    mysqli_stmt_execute($stmtVar);
    $resultVar = mysqli_stmt_get_result($stmtVar);

    while ($row = mysqli_fetch_assoc($resultVar)) {
        $variacoes[] = $row;
        $tipo = trim((string) ($row['tipo'] ?? 'Variação'));
        if (!isset($variacoesPorTipo[$tipo])) {
            $variacoesPorTipo[$tipo] = [];
        }
        $variacoesPorTipo[$tipo][] = $row;
    }
    mysqli_stmt_close($stmtVar);
}

$galeriaImagens = [];
if (!empty($produto['imagens'])) {
    $galeriaImagens = json_decode($produto['imagens'], true) ?: [];
}

$imagemPrincipal = productImageUrl($produto['imagem_principal'] ?? '');

$thumbs = [];
if ($imagemPrincipal !== '') {
    $thumbs[] = $imagemPrincipal;
}
foreach ($galeriaImagens as $imgNome) {
    if (!empty($imgNome)) {
        $imgPath = productImageUrl((string) $imgNome);
        if ($imgPath === '') {
            continue;
        }
        if (!in_array($imgPath, $thumbs, true)) {
            $thumbs[] = $imgPath;
        }
    }
}

$tipoTamanhoKey = null;
foreach (array_keys($variacoesPorTipo) as $tipoKey) {
    if (mb_strtolower($tipoKey) === 'tamanho') {
        $tipoTamanhoKey = $tipoKey;
        break;
    }
}

$tamanhos = [];
if ($tipoTamanhoKey !== null) {
    $precoProdutoBase = (float) ($produto['preco'] ?? 0);

    foreach ($variacoesPorTipo[$tipoTamanhoKey] as $v) {
        $usaPrecoPadraoProduto = !(isset($v['preco']) && (float) $v['preco'] > 0);
        $precoVar = $usaPrecoPadraoProduto ? $precoProdutoBase : (float) $v['preco'];
        $precoPromoVar = isset($v['preco_promocional']) && (float) $v['preco_promocional'] > 0
            ? (float) $v['preco_promocional']
            : null;

        if ($precoPromoVar !== null && $precoPromoVar >= $precoVar) {
            $precoPromoVar = null;
        }

        $imgVar = productImageUrl($v['imagem_principal'] ?? '');
        if ($imgVar === '') {
            $imgVar = productImageUrl($v['imagem'] ?? '');
        }

        $tamanhos[] = [
            'label' => (string) ($v['valor'] ?? 'Tamanho'),
            'variacao_id' => (int) ($v['id'] ?? 0),
            'preco' => $precoVar,
            'preco_promo' => $precoPromoVar,
            'estoque' => isset($v['estoque']) ? (int) $v['estoque'] : (int) ($produto['estoque'] ?? 0),
            'imagem' => $imgVar,
        ];
    }
}

$tamanhoPadrao = null;

$precoOriginalProduto = (float) ($produto['preco'] ?? 0);
$precoBaseProduto = isOnSale($produto)
    ? (float) ($produto['preco_promocional'] ?? $produto['preco'])
    : (float) ($produto['preco'] ?? 0);
$precoOriginal = $precoOriginalProduto;
$precoBase = $precoBaseProduto;

if ($tamanhoPadrao !== null) {
    $precoOriginal = (float) ($tamanhoPadrao['preco'] ?? $precoOriginalProduto);
    $promoPadrao = $tamanhoPadrao['preco_promo'] !== null ? (float) $tamanhoPadrao['preco_promo'] : null;
    $precoBase = ($promoPadrao !== null && $promoPadrao > 0 && $promoPadrao < $precoOriginal)
        ? $promoPadrao
        : $precoOriginal;
}

$descontoPerc = ($precoOriginal > 0 && $precoBase < $precoOriginal)
    ? (int) round((($precoOriginal - $precoBase) / $precoOriginal) * 100)
    : 0;

$categoriaTexto = '';
if (!empty($produto['categoria']) && !empty($produto['marca'])) {
    $categoriaTexto = $produto['categoria'] . ' · ' . $produto['marca'];
} elseif (!empty($produto['categoria'])) {
    $categoriaTexto = $produto['categoria'];
} elseif (!empty($produto['marca'])) {
    $categoriaTexto = $produto['marca'];
} else {
    $categoriaTexto = 'Seleções · Retrô';
}

$descricaoCurta = trim((string) ($produto['descricao'] ?? ''));
if ($descricaoCurta === '') {
    $descricaoCurta = 'Peça premium da RARE7 com caimento moderno, acabamento refinado e presença forte para elevar seu drop.';
}
if (mb_strlen($descricaoCurta) > 180) {
    $descricaoCurta = mb_substr($descricaoCurta, 0, 180) . '...';
}

$tagsProduto = getProductTags($produto);
$badgeProduto = !empty($tagsProduto) ? (string) ($tagsProduto[0]['label'] ?? '') : '';

$relacionadosFinal = array_slice($produtosRelacionados, 0, 3);
if (count($relacionadosFinal) < 3) {
    foreach ($produtosCombos as $combo) {
        if ((int) $combo['id'] === $produtoId) {
            continue;
        }
        $exists = false;
        foreach ($relacionadosFinal as $r) {
            if ((int) $r['id'] === (int) $combo['id']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $relacionadosFinal[] = $combo;
        }
        if (count($relacionadosFinal) >= 3) {
            break;
        }
    }
}

$currentPage = 'cart';
$pageTitle = htmlspecialchars($produto['nome']) . ' | RARE7';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>

<style>
    body {
        padding-top: 0 !important;
    }
</style>

<section class="rare-product-page">
    <div class="rare-product-shell">
        <header class="rare-product-topbar" aria-label="Resumo do produto">
            <div>
                <p class="rare-product-topbar-kicker"><?php echo htmlspecialchars($categoriaTexto); ?></p>
                <h2><?php echo htmlspecialchars($produto['nome']); ?></h2>
            </div>
            <div class="rare-product-topbar-meta">
                <?php if ($badgeProduto !== ''): ?>
                    <span><?php echo htmlspecialchars($badgeProduto); ?></span>
                <?php endif; ?>
                <span>Estoque: <?php echo (int) ($produto['estoque'] ?? 0); ?></span>
            </div>
        </header>

        <nav class="rare-product-breadcrumb" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="produtos.php">Produtos</a>
            <span>/</span>
            <span><?php echo htmlspecialchars($produto['nome']); ?></span>
        </nav>

        <div class="rare-product-main">
            <div class="rare-gallery-card">
                <figure class="rare-gallery-main">
                    <?php if ($badgeProduto !== ''): ?>
                        <span class="rare-gallery-badge"><?php echo strtoupper(htmlspecialchars($badgeProduto)); ?></span>
                    <?php endif; ?>
                    <?php if ($imagemPrincipal !== ''): ?>
                        <img
                            id="rareMainImage"
                            src="<?php echo htmlspecialchars($imagemPrincipal); ?>"
                            alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                            loading="lazy"
                            onerror="this.style.display='none'; this.parentElement.classList.add('is-fallback');"
                        >
                    <?php endif; ?>
                    <div class="rare-gallery-fallback">
                        <span class="material-symbols-sharp">sports_soccer</span>
                    </div>
                </figure>

                <?php if (!empty($thumbs)): ?>
                    <div class="rare-gallery-thumbs" id="rareThumbs">
                        <?php foreach ($thumbs as $idx => $thumb): ?>
                            <button
                                type="button"
                                class="rare-thumb<?php echo $idx === 0 ? ' is-active' : ''; ?>"
                                data-image="<?php echo htmlspecialchars($thumb); ?>"
                                aria-label="Imagem <?php echo $idx + 1; ?>"
                            >
                                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Thumb <?php echo $idx + 1; ?>" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rare-buy-card">
                <p class="rare-buy-category"><?php echo htmlspecialchars($categoriaTexto); ?></p>
                <h1><?php echo htmlspecialchars($produto['nome']); ?></h1>
                <?php if (!empty($tagsProduto)): ?>
                    <div class="rare-product-tags">
                        <?php foreach ($tagsProduto as $tag): ?>
                            <span class="rare-tag rare-tag-<?php echo htmlspecialchars((string) ($tag['type'] ?? 'custom')); ?>">
                                <?php echo htmlspecialchars((string) ($tag['label'] ?? '')); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p class="rare-buy-description"><?php echo htmlspecialchars($descricaoCurta); ?></p>

                <div class="rare-price-box" id="rarePriceBox">
                    <div class="rare-price-row">
                        <strong class="rare-price-current" id="rarePriceCurrent"><?php echo formatPrice($precoBase); ?></strong>
                        <?php if ($precoBase < $precoOriginal): ?>
                            <span class="rare-price-old" id="rarePriceOld"><?php echo formatPrice($precoOriginal); ?></span>
                            <span class="rare-discount-badge" id="rareDiscountBadge"><?php echo $descontoPerc; ?>% OFF</span>
                        <?php else: ?>
                            <span class="rare-price-old is-hidden" id="rarePriceOld"></span>
                            <span class="rare-discount-badge is-hidden" id="rareDiscountBadge"></span>
                        <?php endif; ?>
                    </div>
                    <small id="rarePriceHint">Preço unitário</small>
                    <small class="rare-price-total" id="rarePriceTotal">Total: <?php echo formatPrice($precoBase); ?></small>
                    <small class="rare-stock-status" id="rareStockStatus"></small>
                </div>

                <?php if (!empty($tamanhos)): ?>
                <div class="rare-size-section">
                    <div class="rare-size-header">
                        <span>Tamanhos</span>
                        <a href="#" onclick="event.preventDefault();">Guia de medidas</a>
                    </div>
                    <div class="rare-size-grid" id="rareSizeGrid">
                        <?php foreach ($tamanhos as $size): ?>
                            <?php $sizeStock = (int) ($size['estoque'] ?? 0); ?>
                            <button
                                type="button"
                                class="rare-size-btn<?php echo ($tamanhoPadrao !== null && (int) $size['variacao_id'] === (int) ($tamanhoPadrao['variacao_id'] ?? 0)) ? ' is-active' : ''; ?><?php echo $sizeStock <= 0 ? ' is-sold-out' : ''; ?>"
                                data-size="<?php echo htmlspecialchars($size['label']); ?>"
                                data-variacao-id="<?php echo (int) $size['variacao_id']; ?>"
                                data-preco="<?php echo (float) $size['preco']; ?>"
                                data-preco-promo="<?php echo $size['preco_promo'] !== null ? (float) $size['preco_promo'] : ''; ?>"
                                data-estoque="<?php echo $sizeStock; ?>"
                                data-imagem="<?php echo htmlspecialchars($size['imagem']); ?>"
                                <?php echo $sizeStock <= 0 ? 'disabled aria-disabled="true" aria-label="Tamanho indisponível"' : ''; ?>
                            >
                                <?php echo htmlspecialchars((string) $size['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="rare-personalization-card">
                    <div class="rare-personalization-head">
                        <strong>Personalização</strong>
                        <label class="rare-check-inline">
                            <input type="checkbox" id="rarePersonalizeToggle">
                            <span>Adicionar + R$ 30,00</span>
                        </label>
                    </div>
                    <div class="rare-personalization-fields" id="rarePersonalizationFields" style="display:none;"></div>
                    <small class="rare-personalization-helper" id="rarePersonalizationHelper" style="display:none;">
                        Preencha nome e número para cada camisa personalizada.
                    </small>
                </div>

                <div class="rare-qty-total-wrap">
                    <div class="rare-qty-box" aria-label="Quantidade">
                        <button type="button" id="rareQtyMinus">-</button>
                        <span id="rareQtyValue">1</span>
                        <button type="button" id="rareQtyPlus">+</button>
                    </div>
                </div>

                <div class="rare-buy-actions">
                    <button type="button" class="rare-btn rare-btn-secondary" id="rareAddCartBtn">Adicionar ao carrinho</button>
                    <button type="button" class="rare-btn rare-btn-primary" id="rareBuyNowBtn">Comprar agora</button>
                </div>

                <input type="hidden" id="variacaoSelecionadaId" value="<?php echo $tamanhoPadrao ? (int) $tamanhoPadrao['variacao_id'] : 0; ?>">
                <input type="hidden" id="variacaoSelecionadaValor" value="<?php echo $tamanhoPadrao ? htmlspecialchars((string) $tamanhoPadrao['label']) : ''; ?>">
            </div>
        </div>

        <div class="rare-product-bottom">
            <div class="rare-info-card">
                <div class="rare-tabs">
                    <button class="is-active" type="button" data-info-tab="descricao">Descrição</button>
                    <button type="button" data-info-tab="detalhes">Detalhes</button>
                    <button type="button" data-info-tab="entrega">Entrega</button>
                </div>
                <div class="rare-tab-content">
                    <div class="rare-tab-pane is-active" data-info-pane="descricao">
                        <?php if (!empty($produto['descricao'])): ?>
                            <?php echo nl2br(htmlspecialchars($produto['descricao'])); ?>
                        <?php else: ?>
                            Modelagem premium, tecido respirável e acabamento de alta qualidade para uso casual e performance no dia a dia.
                        <?php endif; ?>
                    </div>

                    <div class="rare-tab-pane" data-info-pane="detalhes">
                        <p><strong>Categoria:</strong> <?php echo htmlspecialchars((string) ($produto['categoria'] ?? 'Não informada')); ?></p>
                        <p><strong>Marca:</strong> <?php echo htmlspecialchars((string) ($produto['marca'] ?? 'RARE7')); ?></p>
                        <p><strong>Estoque:</strong> <?php echo (int) ($produto['estoque'] ?? 0); ?> unidade(s)</p>
                        <p><strong>Origem:</strong> Produto oficial da coleção RARE7.</p>
                    </div>

                    <div class="rare-tab-pane" data-info-pane="entrega">
                        <p>Envio para todo o Brasil com cálculo em tempo real no CEP.</p>
                        <p>Frete grátis para pedidos acima de <strong><?php echo formatPrice((float) $freteGratisValor); ?></strong>.</p>
                        <p>Prazo médio após aprovação do pagamento: 1 a 2 dias úteis para postagem.</p>
                    </div>
                </div>
            </div>

            <aside class="rare-shipping-card">
                <h3>Entrega e compra</h3>
                <div class="rare-shipping-form">
                    <label for="rareCepInput">Calcular frete</label>
                    <div class="rare-shipping-input-row">
                        <input type="text" id="rareCepInput" placeholder="Digite seu CEP" maxlength="9">
                        <button type="button" id="rareCalcFreteBtn">Calcular</button>
                    </div>
                </div>
                <div class="rare-shipping-result" id="rareFreteResult">
                    <p><strong>Entrega padrão</strong> -> 3 a 5 dias úteis</p>
                    <p><strong>Valor estimado</strong> -> R$ 18,90</p>
                </div>
                <button type="button" class="rare-btn rare-btn-primary rare-buy-full" id="rareBuyNowAside">Comprar agora</button>
            </aside>
        </div>

        <?php if (!empty($relacionadosFinal)): ?>
            <section class="rare-related-section">
                <span class="rare-related-kicker">Relacionados</span>
                <h2>Complete seu drop</h2>
                <div class="rare-related-grid">
                    <?php foreach ($relacionadosFinal as $rel): ?>
                        <?php
                            $imgRel = productImageUrl($rel['imagem_principal'] ?? '');
                            $precoRel = isOnSale($rel)
                                ? (float) ($rel['preco_promocional'] ?? $rel['preco'])
                                : (float) ($rel['preco'] ?? 0);
                            $relTags = getProductTags($rel);
                        ?>
                        <article class="rare-related-card product-card">
                            <a href="produto.php?id=<?php echo (int) $rel['id']; ?>" class="rare-related-media product-image-wrap">
                                <?php if ($imgRel !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($imgRel); ?>" alt="<?php echo htmlspecialchars($rel['nome']); ?>" loading="lazy">
                                <?php endif; ?>
                                <div class="rare-related-fallback">
                                    <span class="material-symbols-sharp">sports_soccer</span>
                                </div>
                            </a>
                            <div class="rare-related-copy product-info">
                                <h3><?php echo htmlspecialchars($rel['nome']); ?></h3>
                                <?php if (!empty($relTags)): ?>
                                    <div class="rare-product-tags-inline">
                                        <?php foreach ($relTags as $tag): ?>
                                            <span class="rare-tag rare-tag-small rare-tag-<?php echo htmlspecialchars((string) ($tag['type'] ?? 'custom')); ?>">
                                                <?php echo htmlspecialchars((string) ($tag['label'] ?? '')); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="price-line">
                                    <span class="gold-price"><?php echo formatPrice($precoRel); ?></span>
                                </div>
                                <div class="vitrine-actions rare-related-actions">
                                    <a href="produto.php?id=<?php echo (int) $rel['id']; ?>" class="vitrine-buy">Ver produto</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    const PRODUCT = {
        id: <?php echo (int) $produto['id']; ?>,
        name: <?php echo json_encode((string) $produto['nome']); ?>,
        image: <?php echo json_encode((string) $imagemPrincipal); ?>,
        basePrice: <?php echo json_encode($precoBase); ?>,
        oldPrice: <?php echo json_encode($precoOriginal); ?>,
        baseStock: <?php echo json_encode((int) ($produto['estoque'] ?? 0)); ?>,
        personalizationFee: 30,
        freeShippingThreshold: <?php echo json_encode((float) $freteGratisValor); ?>,
        hasVariations: <?php echo json_encode(!empty($variacoes)); ?>
    };

    let selected = {
        size: <?php echo json_encode($tamanhoPadrao ? (string) $tamanhoPadrao['label'] : ''); ?>,
        variationId: <?php echo json_encode($tamanhoPadrao ? (int) $tamanhoPadrao['variacao_id'] : 0); ?>,
        price: <?php echo json_encode($tamanhoPadrao ? (float) $tamanhoPadrao['preco'] : $precoBase); ?>,
        promoPrice: <?php echo json_encode($tamanhoPadrao && $tamanhoPadrao['preco_promo'] !== null ? (float) $tamanhoPadrao['preco_promo'] : null); ?>,
        stock: <?php echo json_encode($tamanhoPadrao ? (int) $tamanhoPadrao['estoque'] : (!empty($tamanhos) ? 0 : (int) ($produto['estoque'] ?? 0))); ?>,
        image: <?php echo json_encode($tamanhoPadrao ? (string) $tamanhoPadrao['imagem'] : ''); ?>,
        qty: 1,
        personalize: false,
        personalizations: []
    };

    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => Array.from(document.querySelectorAll(sel));

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatBRL(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
    }

    function parseAmount(value) {
        const raw = String(value ?? '').trim();
        if (raw === '') return null;

        // Aceita entradas com virgula e simbolo de moeda sem quebrar o calculo.
        const normalized = raw
            .replace(/[^\d,.-]/g, '')
            .replace(/\.(?=\d{3}(\D|$))/g, '')
            .replace(',', '.');

        const parsed = Number.parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function unitPrice() {
        const usingVariation = Boolean(PRODUCT.hasVariations && Number(selected.variationId || 0) > 0);

        const variationBase = usingVariation
            ? Number(selected.price || 0)
            : (Number(PRODUCT.oldPrice) || Number(PRODUCT.basePrice));

        const productPromo = Number(PRODUCT.basePrice || 0);
        const productOld = Number(PRODUCT.oldPrice || 0);
        const productHasPromo = productPromo > 0 && productOld > productPromo;

        const promoCandidate = usingVariation
            ? Number(selected.promoPrice || 0)
            : (productHasPromo ? productPromo : 0);

        const effectiveUnit = promoCandidate > 0 && promoCandidate < variationBase
            ? promoCandidate
            : (variationBase > 0 ? variationBase : Number(PRODUCT.basePrice || 0));

        return effectiveUnit + (selected.personalize ? PRODUCT.personalizationFee : 0);
    }

    function priceReference() {
        const usingVariation = Boolean(PRODUCT.hasVariations && Number(selected.variationId || 0) > 0);

        const variationBase = usingVariation
            ? Number(selected.price || 0)
            : (Number(PRODUCT.oldPrice) || Number(PRODUCT.basePrice));

        const productPromo = Number(PRODUCT.basePrice || 0);
        const productOld = Number(PRODUCT.oldPrice || 0);
        const productHasPromo = productPromo > 0 && productOld > productPromo;
        const promoCandidate = usingVariation
            ? Number(selected.promoPrice || 0)
            : (productHasPromo ? productPromo : 0);

        if (promoCandidate > 0 && promoCandidate < variationBase) {
            return variationBase;
        }

        return variationBase > 0 ? variationBase : Number(PRODUCT.oldPrice || 0);
    }

    function refreshPriceAndTotal() {
        const availableStock = Number(selected.stock || PRODUCT.baseStock || 0);
        const requiresSizeSelection = Boolean(PRODUCT.hasVariations && Number(selected.variationId || 0) <= 0);

        if (availableStock > 0 && selected.qty > availableStock) {
            selected.qty = availableStock;
        }

        const currentUnit = unitPrice();
        const oldRef = priceReference();
        const totalPrice = currentUnit * selected.qty;
        const totalReference = oldRef * selected.qty;

        const currentEl = $('#rarePriceCurrent');
        const oldEl = $('#rarePriceOld');
        const discountEl = $('#rareDiscountBadge');
        const hintEl = $('#rarePriceHint');
        const totalEl = $('#rarePriceTotal');
        const stockEl = $('#rareStockStatus');
        const qtyValue = $('#rareQtyValue');
        const addBtn = $('#rareAddCartBtn');
        const buyBtn = $('#rareBuyNowBtn');
        const asideBuy = $('#rareBuyNowAside');
        const plusBtn = $('#rareQtyPlus');

        if (currentEl) currentEl.textContent = formatBRL(totalPrice);

        if (oldEl && discountEl) {
            if (totalReference > totalPrice) {
                const off = Math.round(((totalReference - totalPrice) / totalReference) * 100);
                oldEl.textContent = formatBRL(totalReference);
                discountEl.textContent = off + '% OFF';
                oldEl.classList.remove('is-hidden');
                discountEl.classList.remove('is-hidden');
            } else {
                oldEl.classList.add('is-hidden');
                discountEl.classList.add('is-hidden');
            }
        }

        if (hintEl) {
            const qtyText = selected.qty > 1 ? selected.qty + ' un.' : '1 un.';
            hintEl.textContent = 'Total (' + qtyText + ')';
        }

        if (totalEl) {
            totalEl.textContent = 'Preço unitário: ' + formatBRL(currentUnit);
            if (selected.personalize) {
                totalEl.textContent += ' (com personalização)';
            }
        }

        if (qtyValue) qtyValue.textContent = String(selected.qty);

        if (stockEl) {
            stockEl.textContent = requiresSizeSelection
                ? 'Escolha um tamanho para continuar'
                : (availableStock > 0 ? `${availableStock} unidade(s) disponíveis neste tamanho` : '');
            stockEl.classList.toggle('is-sold-out', availableStock <= 0);
        }

        [addBtn, buyBtn, asideBuy].forEach((button) => {
            if (!button) return;
            const blocked = requiresSizeSelection || availableStock <= 0;
            button.disabled = blocked;
            button.setAttribute('aria-disabled', blocked ? 'true' : 'false');
        });

        if (plusBtn) {
            plusBtn.disabled = requiresSizeSelection || (availableStock > 0 ? selected.qty >= availableStock : true);
        }
    }

    function updateHiddenVariation() {
        const idEl = $('#variacaoSelecionadaId');
        const valEl = $('#variacaoSelecionadaValor');
        if (idEl) idEl.value = selected.variationId || 0;
        if (valEl) valEl.value = selected.size || '';
    }

    function bindThumbs() {
        const mainImage = $('#rareMainImage');
        $$('#rareThumbs .rare-thumb').forEach((btn) => {
            btn.addEventListener('click', function () {
                $$('#rareThumbs .rare-thumb').forEach((b) => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                if (mainImage && btn.dataset.image) {
                    mainImage.src = btn.dataset.image;
                }
            });
        });
    }

    function applySizeFromButton(btn) {
        const stockRaw = (btn.dataset.estoque || '').trim();
        const promoRaw = (btn.dataset.precoPromo || '').trim();
        const priceRaw = (btn.dataset.preco || '').trim();
        selected.size = btn.dataset.size || '';
        selected.variationId = parseInt(btn.dataset.variacaoId || '0', 10) || 0;
        selected.price = parseAmount(priceRaw) ?? Number(PRODUCT.oldPrice || PRODUCT.basePrice || 0);
        selected.promoPrice = parseAmount(promoRaw);
        if (selected.promoPrice !== null && selected.promoPrice >= selected.price) {
            selected.promoPrice = null;
        }
        selected.stock = stockRaw !== '' ? parseInt(stockRaw, 10) || 0 : Number(PRODUCT.baseStock || 0);
        selected.image = btn.dataset.imagem || '';

        if (selected.image) {
            const mainImage = $('#rareMainImage');
            if (mainImage) mainImage.src = selected.image;
        }

        updateHiddenVariation();
        refreshPriceAndTotal();
    }

    function bindSizeButtons() {
        const sizeButtons = $$('.rare-size-btn');
        sizeButtons.forEach((btn) => {
            btn.addEventListener('click', function () {
                if (btn.disabled || Number(btn.dataset.estoque || 0) <= 0) {
                    return;
                }

                const parent = btn.closest('.rare-size-grid');
                if (parent) {
                    parent.querySelectorAll('button').forEach((item) => item.classList.remove('is-active'));
                }
                btn.classList.add('is-active');
                applySizeFromButton(btn);
            });
        });
    }

    function bindPersonalization() {
        const toggle = $('#rarePersonalizeToggle');

        if (!toggle) return;

        toggle.addEventListener('change', function () {
            selected.personalize = toggle.checked;
            if (!toggle.checked) {
                selected.personalizations = [];
            } else if (!Array.isArray(selected.personalizations) || selected.personalizations.length === 0) {
                selected.personalizations = Array.from({ length: selected.qty }, () => ({ name: '', number: '' }));
            }

            renderPersonalizationFields();
            refreshPriceAndTotal();
        });
    }

    function collectPersonalizationInputs() {
        const rows = $$('#rarePersonalizationFields .rare-personalization-row');
        if (!rows.length) {
            return Array.isArray(selected.personalizations) ? selected.personalizations : [];
        }

        return rows.map((row) => {
            const name = (row.querySelector('.rare-custom-name')?.value || '').trim();
            const number = (row.querySelector('.rare-custom-number')?.value || '').trim();
            return { name, number };
        });
    }

    function renderPersonalizationFields() {
        const fields = $('#rarePersonalizationFields');
        const helper = $('#rarePersonalizationHelper');
        if (!fields) return;

        if (!selected.personalize) {
            fields.innerHTML = '';
            fields.style.display = 'none';
            if (helper) helper.style.display = 'none';
            return;
        }

        const currentValues = collectPersonalizationInputs();
        const source = currentValues.length ? currentValues : (Array.isArray(selected.personalizations) ? selected.personalizations : []);

        selected.personalizations = Array.from({ length: selected.qty }, (_, index) => {
            const item = source[index] || {};
            return {
                name: String(item.name || ''),
                number: String(item.number || '')
            };
        });

        fields.style.display = 'grid';
        if (helper) helper.style.display = 'block';

        fields.innerHTML = selected.personalizations.map((item, index) => {
            const rowNumber = index + 1;
            return '<div class="rare-personalization-row" data-personalization-index="' + index + '">'
                + '<span class="rare-personalization-item-label">Camisa ' + rowNumber + '</span>'
                + '<input type="text" class="rare-custom-name" maxlength="14" placeholder="Nome nas costas" value="' + escapeHtml(item.name) + '">'
                + '<input type="number" class="rare-custom-number" min="1" max="99" placeholder="Número" value="' + escapeHtml(item.number) + '">'
                + '</div>';
        }).join('');

        fields.querySelectorAll('.rare-personalization-row').forEach((row) => {
            const index = Number(row.getAttribute('data-personalization-index'));
            const nameInput = row.querySelector('.rare-custom-name');
            const numberInput = row.querySelector('.rare-custom-number');

            if (nameInput) {
                nameInput.addEventListener('input', function () {
                    if (!selected.personalizations[index]) selected.personalizations[index] = { name: '', number: '' };
                    selected.personalizations[index].name = nameInput.value;
                });
            }

            if (numberInput) {
                numberInput.addEventListener('input', function () {
                    if (!selected.personalizations[index]) selected.personalizations[index] = { name: '', number: '' };
                    selected.personalizations[index].number = numberInput.value;
                });
            }
        });
    }

    function bindQty() {
        const minus = $('#rareQtyMinus');
        const plus = $('#rareQtyPlus');

        if (minus) {
            minus.addEventListener('click', function () {
                selected.qty = Math.max(1, selected.qty - 1);
                if (selected.personalize) {
                    renderPersonalizationFields();
                }
                refreshPriceAndTotal();
            });
        }

        if (plus) {
            plus.addEventListener('click', function () {
                selected.qty = Math.min(20, selected.qty + 1);
                if (selected.personalize) {
                    renderPersonalizationFields();
                }
                refreshPriceAndTotal();
            });
        }
    }

    function bindInfoTabs() {
        const tabs = $$('[data-info-tab]');
        const panes = $$('[data-info-pane]');

        if (!tabs.length || !panes.length) return;

        tabs.forEach((tab) => {
            tab.addEventListener('click', function () {
                const key = tab.dataset.infoTab || '';

                tabs.forEach((item) => item.classList.remove('is-active'));
                panes.forEach((pane) => pane.classList.remove('is-active'));

                tab.classList.add('is-active');

                const pane = $('[data-info-pane="' + key + '"]');
                if (pane) {
                    pane.classList.add('is-active');
                }
            });
        });
    }

    function getCart() {
        try {
            return JSON.parse(localStorage.getItem('dz_cart') || '[]');
        } catch (e) {
            return [];
        }
    }

    function setCart(cart) {
        localStorage.setItem('dz_cart', JSON.stringify(cart));
    }

    function cartSubtotal() {
        return getCart().reduce((sum, item) => sum + (Number(item.price || 0) * Number(item.qty || 0)), 0);
    }

    function updateCartBadge() {
        const badge = $('#cartBadge');
        if (!badge) return;
        const totalItems = getCart().reduce((sum, item) => sum + Number(item.qty || 0), 0);
        badge.textContent = String(totalItems);
        badge.style.display = totalItems > 0 ? 'flex' : 'none';
    }

    function addCurrentProductToCart() {
        if (PRODUCT.hasVariations && !selected.variationId && !selected.size) {
            alert('Selecione uma opção antes de adicionar ao carrinho.');
            return;
        }
        if (Number(selected.stock || PRODUCT.baseStock || 0) <= 0) {
            alert('A opção selecionada está esgotada.');
            return;
        }
        const price = unitPrice();
        const image = selected.image || PRODUCT.image || '../image/logo_png.png';
        const finalName = PRODUCT.name;
        const cart = getCart();

        const baseVariantParts = [];
        if (selected.size) {
            baseVariantParts.push(selected.size);
        }

        if (selected.personalize) {
            const personalizations = collectPersonalizationInputs();

            if (personalizations.length !== selected.qty) {
                alert('Não foi possível validar as personalizações. Tente novamente.');
                return;
            }

            for (let i = 0; i < personalizations.length; i += 1) {
                const item = personalizations[i] || {};
                const name = String(item.name || '').trim();
                const number = String(item.number || '').trim();

                if (!name || !number) {
                    alert('Preencha nome e número da Camisa ' + (i + 1) + '.');
                    return;
                }

                const n = Number(number);
                if (!Number.isFinite(n) || n < 1 || n > 99) {
                    alert('Número inválido na Camisa ' + (i + 1) + '. Use de 1 a 99.');
                    return;
                }

                const customText = name + ' · #' + n;
                const variantLabel = [...baseVariantParts, 'Personalização: ' + customText].join(' | ');
                const variantKey = [
                    selected.variationId || selected.size || 'base',
                    'custom',
                    name.toLowerCase().replace(/\s+/g, '-'),
                    String(n)
                ].join('::');

                const existing = cart.find((cartItem) => String(cartItem.id) === String(PRODUCT.id) && String(cartItem.variantKey || '') === variantKey);

                if (existing) {
                    existing.qty = Number(existing.qty || 0) + 1;
                } else {
                    cart.push({
                        id: PRODUCT.id,
                        name: finalName,
                        price: price,
                        qty: 1,
                        variant: variantLabel,
                        variantKey: variantKey,
                        image: image,
                        addedAt: new Date().toISOString()
                    });
                }
            }
        } else {
            const variantLabel = baseVariantParts.join(' | ');
            const variantKey = [selected.variationId || selected.size || 'base', 'nop'].join('::');
            const existing = cart.find((item) => String(item.id) === String(PRODUCT.id) && String(item.variantKey || '') === variantKey);

            if (existing) {
                existing.qty = Number(existing.qty || 0) + selected.qty;
            } else {
                cart.push({
                    id: PRODUCT.id,
                    name: finalName,
                    price: price,
                    qty: selected.qty,
                    variant: variantLabel,
                    variantKey: variantKey,
                    image: image,
                    addedAt: new Date().toISOString()
                });
            }
        }

        setCart(cart);
        updateCartBadge();
        openMiniCart();
        renderMiniCart();
    }

    function removeFromCart(itemId, variantKey) {
        const next = getCart().filter((item) => !(String(item.id) === String(itemId) && String(item.variantKey || '') === String(variantKey || '')));
        setCart(next);
        updateCartBadge();
        renderMiniCart();
    }

    function updateQty(itemId, variantKey, nextQty) {
        const cart = getCart();
        const target = cart.find((item) => String(item.id) === String(itemId) && String(item.variantKey || '') === String(variantKey || ''));
        if (!target) return;

        if (nextQty <= 0) {
            removeFromCart(itemId, variantKey);
            return;
        }

        target.qty = nextQty;
        setCart(cart);
        updateCartBadge();
        renderMiniCart();
    }

    function renderMiniCart() {
        const body = $('#miniCartBody');
        const subtotalEl = $('#miniCartSubtotal');
        const shippingBar = $('#freeShippingBar');
        if (!body || !subtotalEl || !shippingBar) return;

        const cart = getCart();
        if (cart.length === 0) {
            body.innerHTML = '<div class="cart-empty"><div class="cart-empty-icon">🛒</div><h3>Seu carrinho está vazio</h3><p>Adicione produtos para começar.</p><button class="btn-continue-shopping" onclick="closeMiniCart()">Continuar comprando</button></div>';
            subtotalEl.textContent = 'R$ 0,00';
            shippingBar.innerHTML = '<div class="shipping-text">Faltam ' + formatBRL(PRODUCT.freeShippingThreshold) + ' para frete grátis</div><div class="shipping-progress"><div class="shipping-progress-bar" style="width:0%"></div></div>';
            return;
        }

        body.innerHTML = cart.map((item) => {
            const variant = item.variant ? '<div class="cart-item-variant">' + item.variant + '</div>' : '';
            const variantKey = String(item.variantKey || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            return '<div class="cart-item">'
                + '<div class="cart-item-image">'
                + (item.image ? '<img src="' + item.image + '" alt="' + item.name.replace(/"/g, '&quot;') + '">' : '<span>🛍️</span>')
                + '</div>'
                + '<div class="cart-item-details">'
                + '<div class="cart-item-name">' + item.name + '</div>'
                + variant
                + '<div class="cart-item-price">' + formatBRL(item.price) + '</div>'
                + '<div class="cart-item-actions">'
                + '<div class="qty-control">'
                + '<button class="qty-btn" onclick="updateQty(' + item.id + ', \'' + variantKey + '\', ' + (item.qty - 1) + ')">−</button>'
                + '<span class="qty-value">' + item.qty + '</span>'
                + '<button class="qty-btn" onclick="updateQty(' + item.id + ', \'' + variantKey + '\', ' + (item.qty + 1) + ')">+</button>'
                + '</div>'
                + '<button class="btn-remove-item" onclick="removeFromCart(' + item.id + ', \'' + variantKey + '\')">'
                + '<span class="material-symbols-sharp">delete</span>'
                + '</button>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('');

        const subtotal = cartSubtotal();
        subtotalEl.textContent = formatBRL(subtotal);

        const remaining = PRODUCT.freeShippingThreshold - subtotal;
        const pct = Math.min(100, Math.max(0, (subtotal / PRODUCT.freeShippingThreshold) * 100));

        if (remaining > 0) {
            shippingBar.innerHTML = '<div class="shipping-text">Faltam ' + formatBRL(remaining) + ' para frete grátis</div><div class="shipping-progress"><div class="shipping-progress-bar" style="width:' + pct + '%"></div></div>';
        } else {
            shippingBar.innerHTML = '<div class="shipping-unlocked"><span class="material-symbols-sharp">local_shipping</span>Você desbloqueou frete grátis</div>';
        }
    }

    function openMiniCart() {
        const overlay = $('#miniCartOverlay');
        const drawer = $('#miniCartDrawer');
        if (overlay) overlay.classList.add('active');
        if (drawer) drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMiniCart() {
        const overlay = $('#miniCartOverlay');
        const drawer = $('#miniCartDrawer');
        if (overlay) overlay.classList.remove('active');
        if (drawer) drawer.classList.remove('active');
        document.body.style.overflow = '';
    }

    async function calcularFrete() {
        const cepInput = $('#rareCepInput');
        const result = $('#rareFreteResult');
        if (!cepInput || !result) return;

        const cep = cepInput.value.trim();
        if (!cep) {
            result.innerHTML = '<p>Digite um CEP válido para calcular.</p>';
            return;
        }

        result.innerHTML = '<p>Calculando frete...</p>';

        try {
            const response = await fetch('api/frete-api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'calculate',
                    cep: cep,
                    subtotal: unitPrice() * selected.qty,
                    items: [{ produto_id: PRODUCT.id, quantidade: selected.qty }]
                })
            });

            const data = await response.json();
            if (!data.success || !data.data || !Array.isArray(data.data.opcoes) || data.data.opcoes.length === 0) {
                result.innerHTML = '<p>Não foi possível calcular agora. Tente novamente.</p>';
                return;
            }

            const opcao = data.data.opcoes[0];
            const prazo = (opcao.prazo_texto || '').trim()
                || (opcao.prazo_dias ? `${opcao.prazo_dias} dias úteis` : '')
                || opcao.prazo
                || '3 a 5 dias úteis';
            const valor = typeof opcao.valor === 'number' ? formatBRL(opcao.valor) : 'A consultar';

            result.innerHTML = '<p><strong>Entrega padrão</strong> -> ' + prazo + '</p><p><strong>Valor estimado</strong> -> ' + valor + '</p>';
        } catch (e) {
            result.innerHTML = '<p>Erro ao calcular frete. Tente novamente.</p>';
        }
    }

    window.removeFromCart = removeFromCart;
    window.updateQty = updateQty;
    window.closeMiniCart = closeMiniCart;

    function normalizeProductCardImages(root = document) {
        root.querySelectorAll('.product-image-wrap img').forEach((img) => {
            const wrap = img.closest('.product-image-wrap');
            if (!wrap) return;

            const applyFitMode = () => {
                const w = Number(img.naturalWidth || 0);
                const h = Number(img.naturalHeight || 0);
                if (!w || !h) return;

                const ratio = w / h;
                wrap.classList.toggle('is-fill-image', ratio >= 1.55);
            };

            if (img.complete) {
                applyFitMode();
            }

            img.addEventListener('load', applyFitMode, { once: true });
            window.setTimeout(applyFitMode, 140);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        normalizeProductCardImages(document);
        bindThumbs();
        bindSizeButtons();
        bindPersonalization();
        bindQty();
        bindInfoTabs();

        const activeSizeBtn = document.querySelector('.rare-size-btn.is-active:not([disabled])');
        if (activeSizeBtn) {
            applySizeFromButton(activeSizeBtn);
        }

        renderPersonalizationFields();
        refreshPriceAndTotal();
        updateHiddenVariation();
        updateCartBadge();
        renderMiniCart();

        const addBtn = $('#rareAddCartBtn');
        const buyBtn = $('#rareBuyNowBtn');
        const asideBuy = $('#rareBuyNowAside');
        const calcFreteBtn = $('#rareCalcFreteBtn');
        const cartBtn = $('#cartButton');
        const closeBtn = $('#closeMiniCart');
        const overlay = $('#miniCartOverlay');

        if (addBtn) addBtn.addEventListener('click', addCurrentProductToCart);
        if (buyBtn) {
            buyBtn.addEventListener('click', function () {
                addCurrentProductToCart();
                window.location.href = 'pages/carrinho.php';
            });
        }
        if (asideBuy) {
            asideBuy.addEventListener('click', function () {
                addCurrentProductToCart();
                window.location.href = 'pages/carrinho.php';
            });
        }

        if (calcFreteBtn) calcFreteBtn.addEventListener('click', calcularFrete);
        if (cartBtn) cartBtn.addEventListener('click', function (e) { e.preventDefault(); openMiniCart(); renderMiniCart(); });
        if (closeBtn) closeBtn.addEventListener('click', closeMiniCart);
        if (overlay) overlay.addEventListener('click', closeMiniCart);
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>

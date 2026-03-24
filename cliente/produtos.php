<?php
// E-commerce RARE7 - PÃ¡gina de Produtos
// Listagem com filtros por categoria e busca

session_start();

header('Content-Type: text/html; charset=UTF-8');

// Incluir configuraÃ§Ã£o e conexÃ£o
require_once 'config.php';
require_once 'conexao.php';
require_once 'cms_data_provider.php';

// Instanciar CMS Provider
$cms = new CMSProvider($conn);

// Buscar dados do footer (para includes)
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

// Verificar se usuÃ¡rio estÃ¡ logado
$usuarioLogado = isset($_SESSION['cliente']);
$nomeUsuario = $usuarioLogado ? htmlspecialchars($_SESSION['cliente']['nome']) : '';

// Buscar configuraÃ§Ã£o de frete grÃ¡tis
$freteGratisValor = getFreteGratisThreshold($pdo);

// ===== PROCESSAMENTO DE FILTROS =====
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$menu = isset($_GET['menu']) ? mb_strtolower(trim($_GET['menu'])) : '';
$menuGroupsPadrao = ['clubes', 'selecoes', 'retro', 'raras'];
$isMenuLancamentos = ($menu === 'lancamentos');
$isMenuGroup = in_array($menu, $menuGroupsPadrao, true);
$isMenuTime = (!empty($menu) && !$isMenuLancamentos && !$isMenuGroup);
$marca = isset($_GET['marca']) ? trim($_GET['marca']) : '';
$secao_marcas = isset($_GET['secao']) && $_GET['secao'] == 'marcas'; // Filtrar apenas produtos com marca
$preco_min = isset($_GET['preco_min']) && is_numeric($_GET['preco_min']) ? (float)$_GET['preco_min'] : null;
$preco_max = isset($_GET['preco_max']) && is_numeric($_GET['preco_max']) ? (float)$_GET['preco_max'] : null;
$apenas_promocao = isset($_GET['promo']) && $_GET['promo'] == '1';
$ordenar = isset($_GET['ordenar']) ? trim($_GET['ordenar']) : 'recentes';

// PaginaÃ§Ã£o
$produtosPorPagina = 13; // hero (1) + grid (12 = 3 linhas × 4 colunas)
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaAtual - 1) * $produtosPorPagina;

// ===== BUSCAR MARCAS PARA FILTROS =====
$marcas = [];
$queryMarcas = "SELECT DISTINCT marca FROM produtos 
                WHERE status = 'ativo' AND marca IS NOT NULL AND marca != '' 
                ORDER BY marca";
$resultMarcas = mysqli_query($conn, $queryMarcas);
while ($row = mysqli_fetch_assoc($resultMarcas)) {
    $marcas[] = $row['marca'];
}

// ===== LÃ“GICA ESPECIAL PARA LANÃ‡AMENTOS =====
// Se menu=lancamentos, buscar produtos da tabela home_featured_products (section_key='launches')
if ($isMenuLancamentos) {
    // Query de count para lanÃ§amentos
    $queryCountLancamentos = "
        SELECT COUNT(*) as total
        FROM home_featured_products fp
        INNER JOIN produtos p ON fp.product_id = p.id
        WHERE fp.section_key = 'launches'
          AND p.status = 'ativo'
    ";
    
    $resultCountLancamentos = mysqli_query($conn, $queryCountLancamentos);
    $totalProdutos = mysqli_fetch_assoc($resultCountLancamentos)['total'];
    $totalPaginas = ceil($totalProdutos / $produtosPorPagina);
    
    // Query principal para lanÃ§amentos com paginaÃ§Ã£o
    $queryLancamentos = "
        SELECT 
            p.id,
            p.nome,
            p.descricao,
            p.preco,
            p.preco_promocional,
            p.estoque,
            p.imagem_principal,
            p.created_at,
            c.nome AS categoria,
            fp.position,
                        (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) AS tem_variacoes,
                        (SELECT GROUP_CONCAT(DISTINCT TRIM(pv.valor) ORDER BY pv.valor SEPARATOR '||')
                         FROM produto_variacoes pv
                         WHERE pv.produto_id = p.id
                             AND pv.ativo = 1
                             AND LOWER(TRIM(pv.tipo)) = 'tamanho'
                             AND TRIM(COALESCE(pv.valor, '')) <> '') AS tamanhos_csv,
            'yes' AS is_lancamento
        FROM home_featured_products fp
        INNER JOIN produtos p ON fp.product_id = p.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE fp.section_key = 'launches'
          AND p.status = 'ativo'
        ORDER BY fp.position ASC
        LIMIT ? OFFSET ?
    ";
    
    $stmtLancamentos = mysqli_prepare($conn, $queryLancamentos);
    mysqli_stmt_bind_param($stmtLancamentos, 'ii', $produtosPorPagina, $offset);
    mysqli_stmt_execute($stmtLancamentos);
    $result = mysqli_stmt_get_result($stmtLancamentos);
    
    // Buscar produtos
    $produtos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $produtos[] = $row;
    }
    
    mysqli_stmt_close($stmtLancamentos);
} else {
    // ===== QUERY NORMAL PARA OUTROS FILTROS =====
    // Primeiro, contar total de produtos (sem limit)
    $queryCount = "
        SELECT COUNT(*) as total
        FROM produtos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.status = 'ativo'
    ";

$params = [];
$types = '';

// Filtro por categoria (prioridade) ou menu group
if (!empty($categoria)) {
    // Categoria especÃ­fica tem prioridade
    $queryCount .= " AND LOWER(c.nome) = LOWER(?)";
    $params[] = $categoria;
    $types .= 's';
} elseif ($isMenuGroup) {
    // Se nÃ£o houver categoria, filtrar por grupo de menu
    $queryCount .= " AND c.menu_group = ?";
    $params[] = $menu;
    $types .= 's';
} elseif ($isMenuTime) {
    // menu customizado com nome do time
    $queryCount .= " AND (LOWER(p.nome) LIKE ? OR LOWER(p.descricao) LIKE ? OR LOWER(c.nome) LIKE ? OR LOWER(COALESCE(p.marca, '')) LIKE ?)";
    $menuLike = '%' . $menu . '%';
    $params[] = $menuLike;
    $params[] = $menuLike;
    $params[] = $menuLike;
    $params[] = $menuLike;
    $types .= 'ssss';
}

// Filtro por marca
if (!empty($marca)) {
    $queryCount .= " AND LOWER(p.marca) = LOWER(?)";
    $params[] = $marca;
    $types .= 's';
}

// Filtro por seÃ§Ã£o de marcas (apenas produtos com marca configurada)
if ($secao_marcas && empty($marca)) {
    $queryCount .= " AND p.marca IS NOT NULL AND p.marca != ''";
}

// Filtro por busca
if (!empty($busca)) {
    $queryCount .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
    $buscaParam = '%' . $busca . '%';
    $params[] = $buscaParam;
    $params[] = $buscaParam;
    $types .= 'ss';
}

// Filtro por faixa de preÃ§o
if ($preco_min !== null) {
    $queryCount .= " AND (COALESCE(p.preco_promocional, p.preco) >= ?)";
    $params[] = $preco_min;
    $types .= 'd';
}

if ($preco_max !== null) {
    $queryCount .= " AND (COALESCE(p.preco_promocional, p.preco) <= ?)";
    $params[] = $preco_max;
    $types .= 'd';
}

// Filtro por promoÃ§Ã£o
if ($apenas_promocao) {
    $queryCount .= " AND p.preco_promocional IS NOT NULL AND p.preco_promocional > 0";
}

// Executar count
$stmtCount = mysqli_prepare($conn, $queryCount);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmtCount, $types, ...$params);
}
mysqli_stmt_execute($stmtCount);
$resultCount = mysqli_stmt_get_result($stmtCount);
$totalProdutos = mysqli_fetch_assoc($resultCount)['total'];
mysqli_stmt_close($stmtCount);

$totalPaginas = ceil($totalProdutos / $produtosPorPagina);

// Agora buscar os produtos da pÃ¡gina atual
$query = "
    SELECT 
        p.id,
        p.nome,
        p.descricao,
        p.preco,
        p.preco_promocional,
        p.destaque,
        p.estoque,
        p.imagem_principal,
        p.created_at,
        c.nome AS categoria,
        CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento,
                (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) AS tem_variacoes,
                (SELECT GROUP_CONCAT(DISTINCT TRIM(pv.valor) ORDER BY pv.valor SEPARATOR '||')
                 FROM produto_variacoes pv
                 WHERE pv.produto_id = p.id
                     AND pv.ativo = 1
                     AND LOWER(TRIM(pv.tipo)) = 'tamanho'
                     AND TRIM(COALESCE(pv.valor, '')) <> '') AS tamanhos_csv
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN home_featured_products fp ON p.id = fp.product_id AND fp.section_key = 'launches'
    WHERE p.status = 'ativo'
";

// Resetar params para reutilizar
$params = [];
$types = '';

// Aplicar mesmos filtros - categoria (prioridade) ou menu group
if (!empty($categoria)) {
    // Categoria especÃ­fica tem prioridade
    $query .= " AND LOWER(c.nome) = LOWER(?)";
    $params[] = $categoria;
    $types .= 's';
} elseif ($isMenuGroup) {
    // Se nÃ£o houver categoria, filtrar por grupo de menu
    $query .= " AND c.menu_group = ?";
    $params[] = $menu;
    $types .= 's';
} elseif ($isMenuTime) {
    // menu customizado com nome do time
    $query .= " AND (LOWER(p.nome) LIKE ? OR LOWER(p.descricao) LIKE ? OR LOWER(c.nome) LIKE ? OR LOWER(COALESCE(p.marca, '')) LIKE ?)";
    $menuLike = '%' . $menu . '%';
    $params[] = $menuLike;
    $params[] = $menuLike;
    $params[] = $menuLike;
    $params[] = $menuLike;
    $types .= 'ssss';
}

if (!empty($marca)) {
    $query .= " AND LOWER(p.marca) = LOWER(?)";
    $params[] = $marca;
    $types .= 's';
}

// Filtro por seÃ§Ã£o de marcas (apenas produtos com marca configurada)
if ($secao_marcas && empty($marca)) {
    $query .= " AND p.marca IS NOT NULL AND p.marca != ''";
}

if (!empty($busca)) {
    $query .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
    $buscaParam = '%' . $busca . '%';
    $params[] = $buscaParam;
    $params[] = $buscaParam;
    $types .= 'ss';
}

if ($preco_min !== null) {
    $query .= " AND (COALESCE(p.preco_promocional, p.preco) >= ?)";
    $params[] = $preco_min;
    $types .= 'd';
}

if ($preco_max !== null) {
    $query .= " AND (COALESCE(p.preco_promocional, p.preco) <= ?)";
    $params[] = $preco_max;
    $types .= 'd';
}

if ($apenas_promocao) {
    $query .= " AND p.preco_promocional IS NOT NULL AND p.preco_promocional > 0";
}

// OrdenaÃ§Ã£o
switch ($ordenar) {
    case 'menor_preco':
        $query .= " ORDER BY p.destaque DESC, COALESCE(p.preco_promocional, p.preco) ASC";
        break;
    case 'maior_preco':
        $query .= " ORDER BY p.destaque DESC, COALESCE(p.preco_promocional, p.preco) DESC";
        break;
    case 'nome_az':
        $query .= " ORDER BY p.destaque DESC, p.nome ASC";
        break;
    case 'nome_za':
        $query .= " ORDER BY p.destaque DESC, p.nome DESC";
        break;
    case 'recentes':
    default:
        $query .= " ORDER BY p.destaque DESC, p.id DESC";
        break;
}

// Adicionar LIMIT e OFFSET para paginaÃ§Ã£o
$query .= " LIMIT ? OFFSET ?";
$params[] = $produtosPorPagina;
$params[] = $offset;
$types .= 'ii';

// Executar query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

    // Buscar produtos
    $produtos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $produtos[] = $row;
    }

    mysqli_stmt_close($stmt);
} // Fim do bloco else (query normal)

// Definir tÃ­tulo da pÃ¡gina
foreach ($produtos as &$produtoLinha) {
    $tamanhos = [];
    $csv = trim((string)($produtoLinha['tamanhos_csv'] ?? ''));
    if ($csv !== '') {
        $parts = explode('||', $csv);
        foreach ($parts as $part) {
            $size = trim((string)$part);
            if ($size === '') {
                continue;
            }
            $exists = false;
            foreach ($tamanhos as $existing) {
                if (mb_strtolower((string)$existing) === mb_strtolower($size)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $tamanhos[] = $size;
            }
        }
    }
    $produtoLinha['tamanhos'] = $tamanhos;
    $produtoLinha['tem_tamanhos'] = count($tamanhos);
}
unset($produtoLinha);

// Definir título da página
$pageTitle = 'Todos os Produtos';
if (!empty($categoria)) {
    $pageTitle = 'Categoria: ' . ucfirst($categoria);
} elseif (!empty($busca)) {
    $pageTitle = 'Resultados para: ' . htmlspecialchars($busca);
} elseif (!empty($menu)) {
    if ($isMenuLancamentos) {
        $pageTitle = 'Lancamentos';
    } elseif ($isMenuTime) {
        $pageTitle = 'Time: ' . ucwords($menu);
    } else {
        $pageTitle = ucfirst($menu);
    }
} elseif (!empty($marca)) {
    $pageTitle = 'Marca: ' . htmlspecialchars($marca);
} elseif ($secao_marcas) {
    $pageTitle = 'Produtos com Marca';
}

$pageKicker = $isMenuLancamentos ? 'LANCAMENTOS' : ($isMenuTime ? 'TIME' : 'CATALOGO');
$pageSubtitle = $isMenuLancamentos
    ? 'Somente produtos selecionados como lancamentos no CMS.'
    : ($isMenuTime
        ? 'Produtos filtrados pelo time selecionado na home.'
        : 'Um novo layout para explorar a colecao completa da RARE.');

$produtoDestaque = $produtos[0] ?? null;
$produtosLista = $produtoDestaque ? array_slice($produtos, 1) : [];

$filtroBotoes = [
    [
        'label' => 'Todos',
        'url' => 'produtos.php',
        'active' => empty($menu) && empty($categoria) && !$apenas_promocao && empty($marca) && !$secao_marcas && empty($busca)
    ],
    [
        'label' => 'Lancamentos',
        'url' => 'produtos.php?menu=lancamentos',
        'active' => $menu === 'lancamentos'
    ],
    [
        'label' => 'Clubes',
        'url' => 'produtos.php?menu=clubes',
        'active' => $menu === 'clubes' || $isMenuTime
    ],
    [
        'label' => 'Selecoes',
        'url' => 'produtos.php?menu=selecoes',
        'active' => $menu === 'selecoes'
    ],
    [
        'label' => 'Retro',
        'url' => 'produtos.php?menu=retro',
        'active' => $menu === 'retro'
    ]
];

// Usa a variante de navbar flutuante premium do include para manter a mesma aparencia da tela principal.
$currentPage = 'cart';

?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>

<style>
    body {
        padding-top: 0 !important;
        background: #0e0e0e;
    }

    .rare-products-page {
        padding-top: 6.4rem;
    }

    /* Nesta tela, manter o mesmo comportamento da home: hover apenas com elevação. */
    .rare-products-page .rare-product-cta .rare-btn-secondary {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.82);
    }

    .rare-products-page .rare-product-cta .rare-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.82);
    }

    .rare-products-page .rare-product-cta .btn-buy-now.rare-btn-primary {
        background: linear-gradient(135deg, rgba(198, 167, 94, 1), rgba(198, 167, 94, 0.78));
        border-color: transparent;
        color: #131313;
        box-shadow: 0 18px 35px rgba(198, 167, 94, 0.18);
    }

    .rare-products-page .rare-product-cta .btn-buy-now.rare-btn-primary:hover {
        background: linear-gradient(135deg, rgba(198, 167, 94, 1), rgba(198, 167, 94, 0.78));
        border-color: transparent;
        color: #131313;
        box-shadow: 0 18px 35px rgba(198, 167, 94, 0.18);
    }
</style>

<section class="rare-products-page">
    <div class="rare-products-shell">
        <div class="rare-products-topbar">
            <div class="rare-products-heading">
                <span class="rare-products-kicker"><?php echo htmlspecialchars($pageKicker); ?></span>
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p><?php echo htmlspecialchars($pageSubtitle); ?></p>
            </div>

        </div>

        <div class="rare-products-toolbar">
            <div class="rare-products-filter-pills">
                <?php foreach ($filtroBotoes as $botaoFiltro): ?>
                    <a href="<?php echo htmlspecialchars($botaoFiltro['url']); ?>" class="rare-filter-pill <?php echo !empty($botaoFiltro['active']) ? 'is-active' : ''; ?>">
                        <?php echo htmlspecialchars($botaoFiltro['label']); ?>
                    </a>
                <?php endforeach; ?>
                <?php if (!empty($categoria)): ?>
                    <a href="produtos.php?categoria=<?php echo urlencode($categoria); ?>" class="rare-filter-pill is-active"><?php echo htmlspecialchars($categoria); ?></a>
                <?php endif; ?>
                <?php if (!empty($marca)): ?>
                    <a href="produtos.php?marca=<?php echo urlencode($marca); ?>" class="rare-filter-pill is-active"><?php echo htmlspecialchars($marca); ?></a>
                <?php endif; ?>
                <?php if (!empty($busca)): ?>
                    <a href="produtos.php?busca=<?php echo urlencode($busca); ?>" class="rare-filter-pill is-active">Busca: <?php echo htmlspecialchars($busca); ?></a>
                <?php endif; ?>
                <?php if ($isMenuTime): ?>
                    <a href="produtos.php?menu=<?php echo urlencode($menu); ?>" class="rare-filter-pill is-active">Time: <?php echo htmlspecialchars(ucwords($menu)); ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($marca) || !empty($categoria) || !empty($busca) || !empty($menu) || $apenas_promocao || $secao_marcas || $preco_min !== null || $preco_max !== null): ?>
                <a href="produtos.php" class="rare-clear-link">Limpar filtros</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($produtoDestaque) && $paginaAtual === 1): ?>
            <div class="rare-hero-destaque-label">
                <span class="material-symbols-sharp">workspace_premium</span>
                Produto em Destaque
            </div>
            <?php
                $heroBadge = getProductBadge($produtoDestaque);
                $heroPrice = isOnSale($produtoDestaque) ? ($produtoDestaque['preco_promocional'] ?? $produtoDestaque['preco']) : ($produtoDestaque['preco'] ?? 0);
                $heroDescription = trim((string) ($produtoDestaque['descricao'] ?? ''));
                $heroExcerpt = $heroDescription !== '' ? mb_substr($heroDescription, 0, 220) . (mb_strlen($heroDescription) > 220 ? '...' : '') : 'Peca selecionada para abrir a vitrine da RARE com presenca forte, acabamento premium e proposta editorial.';
                $heroImage = !empty($produtoDestaque['imagem_principal']) ? '../admin/assets/images/produtos/' . rawurlencode($produtoDestaque['imagem_principal']) : '';
            ?>
            <article class="rare-product-hero" data-product-card data-product-id="<?php echo (int) $produtoDestaque['id']; ?>" data-has-variacoes="<?php echo (isset($produtoDestaque['tem_variacoes']) && $produtoDestaque['tem_variacoes'] > 0) ? '1' : '0'; ?>" data-has-tamanhos="<?php echo (!empty($produtoDestaque['tamanhos'])) ? '1' : '0'; ?>">
                <a href="produto.php?id=<?php echo $produtoDestaque['id']; ?>" class="rare-product-hero-media" aria-label="Abrir produto <?php echo htmlspecialchars($produtoDestaque['nome']); ?>">
                    <?php if ($heroImage !== ''): ?>
                        <img src="<?php echo htmlspecialchars($heroImage); ?>" alt="<?php echo htmlspecialchars($produtoDestaque['nome']); ?>" loading="lazy" onerror="this.parentElement.classList.add('is-fallback'); this.remove();">
                    <?php endif; ?>
                    <div class="rare-product-hero-fallback">
                        <span class="material-symbols-sharp">stadium</span>
                    </div>
                </a>
                <div class="rare-product-hero-copy">
                    <div class="rare-product-hero-copy-inner">
                        <?php if (!empty($heroBadge)): ?>
                            <span class="rare-badge"><?php echo strtoupper(htmlspecialchars($heroBadge)); ?></span>
                        <?php else: ?>
                            <span class="rare-badge">PREMIUM</span>
                        <?php endif; ?>
                        <span class="rare-product-category"><?php echo htmlspecialchars($produtoDestaque['categoria'] ?? 'Colecao Rare'); ?></span>
                        <h2><?php echo htmlspecialchars($produtoDestaque['nome']); ?></h2>
                        <p class="rare-product-description"><?php echo htmlspecialchars($heroExcerpt); ?></p>

                        <?php if (!empty($produtoDestaque['tamanhos'])): ?>
                        <div class="rare-size-selector" data-product-sizes data-product-id="<?php echo (int) $produtoDestaque['id']; ?>">
                            <span class="rare-size-label">Tamanhos</span>
                            <div class="rare-size-options">
                                <?php foreach ($produtoDestaque['tamanhos'] as $size): ?>
                                    <button type="button" class="rare-size-chip" data-size-option="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="rare-product-price-block">
                            <?php if (isOnSale($produtoDestaque)): ?>
                                <span class="rare-price-old"><?php echo formatPrice($produtoDestaque['preco']); ?></span>
                            <?php endif; ?>
                            <strong class="rare-price-current"><?php echo formatPrice($heroPrice); ?></strong>
                        </div>

                        <div class="rare-product-cta" onclick="event.stopPropagation(); event.preventDefault();">
                            <button class="rare-btn rare-btn-secondary" onclick="addToCart(<?php echo (int) $produtoDestaque['id']; ?>, '<?php echo htmlspecialchars($produtoDestaque['nome'], ENT_QUOTES); ?>', event)">Adicionar ao carrinho</button>
                            <button class="rare-btn rare-btn-primary btn-buy-now" onclick="buyNow(<?php echo (int) $produtoDestaque['id']; ?>, event)" data-has-variacoes="<?php echo (isset($produtoDestaque['tem_variacoes']) && $produtoDestaque['tem_variacoes'] > 0) ? '1' : '0'; ?>">Comprar agora</button>
                        </div>
                    </div>
                </div>
            </article>
        <?php endif; ?>

        <div class="rare-sort-bar">
            <span class="rare-sort-bar-count">
                <?php if ($totalProdutos > 0): ?>
                    <?php echo $totalProdutos; ?> produto<?php echo $totalProdutos > 1 ? 's' : ''; ?> disponív<?php echo $totalProdutos > 1 ? 'eis' : 'el'; ?>
                <?php endif; ?>
            </span>
            <div class="rare-sort-bar-right">
                <label>Ordenar por</label>
                <?php
                    $sortLabels = [
                        'recentes'    => 'Mais recentes',
                        'menor_preco' => 'Menor preço',
                        'maior_preco' => 'Maior preço',
                    ];
                    $sortAtual = $sortLabels[$ordenar] ?? 'Mais recentes';
                ?>
                <div class="rare-custom-select" id="sortDropdown">
                    <button class="rare-custom-select-trigger" type="button" onclick="toggleSortDropdown(event)" aria-haspopup="listbox" aria-expanded="false">
                        <span id="sortLabel"><?php echo htmlspecialchars($sortAtual); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6"><path d="M0 0l5 6 5-6z" fill="currentColor"/></svg>
                    </button>
                    <ul class="rare-custom-select-options" role="listbox">
                        <li role="option" data-value="recentes" <?php echo ($ordenar === 'recentes') ? 'class="is-selected"' : ''; ?>>Mais recentes</li>
                        <li role="option" data-value="menor_preco" <?php echo ($ordenar === 'menor_preco') ? 'class="is-selected"' : ''; ?>>Menor preço</li>
                        <li role="option" data-value="maior_preco" <?php echo ($ordenar === 'maior_preco') ? 'class="is-selected"' : ''; ?>>Maior preço</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (!empty($produtos)): ?>
            <div class="rare-products-editorial-list">
                <?php foreach ($produtosLista as $product): ?>
                    <?php
                        $badge = getProductBadge($product);
                        $productImage = !empty($product['imagem_principal']) ? '../admin/assets/images/produtos/' . rawurlencode($product['imagem_principal']) : '';
                        $priceValue = isOnSale($product) ? ($product['preco_promocional'] ?? $product['preco']) : ($product['preco'] ?? 0);
                        $description = trim((string) ($product['descricao'] ?? ''));
                        $excerpt = $description !== '' ? mb_substr($description, 0, 150) . (mb_strlen($description) > 150 ? '...' : '') : 'Peca com acabamento refinado, curadoria premium e presenca marcante dentro da colecao RARE.';
                    ?>
                    <article class="rare-product-row" data-product-card data-product-id="<?php echo (int) $product['id']; ?>" data-has-variacoes="<?php echo (isset($product['tem_variacoes']) && $product['tem_variacoes'] > 0) ? '1' : '0'; ?>" data-has-tamanhos="<?php echo (!empty($product['tamanhos'])) ? '1' : '0'; ?>">
                        <a href="produto.php?id=<?php echo $product['id']; ?>" class="rare-product-row-media" aria-label="Abrir produto <?php echo htmlspecialchars($product['nome']); ?>">
                            <?php if ($productImage !== ''): ?>
                                <img src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($product['nome']); ?>" loading="lazy" onerror="this.parentElement.classList.add('is-fallback'); this.remove();">
                            <?php endif; ?>
                            <div class="rare-product-row-fallback">
                                <span class="material-symbols-sharp">sports_soccer</span>
                            </div>
                            <?php if (!empty($badge)): ?>
                                <span class="rare-inline-badge"><?php echo strtoupper(htmlspecialchars($badge)); ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="rare-product-row-content">
                            <div class="rare-product-row-topline">
                                <span class="rare-product-category"><?php echo htmlspecialchars($product['categoria'] ?? 'Colecao Rare'); ?></span>
                            </div>
                            <h3><a href="produto.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['nome']); ?></a></h3>
                            <p class="rare-product-description"><?php echo htmlspecialchars($excerpt); ?></p>

                            <?php if (!empty($product['tamanhos'])): ?>
                            <div class="rare-size-selector" data-product-sizes data-product-id="<?php echo (int) $product['id']; ?>">
                                <span class="rare-size-label">Tamanhos</span>
                                <div class="rare-size-options">
                                    <?php foreach ($product['tamanhos'] as $size): ?>
                                        <button type="button" class="rare-size-chip" data-size-option="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="rare-product-row-footer" onclick="event.stopPropagation(); event.preventDefault();">
                                <div class="rare-product-price-block">
                                    <?php if (isOnSale($product)): ?>
                                        <span class="rare-price-old"><?php echo formatPrice($product['preco']); ?></span>
                                    <?php endif; ?>
                                    <strong class="rare-price-current"><?php echo formatPrice($priceValue); ?></strong>
                                </div>
                                <div class="rare-product-cta compact">
                                    <button class="rare-btn rare-btn-secondary" onclick="addToCart(<?php echo (int) $product['id']; ?>, '<?php echo htmlspecialchars($product['nome'], ENT_QUOTES); ?>', event)">Adicionar</button>
                                    <button class="rare-btn rare-btn-primary btn-buy-now" onclick="buyNow(<?php echo (int) $product['id']; ?>, event)" data-has-variacoes="<?php echo (isset($product['tem_variacoes']) && $product['tem_variacoes'] > 0) ? '1' : '0'; ?>">Comprar</button>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <?php
                    $queryString = '';
                    if (!empty($categoria)) $queryString .= '&categoria=' . urlencode($categoria);
                    if (!empty($marca)) $queryString .= '&marca=' . urlencode($marca);
                    if (!empty($busca)) $queryString .= '&busca=' . urlencode($busca);
                    if (!empty($menu)) $queryString .= '&menu=' . urlencode($menu);
                    if ($preco_min !== null) $queryString .= '&preco_min=' . $preco_min;
                    if ($preco_max !== null) $queryString .= '&preco_max=' . $preco_max;
                    if ($apenas_promocao) $queryString .= '&promo=1';
                    if (!empty($ordenar)) $queryString .= '&ordenar=' . urlencode($ordenar);
                ?>
                <div class="rare-products-pagination">
                    <?php if ($paginaAtual > 1): ?>
                        <a href="?pagina=<?php echo $paginaAtual - 1; ?><?php echo $queryString; ?>" class="rare-page-link">Anterior</a>
                    <?php endif; ?>

                    <div class="rare-page-numbers">
                        <?php
                            $range = 2;
                            $start = max(1, $paginaAtual - $range);
                            $end = min($totalPaginas, $paginaAtual + $range);
                            if ($start > 1):
                        ?>
                            <a href="?pagina=1<?php echo $queryString; ?>" class="rare-page-number">1</a>
                            <?php if ($start > 2): ?><span class="rare-page-dots">...</span><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo $queryString; ?>" class="rare-page-number <?php echo $i === $paginaAtual ? 'is-active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($end < $totalPaginas): ?>
                            <?php if ($end < $totalPaginas - 1): ?><span class="rare-page-dots">...</span><?php endif; ?>
                            <a href="?pagina=<?php echo $totalPaginas; ?><?php echo $queryString; ?>" class="rare-page-number"><?php echo $totalPaginas; ?></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="?pagina=<?php echo $paginaAtual + 1; ?><?php echo $queryString; ?>" class="rare-page-link">Proxima</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="rare-products-empty">
                <span class="material-symbols-sharp">search_off</span>
                <h3>Nenhum produto encontrado</h3>
                <p>Tente ajustar os filtros ou volte para a colecao completa da RARE.</p>
                <a href="produtos.php" class="rare-btn rare-btn-primary">Ver todos os produtos</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- JavaScript do Carrinho -->
<script>
    const __noopLog = (...args) => {};

    // ===== FUNÇÃO DE ORDENAÇÃO =====
    function aplicarOrdenacao(valor) {
        const url = new URL(window.location.href);
        url.searchParams.set('ordenar', valor);
        url.searchParams.delete('pagina');
        window.location.href = url.toString();
    }

    // ===== DROPDOWN CUSTOMIZADO DE ORDENAÇÃO =====
    function toggleSortDropdown(e) {
        e.stopPropagation();
        const wrap = document.getElementById('sortDropdown');
        wrap.classList.toggle('is-open');
        const btn = wrap.querySelector('.rare-custom-select-trigger');
        btn.setAttribute('aria-expanded', wrap.classList.contains('is-open'));
    }

    document.addEventListener('click', function () {
        const wrap = document.getElementById('sortDropdown');
        if (wrap) {
            wrap.classList.remove('is-open');
            wrap.querySelector('.rare-custom-select-trigger').setAttribute('aria-expanded', 'false');
        }
    });

    document.querySelectorAll('.rare-custom-select-options li').forEach(function (li) {
        li.addEventListener('click', function (e) {
            e.stopPropagation();
            const value = li.dataset.value;
            document.querySelectorAll('.rare-custom-select-options li').forEach(function (el) {
                el.classList.remove('is-selected');
            });
            li.classList.add('is-selected');
            document.getElementById('sortLabel').textContent = li.textContent;
            document.getElementById('sortDropdown').classList.remove('is-open');
            aplicarOrdenacao(value);
        });
    });


    // ===== MINI CARRINHO - JAVASCRIPT =====
    const FREE_SHIPPING_THRESHOLD = <?php echo $freteGratisValor; ?>;

    // FunÃ§Ãµes de gerenciamento do carrinho
    function getCart() {
        const cart = localStorage.getItem('dz_cart');
        return cart ? JSON.parse(cart) : [];
    }

    function setCart(cart) {
        localStorage.setItem('dz_cart', JSON.stringify(cart));
    }

    const PRODUCT_SIZE_STORAGE_KEY = 'rare7_selected_sizes';

    function getSelectedSizes() {
        try {
            const raw = localStorage.getItem(PRODUCT_SIZE_STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            return {};
        }
    }

    function setSelectedSizes(map) {
        localStorage.setItem(PRODUCT_SIZE_STORAGE_KEY, JSON.stringify(map));
    }

    function getSelectedSizeForProduct(productId) {
        const map = getSelectedSizes();
        return map[String(productId)] || '';
    }

    function setSelectedSizeForProduct(productId, size) {
        const map = getSelectedSizes();
        const key = String(productId);

        if (size) {
            map[key] = size;
        } else {
            delete map[key];
        }

        setSelectedSizes(map);
    }

    function applySelectedSizesToUI() {
        document.querySelectorAll('[data-product-sizes]').forEach((selector) => {
            const productId = selector.getAttribute('data-product-id');
            const selectedSize = getSelectedSizeForProduct(productId);

            selector.querySelectorAll('[data-size-option]').forEach((button) => {
                const isActive = button.getAttribute('data-size-option') === selectedSize;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        });
    }

    function initProductSizeSelectors() {
        applySelectedSizesToUI();

        document.querySelectorAll('[data-size-option]').forEach((button) => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                const selector = this.closest('[data-product-sizes]');
                if (!selector) {
                    return;
                }

                const productId = selector.getAttribute('data-product-id');
                const clickedSize = this.getAttribute('data-size-option') || '';
                const currentSize = getSelectedSizeForProduct(productId);
                const nextSize = currentSize === clickedSize ? '' : clickedSize;

                setSelectedSizeForProduct(productId, nextSize);
                applySelectedSizesToUI();
            });
        });
    }
    
    // FunÃ§Ã£o helper para debug - disponÃ­vel no console
    window.clearCart = function() {
        localStorage.removeItem('dz_cart');
        updateCartBadge();
        renderMiniCart();
        __noopLog('ðŸ—‘ï¸ Carrinho limpo com sucesso!');
    };
    
    window.viewCart = function() {
        __noopLog('ðŸ›’ Carrinho atual:', getCart());
        return getCart();
    };

    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'rare-cart-toast';
        notification.textContent = String(message || 'Produto adicionado ao carrinho.');

        document.body.appendChild(notification);

        requestAnimationFrame(() => {
            notification.classList.add('is-visible');
        });

        setTimeout(() => {
            notification.classList.remove('is-visible');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 220);
        }, 1700);
    }

    function addToCart(productId, productName, event) {
        __noopLog('ðŸ›’ Adicionar ao carrinho:', productId, productName);
        
        // Validar productId
        if (!productId || productId === '' || productId === 0 || productId === '0') {
            console.error('âŒ ERRO: productId invÃ¡lido:', productId);
            return;
        }
        
        const evt = event || window.event;
        
        if (!evt) {
            console.error('âŒ Evento nÃ£o disponÃ­vel');
            return;
        }
        
        // Buscar informaÃ§Ãµes do produto
        const productCard = evt.target.closest('[data-product-card], .produto-card');
        
        if (!productCard) {
            console.error('âŒ Card do produto nÃ£o encontrado');
            return;
        }
        
        // Buscar elementos do card
        let priceElement = productCard.querySelector('.produto-price, .rare-price-current');
        let imgElement = productCard.querySelector('img');
        let titleElement = productCard.querySelector('.produto-title') || productCard.querySelector('h2') || productCard.querySelector('h3');

        const productIdValue = productCard.getAttribute('data-product-id') || productId;
        const hasVariacoes = String(productCard.getAttribute('data-has-variacoes') || '0') === '1';
        const hasTamanhos = String(productCard.getAttribute('data-has-tamanhos') || '0') === '1';
        const selectedSize = getSelectedSizeForProduct(productIdValue);

        if (hasVariacoes && hasTamanhos && !selectedSize) {
            window.location.href = 'produto.php?id=' + encodeURIComponent(productIdValue);
            return;
        }

        const variantLabel = selectedSize ? `Tamanho: ${selectedSize}` : '';
        
        // Extrair nome
        const name = titleElement ? titleElement.textContent.trim() : (productName || 'Produto sem nome');
        
        // Extrair preÃ§o
        let price = 0;
        if (priceElement) {
            const priceText = priceElement.textContent.trim();
            const priceMatch = priceText.match(/R\$\s*([\d.,]+)/g);
            
            if (priceMatch && priceMatch.length > 0) {
                const lastPrice = priceMatch[priceMatch.length - 1];
                let priceStr = lastPrice.replace('R$', '').trim();
                priceStr = priceStr.replace(/\./g, '').replace(',', '.');
                price = parseFloat(priceStr);
                
                if (isNaN(price) || price < 0) {
                    price = 0;
                }
            }
        }
        
        // Buscar imagem
        const image = imgElement ? imgElement.src : 'ðŸ’…';
        
        // Garantir que productId seja nÃºmero
        const numericProductId = parseInt(productId);
        
        if (isNaN(numericProductId)) {
            console.error('âŒ productId nÃ£o Ã© um nÃºmero vÃ¡lido:', productId);
            return;
        }
        
        const newProductData = {
            id: numericProductId,
            name: name,
            price: price,
            qty: 1,
            image: image,
            variant: variantLabel,
            variacao_texto: variantLabel
        };
        
        __noopLog('ðŸ“¦ Produto a adicionar:', newProductData);
        
        // Obter carrinho
        let cart = getCart();
        
        // Verificar se produto jÃ¡ existe
        const existingIndex = cart.findIndex(item => {
            const itemId = parseInt(item.id);
            const itemVariant = item.variant || item.variacao_texto || '';
            return itemId === numericProductId && itemVariant === variantLabel;
        });
        
        if (existingIndex >= 0) {
            cart[existingIndex].qty += 1;
            __noopLog('âœ… Quantidade atualizada para:', cart[existingIndex].qty);
        } else {
            cart.push(newProductData);
            __noopLog('âœ… Novo produto adicionado');
        }
        
        // Salvar carrinho
        setCart(cart);
        updateCartBadge();
        renderMiniCart();
        openMiniCart();
        
        // Mostrar notificaÃ§Ã£o
        const notificationLabel = variantLabel ? (name + ' | ' + variantLabel) : name;
        showNotification('Adicionado: ' + notificationLabel);
        
        __noopLog('âœ… Produto adicionado ao carrinho!');
    }

    function buyNow(productId, event) {
        __noopLog('âš¡ Comprar agora:', productId);
        
        // Prevenir propagaÃ§Ã£o do evento
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const productIdValue = parseInt(productId, 10) || productId;
        const productCard = event?.target?.closest('[data-product-card]');
        const hasVariacoes = String(productCard?.getAttribute('data-has-variacoes') || event?.target?.getAttribute('data-has-variacoes') || '0') === '1';
        const hasTamanhos = String(productCard?.getAttribute('data-has-tamanhos') || event?.target?.getAttribute('data-has-tamanhos') || '0') === '1';
        const selectedSize = getSelectedSizeForProduct(productIdValue);

        if (hasVariacoes && hasTamanhos && !selectedSize) {
            window.location.href = 'produto.php?id=' + productIdValue;
            return;
        }

        __noopLog('ðŸ›’ Tamanho selecionado, adicionando ao carrinho e indo para checkout');
        
        // Buscar o nome do produto do card
        const titleElement = productCard?.querySelector('.produto-title') || productCard?.querySelector('h3') || productCard?.querySelector('h2');
        const productName = titleElement ? titleElement.textContent.trim() : 'Produto';
        
        // Adicionar ao carrinho usando a funÃ§Ã£o existente
        addToCart(productId, productName, event);
        
        // Aguardar um pouco para garantir que foi adicionado e entÃ£o redirecionar
        setTimeout(() => {
            window.location.href = 'pages/carrinho.php';
        }, 150);
    }

    function removeFromCart(itemId, variant = '') {
        __noopLog('ðŸ—‘ï¸ Tentando remover produto:', itemId, variant);
        let cart = getCart();
        __noopLog('Carrinho antes da remoÃ§Ã£o:', cart);
        
        // Normalizar itemId (pode ser nÃºmero ou string)
        const numericItemId = (itemId === 0 || itemId === '0') ? 0 : (parseInt(itemId) || itemId);
        __noopLog('ID normalizado para comparaÃ§Ã£o:', numericItemId);
        
        const initialLength = cart.length;
        
        cart = cart.filter((item, index) => {
            // Normalizar item.id tambÃ©m
            const itemNumericId = (item.id === 0 || item.id === '0') ? 0 : (parseInt(item.id) || item.id);
            const itemVariant = item.variant || '';
            
            const idsMatch = itemNumericId === numericItemId;
            const variantsMatch = itemVariant === variant;
            const shouldRemove = idsMatch && variantsMatch;
            const shouldKeep = !shouldRemove;
            
            __noopLog(`Item ${index}:`, {
                originalId: item.id,
                numericId: itemNumericId,
                variant: itemVariant,
                comparandoCom: numericItemId,
                variantComparando: variant,
                idsMatch: idsMatch,
                variantsMatch: variantsMatch,
                shouldRemove: shouldRemove,
                shouldKeep: shouldKeep
            });
            
            return shouldKeep;
        });
        
        const removedCount = initialLength - cart.length;
        __noopLog(`âœ… Removidos ${removedCount} item(ns)`);
        __noopLog('Carrinho apÃ³s remoÃ§Ã£o:', cart);
        
        setCart(cart);
        updateCartBadge();
        
        if (removedCount > 0) {
            renderMiniCart();
        } else {
            console.warn('âš ï¸ Nenhum item foi removido!');
        }
    }

    function updateQty(itemId, variant, newQty) {
        __noopLog('Atualizando quantidade:', itemId, variant, newQty);
        const cart = getCart();
        
        // Normalizar itemId (pode ser nÃºmero ou string, incluindo 0)
        const numericItemId = (itemId === 0 || itemId === '0') ? 0 : (parseInt(itemId) || itemId);
        
        const item = cart.find(i => {
            const iNumericId = (i.id === 0 || i.id === '0') ? 0 : (parseInt(i.id) || i.id);
            const iVariant = i.variant || '';
            return iNumericId === numericItemId && iVariant === variant;
        });
        
        if (item) {
            if (newQty <= 0) {
                removeFromCart(itemId, variant);
            } else {
                item.qty = newQty;
                setCart(cart);
                updateCartBadge();
                renderMiniCart();
            }
        }
    }

    function getSubtotal() {
        const cart = getCart();
        return cart.reduce((total, item) => {
            const itemPrice = (typeof item.price === 'number' && !isNaN(item.price)) ? item.price : 0;
            const itemQty = parseInt(item.qty) || 0;
            return total + (itemPrice * itemQty);
        }, 0);
    }

    function updateCartBadge() {
        const cart = getCart();
        const totalItems = cart.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
        const badge = document.getElementById('cartBadge');
        
        if (badge) {
            badge.textContent = totalItems;
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
        }
    }

    function renderMiniCart() {
        const cart = getCart();
        const body = document.getElementById('miniCartBody');
        const subtotalEl = document.getElementById('miniCartSubtotal');
        const freeShippingBar = document.getElementById('freeShippingBar');
        
        __noopLog('renderMiniCart chamado - Itens no carrinho:', cart);
        
        if (!body) {
            console.error('Elemento miniCartBody nÃ£o encontrado');
            return;
        }
        
        // Se carrinho vazio
        if (cart.length === 0) {
            __noopLog('Carrinho vazio, mostrando mensagem');
            body.innerHTML = `
                <div class="cart-empty">
                    <div class="cart-empty-icon">ðŸ›’</div>
                    <h3>Seu carrinho estÃ¡ vazio</h3>
                    <p>Adicione produtos para comeÃ§ar suas compras!</p>
                    <button class="btn-continue-shopping" onclick="closeMiniCart()">Continuar comprando</button>
                </div>
            `;
            subtotalEl.textContent = 'R$ 0,00';
            
            // Mostrar barra de frete grÃ¡tis mesmo com carrinho vazio
            freeShippingBar.innerHTML = `
                <div class="shipping-text">Faltam R$ ${FREE_SHIPPING_THRESHOLD.toFixed(2).replace('.', ',')} para ganhar frete grÃ¡tis</div>
                <div class="shipping-progress">
                    <div class="shipping-progress-bar" style="width: 0%"></div>
                </div>
            `;
            return;
        }
        
        // Renderizar itens
        __noopLog('Renderizando', cart.length, 'itens no mini carrinho');
        
        body.innerHTML = cart.map((item, index) => {
            // Garantir que o preÃ§o seja vÃ¡lido
            const itemPrice = (typeof item.price === 'number' && !isNaN(item.price)) ? item.price : 0;
            const itemQty = parseInt(item.qty) || 1;
            const itemId = item.id || 0;
            const itemVariant = item.variant || '';
            const itemName = item.name || 'Produto';
            const itemImage = item.image || '';
            
            __noopLog(`Item ${index}:`, {
                id: itemId,
                name: itemName,
                price: itemPrice,
                qty: itemQty,
                image: itemImage ? itemImage.substring(0, 50) + '...' : 'sem imagem'
            });
            
            // Escapar aspas no nome e variant para evitar erros de sintaxe
            const escapedName = itemName.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const escapedVariant = itemVariant.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            
            return `
            <div class="cart-item" data-product-id="${itemId}">
                <div class="cart-item-image">
                    ${itemImage && itemImage.startsWith('http') ? `<img src="${itemImage}" alt="${escapedName}" loading="lazy">` : `<span style="font-size: 2rem;">${itemImage || 'ðŸ’…'}</span>`}
                </div>
                <div class="cart-item-details">
                    <div class="cart-item-name">${itemName}</div>
                    ${itemVariant ? `<div class="cart-item-variant">${itemVariant}</div>` : ''}
                    <div class="cart-item-price">R$ ${itemPrice.toFixed(2).replace('.', ',')}</div>
                    <div class="cart-item-actions">
                        <div class="qty-control">
                            <button class="qty-btn" onclick="updateQty(${itemId}, '${escapedVariant}', ${itemQty - 1})" ${itemQty <= 1 ? 'disabled' : ''} aria-label="Diminuir quantidade">âˆ’</button>
                            <span class="qty-value">${itemQty}</span>
                            <button class="qty-btn" onclick="updateQty(${itemId}, '${escapedVariant}', ${itemQty + 1})" aria-label="Aumentar quantidade">+</button>
                        </div>
                        <button class="btn-remove-item" onclick="removeFromCart(${itemId}, '${escapedVariant}')" title="Remover produto" aria-label="Remover produto">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        }).join('');
        
        // Atualizar subtotal
        const subtotal = getSubtotal();
        subtotalEl.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
        
        // Barra de frete grÃ¡tis
        const remaining = FREE_SHIPPING_THRESHOLD - subtotal;
        const progress = Math.min((subtotal / FREE_SHIPPING_THRESHOLD) * 100, 100);
        
        if (remaining > 0) {
            freeShippingBar.innerHTML = `
                <div class="shipping-text">Faltam R$ ${remaining.toFixed(2).replace('.', ',')} para frete grÃ¡tis</div>
                <div class="shipping-progress">
                    <div class="shipping-progress-bar" style="width: ${progress}%"></div>
                </div>
            `;
        } else {
            freeShippingBar.innerHTML = `
                <div class="shipping-unlocked">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    VocÃª desbloqueou frete grÃ¡tis ðŸŽ‰
                </div>
            `;
        }
    }

    function openMiniCart() {
        __noopLog('ðŸ›’ Abrindo carrinho');
        renderMiniCart();
        document.getElementById('miniCartOverlay').classList.add('active');
        document.getElementById('miniCartDrawer').classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Esconder chat quando carrinho abre
        const chatBtn = document.querySelector('.chat-button');
        const chatModal = document.getElementById('chatModal');
        if (chatBtn) chatBtn.classList.add('chat-hidden');
        if (chatModal) {
            chatModal.classList.add('chat-hidden');
            chatModal.classList.remove('active');
        }
    }

    function closeMiniCart() {
        __noopLog('Fechando mini carrinho');
        document.getElementById('miniCartOverlay').classList.remove('active');
        document.getElementById('miniCartDrawer').classList.remove('active');
        document.body.style.overflow = '';
        
        // Mostrar chat quando carrinho fecha
        const chatBtn = document.querySelector('.chat-button');
        const chatModal = document.getElementById('chatModal');
        if (chatBtn) chatBtn.classList.remove('chat-hidden');
        if (chatModal) chatModal.classList.remove('chat-hidden');
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        __noopLog('=== INICIALIZANDO CARRINHO ===');
        
        // Verificar se o localStorage tem dados corrompidos
        try {
            const cartData = localStorage.getItem('dz_cart');
            __noopLog('LocalStorage dz_cart raw:', cartData);
            
            if (cartData) {
                const cart = JSON.parse(cartData);
                __noopLog('Carrinho parseado:', cart);
                
                // Validar e limpar itens invÃ¡lidos
                const validCart = cart.filter(item => {
                    const hasId = item && (item.id === 0 || item.id);
                    const hasName = item && item.name && item.name !== '';
                    const hasValidPrice = item && typeof item.price === 'number' && !isNaN(item.price);
                    
                    const isValid = hasId && hasName && hasValidPrice;
                    
                    if (!isValid) {
                        console.warn('Item invÃ¡lido encontrado e removido:', item);
                        console.warn('Motivo:', {
                            hasId: hasId,
                            hasName: hasName,
                            hasValidPrice: hasValidPrice
                        });
                    }
                    return isValid;
                });
                
                if (validCart.length !== cart.length) {
                    __noopLog('Carrinho limpo de', cart.length - validCart.length, 'itens invÃ¡lidos');
                    localStorage.setItem('dz_cart', JSON.stringify(validCart));
                }
            }
        } catch (e) {
            console.error('Erro ao validar carrinho, limpando...', e);
            localStorage.removeItem('dz_cart');
        }
        
        // Atualizar badge ao carregar
        updateCartBadge();
        renderMiniCart();
        initProductSizeSelectors();
        
        __noopLog('=== CARRINHO INICIALIZADO ===');
        __noopLog('ðŸ’¡ Comandos Ãºteis no console:');
        __noopLog('  - clearCart() : Limpa todo o carrinho');
        __noopLog('  - viewCart() : Visualiza o conteÃºdo do carrinho');
        
        // Abrir drawer ao clicar no botÃ£o DO CARRINHO apenas
        const cartButton = document.getElementById('cartButton');
        if (cartButton) {
            cartButton.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                
                if (e.currentTarget.id === 'cartButton') {
                    openMiniCart();
                }
            });
        }
        
        // Fechar drawer
        const closeBtn = document.getElementById('closeMiniCart');
        const overlay = document.getElementById('miniCartOverlay');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeMiniCart);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeMiniCart);
        }
        
        // Fechar com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeMiniCart();
            }
        });
        
        __noopLog('ðŸ›’ Mini Carrinho inicializado!');
    });

    // ===== DROPDOWN DO USUÃRIO =====
    function toggleUserDropdown(event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('active');
        }
    }
    
    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });

    // ===== MENU MOBILE =====
    function toggleMobileMenu(event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        const hamburger = document.querySelector('.hamburger');
        const overlay = document.querySelector('.mobile-menu-overlay');
        const menu = document.querySelector('.mobile-menu');
        
        if (hamburger && overlay && menu) {
            hamburger.classList.toggle('open');
            overlay.classList.toggle('active');
            menu.classList.toggle('active');
            document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
        }
    }

    function closeMobileMenu(event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        const hamburger = document.querySelector('.hamburger');
        const overlay = document.querySelector('.mobile-menu-overlay');
        const menu = document.querySelector('.mobile-menu');
        
        if (hamburger) hamburger.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        if (menu) menu.classList.remove('active');
        document.body.style.overflow = '';
    }

    // ===== BARRA DE PESQUISA NA NAVBAR =====
    const searchToggle = document.getElementById('searchToggle');
    const searchPanel = document.getElementById('searchPanel');
    const searchInput = document.getElementById('searchInput');

    function closeSearchPanel() {
        if (!searchPanel || !searchToggle) return;
        searchPanel.classList.remove('active');
        searchToggle.setAttribute('aria-expanded', 'false');
    }

    if (searchToggle && searchPanel) {
        searchToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = searchPanel.classList.contains('active');
            const searchValue = searchInput ? searchInput.value.trim() : '';
            
            __noopLog('ðŸ” BotÃ£o de pesquisa clicado');
            __noopLog('   - Painel aberto:', isOpen);
            __noopLog('   - Valor:', searchValue);
            
            // Se painel estiver aberto E houver texto, enviar formulÃ¡rio
            if (isOpen && searchValue) {
                __noopLog('âœ… Enviando busca para: produtos.php?busca=' + searchValue);
                window.location.href = 'produtos.php?busca=' + encodeURIComponent(searchValue);
                return;
            }
            
            // Se painel fechado, abrir
            if (!isOpen) {
                requestAnimationFrame(() => {
                    searchPanel.classList.add('active');
                    searchToggle.setAttribute('aria-expanded', 'true');
                    
                    if (searchInput) {
                        setTimeout(() => {
                            requestAnimationFrame(() => {
                                searchInput.focus();
                            });
                        }, 350);
                    }
                });
                return;
            }
            
            // Se painel aberto mas sem texto, fechar
            closeSearchPanel();
        });
    }

    document.addEventListener('click', (e) => {
        if (!searchPanel || !searchToggle) return;
        if (!searchPanel.classList.contains('active')) return;
        if (searchPanel.contains(e.target) || searchToggle.contains(e.target)) return;
        closeSearchPanel();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSearchPanel();
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>


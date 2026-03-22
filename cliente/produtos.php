<?php
// E-commerce D&Z - PÃ¡gina de Produtos
// Listagem com filtros por categoria e busca

session_start();

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
$menu = isset($_GET['menu']) ? trim($_GET['menu']) : '';
$marca = isset($_GET['marca']) ? trim($_GET['marca']) : '';
$secao_marcas = isset($_GET['secao']) && $_GET['secao'] == 'marcas'; // Filtrar apenas produtos com marca
$preco_min = isset($_GET['preco_min']) && is_numeric($_GET['preco_min']) ? (float)$_GET['preco_min'] : null;
$preco_max = isset($_GET['preco_max']) && is_numeric($_GET['preco_max']) ? (float)$_GET['preco_max'] : null;
$apenas_promocao = isset($_GET['promo']) && $_GET['promo'] == '1';
$ordenar = isset($_GET['ordenar']) ? trim($_GET['ordenar']) : 'recentes';

// PaginaÃ§Ã£o
$produtosPorPagina = 12;
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
if (!empty($menu) && $menu === 'lancamentos') {
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
} elseif (!empty($menu)) {
    // Se nÃ£o houver categoria, filtrar por grupo de menu
    $queryCount .= " AND c.menu_group = ?";
    $params[] = $menu;
    $types .= 's';
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
        p.estoque,
        p.imagem_principal,
        p.created_at,
        c.nome AS categoria,
        CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento,
        (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) AS tem_variacoes
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
} elseif (!empty($menu)) {
    // Se nÃ£o houver categoria, filtrar por grupo de menu
    $query .= " AND c.menu_group = ?";
    $params[] = $menu;
    $types .= 's';
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
        $query .= " ORDER BY COALESCE(p.preco_promocional, p.preco) ASC";
        break;
    case 'maior_preco':
        $query .= " ORDER BY COALESCE(p.preco_promocional, p.preco) DESC";
        break;
    case 'nome_az':
        $query .= " ORDER BY p.nome ASC";
        break;
    case 'nome_za':
        $query .= " ORDER BY p.nome DESC";
        break;
    case 'recentes':
    default:
        $query .= " ORDER BY p.id DESC";
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
$pageTitle = 'Todos os Produtos';
if (!empty($categoria)) {
    $pageTitle = 'Categoria: ' . ucfirst($categoria);
} elseif (!empty($busca)) {
    $pageTitle = 'Resultados para: ' . htmlspecialchars($busca);
} elseif (!empty($menu)) {
    $pageTitle = ($menu === 'lancamentos') ? 'LanÃ§amentos' : ucfirst($menu);
} elseif (!empty($marca)) {
    $pageTitle = 'Marca: ' . htmlspecialchars($marca);
} elseif ($secao_marcas) {
    $pageTitle = 'Produtos com Marca';
}

?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>

<!-- Mini Cart Overlay -->
<div class="mini-cart-overlay" id="miniCartOverlay"></div>

<!-- Mini Cart Drawer -->
<div id="miniCartDrawer" class="mini-cart-drawer">
    <div class="mini-cart-header">
        <h2>Seu carrinho</h2>
        <button id="closeMiniCart" class="btn-close-cart" aria-label="Fechar carrinho">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    </div>

    <div class="mini-cart-body" id="miniCartBody">
        <!-- ConteÃºdo preenchido via JS -->
    </div>

    <div class="mini-cart-footer">
        <div class="free-shipping-bar" id="freeShippingBar">
            <!-- Barra de progresso preenchida via JS -->
        </div>
        <div class="mini-cart-subtotal">
            <span>Subtotal:</span>
            <strong id="miniCartSubtotal">R$ 0,00</strong>
        </div>
        <a href="pages/carrinho.php" class="btn-view-cart">Ver carrinho completo</a>
    </div>
</div>

<!-- ===== PÃGINA DE PRODUTOS ===== -->
<section class="produtos-page" style="padding: 0 0 60px; min-height: 70vh; background: #fafafa; margin-top: 0;">
    <div class="produtos-container-wide">
        
        <!-- Layout: Sidebar + ConteÃºdo -->
        <div class="produtos-layout">
            
            <!-- ===== SIDEBAR DE FILTROS - NOVA ESTRUTURA MINIMALISTA ===== -->
            <aside class="filtros-sidebar">
                <div class="sidebar-header">
                    <h3>Filtrar por</h3>
                    <?php if (!empty($marca) || $preco_min !== null || $preco_max !== null || $apenas_promocao || $secao_marcas): ?>
                        <a href="produtos.php" class="btn-limpar">Limpar</a>
                    <?php endif; ?>
                </div>
                
                <!-- SEÃ‡ÃƒO: MARCAS -->
                <?php if (!empty($marcas)): ?>
                <div class="filtro-secao">
                    <h4 class="filtro-titulo">Marcas</h4>
                    <ul class="filtro-lista">
                        <?php foreach ($marcas as $m): ?>
                        <li>
                            <a href="?marca=<?php echo urlencode($m); ?>" 
                               class="filtro-link <?php echo ($marca == $m) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($m); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- SEÃ‡ÃƒO: FAIXA DE PREÃ‡O -->
                <div class="filtro-secao">
                    <h4 class="filtro-titulo">Faixa de PreÃ§o</h4>
                    <ul class="filtro-lista">
                        <li>
                            <a href="?preco_max=50<?php echo !empty($marca) ? '&marca='.urlencode($marca) : ''; ?>" 
                               class="filtro-link <?php echo ($preco_max == 50 && $preco_min === null) ? 'active' : ''; ?>">
                                AtÃ© R$ 50
                            </a>
                        </li>
                        <li>
                            <a href="?preco_min=50&preco_max=100<?php echo !empty($marca) ? '&marca='.urlencode($marca) : ''; ?>" 
                               class="filtro-link <?php echo ($preco_min == 50 && $preco_max == 100) ? 'active' : ''; ?>">
                                R$ 50 â€“ R$ 100
                            </a>
                        </li>
                        <li>
                            <a href="?preco_min=100<?php echo !empty($marca) ? '&marca='.urlencode($marca) : ''; ?>" 
                               class="filtro-link <?php echo ($preco_min == 100 && $preco_max === null) ? 'active' : ''; ?>">
                                Acima de R$ 100
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- SEÃ‡ÃƒO: STATUS -->
                <div class="filtro-secao">
                    <h4 class="filtro-titulo">Status</h4>
                    <div class="filtro-opcoes">
                        <label class="filtro-checkbox">
                            <input type="checkbox" <?php echo $apenas_promocao ? 'checked' : ''; ?> 
                                   onchange="window.location.href='?promo=<?php echo $apenas_promocao ? '0' : '1'; ?><?php echo !empty($marca) ? '&marca='.urlencode($marca) : ''; ?><?php echo $preco_min !== null ? '&preco_min='.$preco_min : ''; ?><?php echo $preco_max !== null ? '&preco_max='.$preco_max : ''; ?>'">
                            <span>Apenas promoÃ§Ãµes</span>
                        </label>
                        <label class="filtro-checkbox">
                            <input type="checkbox">
                            <span>Apenas lanÃ§amentos</span>
                        </label>
                    </div>
                </div>
            </aside>
            
            <!-- ===== CONTEÃšDO PRINCIPAL (TÃ­tulo + Barra Superior + Grid) ===== -->
            <div class="produtos-conteudo">
                
                <!-- TÃ­tulo e contagem -->
                <div class="produtos-header-inline">
                    <div>
                        <h1 class="produtos-titulo"><?php echo htmlspecialchars($pageTitle); ?></h1>
                        <p class="produtos-contagem">
                            <?php if ($totalProdutos > 0): ?>
                                Mostrando <strong><?php echo $offset + 1; ?></strong>â€“<strong><?php echo min($offset + $produtosPorPagina, $totalProdutos); ?></strong> de <strong><?php echo $totalProdutos; ?></strong> produto<?php echo $totalProdutos > 1 ? 's' : ''; ?>
                            <?php else: ?>
                                Nenhum produto encontrado
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="barra-ordenacao">
                        <label for="ordenar">Ordenar:</label>
                        <select name="ordenar" id="ordenar" onchange="aplicarOrdenacao(this.value)">
                            <option value="recentes" <?php echo ($ordenar == 'recentes') ? 'selected' : ''; ?>>Mais recentes</option>
                            <option value="menor_preco" <?php echo ($ordenar == 'menor_preco') ? 'selected' : ''; ?>>Menor preÃ§o</option>
                            <option value="maior_preco" <?php echo ($ordenar == 'maior_preco') ? 'selected' : ''; ?>>Maior preÃ§o</option>
                            <option value="nome_az" <?php echo ($ordenar == 'nome_az') ? 'selected' : ''; ?>>Nome (A-Z)</option>
                            <option value="nome_za" <?php echo ($ordenar == 'nome_za') ? 'selected' : ''; ?>>Nome (Z-A)</option>
                        </select>
                    </div>
                </div>
                
                <!-- ===== BARRA DE PAGINAÃ‡ÃƒO (se necessÃ¡rio) ===== -->
                <?php if ($totalPaginas > 1): ?>
                <div class="produtos-barra-superior">
                    <div class="barra-info">
                        <span class="pagina-info">PÃ¡gina <strong><?php echo $paginaAtual; ?></strong> de <strong><?php echo $totalPaginas; ?></strong></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Grid de Produtos -->
                <?php if (!empty($produtos)): ?>
                <div class="produtos-grid-page">
                    
                    <?php foreach ($produtos as $product): ?>
                    <!-- Produto: <?php echo htmlspecialchars($product['nome']); ?> -->
                    <?php $badge = getProductBadge($product); ?>
                    <!-- Badge: [<?php echo $badge ?: 'NENHUM'; ?>] -->
                    <a href="produto.php?id=<?php echo $product['id']; ?>" class="produto-card-link">
                        <div class="produto-card">
                            <div class="produto-image<?php echo !empty($badge) ? ' ' . $badge : ''; ?>">
                                <?php if (!empty($product['imagem_principal'])): ?>
                                <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($product['imagem_principal']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['nome']); ?>"
                                     style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;"
                                     onerror="this.parentElement.innerHTML='<div class=\'produto-placeholder\'>ðŸ’…</div>';">
                                <?php else: ?>
                                <div class="produto-placeholder">ðŸ’…</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="produto-content">
                                <h3 class="produto-title"><?php echo htmlspecialchars($product['nome']); ?></h3>
                                <p class="produto-description">
                                    <?php echo htmlspecialchars(substr($product['descricao'] ?? '', 0, 80)); ?><?php echo strlen($product['descricao'] ?? '') > 80 ? '...' : ''; ?>
                                </p>
                                
                                <div class="produto-price">
                                    <?php if (isOnSale($product)): ?>
                                        <span style="text-decoration: line-through; opacity: 0.6; font-size: 0.85em; margin-right: 8px;">
                                            <?php echo formatPrice($product['preco']); ?>
                                        </span>
                                        <span style="color: var(--color-magenta); font-weight: 700;">
                                            <?php echo formatPrice($product['preco_promocional']); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo formatPrice($product['preco']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="produto-actions" onclick="event.stopPropagation(); event.preventDefault();">
                                    <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['nome'], ENT_QUOTES); ?>', event)">
                                        ðŸ›’ Adicionar
                                    </button>
                                    <button class="btn-buy-now" 
                                            onclick="buyNow(<?php echo $product['id']; ?>, event)" 
                                            data-has-variacoes="<?php echo (isset($product['tem_variacoes']) && $product['tem_variacoes'] > 0) ? '1' : '0'; ?>">
                                        Comprar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    
                </div>
                
                <!-- ===== PAGINAÃ‡ÃƒO ===== -->
                <?php if ($totalPaginas > 1): ?>
                <div class="paginacao">
                    <?php
                    // Construir query string preservando filtros
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
                    
                    <?php if ($paginaAtual > 1): ?>
                        <a href="?pagina=<?php echo $paginaAtual - 1; ?><?php echo $queryString; ?>" class="btn-paginacao">â† Anterior</a>
                    <?php endif; ?>
                    
                    <div class="paginacao-numeros">
                        <?php
                        $range = 2; // Mostrar 2 pÃ¡ginas antes e depois
                        $start = max(1, $paginaAtual - $range);
                        $end = min($totalPaginas, $paginaAtual + $range);
                        
                        if ($start > 1): ?>
                            <a href="?pagina=1<?php echo $queryString; ?>" class="btn-pagina">1</a>
                            <?php if ($start > 2): ?>
                                <span class="paginacao-ellipsis">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo $queryString; ?>" 
                               class="btn-pagina <?php echo ($i == $paginaAtual) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($end < $totalPaginas): ?>
                            <?php if ($end < $totalPaginas - 1): ?>
                                <span class="paginacao-ellipsis">...</span>
                            <?php endif; ?>
                            <a href="?pagina=<?php echo $totalPaginas; ?><?php echo $queryString; ?>" class="btn-pagina"><?php echo $totalPaginas; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="?pagina=<?php echo $paginaAtual + 1; ?><?php echo $queryString; ?>" class="btn-paginacao">PrÃ³xima â†’</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                
                <!-- Mensagem quando nÃ£o hÃ¡ produtos -->
                <div class="no-products" style="text-align: center; padding: 80px 20px; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    <div style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;">ðŸ”</div>
                    <h3 style="font-size: 24px; color: #333; margin-bottom: 12px;">Nenhum produto encontrado</h3>
                    <p style="color: #666; margin-bottom: 30px;">
                        Tente ajustar seus filtros ou explore outras categorias.
                    </p>
                    <a href="produtos.php" class="btn-primary" style="display: inline-block; padding: 12px 32px; background: linear-gradient(135deg, #E6007E, #C4006A); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.2s;">
                        Ver todos os produtos
                    </a>
                </div>
                
                <?php endif; ?>
                
            </div>
            
        </div>
        
    </div>
</section>

<!-- CSS do Mini Cart -->
<style>
    /* ===== BADGES/SELOS DOS CARDS (ESPECÃFICO PRODUTOS.PHP) ===== */
    .produtos-grid-page .produto-image {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
    }
    
    /* Badges - aplicar APENAS quando houver classe especÃ­fica */
    .produtos-grid-page .produto-image.novo::before {
        content: 'NOVO';
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 6px 12px;
        min-height: 28px;
        max-width: calc(100% - 20px);
        background: #10b981;
        color: white;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1.3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        z-index: 3;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        box-sizing: border-box;
    }
    
    .produtos-grid-page .produto-image.promocao::before {
        content: 'PROMOÃ‡ÃƒO';
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        min-height: 22px;
        max-width: calc(100% - 20px);
        background: #ef4444;
        color: white;
        border-radius: 12px;
        font-size: 0.6rem;
        font-weight: 700;
        line-height: 1.3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        z-index: 3;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        box-sizing: border-box;
    }
    
    .produtos-grid-page .produto-image.lancamento::before {
        content: 'LANÃ‡AMENTO';
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        min-height: 22px;
        max-width: calc(100% - 20px);
        background: #f59e0b;
        color: white;
        border-radius: 12px;
        font-size: 0.6rem;
        font-weight: 700;
        line-height: 1.3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        z-index: 3;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        box-sizing: border-box;
    }
    
    .produtos-grid-page .produto-image.exclusivo::before {
        content: 'EXCLUSIVO';
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        min-height: 22px;
        max-width: calc(100% - 20px);
        background: #8b5cf6;
        color: white;
        border-radius: 12px;
        font-size: 0.6rem;
        font-weight: 700;
        line-height: 1.3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        z-index: 3;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        box-sizing: border-box;
    }
    
    /* ===== CONTAINER PRINCIPAL LARGO ===== */
    .produtos-container-wide {
        max-width: 1500px;
        width: 100%;
        padding: 24px 24px 32px;
        margin: 0 auto;
    }
    
    /* ===== CABEÃ‡ALHO INTEGRADO (dentro do conteÃºdo) ===== */
    .produtos-header-inline {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f0f0f0;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .produtos-titulo {
        font-size: 1.75rem;
        color: #333;
        margin: 0 0 4px 0;
        font-weight: 700;
        letter-spacing: -0.02em;
    }
    
    .produtos-contagem {
        font-size: 0.9rem;
        color: #666;
        margin: 0;
        font-weight: 400;
    }
    
    .produtos-contagem strong {
        color: #E6007E;
        font-weight: 600;
    }
    
    /* ===== LAYOUT: SIDEBAR + CONTEÃšDO ===== */
    .produtos-layout {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 28px;
        align-items: start;
    }
    
    /* ===== SIDEBAR DE FILTROS - MINIMALISTA E ELEGANTE ===== */
    .filtros-sidebar {
        background: white;
        border-radius: 8px;
        padding: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        height: fit-content;
        position: sticky;
        top: 100px;
        border: 1px solid #f0f0f0;
        overflow: hidden;
    }
    
    /* HEADER DA SIDEBAR */
    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 20px;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .sidebar-header h3 {
        font-size: 0.938rem;
        color: #333;
        margin: 0;
        font-weight: 600;
        letter-spacing: 0.2px;
    }
    
    .btn-limpar {
        font-size: 0.813rem;
        color: #E6007E;
        text-decoration: none;
        font-weight: 500;
        padding: 0;
        background: none;
        border: none;
        cursor: pointer;
        transition: color 0.2s;
    }
    
    .btn-limpar:hover {
        color: #C4006A;
        text-decoration: underline;
    }
    
    /* SEÃ‡Ã•ES DE FILTRO */
    .filtro-secao {
        padding: 16px 20px;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .filtro-secao:last-child {
        border-bottom: none;
    }
    
    .filtro-titulo {
        font-size: 0.75rem;
        color: #888;
        margin: 0 0 12px 0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    /* LISTAS DE LINKS */
    .filtro-lista {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .filtro-lista li {
        margin: 0;
    }
    
    .filtro-link {
        display: block;
        padding: 7px 0;
        color: #555;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.3;
        transition: all 0.2s ease;
        position: relative;
        padding-left: 0;
    }
    
    .filtro-link:hover {
        color: #E6007E;
        padding-left: 3px;
    }
    
    .filtro-link.active {
        color: #E6007E;
        font-weight: 500;
        padding-left: 3px;
    }
    
    .filtro-link.active::before {
        content: '';
        position: absolute;
        left: -10px;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 3px;
        background: #E6007E;
        border-radius: 50%;
    }
    
    /* CHECKBOXES (STATUS) */
    .filtro-opcoes {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .filtro-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 0.875rem;
        color: #555;
        font-weight: 400;
        transition: color 0.2s;
    }
    
    .filtro-checkbox:hover {
        color: #E6007E;
    }
    
    .filtro-checkbox input[type="checkbox"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #E6007E;
        margin: 0;
    }
    
    .filtro-checkbox span {
        user-select: none;
    }
    
    /* ===== CONTEÃšDO PRINCIPAL ===== */
    .produtos-conteudo {
        min-width: 0; /* Fix para grid overflow */
        margin-top: 0;
        padding-top: 0;
    }
    
    /* ===== BARRA SUPERIOR DE INFORMAÃ‡Ã•ES (PaginaÃ§Ã£o) ===== */
    .produtos-barra-superior {
        background: transparent;
        padding: 0;
        margin-bottom: 16px;
        border: none;
        display: flex;
        justify-content: flex-start;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .barra-info {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .pagina-info {
        font-size: 0.85rem;
        color: #666;
        margin: 0;
        white-space: nowrap;
    }
    
    .pagina-info strong {
        color: #E6007E;
        font-weight: 600;
    }
    
    .barra-ordenacao {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 14px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .barra-ordenacao label {
        font-size: 0.9rem;
        color: #666;
        font-weight: 500;
        white-space: nowrap;
    }
    
    .barra-ordenacao select {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        min-width: 160px;
    }
    
    .barra-ordenacao select:hover,
    .barra-ordenacao select:focus {
        border-color: #E6007E;
        outline: none;
    }
    
    /* ===== PAGINAÃ‡ÃƒO ===== */
    .paginacao {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        margin-top: 48px;
        flex-wrap: wrap;
    }
    
    .btn-paginacao,
    .btn-pagina {
        padding: 10px 16px;
        background: white;
        color: #666;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.2s;
    }
    
    .btn-paginacao:hover,
    .btn-pagina:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        color: #E6007E;
    }
    
    .btn-pagina.active {
        background: linear-gradient(135deg, #E6007E, #C4006A);
        color: white;
    }
    
    .paginacao-numeros {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .paginacao-ellipsis {
        color: #999;
        padding: 0 4px;
    }
    
    /* ===== RESPONSIVIDADE ===== */
    @media (max-width: 1200px) {
        .produtos-container-wide {
            max-width: 1200px;
            padding: 16px 20px 24px;
        }
        
        .produtos-grid-page {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }
    }
    
    @media (max-width: 992px) {
        .produtos-container-wide {
            padding: 12px 16px 20px;
        }
        
        .produtos-header-inline {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
        }
        
        .produtos-titulo {
            font-size: 1.5rem;
        }
        
        .produtos-layout {
            grid-template-columns: 1fr;
            gap: 24px;
        }
        
        .filtros-sidebar {
            position: static;
            order: 2;
        }
        
        .produtos-conteudo {
            order: 1;
        }
        
        .produtos-grid-page {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
    }
    
    @media (max-width: 768px) {
        .produtos-titulo {
            font-size: 1.4rem;
        }
        
        .produtos-contagem {
            font-size: 0.85rem;
        }
        
        .produtos-header-inline {
            margin-bottom: 10px;
            padding-bottom: 10px;
        }
        
        .barra-ordenacao {
            width: 100%;
            justify-content: space-between;
        }
        
        .barra-ordenacao label {
            font-size: 0.85rem;
        }
        
        .barra-ordenacao select {
            flex: 1;
            max-width: 200px;
            font-size: 0.85rem;
        }
        
        .produtos-barra-superior {
            padding-bottom: 12px;
        }
        
        .barra-info {
            flex-direction: column;
            gap: 6px;
            align-items: flex-start;
        }
        
        .produtos-grid-page {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .filtros-sidebar {
            padding: 16px;
        }
        
        .btn-paginacao,
        .btn-pagina {
            padding: 8px 12px;
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 480px) {
        .produtos-grid-page {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .paginacao {
            gap: 8px;
        }
        
        .btn-paginacao {
            font-size: 0.85rem;
            padding: 8px 10px;
        }
        
        .btn-pagina {
            padding: 8px 10px;
            font-size: 0.85rem;
        }
    }
    
    /* ===== GRID DE PRODUTOS (PÃ¡gina de Listagem) ===== */
    .produtos-grid-page {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
        width: 100%;
        margin: 0 auto;
        align-items: start;
    }
    
    /* Link wrapper para tornar cards clicÃ¡veis */
    .produto-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: all 0.3s ease;
    }
    
    .produto-card-link:hover .produto-card {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    
    /* Ajustar cards para grid (sem comportamento de carrossel) */
    .produtos-grid-page .produto-card {
        flex: none;
        width: 100%;
        min-width: 100%;
        max-width: 100%;
        height: 480px;
        min-height: 480px;
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
    }
    
    /* Garantir altura uniforme do conteÃºdo */
    .produtos-grid-page .produto-card .produto-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    /* Empurrar botÃµes para o final */
    .produtos-grid-page .produto-card .produto-actions {
        margin-top: auto;
    }
    
    /* Garantir alturas fixas dos elementos de conteÃºdo */
    .produtos-grid-page .produto-card .produto-title {
        height: 3.4rem;
        min-height: 3.4rem;
        max-height: 3.4rem;
    }
    
    .produtos-grid-page .produto-card .produto-description {
        height: 2.7rem;
        min-height: 2.7rem;
        max-height: 2.7rem;
    }
    
    .produtos-grid-page .produto-card .produto-price {
        height: 2.25rem;
        min-height: 2.25rem;
        max-height: 2.25rem;
    }
    
    /* ===== MINI CARRINHO DRAWER - CSS ===== */
    .mini-cart-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9998;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .mini-cart-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .mini-cart-drawer {
        position: fixed;
        top: 0;
        right: 0;
        width: 380px;
        max-width: 100%;
        height: 100vh;
        background: white;
        box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        transform: translateX(100%);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }

    .mini-cart-drawer.active {
        transform: translateX(0);
    }

    .mini-cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 2px solid #f1f5f9;
        flex-shrink: 0;
    }

    .mini-cart-header h2 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .btn-close-cart {
        width: 38px;
        height: 38px;
        border-radius: 19px;
        border: none;
        background: rgba(230, 0, 126, 0.1);
        color: var(--color-magenta);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-close-cart:hover {
        background: var(--color-magenta);
        color: white;
        transform: rotate(90deg) scale(1.05);
    }

    .btn-close-cart:active {
        transform: rotate(90deg) scale(0.95);
    }

    .mini-cart-body {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 14px;
        min-height: 200px;
        max-height: calc(100vh - 320px);
        background: #f8fafc;
    }

    .mini-cart-body::-webkit-scrollbar {
        width: 6px;
    }

    .mini-cart-body::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 3px;
        margin: 4px 0;
    }

    .mini-cart-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
        border-radius: 3px;
    }

    .mini-cart-body::-webkit-scrollbar-thumb:hover {
        background: var(--color-magenta-dark);
    }

    .cart-empty {
        text-align: center;
        padding: 40px 20px;
    }

    .cart-empty-icon {
        font-size: 56px;
        margin-bottom: 12px;
        opacity: 0.3;
    }

    .cart-empty h3 {
        font-size: 1.1rem;
        color: #64748b;
        margin-bottom: 6px;
    }

    .cart-empty p {
        color: #94a3b8;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    .btn-continue-shopping {
        background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 22px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-continue-shopping:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
    }

    .cart-item {
        display: grid;
        grid-template-columns: 70px 1fr;
        gap: 12px;
        padding: 14px;
        background: white;
        border-radius: 12px;
        margin-bottom: 10px;
        position: relative;
        transition: all 0.3s ease;
        align-items: start;
        border: 1px solid #f1f5f9;
    }

    .cart-item:hover {
        background: #fafafa;
        border-color: #e2e8f0;
        box-shadow: 0 2px 8px rgba(230, 0, 126, 0.08);
    }

    .cart-item:last-child {
        margin-bottom: 0;
    }

    .cart-item-image {
        width: 70px;
        height: 70px;
        border-radius: 10px;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        flex-shrink: 0;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 10px;
    }

    .cart-item-image span {
        display: block;
        font-size: 2rem;
        line-height: 1;
    }

    .cart-item-details {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
        width: 100%;
    }

    .cart-item-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.9rem;
        line-height: 1.4;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        word-break: break-word;
        margin-bottom: 2px;
    }

    .cart-item-variant {
        font-size: 0.75rem;
        color: #64748b;
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
        margin-top: 2px;
    }

    .cart-item-price {
        font-weight: 700;
        color: var(--color-magenta);
        font-size: 1.05rem;
        margin: 0;
        letter-spacing: -0.01em;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-variant-numeric: tabular-nums;
    }

    .cart-item-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 6px;
    }

    .qty-control {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: white;
        border-radius: 20px;
        padding: 4px 6px;
        border: 1.5px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .qty-btn {
        width: 26px;
        height: 26px;
        border-radius: 13px;
        border: none;
        background: linear-gradient(135deg, rgba(230, 0, 126, 0.1) 0%, rgba(230, 0, 126, 0.15) 100%);
        color: var(--color-magenta);
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        line-height: 1;
    }

    .qty-btn:hover:not(:disabled) {
        background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
        color: white;
        transform: scale(1.1);
        box-shadow: 0 2px 6px rgba(230, 0, 126, 0.3);
    }

    .qty-btn:active:not(:disabled) {
        transform: scale(0.95);
    }

    .qty-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
        background: rgba(148, 163, 184, 0.1);
        color: #94a3b8;
    }

    .qty-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .qty-value {
        min-width: 28px;
        text-align: center;
        font-weight: 700;
        color: var(--color-magenta);
        font-size: 0.95rem;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        letter-spacing: -0.02em;
    }

    .btn-remove-item {
        width: 30px;
        height: 30px;
        border-radius: 15px;
        border: none;
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .btn-remove-item:hover {
        background: #ef4444;
        color: white;
        transform: scale(1.15);
    }

    .btn-remove-item:active {
        transform: scale(0.95);
    }

    .btn-remove-item svg {
        width: 15px;
        height: 15px;
    }

    .free-shipping-bar {
        padding: 14px;
        background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
        border-radius: 10px;
        margin-bottom: 14px;
        border: 1px solid #fbcfe8;
    }

    .shipping-text {
        font-size: 0.8rem;
        color: #1e293b;
        margin-bottom: 8px;
        font-weight: 600;
        text-align: center;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .shipping-progress {
        height: 6px;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 3px;
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .shipping-progress-bar {
        height: 100%;
        background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
        border-radius: 4px;
        transition: width 0.5s ease;
        position: relative;
    }

    .shipping-progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .shipping-unlocked {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #10b981;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .mini-cart-footer {
        padding: 16px 20px;
        border-top: 2px solid #f1f5f9;
        background: white;
        flex-shrink: 0;
    }

    .mini-cart-subtotal {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        padding: 12px 14px;
        background: #fafafa;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
    }

    .mini-cart-subtotal span {
        color: #64748b;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .mini-cart-subtotal strong {
        color: var(--color-magenta);
        font-size: 1.3rem;
        font-weight: 700;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }

    .btn-view-cart {
        display: block;
        width: 100%;
        background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
        color: white;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(230, 0, 126, 0.25);
        letter-spacing: 0.02em;
    }

    .btn-view-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(230, 0, 126, 0.4);
    }

    .btn-view-cart:active {
        transform: translateY(0);
    }

    /* Responsivo */
    @media (max-width: 480px) {
        .mini-cart-drawer {
            width: 100%;
        }

        .cart-count {
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            top: -4px;
            right: -4px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .cart-item {
            grid-template-columns: 60px 1fr;
            gap: 10px;
            padding: 10px;
        }

        .cart-item-image {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }

        .cart-item-image span {
            font-size: 1.6rem;
        }

        .cart-item-name {
            font-size: 0.85rem;
        }

        .cart-item-price {
            font-size: 0.95rem;
        }

        .cart-item-actions {
            flex-direction: row;
            gap: 6px;
        }

        .qty-control {
            padding: 3px 5px;
            gap: 3px;
        }

        .qty-btn {
            width: 24px;
            height: 24px;
            font-size: 14px;
        }

        .qty-value {
            min-width: 24px;
            font-size: 0.9rem;
        }

        .btn-remove-item {
            width: 28px;
            height: 28px;
        }

        .mini-cart-header {
            padding: 16px;
        }

        .mini-cart-header h2 {
            font-size: 1.25rem;
        }

        .mini-cart-body {
            padding: 12px;
        }

        .mini-cart-footer {
            padding: 12px 14px;
        }

        .mini-cart-subtotal {
            padding: 10px 12px;
            margin-bottom: 12px;
        }

        .mini-cart-subtotal span {
            font-size: 0.9rem;
        }

        .mini-cart-subtotal strong {
            font-size: 1.2rem;
        }

        .free-shipping-bar {
            padding: 12px;
        }

        .btn-view-cart {
            padding: 13px;
            font-size: 0.9rem;
        }
    }
</style>

<!-- JavaScript do Carrinho -->
<script>
    const __noopLog = (...args) => {};

    // ===== FUNÃ‡ÃƒO DE ORDENAÃ‡ÃƒO =====
    function aplicarOrdenacao(valor) {
        const url = new URL(window.location.href);
        url.searchParams.set('ordenar', valor);
        url.searchParams.delete('pagina'); // Reset para primeira pÃ¡gina ao mudar ordenaÃ§Ã£o
        window.location.href = url.toString();
    }
    
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

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        
        const colors = {
            success: 'linear-gradient(135deg, #10b981, #059669)',
            info: 'linear-gradient(135deg, #3b82f6, #1e40af)',
            warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
            error: 'linear-gradient(135deg, #ef4444, #dc2626)'
        };
        
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${colors[type]};
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            max-width: 300px;
            backdrop-filter: blur(10px);
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover apÃ³s 4 segundos
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 400);
        }, 4000);
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
        const productCard = evt.target.closest('.produto-card');
        
        if (!productCard) {
            console.error('âŒ Card do produto nÃ£o encontrado');
            return;
        }
        
        // Buscar elementos do card
        let priceElement = productCard.querySelector('.produto-price');
        let imgElement = productCard.querySelector('img');
        let titleElement = productCard.querySelector('.produto-title') || productCard.querySelector('h3');
        
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
            image: image
        };
        
        __noopLog('ðŸ“¦ Produto a adicionar:', newProductData);
        
        // Obter carrinho
        let cart = getCart();
        
        // Verificar se produto jÃ¡ existe
        const existingIndex = cart.findIndex(item => parseInt(item.id) === numericProductId);
        
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
        
        // Mostrar notificaÃ§Ã£o
        showNotification('ðŸ›’ ' + name + ' adicionado ao carrinho!', 'success');
        
        __noopLog('âœ… Produto adicionado ao carrinho!');
    }

    function buyNow(productId, event) {
        __noopLog('âš¡ Comprar agora:', productId);
        
        // Prevenir propagaÃ§Ã£o do evento
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Verificar se o produto tem variaÃ§Ãµes
        const button = event?.target?.closest('.btn-buy-now');
        const hasVariacoes = button?.getAttribute('data-has-variacoes') === '1';
        
        __noopLog('ðŸ” Possui variaÃ§Ãµes?', hasVariacoes);
        
        // Se tem variaÃ§Ãµes, redirecionar para pÃ¡gina do produto
        if (hasVariacoes) {
            __noopLog('âž¡ï¸ Redirecionando para pÃ¡gina do produto (tem variaÃ§Ãµes)');
            window.location.href = 'produto.php?id=' + productId;
            return;
        }
        
        // Se nÃ£o tem variaÃ§Ãµes, adicionar direto ao carrinho
        __noopLog('ðŸ›’ Produto sem variaÃ§Ãµes, adicionando ao carrinho');
        
        // Buscar o nome do produto do card
        const productCard = event?.target?.closest('.produto-card');
        const titleElement = productCard?.querySelector('.produto-title') || productCard?.querySelector('h3');
        const productName = titleElement ? titleElement.textContent.trim() : 'Produto';
        
        // Adicionar ao carrinho usando a funÃ§Ã£o existente
        addToCart(productId, productName, event);
        
        // Aguardar um pouco para garantir que foi adicionado e entÃ£o redirecionar
        setTimeout(() => {
            window.location.href = 'pages/carrinho.php';
        }, 300);
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
                            <button class="qty-btn" onclick="updateQty(${itemId}, '', ${itemQty - 1})" ${itemQty <= 1 ? 'disabled' : ''} aria-label="Diminuir quantidade">âˆ’</button>
                            <span class="qty-value">${itemQty}</span>
                            <button class="qty-btn" onclick="updateQty(${itemId}, '', ${itemQty + 1})" aria-label="Aumentar quantidade">+</button>
                        </div>
                        <button class="btn-remove-item" onclick="removeFromCart(${itemId}, '')" title="Remover produto" aria-label="Remover produto">
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

<?php require_once 'includes/chat.php'; ?>
<?php require_once 'includes/footer.php'; ?>


<?php
// E-commerce D&Z - Página Individual do Produto
session_start();

// Incluir configuração e conexão
require_once 'config.php';
require_once 'conexao.php';
require_once 'cms_data_provider.php';

// Instanciar CMS Provider
$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

// Verificar se usuário está logado
$usuarioLogado = isset($_SESSION['cliente']);
$nomeUsuario = $usuarioLogado ? htmlspecialchars($_SESSION['cliente']['nome']) : '';

// Buscar configuração de frete grátis
$freteGratisValor = getFreteGratisThreshold($pdo);

// Validar ID do produto
$produtoId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($produtoId <= 0) {
    header('Location: produtos.php');
    exit;
}

// Buscar dados do produto
$query = "SELECT p.*, c.nome AS categoria, c.id AS categoria_id
          FROM produtos p
          LEFT JOIN categorias c ON p.categoria_id = c.id
          WHERE p.id = ? AND p.status = 'ativo'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $produtoId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produto = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Se produto não encontrado ou inativo
if (!$produto) {
    header('Location: produtos.php');
    exit;
}

// Buscar produtos relacionados (mesma categoria)
$produtosRelacionados = [];
if ($produto['categoria_id']) {
    $queryRelacionados = "SELECT * FROM produtos 
                          WHERE categoria_id = ? AND id != ? AND status = 'ativo'
                          ORDER BY RAND()
                          LIMIT 4";
    $stmtRel = mysqli_prepare($conn, $queryRelacionados);
    mysqli_stmt_bind_param($stmtRel, 'ii', $produto['categoria_id'], $produtoId);
    mysqli_stmt_execute($stmtRel);
    $resultRel = mysqli_stmt_get_result($stmtRel);
    while ($row = mysqli_fetch_assoc($resultRel)) {
        $produtosRelacionados[] = $row;
    }
    mysqli_stmt_close($stmtRel);
}

// Buscar combos/sugestões (mesma marca ou complementares)
$produtosCombos = [];
if (!empty($produto['marca'])) {
    $queryCombos = "SELECT * FROM produtos 
                    WHERE marca = ? AND id != ? AND status = 'ativo'
                    ORDER BY RAND()
                    LIMIT 4";
    $stmtCombos = mysqli_prepare($conn, $queryCombos);
    mysqli_stmt_bind_param($stmtCombos, 'si', $produto['marca'], $produtoId);
    mysqli_stmt_execute($stmtCombos);
    $resultCombos = mysqli_stmt_get_result($stmtCombos);
    while ($row = mysqli_fetch_assoc($resultCombos)) {
        $produtosCombos[] = $row;
    }
    mysqli_stmt_close($stmtCombos);
}

// Buscar variações do produto
$variacoes = [];
$variacoesPorTipo = [];
$checkTableVariacoes = mysqli_query($conn, "SHOW TABLES LIKE 'produto_variacoes'");
if (mysqli_num_rows($checkTableVariacoes) > 0) {
    $queryVariacoes = "SELECT * FROM produto_variacoes 
                       WHERE produto_id = ? AND ativo = 1
                       ORDER BY tipo, valor";
    $stmtVar = mysqli_prepare($conn, $queryVariacoes);
    mysqli_stmt_bind_param($stmtVar, 'i', $produtoId);
    mysqli_stmt_execute($stmtVar);
    $resultVar = mysqli_stmt_get_result($stmtVar);
    while ($row = mysqli_fetch_assoc($resultVar)) {
        $variacoes[] = $row;
        // Agrupar por tipo
        $tipo = $row['tipo'] ?? 'Variação';
        if (!isset($variacoesPorTipo[$tipo])) {
            $variacoesPorTipo[$tipo] = [];
        }
        $variacoesPorTipo[$tipo][] = $row;
    }
    mysqli_stmt_close($stmtVar);
}

// Decodificar galeria de imagens do campo JSON
$galeriaImagens = [];
if (!empty($produto['imagens'])) {
    $galeriaImagens = json_decode($produto['imagens'], true) ?? [];
}

$pageTitle = htmlspecialchars($produto['nome']) . ' | D&Z Professional';
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/navbar.php'; ?>

<!-- ===== BREADCRUMB ===== -->
<div class="breadcrumb-container">
    <div class="breadcrumb">
        <a href="index.php">Início</a>
        <span>/</span>
        <a href="produtos.php">Produtos</a>
        <?php if ($produto['categoria']): ?>
        <span>/</span>
        <a href="produtos.php?categoria=<?php echo urlencode($produto['categoria']); ?>"><?php echo htmlspecialchars($produto['categoria']); ?></a>
        <?php endif; ?>
        <span>/</span>
        <span class="current"><?php echo htmlspecialchars($produto['nome']); ?></span>
    </div>
</div>

<!-- ===== PÁGINA DO PRODUTO ===== -->
<section class="produto-page">
    <div class="produto-container">
        
        <!-- ÁREA PRINCIPAL DO PRODUTO -->
        <div class="produto-main">
            
            <!-- COLUNA ESQUERDA: IMAGENS -->
            <div class="produto-gallery">
                <div class="produto-image-main">
                    <?php if (!empty($produto['imagem_principal'])): ?>
                    <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($produto['imagem_principal']); ?>" 
                         alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                         id="mainProductImage"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'400\'%3E%3Crect fill=\'%23f5f5f5\' width=\'400\' height=\'400\'/%3E%3Ctext fill=\'%23E6007E\' font-family=\'Arial\' font-size=\'80\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3E💅%3C/text%3E%3C/svg%3E';">
                    <?php else: ?>
                    <div class="produto-placeholder-large">💅</div>
                    <?php endif; ?>
                </div>
                
                <!-- Galeria de miniaturas (se houver imagens extras) -->
                <?php if (!empty($galeriaImagens) && count($galeriaImagens) > 0): ?>
                <div class="produto-gallery-thumbs">
                    <!-- Imagem principal como primeira thumb -->
                    <?php if (!empty($produto['imagem_principal'])): ?>
                    <div class="thumb-item active" onclick="changeMainImage('../admin/assets/images/produtos/<?php echo htmlspecialchars($produto['imagem_principal']); ?>', this)">
                        <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($produto['imagem_principal']); ?>" 
                             alt="Imagem 1">
                    </div>
                    <?php endif; ?>
                    
                    <!-- Imagens extras da galeria -->
                    <?php foreach ($galeriaImagens as $index => $imgNome): ?>
                    <div class="thumb-item" onclick="changeMainImage('../admin/assets/images/produtos/<?php echo htmlspecialchars($imgNome); ?>', this)">
                        <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($imgNome); ?>" 
                             alt="Imagem <?php echo $index + 2; ?>"
                             onerror="this.parentElement.style.display='none';">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- COLUNA DIREITA: INFORMAÇÕES -->
            <div class="produto-info">
                <!-- Cabeçalho com título e meta -->
                <div class="produto-header">
                    <?php if ($produto['marca'] || $produto['categoria']): ?>
                    <div class="produto-meta">
                        <?php if ($produto['marca']): ?>
                        <a href="produtos.php?marca=<?php echo urlencode($produto['marca']); ?>" class="meta-link"><?php echo htmlspecialchars($produto['marca']); ?></a>
                        <?php endif; ?>
                        <?php if ($produto['marca'] && $produto['categoria']): ?>
                        <span class="meta-separator">•</span>
                        <?php endif; ?>
                        <?php if ($produto['categoria']): ?>
                        <a href="produtos.php?categoria=<?php echo urlencode($produto['categoria']); ?>" class="meta-link"><?php echo htmlspecialchars($produto['categoria']); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <h1 class="produto-name"><?php echo htmlspecialchars($produto['nome']); ?></h1>
                </div>
                
                <!-- Preço destacado -->
                <div class="produto-price-section">
                    <?php if (isOnSale($produto)): ?>
                    <div class="price-badge-sale">Em Promoção</div>
                    <div class="price-wrapper">
                        <div class="price-main">
                            <span class="price-label">De:</span>
                            <span class="price-old"><?php echo formatPrice($produto['preco']); ?></span>
                        </div>
                        <div class="price-main">
                            <span class="price-label">Por:</span>
                            <span class="price-current sale"><?php echo formatPrice($produto['preco_promocional']); ?></span>
                        </div>
                        <div class="price-discount">
                            <?php
                            $desconto = (($produto['preco'] - $produto['preco_promocional']) / $produto['preco']) * 100;
                            echo 'Economize ' . round($desconto) . '%';
                            ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="price-wrapper">
                        <span class="price-current"><?php echo formatPrice($produto['preco']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Variações do Produto (se existirem) -->
                <?php if (!empty($variacoesPorTipo)): ?>
                <div class="produto-variacoes-section">
                    <?php foreach ($variacoesPorTipo as $tipo => $variacoesTipo): ?>
                    <div class="variacao-grupo">
                        <label class="variacao-label"><?php echo htmlspecialchars($tipo); ?>:</label>
                        <div class="variacao-opcoes">
                            <?php foreach ($variacoesTipo as $variacao): ?>
                            <?php 
                            // Determinar imagem da variação (priorizar imagem_principal, depois imagem)
                            $imagemVariacao = '';
                            if (!empty($variacao['imagem_principal'])) {
                                $imagemVariacao = $variacao['imagem_principal'];
                            } elseif (!empty($variacao['imagem'])) {
                                $imagemVariacao = $variacao['imagem'];
                            }
                            
                            // Determinar preço da variação seguindo prioridade correta
                            $precoVariacao = null;
                            $precoPromoVariacao = null;
                            $precoAdicional = 0;
                            
                            // Prioridade 1: preco_promocional da variação
                            if (isset($variacao['preco_promocional']) && $variacao['preco_promocional'] > 0) {
                                $precoPromoVariacao = $variacao['preco_promocional'];
                                if (isset($variacao['preco']) && $variacao['preco'] > 0) {
                                    $precoVariacao = $variacao['preco'];
                                } else {
                                    $precoVariacao = $produto['preco'];
                                }
                            }
                            // Prioridade 2: preco específico da variação
                            elseif (isset($variacao['preco']) && $variacao['preco'] > 0) {
                                $precoVariacao = $variacao['preco'];
                            }
                            // Prioridade 3: preco_adicional
                            elseif (isset($variacao['preco_adicional']) && $variacao['preco_adicional'] > 0) {
                                $precoAdicional = $variacao['preco_adicional'];
                                $precoVariacao = $produto['preco'] + $precoAdicional;
                            }
                            // Fallback: preço base do produto
                            else {
                                $precoVariacao = $produto['preco'];
                                if (isset($produto['preco_promocional']) && $produto['preco_promocional'] > 0) {
                                    $precoPromoVariacao = $produto['preco_promocional'];
                                }
                            }
                            
                            $estoqueVariacao = isset($variacao['estoque']) ? (int)$variacao['estoque'] : 0;
                            ?>
                            <button type="button" 
                                    class="variacao-btn" 
                                    data-variacao-id="<?php echo $variacao['id']; ?>"
                                    data-tipo="<?php echo htmlspecialchars($tipo); ?>"
                                    data-valor="<?php echo htmlspecialchars($variacao['valor']); ?>"
                                    data-preco="<?php echo $precoVariacao; ?>"
                                    data-preco-promo="<?php echo $precoPromoVariacao ?? ''; ?>"
                                    data-estoque="<?php echo $estoqueVariacao; ?>"
                                    data-imagem="<?php echo htmlspecialchars($imagemVariacao); ?>"
                                    onclick="selectVariacao(this)">
                                <?php echo htmlspecialchars($variacao['valor']); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Info e descrição -->
                <div class="produto-details-block">
                    <div class="produto-stock">
                        <?php if ($produto['estoque'] > 0): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="stock-icon">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        <span class="stock-text">Em estoque (<?php echo $produto['estoque']; ?> unidades)</span>
                        <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="stock-icon">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                        <span class="stock-text">Produto esgotado</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($produto['descricao'])): ?>
                    <div class="produto-description-short">
                        <?php 
                        $descCurta = substr($produto['descricao'], 0, 150);
                        echo nl2br(htmlspecialchars($descCurta));
                        if (strlen($produto['descricao']) > 150) echo '...';
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Divisor -->
                <div class="produto-divider"></div>
                
                <!-- Hidden inputs para variação selecionada -->
                <input type="hidden" id="variacaoSelecionadaId" value="">
                <input type="hidden" id="variacaoSelecionadaTipo" value="">
                <input type="hidden" id="variacaoSelecionadaValor" value="">
                <input type="hidden" id="variacaoSelecionadaPreco" value="">
                <input type="hidden" id="variacaoSelecionadaPrecoPromo" value="">
                <input type="hidden" id="variacaoSelecionadaEstoque" value="">
                <input type="hidden" id="variacaoSelecionadaImagem" value="">
                
                <!-- Botões de ação -->
                <div class="produto-actions-block">
                <div class="produto-actions-box">
                    <?php if ($produto['estoque'] > 0): ?>
                    <?php 
                    $precoFinal = isOnSale($produto) ? $produto['preco_promocional'] : $produto['preco'];
                    $imagemProduto = !empty($produto['imagem_principal']) 
                        ? 'http://localhost/admin-teste/admin/assets/images/produtos/' . htmlspecialchars($produto['imagem_principal']) 
                        : '💅';
                    ?>
                    <button class="btn-add-to-cart-large" onclick="addToCart(<?php echo $produto['id']; ?>, '<?php echo htmlspecialchars($produto['nome'], ENT_QUOTES); ?>', <?php echo $precoFinal; ?>, '<?php echo htmlspecialchars($imagemProduto, ENT_QUOTES); ?>', event)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="btn-icon">
                            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                        <span>Adicionar ao Carrinho</span>
                    </button>
                    <button class="btn-buy-now-large" onclick="buyNow(<?php echo $produto['id']; ?>, '<?php echo htmlspecialchars($produto['nome'], ENT_QUOTES); ?>', <?php echo $precoFinal; ?>, '<?php echo htmlspecialchars($imagemProduto, ENT_QUOTES); ?>', event)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="btn-icon">
                            <path d="M13 7h-2v4H7v2h4v4h2v-4h4v-2h-4V7zm-1-5C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                        </svg>
                        <span>Comprar Agora</span>
                    </button>
                    <?php else: ?>
                    <button class="btn-disabled" disabled>
                        <span>Produto Esgotado</span>
                    </button>
                    <?php endif; ?>
                </div>
                </div>
            </div>
            
        </div>
        
        <!-- DESCRIÇÃO COMPLETA -->
        <?php if (!empty($produto['descricao'])): ?>
        <div class="produto-section">
            <h2 class="section-title">Descrição do Produto</h2>
            <div class="produto-description-full">
                <?php echo nl2br(htmlspecialchars($produto['descricao'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- PRODUTOS RELACIONADOS -->
        <?php if (!empty($produtosRelacionados)): ?>
        <div class="produto-section">
            <h2 class="section-title">Produtos Relacionados</h2>
            <div class="produtos-grid-related">
                <?php foreach ($produtosRelacionados as $rel): ?>
                <a href="produto.php?id=<?php echo $rel['id']; ?>" class="produto-card-mini">
                    <div class="produto-image-mini">
                        <?php if (!empty($rel['imagem_principal'])): ?>
                        <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($rel['imagem_principal']); ?>" 
                             alt="<?php echo htmlspecialchars($rel['nome']); ?>"
                             onerror="this.parentElement.innerHTML='<div class=\'produto-placeholder-mini\'>💅</div>';">
                        <?php else: ?>
                        <div class="produto-placeholder-mini">💅</div>
                        <?php endif; ?>
                    </div>
                    <div class="produto-content-mini">
                        <h4><?php echo htmlspecialchars($rel['nome']); ?></h4>
                        <p class="price-mini">
                            <?php if (isOnSale($rel)): ?>
                                <span class="old"><?php echo formatPrice($rel['preco']); ?></span>
                                <span class="sale"><?php echo formatPrice($rel['preco_promocional']); ?></span>
                            <?php else: ?>
                                <?php echo formatPrice($rel['preco']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- COMBOS/SUGESTÕES -->
        <?php if (!empty($produtosCombos)): ?>
        <div class="produto-section">
            <h2 class="section-title">Você também pode gostar</h2>
            <div class="produtos-grid-related">
                <?php foreach ($produtosCombos as $combo): ?>
                <a href="produto.php?id=<?php echo $combo['id']; ?>" class="produto-card-mini">
                    <div class="produto-image-mini">
                        <?php if (!empty($combo['imagem_principal'])): ?>
                        <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($combo['imagem_principal']); ?>" 
                             alt="<?php echo htmlspecialchars($combo['nome']); ?>"
                             onerror="this.parentElement.innerHTML='<div class=\'produto-placeholder-mini\'>💅</div>';">
                        <?php else: ?>
                        <div class="produto-placeholder-mini">💅</div>
                        <?php endif; ?>
                    </div>
                    <div class="produto-content-mini">
                        <h4><?php echo htmlspecialchars($combo['nome']); ?></h4>
                        <p class="price-mini">
                            <?php if (isOnSale($combo)): ?>
                                <span class="old"><?php echo formatPrice($combo['preco']); ?></span>
                                <span class="sale"><?php echo formatPrice($combo['preco_promocional']); ?></span>
                            <?php else: ?>
                                <?php echo formatPrice($combo['preco']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- AVALIAÇÕES (Seção preparada) -->
        <div class="produto-section">
            <h2 class="section-title">Avaliações de Clientes</h2>
            <div class="avaliacoes-empty">
                <div class="stars-preview">☆ ☆ ☆ ☆ ☆</div>
                <h3>Seja a primeira a avaliar!</h3>
                <p>Compartilhe sua experiência com este produto.<br>Sua opinião ajuda outras clientes a escolherem melhor.</p>
                <span class="cta-review">
                    <span>⭐</span>
                    <span>Em breve: sistema de avaliações</span>
                </span>
            </div>
        </div>
                <p class="text-muted">Em breve você poderá ver e deixar sua avaliação sobre este produto.</p>
            </div>
        </div>
        
    </div>
</section>

<!-- ===== MINI CARRINHO DRAWER ===== -->
<div id="miniCartOverlay" class="mini-cart-overlay"></div>
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
        <!-- Conteúdo preenchido via JS -->
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

<style>
    :root {
        --color-magenta: #E6007E;
        --color-magenta-dark: #C4006A;
    }
    
    /* ===== BREADCRUMB ===== */
    .breadcrumb-container {
        background: #fff;
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }
    
    .breadcrumb {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 24px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .breadcrumb a {
        color: #666;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .breadcrumb a:hover {
        color: var(--color-magenta);
    }
    
    .breadcrumb .current {
        color: var(--color-magenta);
        font-weight: 600;
    }
    
    /* ===== PÁGINA DO PRODUTO ===== */
    .produto-page {
        padding: 40px 0 60px;
        background: linear-gradient(to bottom, #fafafa 0%, #f5f5f5 100%);
        min-height: 60vh;
    }
    
    .produto-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 24px;
    }
    
    /* ===== ÁREA PRINCIPAL ===== */
    .produto-main {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.08);
        margin-bottom: 40px;
        border: 1px solid rgba(0,0,0,0.04);
    }
    
    /* ===== GALERIA DE IMAGENS ===== */
    .produto-gallery {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    
    .produto-image-main {
        width: 100%;
        height: auto;
        aspect-ratio: 1;
        border-radius: 12px;
        overflow: hidden;
        background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: all 0.3s ease;
    }
    
    .produto-image-main:hover {
        box-shadow: 0 4px 16px rgba(230, 0, 126, 0.12);
    }
    
    .produto-image-main img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.4s ease;
    }
    
    .produto-image-main:hover img {
        transform: scale(1.05);
    }
    
    .produto-placeholder-large {
        font-size: 120px;
        color: var(--color-magenta);
        opacity: 0.2;
    }
    
    /* Galeria de miniaturas */
    .produto-gallery-thumbs {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding: 8px 0;
    }
    
    .thumb-item {
        flex-shrink: 0;
        width: 80px;
        height: 80px;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        background: #f5f5f5;
    }
    
    .thumb-item:hover {
        border-color: #ddd;
        transform: translateY(-2px);
    }
    
    .thumb-item.active {
        border-color: var(--color-magenta);
        box-shadow: 0 2px 8px rgba(230, 0, 126, 0.3);
    }
    
    .thumb-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* ===== INFORMAÇÕES DO PRODUTO ===== */
    .produto-info {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    
    /* Cabeçalho */
    .produto-header {
        margin-bottom: 28px;
    }
    
    .produto-name {
        font-size: 2rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 10px 0 0;
        line-height: 1.2;
        letter-spacing: -0.01em;
    }
    
    .produto-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #888;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
        margin-bottom: 8px;
    }
    
    .produto-meta .meta-link {
        color: var(--color-magenta);
        text-decoration: none;
        transition: all 0.2s ease;
        position: relative;
    }
    
    .produto-meta .meta-link::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 1px;
        background: var(--color-magenta);
        transition: width 0.3s ease;
    }
    
    .produto-meta .meta-link:hover::after {
        width: 100%;
    }
    
    .produto-meta .meta-separator {
        color: #ddd;
        font-size: 0.7rem;
    }
    
    /* Preço */
    .produto-price-section {
        padding: 24px 0;
        margin-bottom: 32px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .price-badge-sale {
        display: inline-block;
        background: transparent;
        color: var(--color-magenta);
        padding: 0;
        border-radius: 0;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 12px;
    }
    
    .price-wrapper {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .price-main {
        display: flex;
        align-items: baseline;
        gap: 12px;
    }
    
    .price-label {
        font-size: 0.85rem;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .price-old {
        font-size: 1.4rem;
        color: #aaa;
        text-decoration: line-through;
        font-weight: 600;
    }
    
    .price-current {
        font-size: 2.8rem;
        font-weight: 800;
        color: #1a1a1a;
        letter-spacing: -0.02em;
        line-height: 1;
    }
    
    .price-current.sale {
        background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .price-discount {
        display: inline-block;
        background: transparent;
        color: #10b981;
        padding: 0;
        border-radius: 0;
        font-weight: 600;
        font-size: 0.9rem;
        margin-top: 8px;
        width: fit-content;
    }
    
    /* Variações do Produto */
    .produto-variacoes-section {
        margin: 28px 0;
        padding: 20px 0;
        border-top: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .variacao-grupo {
        margin-bottom: 20px;
    }
    
    .variacao-grupo:last-child {
        margin-bottom: 0;
    }
    
    .variacao-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #2a2a2a;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .variacao-opcoes {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .variacao-btn {
        padding: 10px 18px;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #2a2a2a;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .variacao-btn:hover {
        border-color: var(--color-magenta);
        background: rgba(230, 0, 126, 0.05);
        transform: translateY(-1px);
    }
    
    .variacao-btn.selected {
        border-color: var(--color-magenta);
        background: var(--color-magenta);
        color: white;
    }
    
    /* Detalhes do produto */
    .produto-details-block {
        display: flex;
        flex-direction: column;
        gap: 0;
        margin-bottom: 0;
    }
    
    .produto-stock {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        padding: 0;
        margin-bottom: 24px;
    }
    
    .stock-icon {
        flex-shrink: 0;
    }
    
    .produto-stock .stock-text {
        color: #666;
    }
    
    .produto-stock svg {
        color: #10b981;
        width: 18px;
        height: 18px;
    }
    
    .produto-stock:has(.stock-unavailable) svg {
        color: #f44336;
    }
    
    .produto-description-short {
        font-size: 0.95rem;
        line-height: 1.8;
        color: #666;
        background: transparent;
        padding: 0;
        border-radius: 0;
        border-left: none;
    }
    
    /* Divisor */
    .produto-divider {
        height: 1px;
        background: #f0f0f0;
        margin: 32px 0;
    }
    
    /* ===== AÇÕES ===== */
    .produto-actions-block {
        margin-top: 0;
    }
    
    .produto-actions-box {
        display: flex;
        gap: 12px;
        margin-top: 0;
    }
    
    .btn-add-to-cart-large,
    .btn-buy-now-large,
    .btn-disabled {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 24px;
        font-size: 0.9rem;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: none;
        letter-spacing: 0;
        position: relative;
        overflow: hidden;
    }
    
    .btn-add-to-cart-large .btn-icon,
    .btn-buy-now-large .btn-icon,
    .btn-disabled .btn-icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        z-index: 1;
    }
    
    .btn-add-to-cart-large span,
    .btn-buy-now-large span,
    .btn-disabled span {
        z-index: 1;
    }
    
    .btn-add-to-cart-large::before,
    .btn-buy-now-large::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
    }
    
    .btn-add-to-cart-large:hover::before,
    .btn-buy-now-large:hover::before {
        width: 300px;
        height: 300px;
    }
    
    .btn-add-to-cart-large {
        background: var(--color-magenta);
        color: white;
        box-shadow: 0 2px 8px rgba(230, 0, 126, 0.2);
    }
    
    .btn-add-to-cart-large:hover {
        background: var(--color-magenta-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(230, 0, 126, 0.3);
    }
    
    .btn-add-to-cart-large:active {
        transform: translateY(0);
    }
    
    .btn-buy-now-large {
        background: white;
        color: #2a2a2a;
        border: 2px solid #e0e0e0;
        box-shadow: none;
    }
    
    .btn-buy-now-large:hover {
        background: #fafafa;
        border-color: #2a2a2a;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-buy-now-large:active {
        transform: translateY(0);
    }
    
    .btn-disabled {
        background: #ddd;
        color: #999;
        cursor: not-allowed;
        box-shadow: none;
    }
    
    .btn-disabled:hover {
        transform: none;
    }
    
    /* ===== SEÇÕES ===== */
    .produto-section {
        background: linear-gradient(to bottom, #ffffff 0%, #fafbfc 100%);
        padding: 48px 40px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.06);
        margin-bottom: 28px;
        border: 1px solid rgba(230, 0, 126, 0.08);
        position: relative;
        overflow: hidden;
    }
    
    .produto-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, transparent 0%, var(--color-magenta) 50%, transparent 100%);
        opacity: 0.6;
    }
    
    .section-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0 0 32px;
        padding: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
    }
    
    .section-title::before {
        content: '✦';
        font-size: 1.2rem;
        color: var(--color-magenta);
        opacity: 0.7;
    }
    
    .section-title::after {
        content: '';
        flex: 1;
        height: 2px;
        background: linear-gradient(90deg, rgba(230, 0, 126, 0.3) 0%, transparent 100%);
        margin-left: 12px;
    }
    
    .produto-description-full {
        font-size: 1.05rem;
        line-height: 1.9;
        color: #3a3a3a;
        background: white;
        padding: 24px;
        border-radius: 8px;
        border-left: 3px solid var(--color-magenta);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    /* ===== PRODUTOS RELACIONADOS ===== */
    .produtos-grid-related {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }
    
    .produto-card-mini {
        display: block;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        position: relative;
    }
    
    .produto-card-mini::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--color-magenta), #ff4da6);
        transform: scaleX(0);
        transition: transform 0.4s ease;
    }
    
    .produto-card-mini:hover::before {
        transform: scaleX(1);
    }
    
    .produto-card-mini:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 28px rgba(230, 0, 126, 0.15);
        border-color: rgba(230, 0, 126, 0.2);
    }
    
    .produto-image-mini {
        width: 100%;
        aspect-ratio: 1;
        background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    
    .produto-image-mini img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    
    .produto-card-mini:hover .produto-image-mini img {
        transform: scale(1.08);
    }
    
    .produto-placeholder-mini {
        font-size: 56px;
        color: var(--color-magenta);
        opacity: 0.25;
    }
    
    .produto-content-mini {
        padding: 16px 14px;
        background: white;
    }
    
    .produto-content-mini h4 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #2a2a2a;
        margin: 0 0 10px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.8rem;
    }
    
    .price-mini {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .price-mini .old {
        font-size: 0.85rem;
        color: #aaa;
        text-decoration: line-through;
        font-weight: 500;
    }
    
    .price-mini .sale {
        color: var(--color-magenta);
    }
    
    /* ===== AVALIAÇÕES ===== */
    .avaliacoes-empty {
        text-align: center;
        padding: 48px 20px;
        background: linear-gradient(135deg, rgba(230, 0, 126, 0.03) 0%, rgba(255, 77, 166, 0.03) 100%);
        border-radius: 12px;
        border: 2px dashed rgba(230, 0, 126, 0.15);
    }
    
    .avaliacoes-empty .stars-preview {
        font-size: 2.5rem;
        margin-bottom: 16px;
        opacity: 0.2;
        letter-spacing: 4px;
    }
    
    .avaliacoes-empty .icon-reviews {
        font-size: 3.5rem;
        margin-bottom: 16px;
        opacity: 0.15;
    }
    
    .avaliacoes-empty h3 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2a2a2a;
        margin: 0 0 8px;
    }
    
    .avaliacoes-empty p {
        font-size: 1rem;
        color: #6b6b6b;
        margin: 8px 0 20px;
        line-height: 1.6;
    }
    
    .avaliacoes-empty .cta-review {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: white;
        color: var(--color-magenta);
        border: 2px solid var(--color-magenta);
        border-radius: 24px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: not-allowed;
        opacity: 0.6;
        transition: all 0.3s ease;
    }
    
    .text-muted {
        color: #999;
        font-size: 0.95rem;
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
        opacity: 0.3;
        cursor: not-allowed;
        background: rgba(148, 163, 184, 0.1);
        color: #94a3b8;
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
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(230, 0, 126, 0.25);
    }

    .btn-view-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(230, 0, 126, 0.35);
    }
    
    /* ===== RESPONSIVIDADE ===== */
    @media (max-width: 992px) {
        .produto-main {
            grid-template-columns: 1fr;
            gap: 32px;
            padding: 32px 24px;
        }
        
        .produto-name {
            font-size: 1.75rem;
        }
        
        .price-current {
            font-size: 2rem;
        }
        
        .produto-section {
            padding: 36px 28px;
        }
        
        .section-title {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 768px) {
        .produto-container {
            padding: 0 16px;
        }
        
        .produto-content {
            flex-direction: column;
            gap: 32px;
        }
        
        .produto-gallery {
            width: 100%;
        }
        
        .produto-info {
            width: 100%;
        }
        
        .produto-name {
            font-size: 1.8rem;
        }
        
        .produto-price-section {
            padding: 20px 16px;
        }
        
        .price-current {
            font-size: 2.2rem;
        }
        
        .price-old {
            font-size: 1.2rem;
        }
        
        .produto-actions-box {
            flex-direction: column;
        }
        
        .btn-add-to-cart-large,
        .btn-buy-now-large,
        .btn-disabled {
            width: 100%;
            font-size: 0.95rem;
        }
        
        .produto-gallery-thumbs {
            gap: 8px;
        }
        
        .thumb-item {
            width: 70px;
            height: 70px;
        }
        
        .variacao-opcoes {
            gap: 8px;
        }
        
        .variacao-btn {
            padding: 8px 14px;
            font-size: 0.85rem;
        }
        
        .produto-section {
            padding: 32px 20px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.4rem;
            margin-bottom: 24px;
        }
        
        .section-title::after {
            display: none;
        }
        
        .produto-description-full {
            padding: 20px 16px;
            font-size: 0.98rem;
        }
        
        .produtos-grid-related {
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        
        .produto-content-mini h4 {
            font-size: 0.9rem;
            min-height: auto;
        }
        
        .price-mini {
            font-size: 1rem;
        }
        
        .avaliacoes-empty {
            padding: 36px 16px;
        }
        
        .avaliacoes-empty .stars-preview {
            font-size: 2rem;
        }
        
        .avaliacoes-empty h3 {
            font-size: 1.1rem;
        }
        
        .avaliacoes-empty p {
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 480px) {
        .produto-name {
            font-size: 1.5rem;
        }
        
        .produto-price-section {
            padding: 16px 12px;
        }
        
        .price-current {
            font-size: 2rem;
        }
        
        .price-old {
            font-size: 1rem;
        }
        
        .btn-add-to-cart-large,
        .btn-buy-now-large,
        .btn-disabled {
            padding: 16px 20px;
            font-size: 0.9rem;
            gap: 8px;
        }
        
        .btn-add-to-cart-large .btn-icon,
        .btn-buy-now-large .btn-icon,
        .btn-disabled .btn-icon {
            width: 18px;
            height: 18px;
        }
        
        .produto-gallery-thumbs {
            gap: 6px;
        }
        
        .thumb-item {
            width: 60px;
            height: 60px;
        }
        
        .variacao-btn {
            padding: 8px 12px;
            font-size: 0.8rem;
            flex: 1 1 auto;
            min-width: calc(50% - 5px);
        }
        
        .produtos-grid-related {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .produto-card-mini {
            max-width: 100%;
        }
    }
</style>

<script>
    // Salvar valores originais do produto para poder voltar
    let imagemOriginalProduto = '';
    let precoOriginalProduto = '';
    let precoPromoOriginalProduto = '';
    let estoqueOriginalProduto = '';
    let produtoTemPromocao = false;
    let htmlOriginalPreco = '';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Salvar imagem original
        const mainImage = document.getElementById('mainProductImage');
        if (mainImage && mainImage.src) {
            imagemOriginalProduto = mainImage.src;
        }
        
        // Salvar preço original
        const precoCurrentElement = document.querySelector('.price-current');
        if (precoCurrentElement) {
            precoOriginalProduto = precoCurrentElement.textContent;
        }
        
        // Salvar preço antigo (se tiver promoção)
        const precoOldElement = document.querySelector('.price-old');
        if (precoOldElement) {
            precoPromoOriginalProduto = precoOldElement.textContent;
        }
        
        // Salvar estoque original
        const stockTextElement = document.querySelector('.stock-text');
        if (stockTextElement) {
            estoqueOriginalProduto = stockTextElement.textContent;
        }
        
        // Verificar se produto tem promoção e salvar HTML original completo
        const priceSection = document.querySelector('.produto-price-section');
        if (priceSection) {
            htmlOriginalPreco = priceSection.innerHTML;
            produtoTemPromocao = priceSection.querySelector('.price-badge-sale') !== null;
        }
    });
    
    // ===== GALERIA DE IMAGENS =====
    function changeMainImage(imageUrl, thumbElement) {
        // Atualizar imagem principal
        const mainImage = document.getElementById('mainProductImage');
        if (mainImage) {
            mainImage.src = imageUrl;
        }
        
        // Remover classe active de todos os thumbs
        const allThumbs = document.querySelectorAll('.thumb-item');
        allThumbs.forEach(thumb => thumb.classList.remove('active'));
        
        // Adicionar classe active no thumb clicado
        if (thumbElement) {
            thumbElement.classList.add('active');
        }
        
        // Remover seleção das variações quando clicar em uma miniatura
        const allVariacaoBtns = document.querySelectorAll('.variacao-btn');
        allVariacaoBtns.forEach(btn => btn.classList.remove('selected'));
        
        // Limpar variação selecionada dos campos hidden
        document.getElementById('variacaoSelecionadaId').value = '';
        document.getElementById('variacaoSelecionadaTipo').value = '';
        document.getElementById('variacaoSelecionadaValor').value = '';
        document.getElementById('variacaoSelecionadaPreco').value = '';
        document.getElementById('variacaoSelecionadaPrecoPromo').value = '';
        document.getElementById('variacaoSelecionadaEstoque').value = '';
        document.getElementById('variacaoSelecionadaImagem').value = '';
        
        // Restaurar HTML original completo da seção de preço
        const priceSection = document.querySelector('.produto-price-section');
        if (priceSection && htmlOriginalPreco) {
            priceSection.innerHTML = htmlOriginalPreco;
        }
        
        // Restaurar estoque original
        if (estoqueOriginalProduto) {
            const stockTextElement = document.querySelector('.stock-text');
            const stockIconElement = document.querySelector('.stock-icon');
            
            if (stockTextElement) {
                stockTextElement.textContent = estoqueOriginalProduto;
                
                // Restaurar ícone apropriado baseado no texto
                if (stockIconElement) {
                    if (estoqueOriginalProduto.includes('esgotado')) {
                        stockIconElement.innerHTML = '<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>';
                        stockIconElement.style.color = '#f44336';
                    } else {
                        stockIconElement.innerHTML = '<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>';
                        stockIconElement.style.color = '#10b981';
                    }
                }
            }
        }
    }
    
    // ===== VARIAÇÕES DO PRODUTO =====
    function selectVariacao(buttonElement) {
        // Remover seleção de outros botões do mesmo grupo
        const grupo = buttonElement.closest('.variacao-opcoes');
        if (grupo) {
            grupo.querySelectorAll('.variacao-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
        }
        
        // Adicionar classe selected no botão clicado
        buttonElement.classList.add('selected');
        
        // Armazenar dados da variação selecionada nos campos hidden
        document.getElementById('variacaoSelecionadaId').value = buttonElement.dataset.variacaoId || '';
        document.getElementById('variacaoSelecionadaTipo').value = buttonElement.dataset.tipo || '';
        document.getElementById('variacaoSelecionadaValor').value = buttonElement.dataset.valor || '';
        document.getElementById('variacaoSelecionadaPreco').value = buttonElement.dataset.preco || '';
        document.getElementById('variacaoSelecionadaPrecoPromo').value = buttonElement.dataset.precoPromo || '';
        document.getElementById('variacaoSelecionadaEstoque').value = buttonElement.dataset.estoque || '';
        document.getElementById('variacaoSelecionadaImagem').value = buttonElement.dataset.imagem || '';
        
        // Trocar imagem principal se variação tiver imagem própria
        const imagemVariacao = buttonElement.dataset.imagem;
        if (imagemVariacao && imagemVariacao.trim() !== '') {
            const mainImage = document.getElementById('mainProductImage');
            if (mainImage) {
                // Efeito de fade suave
                mainImage.style.opacity = '0.5';
                
                setTimeout(() => {
                    // Construir caminho completo da imagem
                    const imagePath = '../admin/assets/images/produtos/' + imagemVariacao;
                    mainImage.src = imagePath;
                    
                    // Restaurar opacidade
                    mainImage.style.opacity = '1';
                    
                    // Remover classe active de todos os thumbs (já que estamos mostrando imagem da variação)
                    const allThumbs = document.querySelectorAll('.thumb-item');
                    allThumbs.forEach(thumb => thumb.classList.remove('active'));
                }, 150);
            }
        }
        
        // Atualizar preço e promoção da variação
        const preco = parseFloat(buttonElement.dataset.preco);
        const precoPromo = buttonElement.dataset.precoPromo ? parseFloat(buttonElement.dataset.precoPromo) : null;
        
        const priceSection = document.querySelector('.produto-price-section');
        if (!priceSection) return;
        
        // Verificar se variação tem promoção válida
        const temPromocao = precoPromo && precoPromo > 0 && precoPromo < preco;
        
        if (temPromocao) {
            // VARIAÇÃO COM PROMOÇÃO - Reorganizar HTML para mostrar promoção
            const desconto = Math.round(((preco - precoPromo) / preco) * 100);
            
            priceSection.innerHTML = `
                <div class="price-badge-sale">Em Promoção</div>
                <div class="price-wrapper">
                    <div class="price-main">
                        <span class="price-label">De:</span>
                        <span class="price-old">R$ ${preco.toFixed(2).replace('.', ',')}</span>
                    </div>
                    <div class="price-main">
                        <span class="price-label">Por:</span>
                        <span class="price-current sale">R$ ${precoPromo.toFixed(2).replace('.', ',')}</span>
                    </div>
                    <div class="price-discount">
                        Economize ${desconto}%
                    </div>
                </div>
            `;
        } else {
            // VARIAÇÃO SEM PROMOÇÃO - Apenas preço normal
            priceSection.innerHTML = `
                <div class="price-wrapper">
                    <span class="price-current">R$ ${preco.toFixed(2).replace('.', ',')}</span>
                </div>
            `;
        }
        
        // Atualizar estoque da variação
        const estoque = parseInt(buttonElement.dataset.estoque);
        const stockTextElement = document.querySelector('.stock-text');
        const stockIconElement = document.querySelector('.stock-icon');
        
        if (stockTextElement) {
            if (estoque > 0) {
                stockTextElement.textContent = 'Em estoque (' + estoque + ' unidades)';
                
                // Atualizar ícone para checkmark (estoque disponível)
                if (stockIconElement) {
                    stockIconElement.innerHTML = '<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>';
                    stockIconElement.style.color = '#10b981';
                }
            } else {
                stockTextElement.textContent = 'Produto esgotado';
                
                // Atualizar ícone para X (sem estoque)
                if (stockIconElement) {
                    stockIconElement.innerHTML = '<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>';
                    stockIconElement.style.color = '#f44336';
                }
            }
        }
    }
    
    // ===== CARRINHO - FUNÇÕES PRINCIPAIS =====
    const FREE_SHIPPING_THRESHOLD = <?php echo $freteGratisValor; ?>;

    function getCart() {
        const cart = localStorage.getItem('dz_cart');
        return cart ? JSON.parse(cart) : [];
    }

    function setCart(cart) {
        localStorage.setItem('dz_cart', JSON.stringify(cart));
    }

    function addToCart(productId, productName, productPrice, productImage, event) {
        if (event) event.preventDefault();
        
        // Obter dados da variação selecionada (se houver)
        const variacaoId = document.getElementById('variacaoSelecionadaId')?.value || '';
        const variacaoTipo = document.getElementById('variacaoSelecionadaTipo')?.value || '';
        const variacaoValor = document.getElementById('variacaoSelecionadaValor')?.value || '';
        const variacaoPreco = document.getElementById('variacaoSelecionadaPreco')?.value || '';
        const variacaoPrecoPromo = document.getElementById('variacaoSelecionadaPrecoPromo')?.value || '';
        const variacaoEstoque = document.getElementById('variacaoSelecionadaEstoque')?.value || '';
        const variacaoImagem = document.getElementById('variacaoSelecionadaImagem')?.value || '';
        
        // Verificar se produto tem variações e se alguma foi selecionada
        const temVariacoes = document.querySelector('.produto-variacoes-section') !== null;
        if (temVariacoes && !variacaoId) {
            alert('Por favor, selecione uma opção antes de adicionar ao carrinho.');
            return;
        }
        
        const cart = getCart();
        const numericId = (productId === 0 || productId === '0') ? 0 : (parseInt(productId) || productId);
        
        // Se houver variação, usar preço e imagem da variação
        let finalPrice = parseFloat(productPrice) || 0;
        let finalImage = productImage;
        let finalName = productName;
        let variantKey = '';
        
        if (variacaoId) {
            // Usar preço da variação (prioridade: promocional > normal)
            if (variacaoPrecoPromo && parseFloat(variacaoPrecoPromo) > 0) {
                finalPrice = parseFloat(variacaoPrecoPromo);
            } else if (variacaoPreco && parseFloat(variacaoPreco) > 0) {
                finalPrice = parseFloat(variacaoPreco);
            }
            
            // Usar imagem da variação se existir
            if (variacaoImagem && variacaoImagem.trim() !== '') {
                finalImage = 'http://localhost/admin-teste/admin/assets/images/produtos/' + variacaoImagem;
            }
            
            // Montar nome com variação
            finalName = productName + ' - ' + variacaoValor;
            variantKey = variacaoId;
        }
        
        // Procurar item existente (produto + variação)
        const existingItem = cart.find(item => {
            const itemNumericId = (item.id === 0 || item.id === '0') ? 0 : (parseInt(item.id) || item.id);
            const itemVariantKey = item.variantKey || '';
            return itemNumericId === numericId && itemVariantKey === variantKey;
        });
        
        if (existingItem) {
            existingItem.qty = (parseInt(existingItem.qty) || 0) + 1;
        } else {
            cart.push({
                id: numericId,
                name: finalName,
                price: finalPrice,
                qty: 1,
                variant: variacaoValor || '',
                variantKey: variantKey,
                image: finalImage,
                addedAt: new Date().toISOString()
            });
        }
        
        setCart(cart);
        updateCartBadge();
        openMiniCart();
        renderMiniCart();
    }

    function buyNow(productId, productName, productPrice, productImage, event) {
        if (event) event.preventDefault();
        addToCart(productId, productName, productPrice, productImage, null);
        setTimeout(() => {
            window.location.href = 'pages/carrinho.php';
        }, 300);
    }

    function removeFromCart(itemId, variantKey = '') {
        let cart = getCart();
        const numericItemId = (itemId === 0 || itemId === '0') ? 0 : (parseInt(itemId) || itemId);
        const initialLength = cart.length;
        
        cart = cart.filter((item) => {
            const itemNumericId = (item.id === 0 || item.id === '0') ? 0 : (parseInt(item.id) || item.id);
            const itemVariantKey = item.variantKey || '';
            const idsMatch = itemNumericId === numericItemId;
            const variantsMatch = itemVariantKey === variantKey;
            return !(idsMatch && variantsMatch);
        });
        
        const removedCount = initialLength - cart.length;
        setCart(cart);
        updateCartBadge();
        
        if (removedCount > 0) {
            renderMiniCart();
        }
    }

    function updateQty(itemId, variantKey, newQty) {
        const cart = getCart();
        
        const numericItemId = (itemId === 0 || itemId === '0') ? 0 : (parseInt(itemId) || itemId);
        
        const item = cart.find(i => {
            const iNumericId = (i.id === 0 || i.id === '0') ? 0 : (parseInt(i.id) || i.id);
            const iVariantKey = i.variantKey || '';
            return iNumericId === numericItemId && iVariantKey === variantKey;
        });
        
        if (item) {
            if (newQty <= 0) {
                removeFromCart(itemId, variantKey);
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
        
        if (!body) return;
        
        // Se carrinho vazio
        if (cart.length === 0) {
            body.innerHTML = `
                <div class="cart-empty">
                    <div class="cart-empty-icon">🛒</div>
                    <h3>Seu carrinho está vazio</h3>
                    <p>Adicione produtos para começar suas compras!</p>
                    <button class="btn-continue-shopping" onclick="closeMiniCart()">Continuar comprando</button>
                </div>
            `;
            subtotalEl.textContent = 'R$ 0,00';
            
            freeShippingBar.innerHTML = `
                <div class="shipping-text">Faltam R$ ${FREE_SHIPPING_THRESHOLD.toFixed(2).replace('.', ',')} para ganhar frete grátis</div>
                <div class="shipping-progress">
                    <div class="shipping-progress-bar" style="width: 0%"></div>
                </div>
            `;
            return;
        }
        
        // Renderizar itens
        body.innerHTML = cart.map((item, index) => {
            const itemPrice = (typeof item.price === 'number' && !isNaN(item.price)) ? item.price : 0;
            const itemQty = parseInt(item.qty) || 1;
            const itemId = item.id || 0;
            const itemVariant = item.variant || '';
            const itemVariantKey = item.variantKey || '';
            const itemName = item.name || 'Produto';
            const itemImage = item.image || '';
            
            const escapedName = itemName.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const escapedVariantKey = itemVariantKey.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            
            return `
            <div class="cart-item" data-product-id="${itemId}" data-variant-key="${escapedVariantKey}">
                <div class="cart-item-image">
                    ${itemImage && itemImage.startsWith('http') ? `<img src="${itemImage}" alt="${escapedName}" loading="lazy">` : `<span style="font-size: 2rem;">${itemImage || '💅'}</span>`}
                </div>
                <div class="cart-item-details">
                    <div class="cart-item-name">${itemName}</div>
                    <div class="cart-item-price">R$ ${itemPrice.toFixed(2).replace('.', ',')}</div>
                    <div class="cart-item-actions">
                        <div class="qty-control">
                            <button class="qty-btn" onclick="updateQty(${itemId}, '${escapedVariantKey}', ${itemQty - 1})" ${itemQty <= 1 ? 'disabled' : ''} aria-label="Diminuir quantidade">−</button>
                            <span class="qty-value">${itemQty}</span>
                            <button class="qty-btn" onclick="updateQty(${itemId}, '${escapedVariantKey}', ${itemQty + 1})" aria-label="Aumentar quantidade">+</button>
                        </div>
                        <button class="btn-remove-item" onclick="removeFromCart(${itemId}, '${escapedVariantKey}')" title="Remover produto" aria-label="Remover produto">
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
        
        // Barra de frete grátis
        const remaining = FREE_SHIPPING_THRESHOLD - subtotal;
        const progress = Math.min((subtotal / FREE_SHIPPING_THRESHOLD) * 100, 100);
        
        if (remaining > 0) {
            freeShippingBar.innerHTML = `
                <div class="shipping-text">Faltam R$ ${remaining.toFixed(2).replace('.', ',')} para frete grátis</div>
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
                    Você desbloqueou frete grátis 🎉
                </div>
            `;
        }
    }

    function openMiniCart() {
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
        document.getElementById('miniCartOverlay').classList.remove('active');
        document.getElementById('miniCartDrawer').classList.remove('active');
        document.body.style.overflow = '';
        
        // Mostrar chat quando carrinho fecha
        const chatBtn = document.querySelector('.chat-button');
        const chatModal = document.getElementById('chatModal');
        if (chatBtn) chatBtn.classList.remove('chat-hidden');
        if (chatModal) chatModal.classList.remove('chat-hidden');
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', () => {
        updateCartBadge();
        renderMiniCart();
        
        // Abrir mini carrinho ao clicar no botão do carrinho
        const cartButton = document.getElementById('cartButton');
        if (cartButton) {
            cartButton.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                openMiniCart();
            });
        }
        
        const btnClose = document.getElementById('closeMiniCart');
        if (btnClose) btnClose.addEventListener('click', closeMiniCart);
        
        const overlay = document.getElementById('miniCartOverlay');
        if (overlay) overlay.addEventListener('click', closeMiniCart);
        
        // Fechar com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeMiniCart();
                closeSearchPanel();
            }
        });
    });

    // ===== DROPDOWN DO USUÁRIO =====
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
            
            requestAnimationFrame(() => {
                const isOpen = searchPanel.classList.toggle('active');
                searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                
                if (isOpen && searchInput) {
                    setTimeout(() => {
                        requestAnimationFrame(() => {
                            searchInput.focus();
                        });
                    }, 350);
                }
            });
        });
    }

    document.addEventListener('click', (e) => {
        if (!searchPanel || !searchToggle) return;
        if (!searchPanel.classList.contains('active')) return;
        if (searchPanel.contains(e.target) || searchToggle.contains(e.target)) return;
        closeSearchPanel();
    });
</script>

<?php require_once 'includes/chat.php'; ?>
<?php require_once 'includes/footer.php'; ?>

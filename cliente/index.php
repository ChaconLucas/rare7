<?php
session_start();

require_once 'config.php';
require_once 'conexao.php';
require_once 'cms_data_provider.php';

$cms = new CMSProvider($conn);

$freteGratisValor = getFreteGratisThreshold($pdo);
$homeSettings = $cms->getHomeSettings();
$banners = $cms->getActiveBanners();
$featuredProducts = $cms->getFeaturedProducts(48);
$allProducts = $cms->getAllProducts(12);
$beneficios = $cms->getHomeBenefits();
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();
$promocoes = $cms->getActivePromotions();
$metricas = $cms->getActiveMetrics();
$testimonials = $cms->getTestimonials(3);

$clubesDestaque = [];
$clubsQuery = "SELECT nome, sigla, imagem_path, ordem FROM cms_home_clubs WHERE ativo = 1 ORDER BY ordem ASC, id ASC";
$clubsResult = mysqli_query($conn, $clubsQuery);
if ($clubsResult) {
    while ($club = mysqli_fetch_assoc($clubsResult)) {
        $clubesDestaque[] = $club;
    }
}

if (empty($clubesDestaque)) {
    $clubesDestaque = [
        ['nome' => 'Real Madrid', 'sigla' => 'RMA', 'imagem_path' => '', 'ordem' => 1],
        ['nome' => 'Barcelona', 'sigla' => 'BAR', 'imagem_path' => '', 'ordem' => 2],
        ['nome' => 'Manchester City', 'sigla' => 'MCI', 'imagem_path' => '', 'ordem' => 3],
        ['nome' => 'Bayern', 'sigla' => 'BAY', 'imagem_path' => '', 'ordem' => 4],
        ['nome' => 'PSG', 'sigla' => 'PSG', 'imagem_path' => '', 'ordem' => 5],
        ['nome' => 'Milan', 'sigla' => 'MIL', 'imagem_path' => '', 'ordem' => 6],
        ['nome' => 'Benfica', 'sigla' => 'BEN', 'imagem_path' => '', 'ordem' => 7],
        ['nome' => 'Inter', 'sigla' => 'INT', 'imagem_path' => '', 'ordem' => 8],
        ['nome' => 'Liverpool', 'sigla' => 'LIV', 'imagem_path' => '', 'ordem' => 9],
        ['nome' => 'Juventus', 'sigla' => 'JUV', 'imagem_path' => '', 'ordem' => 10]
    ];
}

function resolveClubImageUrl(string $rawPath): string {
    $path = trim($rawPath);
    if ($path === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path) || strpos($path, 'data:') === 0 || strpos($path, '//') === 0) {
        return $path;
    }

    $normalized = ltrim($path, '/');
    if (strpos($normalized, '../') === 0) {
        return $normalized;
    }

    return '../' . $normalized;
}

if (empty($banners)) {
    $banners = [
        [
            'title' => 'ESTILO GLOBAL',
            'subtitle' => 'NOVA COLECAO 2026',
            'description' => 'CAMISAS EXCLUSIVAS',
            'image_path' => 'uploads/banners/banner-1.jpg',
            'button_text' => 'EXPLORAR',
            'button_link' => '#vitrine'
        ],
        [
            'title' => 'NOVA COLECAO 2026',
            'subtitle' => 'Rare Premium',
            'description' => 'Modelos selecionados',
            'image_path' => 'uploads/banners/banner-2.jpg',
            'button_text' => 'EXPLORAR',
            'button_link' => '#vitrine'
        ],
        [
            'title' => 'CAMISAS EXCLUSIVAS',
            'subtitle' => 'Edicao limitada',
            'description' => 'Pequenos lotes, grande identidade',
            'image_path' => 'uploads/banners/banner-3.jpg',
            'button_text' => 'EXPLORAR',
            'button_link' => '#vitrine'
        ]
    ];
}

$usuarioLogado = isset($_SESSION['cliente']);
$nomeUsuario = $usuarioLogado ? htmlspecialchars($_SESSION['cliente']['nome']) : '';
$profileLink = $usuarioLogado ? 'pages/minha-conta.php' : 'pages/login.php';

$productIdsForSizes = [];
foreach ($allProducts as $pSize) {
    $id = (int)($pSize['id'] ?? 0);
    if ($id > 0) {
        $productIdsForSizes[$id] = true;
    }
}
foreach ($featuredProducts as $pSize) {
    $id = (int)($pSize['id'] ?? 0);
    if ($id > 0) {
        $productIdsForSizes[$id] = true;
    }
}

$productSizesMap = [];
if (!empty($productIdsForSizes)) {
    $ids = array_keys($productIdsForSizes);
    $inClause = implode(',', array_map('intval', $ids));
    $sizesSql = "
        SELECT produto_id, valor
        FROM produto_variacoes
        WHERE ativo = 1
          AND LOWER(TRIM(tipo)) = 'tamanho'
          AND produto_id IN ($inClause)
        ORDER BY produto_id ASC, valor ASC
    ";
    $sizesResult = mysqli_query($conn, $sizesSql);
    if ($sizesResult) {
        while ($sizeRow = mysqli_fetch_assoc($sizesResult)) {
            $pid = (int)($sizeRow['produto_id'] ?? 0);
            $value = trim((string)($sizeRow['valor'] ?? ''));
            if ($pid <= 0 || $value === '') {
                continue;
            }
            if (!isset($productSizesMap[$pid])) {
                $productSizesMap[$pid] = [];
            }
            $lower = mb_strtolower($value);
            $exists = false;
            foreach ($productSizesMap[$pid] as $existing) {
                if (mb_strtolower((string)$existing) === $lower) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $productSizesMap[$pid][] = $value;
            }
        }
    }
}

$mapToVitrinePayload = static function (array $product): array {
    $img = '';
    if (!empty($product['imagem_principal'])) {
        $imageName = ltrim((string)$product['imagem_principal'], '/');
        $imageFile = __DIR__ . '/../admin/assets/images/produtos/' . $imageName;
        if (is_file($imageFile)) {
            $img = '../admin/assets/images/produtos/' . $imageName;
        }
    }

    $productId = (int)($product['id'] ?? 0);
    global $productSizesMap;
    $sizes = $productSizesMap[$productId] ?? [];

    return [
        'id' => (int)($product['id'] ?? 0),
        'name' => $product['nome'] ?? 'Produto',
        'description' => mb_substr((string)($product['descricao'] ?? ''), 0, 90),
        'category' => $product['categoria'] ?? 'Raras',
        'price' => (float)($product['preco'] ?? 0),
        'sale_price' => (float)($product['preco_promocional'] ?? 0),
        'image' => $img,
        'is_launch' => ($product['is_lancamento'] ?? '') === 'yes',
        'sizes' => $sizes,
        'has_sizes' => !empty($sizes)
    ];
};

$vitrineProducts = array_map($mapToVitrinePayload, $allProducts);
$vitrineLaunchProducts = array_map($mapToVitrinePayload, $featuredProducts);

$featuredIds = array_map(static function ($product) {
    return (int)($product['id'] ?? 0);
}, $featuredProducts);

$bannerSlides = [];
foreach ($banners as $banner) {
    $titleRaw = trim((string)($banner['title'] ?? ''));
    $subtitleRaw = trim((string)($banner['subtitle'] ?? ''));
    $descriptionRaw = trim((string)($banner['description'] ?? ''));
    $buttonTextRaw = trim((string)($banner['button_text'] ?? ''));
    $buttonLinkRaw = trim((string)($banner['button_link'] ?? ''));

    $bannerSlides[] = [
        'title' => $titleRaw,
        'subtitle' => $subtitleRaw !== '' ? $subtitleRaw : $descriptionRaw,
        'image' => getBannerImageUrl((string)($banner['image_path'] ?? '')),
        'button_text' => $buttonTextRaw,
        'button_link' => $buttonLinkRaw !== '' ? $buttonLinkRaw : '#vitrine'
    ];
}

if (empty($bannerSlides)) {
    $bannerSlides = [
        ['title' => 'ESTILO GLOBAL', 'subtitle' => 'A assinatura visual do futebol de luxo.', 'image' => getBannerImageUrl('uploads/banners/banner-1.jpg'), 'button_text' => 'EXPLORAR', 'button_link' => '#vitrine'],
        ['title' => 'NOVA COLECAO 2026', 'subtitle' => 'Recortes limpos, tecnologia e identidade Rare.', 'image' => getBannerImageUrl('uploads/banners/banner-2.jpg'), 'button_text' => 'EXPLORAR', 'button_link' => '#vitrine'],
        ['title' => 'CAMISAS EXCLUSIVAS', 'subtitle' => 'Series limitadas para quem veste autenticidade.', 'image' => getBannerImageUrl('uploads/banners/banner-3.jpg'), 'button_text' => 'EXPLORAR', 'button_link' => '#vitrine']
    ];
}

$fallbackBenefits = [
    ['icone' => 'local_shipping', 'titulo' => 'Frete gratis', 'descricao' => 'Entrega rapida para todo o Brasil'],
    ['icone' => 'support_agent', 'titulo' => 'Atendimento 24h', 'descricao' => 'Equipe pronta para te atender'],
    ['icone' => 'credit_card', 'titulo' => 'Parcelamento', 'descricao' => 'Pague com flexibilidade e seguranca'],
    ['icone' => 'style', 'titulo' => 'Personalizacao', 'descricao' => 'Detalhes unicos para sua camisa']
];
$beneficiosRender = !empty($beneficios) ? $beneficios : $fallbackBenefits;

$heroTitle = trim($homeSettings['hero_title'] ?? '') ?: 'RARE';
$heroKicker = array_key_exists('hero_kicker', $homeSettings)
    ? trim((string)$homeSettings['hero_kicker'])
    : 'RARE EXPERIENCE';
$heroSubtitle = array_key_exists('hero_subtitle', $homeSettings)
    ? trim((string)$homeSettings['hero_subtitle'])
    : 'Futebol com estetica premium.';
$heroDescription = array_key_exists('hero_description', $homeSettings)
    ? trim((string)$homeSettings['hero_description'])
    : 'Curadoria premium para quem veste autenticidade.';
$heroButtonText = array_key_exists('hero_button_text', $homeSettings)
    ? trim((string)$homeSettings['hero_button_text'])
    : 'EXPLORAR';
$heroButtonLink = array_key_exists('hero_button_link', $homeSettings)
    ? trim((string)$homeSettings['hero_button_link'])
    : '#vitrine';

$defaultHeroLogoPath = 'assets/images/logo.png';
$fallbackHeroLogoPngPath = 'assets/images/logo.png';
$heroLogoPathRaw = trim($homeSettings['hero_logo_path'] ?? '');
$heroLogoPath = $heroLogoPathRaw !== '' ? $heroLogoPathRaw : $defaultHeroLogoPath;

// Resolve local logo path safely when CMS provides a relative path.
if (!preg_match('/^https?:\/\//i', $heroLogoPath) && stripos($heroLogoPath, 'data:') !== 0) {
    $normalized = str_replace('\\', '/', ltrim($heroLogoPath, '/\\'));
    $normalizedNoCliente = preg_replace('#^cliente/#i', '', $normalized);
    $candidates = [
        $normalized,
        $normalizedNoCliente,
        '../' . $normalizedNoCliente,
        '../image/' . basename($normalizedNoCliente),
        'assets/images/' . basename($normalizedNoCliente),
        $defaultHeroLogoPath
    ];

    $resolved = null;
    foreach ($candidates as $candidate) {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
        if (is_file($filePath)) {
            $resolved = $candidate;
            break;
        }
    }

    $heroLogoPath = $resolved ?: $defaultHeroLogoPath;
}

$scriptBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/cliente/index.php')), '/');
$toPublicUrl = static function (string $path) use ($scriptBasePath): string {
    $normalizedPath = str_replace('\\', '/', trim($path));
    if ($normalizedPath === '') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $normalizedPath) || stripos($normalizedPath, 'data:') === 0) {
        return $normalizedPath;
    }
    if (strpos($normalizedPath, '/') === 0) {
        return $normalizedPath;
    }
    return ($scriptBasePath !== '' ? $scriptBasePath : '') . '/' . ltrim($normalizedPath, '/');
};

$heroLogoUrl = $toPublicUrl($heroLogoPath);
$fallbackHeroLogoUrl = $toPublicUrl($fallbackHeroLogoPngPath);

$launchTitle = trim($homeSettings['launch_title'] ?? '') ?: 'Vitrine Interativa';
$launchSubtitle = trim($homeSettings['launch_subtitle'] ?? '') ?: 'Selecione a categoria e navegue por uma curadoria de produtos.';
$launchButtonText = trim($homeSettings['launch_button_text'] ?? '') ?: 'Ver Todos os Lançamentos';
$launchButtonLink = trim($homeSettings['launch_button_link'] ?? '') ?: '#catalogo';

$benefitsTitle = trim($homeSettings['benefits_title'] ?? '') ?: 'Beneficios Rare';
$benefitsSubtitle = trim($homeSettings['benefits_subtitle'] ?? '') ?: 'Acabamento premium e experiencia de compra refinada.';

$productsTitle = trim($homeSettings['products_title'] ?? '') ?: 'Produtos Reais da Loja';
$productsSubtitle = trim($homeSettings['products_subtitle'] ?? '') ?: 'Itens vindos diretamente do seu loop PHP atual.';
$productsButtonText = trim($homeSettings['products_button_text'] ?? '') ?: 'Ver todos os produtos';
$productsButtonLink = trim($homeSettings['products_button_link'] ?? '') ?: 'produtos.php';

$bannerInterval = (int)($homeSettings['banner_interval'] ?? 4);
if ($bannerInterval < 3 || $bannerInterval > 30) {
    $bannerInterval = 4;
}

$activePromo = !empty($promocoes) ? $promocoes[0] : null;
$couponTitle = $activePromo['titulo'] ?? 'Ganhe 10% OFF no seu primeiro pedido';
$couponSubtitle = $activePromo['subtitulo'] ?? 'Use seu cupom para liberar sua primeira vantagem Rare.';
$couponCode = trim($activePromo['cupom_codigo'] ?? '') ?: 'RARE10';

$footerProductLinks = $footerLinks['produtos'] ?? [];
$footerSupportLinks = $footerLinks['atendimento'] ?? [];
if (empty($footerProductLinks)) {
    $footerProductLinks = [
        ['url' => 'produtos.php', 'titulo' => 'Todos os produtos'],
        ['url' => '#vitrine', 'titulo' => 'Lançamentos']
    ];
}
if (empty($footerSupportLinks)) {
    $footerSupportLinks = [
        ['url' => 'pages/minha-conta.php', 'titulo' => 'Minha conta'],
        ['url' => 'pages/carrinho.php', 'titulo' => 'Carrinho']
    ];
}

$instagramUrl = trim($footerData['instagram'] ?? '') ?: '#';
$tiktokUrl = trim($footerData['tiktok'] ?? '') ?: '#';
$whatsappRaw = trim($footerData['whatsapp'] ?? '');
$whatsappDigits = preg_replace('/\D+/', '', $whatsappRaw);
$whatsappUrl = $whatsappDigits ? ('https://wa.me/' . $whatsappDigits) : '#';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rare7 | E-commerce Premium</title>
    <meta name="description" content="Rare7 - Futebol com estetica premium.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Cinzel:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,300..700,0..1,-50..200">
    <link rel="stylesheet" href="css/loja.css">
</head>
<body>
    <section class="hero" id="topo">
        <div class="hero-bg-glow"></div>
        <div class="hero-content">
            <img src="<?php echo htmlspecialchars($heroLogoUrl); ?>" alt="Logo da marca" class="hero-brand-logo" loading="lazy" onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='<?php echo htmlspecialchars($fallbackHeroLogoUrl); ?>';}else{this.style.display='none';}">
            <?php if ($heroKicker !== ''): ?>
            <p class="hero-kicker"><?php echo htmlspecialchars($heroKicker); ?></p>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($heroTitle); ?></h1>
            <?php if ($heroSubtitle !== ''): ?>
            <p class="hero-subtitle"><?php echo htmlspecialchars($heroSubtitle); ?></p>
            <?php endif; ?>
            <?php if ($heroDescription !== ''): ?>
            <p class="hero-description"><?php echo htmlspecialchars($heroDescription); ?></p>
            <?php endif; ?>
            <?php if ($heroButtonText !== ''): ?>
            <a href="<?php echo htmlspecialchars($heroButtonLink !== '' ? $heroButtonLink : '#'); ?>" class="btn-gold"><?php echo htmlspecialchars($heroButtonText); ?></a>
            <?php endif; ?>
        </div>
        <a class="hero-scroll" href="#beneficios">↓ scroll</a>
    </section>

    <header class="floating-navbar" id="floatingNavbar">
        <div class="nav-wrap container-shell">
            <a href="index.php" class="nav-logo" aria-label="Rare - Inicio">
                <img src="<?php echo htmlspecialchars($heroLogoUrl); ?>" alt="Logo Rare" class="nav-logo-mark" loading="lazy" onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='<?php echo htmlspecialchars($fallbackHeroLogoUrl); ?>';}else{this.style.display='none';}">
                <span class="nav-logo-text">RARE7</span>
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="#vitrine">Camisas</a></li>
                    <li><a href="#vitrine">Retro</a></li>
                    <li><a href="#times">Clubes</a></li>
                    <li><a href="#vitrine">Selecoes</a></li>
                </ul>
            </nav>
            <div class="nav-icons">
                <form class="nav-search" id="navSearchForm" action="produtos.php" method="get" role="search">
                    <input type="search" id="navSearchInput" name="busca" placeholder="Buscar camisa..." aria-label="Buscar produtos">
                    <button type="button" class="nav-icon-link nav-search-toggle" id="navSearchToggle" aria-label="Abrir pesquisa">
                        <span class="material-symbols-sharp">search</span>
                    </button>
                </form>
                <?php if ($usuarioLogado): ?>
                <div class="user-dropdown">
                    <button class="user-dropdown-btn" onclick="toggleUserDropdown(event)" aria-label="Menu de usuário" aria-expanded="false">
                        <span class="material-symbols-sharp">person</span>
                    </button>
                    <div class="user-dropdown-menu">
                        <div class="user-greeting">Olá, <?php echo isset($nomeUsuario) ? htmlspecialchars($nomeUsuario) : 'Cliente'; ?></div>
                        <a href="pages/minha-conta.php">Minha conta</a>
                        <a href="pages/minha-conta.php?tab=pedidos">Meus pedidos</a>
                        <a href="pages/logout.php">Sair</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?php echo htmlspecialchars($profileLink); ?>" class="nav-icon-link" aria-label="Perfil">
                    <span class="material-symbols-sharp">person</span>
                </a>
                <?php endif; ?>
                <a href="pages/carrinho.php" class="nav-icon-link" aria-label="Carrinho" data-open-mini-cart><span class="material-symbols-sharp">shopping_bag</span></a>
            </div>
        </div>
    </header>

    <main>
        <section class="benefits section" id="beneficios">
            <div class="container-shell">
                <div class="section-head">
                    <h2><?php echo htmlspecialchars($benefitsTitle); ?></h2>
                    <p><?php echo htmlspecialchars($benefitsSubtitle); ?></p>
                </div>
                <div class="benefit-grid">
                    <?php foreach ($beneficiosRender as $beneficio): ?>
                    <article class="benefit-card reveal">
                        <span class="material-symbols-sharp benefit-icon"><?php echo htmlspecialchars($beneficio['icone'] ?? 'verified'); ?></span>
                        <h3><?php echo htmlspecialchars($beneficio['titulo'] ?? 'Beneficio'); ?></h3>
                        <p><?php echo htmlspecialchars($beneficio['descricao'] ?? 'Descricao do beneficio.'); ?></p>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="teams section" id="times">
            <div class="container-shell">
                <div class="section-head center">
                    <h2>Clubes Em Destaque</h2>
                </div>
                <div class="teams-marquee">
                    <div class="teams-track" id="teamsTrack">
                        <?php foreach ($clubesDestaque as $clube): ?>
                            <?php
                                $siglaClube = strtoupper(trim((string)($clube['sigla'] ?? 'CLB')));
                                $nomeClube = trim((string)($clube['nome'] ?? 'Clube'));
                                $imageUrl = resolveClubImageUrl((string)($clube['imagem_path'] ?? ''));
                            ?>
                            <button
                                type="button"
                                class="team-badge team-badge-button<?php echo $imageUrl !== '' ? ' has-image' : ''; ?>"
                                title="Ver camisas do <?php echo htmlspecialchars($nomeClube); ?>"
                                aria-label="Ver camisas do <?php echo htmlspecialchars($nomeClube); ?>"
                                data-team-name="<?php echo htmlspecialchars($nomeClube); ?>"
                                data-team-sigla="<?php echo htmlspecialchars($siglaClube); ?>"
                            >
                                <?php if ($imageUrl !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($nomeClube); ?>" loading="lazy" onerror="this.closest('.team-badge').classList.remove('has-image'); this.remove();">
                                <?php endif; ?>
                                <span class="team-badge-text"><?php echo htmlspecialchars($siglaClube); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="vitrine section" id="vitrine">
            <div class="container-shell">
                <div class="section-head">
                    <h2><?php echo htmlspecialchars($launchTitle); ?></h2>
                    <p><?php echo htmlspecialchars($launchSubtitle); ?></p>
                </div>

                <div class="vitrine-filters" id="vitrineFilters">
                    <button class="active" data-category="lancamentos"><span></span>Lancamentos</button>
                    <button data-category="clubes"><span></span>Clubes</button>
                    <button data-category="selecoes"><span></span>Selecoes</button>
                    <button data-category="retro"><span></span>Retro</button>
                    <button data-category="raras"><span></span>Raras</button>
                </div>

                <div class="vitrine-stage">
                    <button class="slider-arrow" id="vitrinePrev" aria-label="Anterior">←</button>
                    <div class="vitrine-cards" id="vitrineCards"></div>
                    <button class="slider-arrow" id="vitrineNext" aria-label="Proximo">→</button>
                </div>

                <div class="vitrine-counter" id="vitrineCounter">Mostrando 0–0 de 0</div>

                <?php if ($launchButtonText !== ''): ?>
                    <div class="section-cta">
                        <a href="<?php echo htmlspecialchars($launchButtonLink); ?>" class="btn-outline-gold"><?php echo htmlspecialchars($launchButtonText); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="showcase section" id="showcaseBanner">
            <div class="showcase-overlay"></div>
            <div class="showcase-content container-shell">
                <p class="showcase-kicker" id="showcaseKicker">Rare Collection</p>
                <h2 id="showcaseTitle">ESTILO GLOBAL</h2>
                <p id="showcaseSub">A assinatura visual do futebol de luxo.</p>
                <a id="showcaseCta" class="btn-outline-gold" href="#vitrine" style="display:none; margin-top: 0.5rem;">Explorar</a>
            </div>
            <div class="showcase-dots" id="showcaseDots"></div>
        </section>

        <section class="products section" id="catalogo">
            <div class="container-shell">
                <div class="section-head">
                    <h2><?php echo htmlspecialchars($productsTitle); ?></h2>
                    <p><?php echo htmlspecialchars($productsSubtitle); ?></p>
                </div>

                <div class="product-grid" id="catalogCards">
                    <?php foreach ($allProducts as $product): ?>
                    <article class="product-card reveal" data-product-id="<?php echo (int)$product['id']; ?>" data-product-url="produto.php?id=<?php echo (int)$product['id']; ?>">
                            <div class="product-image-wrap">
                                <?php if (!empty($product['imagem_principal'])): ?>
                                <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($product['imagem_principal']); ?>" alt="<?php echo htmlspecialchars($product['nome']); ?>" loading="lazy">
                                <?php else: ?>
                                <div class="product-image-fallback">RARE</div>
                                <?php endif; ?>
                                <span class="product-badge"><?php echo htmlspecialchars($product['categoria'] ?? 'Premium'); ?></span>
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['nome']); ?></h3>
                                <div class="price-line">
                                    <?php if (isOnSale($product)): ?>
                                    <span class="old-price"><?php echo formatPrice($product['preco']); ?></span>
                                    <span class="gold-price"><?php echo formatPrice($product['preco_promocional']); ?></span>
                                    <?php else: ?>
                                    <span class="gold-price"><?php echo formatPrice($product['preco']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php $catalogSizes = $productSizesMap[(int)$product['id']] ?? []; ?>
                                <?php if (!empty($catalogSizes)): ?>
                                <div class="vitrine-size-selector" data-size-group="<?php echo (int)$product['id']; ?>">
                                    <?php foreach ($catalogSizes as $size): ?>
                                        <button type="button" class="vitrine-size-chip" data-vitrine-size="<?php echo htmlspecialchars($size); ?>" data-product-id="<?php echo (int)$product['id']; ?>"><?php echo htmlspecialchars($size); ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="vitrine-actions">
                                    <button type="button" class="vitrine-more" data-catalog-action="add" data-product-id="<?php echo (int)$product['id']; ?>">Adicionar</button>
                                    <button type="button" class="vitrine-buy" data-catalog-action="buy" data-product-id="<?php echo (int)$product['id']; ?>">Comprar</button>
                                </div>
                            </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <div class="section-cta">
                    <a href="<?php echo htmlspecialchars($productsButtonLink); ?>" class="btn-outline-gold"><?php echo htmlspecialchars($productsButtonText); ?></a>
                </div>
            </div>
        </section>

        <section class="coupon section" id="cupom">
            <div class="container-shell">
                <div class="coupon-box reveal">
                    <p class="coupon-kicker">Oferta de boas-vindas</p>
                    <h2><?php echo htmlspecialchars($couponTitle); ?></h2>
                    <p><?php echo htmlspecialchars($couponSubtitle); ?></p>
                    <p class="coupon-code">Cupom: <strong><?php echo htmlspecialchars($couponCode); ?></strong></p>
                    <form class="coupon-form" id="couponForm">
                        <input type="text" name="nome" placeholder="Nome" required>
                        <input type="email" name="email" placeholder="Email" required>
                        <button type="submit">Quero meu cupom</button>
                    </form>
                    <p class="coupon-feedback" id="couponFeedback"></p>
                </div>
            </div>
        </section>

        <section class="testimonials section" id="depoimentos">
            <div class="container-shell">
                <div class="section-head">
                    <h2>Depoimentos</h2>
                    <p>Clientes que sentem a experiencia Rare.</p>
                </div>
                <div class="testimonials-grid">
                    <?php if (!empty($testimonials)): ?>
                        <?php foreach ($testimonials as $depoimento): ?>
                        <article class="testimonial-card reveal">
                            <p class="testimonial-text"><?php echo htmlspecialchars($depoimento['texto'] ?? 'Excelente experiencia.'); ?></p>
                            <p class="testimonial-name"><?php echo htmlspecialchars($depoimento['nome'] ?? 'Cliente Rare'); ?></p>
                            <p class="testimonial-stars"><?php echo str_repeat('★', max(1, min(5, (int)($depoimento['rating'] ?? 5)))); ?></p>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <article class="testimonial-card reveal"><p class="testimonial-text">"Entrega impecavel e acabamento premium."</p><p class="testimonial-name">Camila R.</p><p class="testimonial-stars">★★★★★</p></article>
                        <article class="testimonial-card reveal"><p class="testimonial-text">"A melhor experiencia de compra em camisas."</p><p class="testimonial-name">Lucas M.</p><p class="testimonial-stars">★★★★★</p></article>
                        <article class="testimonial-card reveal"><p class="testimonial-text">"Visual elegante e produtos exclusivos."</p><p class="testimonial-name">Renata S.</p><p class="testimonial-stars">★★★★★</p></article>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/mini-cart.php'; ?>

    <footer class="premium-footer" id="footer">
        <div class="container-shell footer-grid">
            <div>
                <h4>Marca</h4>
                <p><?php echo htmlspecialchars($footerData['marca_descricao'] ?? 'Rare7, futebol com estetica premium.'); ?></p>
                <div class="social-row">
                    <a href="<?php echo htmlspecialchars($instagramUrl); ?>" target="_blank" rel="noopener" aria-label="Instagram">IG</a>
                    <a href="<?php echo htmlspecialchars($tiktokUrl); ?>" target="_blank" rel="noopener" aria-label="TikTok">TK</a>
                    <a href="<?php echo htmlspecialchars($whatsappUrl); ?>" target="_blank" rel="noopener" aria-label="Whatsapp">WA</a>
                </div>
            </div>
            <div>
                <h4>Loja</h4>
                <ul>
                    <?php foreach ($footerProductLinks as $link): ?>
                    <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['titulo']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4>Atendimento</h4>
                <ul>
                    <?php foreach ($footerSupportLinks as $link): ?>
                    <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['titulo']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="pages/login.php"><?php echo $usuarioLogado ? 'Minha conta' : 'Entrar'; ?></a></li>
                </ul>
            </div>
            <div>
                <h4>Newsletter</h4>
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" placeholder="Seu melhor email" required>
                    <button type="submit">Assinar</button>
                </form>
                <small>Frete gratis acima de <?php echo formatPrice($freteGratisValor); ?></small>
            </div>
        </div>
        <div class="footer-bottom container-shell">
            <span><?php echo htmlspecialchars($footerData['copyright_texto'] ?? '© 2026 Rare7. Todos os direitos reservados.'); ?></span>
        </div>
    </footer>

    <script>
    window.__RARE_PRODUCTS__ = <?php echo json_encode($vitrineProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.__RARE_LAUNCH_PRODUCTS__ = <?php echo json_encode($vitrineLaunchProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.__RARE_FEATURED_IDS__ = <?php echo json_encode($featuredIds, JSON_UNESCAPED_UNICODE); ?>;
    window.__RARE_BANNER_SLIDES__ = <?php echo json_encode($bannerSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    window.__RARE_BANNER_INTERVAL__ = <?php echo json_encode($bannerInterval); ?>;
    </script>

    <script>
    (function () {
      const navbar = document.getElementById('floatingNavbar');
      const revealItems = document.querySelectorAll('.reveal');
            const navSearchForm = document.getElementById('navSearchForm');
            const navSearchInput = document.getElementById('navSearchInput');
            const navSearchToggle = document.getElementById('navSearchToggle');

            if (navSearchForm && navSearchInput && navSearchToggle) {
                const closeSearch = () => {
                    navSearchForm.classList.remove('active');
                    navSearchToggle.setAttribute('aria-label', 'Abrir pesquisa');
                };

                navSearchToggle.addEventListener('click', () => {
                    if (!navSearchForm.classList.contains('active')) {
                        navSearchForm.classList.add('active');
                        navSearchToggle.setAttribute('aria-label', 'Pesquisar agora');
                        requestAnimationFrame(() => navSearchInput.focus());
                        return;
                    }

                    if (navSearchInput.value.trim() !== '') {
                        navSearchForm.submit();
                        return;
                    }

                    closeSearch();
                });

                document.addEventListener('click', (event) => {
                    if (!navSearchForm.contains(event.target)) {
                        closeSearch();
                    }
                });

                navSearchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeSearch();
                        navSearchInput.blur();
                    }
                });
            }

      function toggleNavbar() {
        if (window.scrollY > window.innerHeight * 0.7) {
          navbar.classList.add('visible');
        } else {
          navbar.classList.remove('visible');
        }
      }

      window.addEventListener('scroll', toggleNavbar, { passive: true });
      toggleNavbar();

      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.2 });

      revealItems.forEach((item) => observer.observe(item));

            const track = document.getElementById('teamsTrack');
      if (track) {
        track.innerHTML += track.innerHTML;
      }

      const products = Array.isArray(window.__RARE_PRODUCTS__) ? window.__RARE_PRODUCTS__ : [];
    const launchProducts = Array.isArray(window.__RARE_LAUNCH_PRODUCTS__) ? window.__RARE_LAUNCH_PRODUCTS__ : [];
      const featuredIds = Array.isArray(window.__RARE_FEATURED_IDS__) ? window.__RARE_FEATURED_IDS__ : [];
      const cardsRoot = document.getElementById('vitrineCards');
    const catalogRoot = document.getElementById('catalogCards');
      const counter = document.getElementById('vitrineCounter');
      const filters = document.getElementById('vitrineFilters');
      const btnPrev = document.getElementById('vitrinePrev');
      const btnNext = document.getElementById('vitrineNext');
    const selectedSizes = {};

            let activeCategory = 'lancamentos';
      let page = 0;
      const perPage = 4;

      function buildCategoryMap() {
        const byKeyword = (keywords) => products.filter((p) => {
          const source = `${p.name} ${p.category}`.toLowerCase();
          return keywords.some((k) => source.includes(k));
        });

                const launches = launchProducts.length
                    ? launchProducts
                    : products.filter((p) => featuredIds.includes(p.id) || p.is_launch);

        return {
                    lancamentos: launches,
          clubes: byKeyword(['clube', 'club', 'fc', 'city', 'madrid', 'barca', 'inter']),
          selecoes: byKeyword(['selec', 'selection', 'brasil', 'argentina', 'franca']),
          retro: byKeyword(['retro', 'retrô', 'classic', 'vintage']),
          raras: byKeyword(['rara', 'exclusive', 'limited'])
        };
      }

      function ensureFallback(list) {
        if (list && list.length) return list;
        return products.slice(0, 8);
      }

      const categoryMap = buildCategoryMap();

            function getCurrentList() {
                return ensureFallback(categoryMap[activeCategory]);
            }

            function showCartNotice(message) {
                const text = String(message || 'Produto adicionado ao carrinho.');
                const el = document.createElement('div');
                el.className = 'rare-cart-toast';
                el.textContent = text;
                document.body.appendChild(el);

                requestAnimationFrame(() => {
                    el.classList.add('is-visible');
                });

                setTimeout(() => {
                    el.classList.remove('is-visible');
                    setTimeout(() => el.remove(), 220);
                }, 1700);
            }

            function getUnitPrice(product) {
                const sale = Number(product.sale_price || 0);
                const regular = Number(product.price || 0);
                return sale > 0 && sale < regular ? sale : regular;
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

            function addProductToCart(product, size) {
                const cart = getCart();
                const productId = Number(product.id || 0);
                const unitPrice = getUnitPrice(product);
                const variantText = `Tamanho: ${size}`;
                const variantKey = `size::${String(size).toUpperCase()}`;

                const existing = cart.find((item) => String(item.id) === String(productId) && String(item.variantKey || '') === variantKey);

                if (existing) {
                    existing.qty = Number(existing.qty || 0) + 1;
                } else {
                    cart.push({
                        id: productId,
                        name: product.name || 'Produto',
                        price: unitPrice,
                        qty: 1,
                        image: product.image || '',
                        variacao_texto: variantText,
                        variant: variantText,
                        variantKey: variantKey,
                        addedAt: new Date().toISOString()
                    });
                }

                setCart(cart);
            }

            function runVitrineAction(productId, action) {
                const list = getCurrentList();
                const product = list.find((item) => Number(item.id) === Number(productId));
                if (!product) return;

                const selectedSize = selectedSizes[productId] || '';
                const hasSizes = Array.isArray(product.sizes) && product.sizes.length > 0;
                if (hasSizes && !selectedSize) {
                    window.location.href = `produto.php?id=${Number(productId)}`;
                    return;
                }

                addProductToCart(product, selectedSize);

                if (action === 'add') {
                    showCartNotice(`Adicionado: ${product.name} (${selectedSize})`);
                    if (window.RareMiniCart) {
                        window.RareMiniCart.open();
                    }
                }

                if (action === 'buy') {
                    window.location.href = 'pages/carrinho.php';
                }
            }

            function runCatalogAction(productId, action) {
                const catalogProduct = products.find((item) => Number(item.id) === Number(productId));
                if (!catalogProduct) return;

                const selectedSize = selectedSizes[productId] || '';
                const hasSizes = Array.isArray(catalogProduct.sizes) && catalogProduct.sizes.length > 0;
                if (hasSizes && !selectedSize) {
                    window.location.href = `produto.php?id=${Number(productId)}`;
                    return;
                }

                addProductToCart(catalogProduct, selectedSize);

                if (action === 'add') {
                    showCartNotice(`Adicionado: ${catalogProduct.name} (${selectedSize})`);
                    if (window.RareMiniCart) {
                        window.RareMiniCart.open();
                    }
                }

                if (action === 'buy') {
                    window.location.href = 'pages/carrinho.php';
                }
            }

      function renderVitrine() {
        const list = getCurrentList();
        const start = page * perPage;
                const safeStart = Math.min(start, Math.max(0, list.length - 1));
        const current = list.slice(safeStart, safeStart + perPage);

        cardsRoot.innerHTML = current.map((p) => {
          const hasSale = p.sale_price > 0 && p.sale_price < p.price;
          const priceHtml = hasSale
            ? `<span class="old-price">${formatPrice(p.price)}</span><span class="gold-price">${formatPrice(p.sale_price)}</span>`
            : `<span class="gold-price">${formatPrice(p.price)}</span>`;

          const img = p.image
            ? `<img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" loading="lazy">`
            : '<div class="product-image-fallback">RARE</div>';

          return `
                        <article class="vitrine-card" data-product-id="${Number(p.id || 0)}" data-product-url="produto.php?id=${Number(p.id || 0)}">
                                <div class="vitrine-image">${img}<span class="product-badge">${escapeHtml(p.category || 'Rare')}</span></div>
                                <div class="vitrine-body">
                                    <h3>${escapeHtml(p.name)}</h3>
                                    <p>${escapeHtml((p.description || '').trim() || 'Produto premium da Rare.')}</p>
                                    <div class="price-line">${priceHtml}</div>
                                    ${Array.isArray(p.sizes) && p.sizes.length ? `<div class="vitrine-size-selector" data-size-group="${Number(p.id || 0)}">${p.sizes.map((size) => `<button type="button" class="vitrine-size-chip" data-vitrine-size="${escapeHtml(size)}" data-product-id="${Number(p.id || 0)}">${escapeHtml(size)}</button>`).join('')}</div>` : ''}
                                    <div class="vitrine-actions">
                                        <button type="button" class="vitrine-more" data-vitrine-action="add" data-product-id="${Number(p.id || 0)}">Adicionar</button>
                                        <button type="button" class="vitrine-buy" data-vitrine-action="buy" data-product-id="${Number(p.id || 0)}">Comprar</button>
                                    </div>
                                </div>
            </article>`;
        }).join('');

        const x = list.length ? safeStart + 1 : 0;
        const y = Math.min(safeStart + current.length, list.length);
        counter.textContent = `Mostrando ${x}–${y} de ${list.length}`;
      }

      function formatPrice(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
      }

      function escapeHtml(text) {
        return String(text || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

            cardsRoot.addEventListener('click', (event) => {
                const sizeBtn = event.target.closest('[data-vitrine-size]');
                if (sizeBtn) {
                    event.preventDefault();
                    event.stopPropagation();

                    const productId = Number(sizeBtn.dataset.productId || 0);
                    const size = String(sizeBtn.dataset.vitrineSize || '').toUpperCase();
                    if (!productId || !size) return;

                    selectedSizes[productId] = size;

                    const group = cardsRoot.querySelector(`[data-size-group="${productId}"]`);
                    if (group) {
                        group.querySelectorAll('.vitrine-size-chip').forEach((chip) => {
                            chip.classList.toggle('is-active', chip === sizeBtn);
                        });
                    }
                    return;
                }

                const actionBtn = event.target.closest('[data-vitrine-action]');
                if (actionBtn) {
                    event.preventDefault();
                    event.stopPropagation();

                    const productId = Number(actionBtn.dataset.productId || 0);
                    const action = String(actionBtn.dataset.vitrineAction || '');
                    if (!productId) return;

                    runVitrineAction(productId, action);
                    return;
                }

                const card = event.target.closest('.vitrine-card');
                if (!card) return;

                const url = card.dataset.productUrl || '';
                if (url) {
                    window.location.href = url;
                }
            });

            if (catalogRoot) {
                catalogRoot.addEventListener('click', (event) => {
                    const sizeBtn = event.target.closest('[data-vitrine-size]');
                    if (sizeBtn) {
                        event.preventDefault();
                        event.stopPropagation();

                        const productId = Number(sizeBtn.dataset.productId || 0);
                        const size = String(sizeBtn.dataset.vitrineSize || '').toUpperCase();
                        if (!productId || !size) return;

                        selectedSizes[productId] = size;

                        const group = catalogRoot.querySelector(`[data-size-group="${productId}"]`);
                        if (group) {
                            group.querySelectorAll('.vitrine-size-chip').forEach((chip) => {
                                chip.classList.toggle('is-active', chip === sizeBtn);
                            });
                        }
                        return;
                    }

                    const actionBtn = event.target.closest('[data-catalog-action]');
                    if (actionBtn) {
                        event.preventDefault();
                        event.stopPropagation();

                        const productId = Number(actionBtn.dataset.productId || 0);
                        const action = String(actionBtn.dataset.catalogAction || '');
                        if (!productId) return;

                        runCatalogAction(productId, action);
                        return;
                    }

                    const card = event.target.closest('.product-card');
                    if (!card) return;

                    const url = card.dataset.productUrl || '';
                    if (url) {
                        window.location.href = url;
                    }
                });
            }

            if (track) {
                track.addEventListener('click', (event) => {
                    const badge = event.target.closest('.team-badge[data-team-name][data-team-sigla]');
                    if (!badge) return;

                    event.preventDefault();

                    const teamName = badge.getAttribute('data-team-name') || '';
                    const menuValue = teamName.trim() || (badge.getAttribute('data-team-sigla') || '').trim();
                    if (!menuValue) return;

                    window.location.href = `produtos.php?menu=${encodeURIComponent(menuValue.toLowerCase())}`;
                });
            }

      filters.addEventListener('click', (event) => {
        const btn = event.target.closest('button[data-category]');
        if (!btn) return;
        activeCategory = btn.dataset.category;
        page = 0;
        filters.querySelectorAll('button').forEach((item) => item.classList.remove('active'));
        btn.classList.add('active');
        renderVitrine();
      });

      btnPrev.addEventListener('click', () => {
        const list = getCurrentList();
        const maxPage = Math.max(0, Math.ceil(list.length / perPage) - 1);
        page = page <= 0 ? maxPage : page - 1;
        renderVitrine();
      });

      btnNext.addEventListener('click', () => {
        const list = getCurrentList();
        const maxPage = Math.max(0, Math.ceil(list.length / perPage) - 1);
        page = page >= maxPage ? 0 : page + 1;
        renderVitrine();
      });

      renderVitrine();

      const bannerSection = document.getElementById('showcaseBanner');
    const kicker = document.getElementById('showcaseKicker');
      const title = document.getElementById('showcaseTitle');
      const subtitle = document.getElementById('showcaseSub');
            const cta = document.getElementById('showcaseCta');
      const dotsRoot = document.getElementById('showcaseDots');

            const bannerSlides = Array.isArray(window.__RARE_BANNER_SLIDES__) && window.__RARE_BANNER_SLIDES__.length
                ? window.__RARE_BANNER_SLIDES__
                : [
                        { title: 'ESTILO GLOBAL', subtitle: 'A assinatura visual do futebol de luxo.', image: '', button_text: 'EXPLORAR', button_link: '#vitrine' },
                        { title: 'NOVA COLECAO 2026', subtitle: 'Recortes limpos, tecnologia e identidade Rare.', image: '', button_text: 'EXPLORAR', button_link: '#vitrine' },
                        { title: 'CAMISAS EXCLUSIVAS', subtitle: 'Series limitadas para quem veste autenticidade.', image: '', button_text: 'EXPLORAR', button_link: '#vitrine' }
                    ];
      let bannerIndex = 0;

      function paintBanner(index) {
                const slide = bannerSlides[index % bannerSlides.length];
        title.classList.remove('fade-in');
        subtitle.classList.remove('fade-in');
        void title.offsetWidth;

            const titleText = String(slide.title || '').trim();
            const subtitleText = String(slide.subtitle || '').trim();

            title.textContent = titleText;
            subtitle.textContent = subtitleText;

            title.style.display = titleText ? '' : 'none';
            subtitle.style.display = subtitleText ? '' : 'none';
            if (kicker) {
                kicker.style.display = (titleText || subtitleText) ? '' : 'none';
            }

        title.classList.add('fade-in');
        subtitle.classList.add('fade-in');

                if (cta) {
                    const btnText = String(slide.button_text || '').trim();
                    if (btnText) {
                        cta.textContent = btnText;
                        cta.href = String(slide.button_link || '#vitrine').trim() || '#vitrine';
                        cta.style.display = 'inline-flex';
                    } else {
                        cta.style.display = 'none';
                    }
                }

                if (slide.image) {
                    bannerSection.style.backgroundImage = `linear-gradient(120deg, rgba(14,14,14,.7), rgba(15,28,46,.65)), url('${slide.image}')`;
        }

        dotsRoot.querySelectorAll('button').forEach((dot, dotIndex) => {
          dot.classList.toggle('active', dotIndex === index);
        });
      }

            bannerSlides.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.addEventListener('click', () => {
          bannerIndex = index;
          paintBanner(bannerIndex);
        });
        dotsRoot.appendChild(dot);
      });

      paintBanner(0);
            const bannerIntervalMs = Math.max(3000, Math.min(30000, Number(window.__RARE_BANNER_INTERVAL__ || 4) * 1000));
            setInterval(() => {
                bannerIndex = (bannerIndex + 1) % bannerSlides.length;
        paintBanner(bannerIndex);
            }, bannerIntervalMs);

      const couponForm = document.getElementById('couponForm');
      const couponFeedback = document.getElementById('couponFeedback');
      couponForm.addEventListener('submit', (event) => {
        event.preventDefault();
        couponFeedback.textContent = 'Cupom RARE10 liberado. Verifique seu email em instantes.';
      });

      document.getElementById('newsletterForm').addEventListener('submit', (event) => {
        event.preventDefault();
      });

      // ===== DROPDOWN DO USUÁRIO =====
      window.toggleUserDropdown = function(event) {
        if (event) {
          event.stopPropagation();
          event.preventDefault();
        }

        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown) {
          dropdown.classList.toggle('active');
          const btn = dropdown.querySelector('.user-dropdown-btn');
          if (btn) {
            btn.setAttribute('aria-expanded', dropdown.classList.contains('active'));
          }
        }
      }

      // Fechar dropdown ao clicar fora
      document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
          dropdown.classList.remove('active');
          const btn = dropdown.querySelector('.user-dropdown-btn');
          if (btn) {
            btn.setAttribute('aria-expanded', 'false');
          }
        }
      }, true);
    })();
    </script>
</body>
</html>

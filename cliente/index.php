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

$ligasDestaque = [];

$_ligasTabelaExiste = false;
$_tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'cms_home_leagues'");
if ($_tableCheck && mysqli_num_rows($_tableCheck) > 0) {
    $_ligasTabelaExiste = true;
}

if ($_ligasTabelaExiste) {
    $_ligasQr = mysqli_query($conn, "SELECT nome, slug, sigla, classe, logo_path FROM cms_home_leagues WHERE ativo = 1 ORDER BY ordem ASC, id ASC");
    if ($_ligasQr) {
        while ($_lr = mysqli_fetch_assoc($_ligasQr)) {
            $ligasDestaque[] = [
                'nome' => $_lr['nome'],
                'slug' => $_lr['slug'],
                'sigla' => $_lr['sigla'],
                'classe' => $_lr['classe'],
                'logo' => $_lr['logo_path'] ?? ''
            ];
        }
    }
}
if (empty($ligasDestaque)) {
    $ligasDestaque = [
        ['nome' => 'Premier League',   'slug' => 'premier-league',   'sigla' => 'PL',  'logo' => '', 'classe' => 'league-premier'],
        ['nome' => 'La Liga',          'slug' => 'la-liga',          'sigla' => 'LL',  'logo' => '', 'classe' => 'league-laliga'],
        ['nome' => 'Brasileirão',      'slug' => 'brasileirao',      'sigla' => 'BR',  'logo' => '', 'classe' => 'league-brasileirao'],
        ['nome' => 'Serie A',          'slug' => 'serie-a',          'sigla' => 'SA',  'logo' => '', 'classe' => 'league-seriea'],
        ['nome' => 'Bundesliga',       'slug' => 'bundesliga',       'sigla' => 'BL',  'logo' => '', 'classe' => 'league-bundesliga'],
        ['nome' => 'Champions League', 'slug' => 'champions-league', 'sigla' => 'UCL', 'logo' => '', 'classe' => 'league-champions'],
    ];
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

function resolveLeagueLogoUrl(string $rawPath): string {
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

    // Se vier só o nome do arquivo (ex: premier.png), assume pasta image/.
    if (strpos($normalized, '/') === false) {
        $normalized = 'image/' . $normalized;
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

$productSizeOptionsMap = [];
if (!empty($productIdsForSizes)) {
    $ids = array_keys($productIdsForSizes);
    $inClause = implode(',', array_map('intval', $ids));
    $sizesSql = "
        SELECT
            pv.produto_id,
            pv.id,
            pv.valor,
            pv.estoque,
            pv.preco,
            pv.preco_promocional,
            p.preco AS produto_preco,
            p.preco_promocional AS produto_preco_promocional
        FROM produto_variacoes pv
        INNER JOIN produtos p ON p.id = pv.produto_id
        WHERE pv.ativo = 1
          AND LOWER(TRIM(pv.tipo)) = 'tamanho'
          AND pv.produto_id IN ($inClause)
        ORDER BY pv.produto_id ASC, pv.id ASC
    ";
    $sizesResult = mysqli_query($conn, $sizesSql);
    if ($sizesResult) {
        while ($sizeRow = mysqli_fetch_assoc($sizesResult)) {
            $pid = (int)($sizeRow['produto_id'] ?? 0);
            $value = trim((string)($sizeRow['valor'] ?? ''));
            if ($pid <= 0 || $value === '') {
                continue;
            }
            if (!isset($productSizeOptionsMap[$pid])) {
                $productSizeOptionsMap[$pid] = [];
            }

            $lower = mb_strtolower($value);
            $exists = false;
            foreach ($productSizeOptionsMap[$pid] as $existing) {
                if (mb_strtolower((string)($existing['label'] ?? '')) === $lower) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $precoPai = (float)($sizeRow['produto_preco'] ?? 0);
                $precoPromoPai = (float)($sizeRow['produto_preco_promocional'] ?? 0);
                $usaPrecoPai = !isset($sizeRow['preco']) || $sizeRow['preco'] === null || (float)$sizeRow['preco'] <= 0;
                $precoVariacao = $usaPrecoPai ? $precoPai : (float)$sizeRow['preco'];
                $precoPromoVariacao = null;

                if (isset($sizeRow['preco_promocional']) && $sizeRow['preco_promocional'] !== null && (float)$sizeRow['preco_promocional'] > 0) {
                    $precoPromoVariacao = (float)$sizeRow['preco_promocional'];
                } elseif ($usaPrecoPai && $precoPromoPai > 0 && $precoPromoPai < $precoPai) {
                    $precoPromoVariacao = $precoPromoPai;
                }

                $productSizeOptionsMap[$pid][] = [
                    'label' => $value,
                    'variation_id' => (int)($sizeRow['id'] ?? 0),
                    'price' => $precoVariacao,
                    'sale_price' => $precoPromoVariacao,
                    'stock' => max(0, (int)($sizeRow['estoque'] ?? 0)),
                ];
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
    global $productSizeOptionsMap;
    $sizeOptions = $productSizeOptionsMap[$productId] ?? [];
    $sizes = array_values(array_map(static function ($option) {
        return (string)($option['label'] ?? '');
    }, $sizeOptions));
    $productTags = getProductTags($product);
    $tagLabels = array_values(array_map(static function ($tag) {
        return (string) ($tag['label'] ?? '');
    }, $productTags));

    return [
        'id' => (int)($product['id'] ?? 0),
        'name' => $product['nome'] ?? 'Produto',
        'description' => mb_substr((string)($product['descricao'] ?? ''), 0, 90),
        'category' => $product['categoria'] ?? 'Raras',
        'price' => (float)($product['preco'] ?? 0),
        'sale_price' => (float)($product['preco_promocional'] ?? 0),
        'image' => $img,
        'is_launch' => ($product['is_lancamento'] ?? '') === 'yes',
        'tags' => $tagLabels,
        'size_options' => $sizeOptions,
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

$productsTitle = trim($homeSettings['products_title'] ?? '') ?: 'Todos os Produtos';
$productsSubtitle = array_key_exists('products_subtitle', $homeSettings)
    ? trim((string)$homeSettings['products_subtitle'])
    : 'Toda a nossa coleção premium em um só lugar';
$productsButtonText = trim($homeSettings['products_button_text'] ?? '') ?: 'Ver todos os produtos';
$productsButtonLink = trim($homeSettings['products_button_link'] ?? '') ?: 'produtos.php';

$bannerInterval = (int)($homeSettings['banner_interval'] ?? 4);
if ($bannerInterval < 3 || $bannerInterval > 30) {
    $bannerInterval = 4;
}

$activePromo = !empty($promocoes) ? $promocoes[0] : null;
$couponTitle = $activePromo['titulo'] ?? 'Ganhe 10% OFF no seu primeiro pedido';
$couponSubtitle = $activePromo['subtitulo'] ?? 'Cadastre-se para receber sua vantagem exclusiva no email.';

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
    <link rel="icon" type="image/png" href="../image/logo_png.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Cinzel:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,300..700,0..1,-50..200">
    <link rel="stylesheet" href="css/loja.css?v=<?php echo filemtime(__DIR__.'/css/loja.css'); ?>">
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
                    <li><a href="produtos.php">Todos Produtos</a></li>
                    <li><a href="produtos.php?tag=retro">Retro</a></li>
                    <li><a href="produtos.php?categoria=Times">Times</a></li>
                    <li><a href="produtos.php?categoria=Sele%C3%A7%C3%B5es">Seleções</a></li>
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

        <!-- LIGAS EM DESTAQUE -->
        <section class="rare-leagues-section section" id="ligas-destaque">
            <div class="container-shell">
                <div class="section-head center">
                    <h2>Ligas em Destaque</h2>
                    <p>Explore camisas oficiais das maiores competições do mundo</p>
                </div>
                <div class="rl-viewport-wrap">
                    <button type="button" class="rl-arrow rl-arrow-prev" id="rareLeaguePrev" aria-label="Ligas anteriores">&#10094;</button>
                    <div class="rl-viewport" id="rareLeaguesViewport">
                        <div class="rl-track" id="rareLeaguesTrack">
                            <?php foreach ($ligasDestaque as $liga):
                                $lp = trim((string)($liga['logo'] ?? ''));
                                $ligaLogoUrl = resolveLeagueLogoUrl($lp);
                            ?>
                                     <a href="produtos.php?liga=<?php echo urlencode($liga['slug']); ?>"
                               class="rl-card <?php echo htmlspecialchars($liga['classe']); ?>"
                               title="Ver camisas da <?php echo htmlspecialchars($liga['nome']); ?>">
                                <div class="rl-card-inner">
                                    <?php if ($ligaLogoUrl !== ''): ?>
                                        <img class="rl-logo" src="<?php echo htmlspecialchars($ligaLogoUrl); ?>"
                                             alt="<?php echo htmlspecialchars($liga['nome']); ?>" loading="lazy"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        <span class="rl-sigla" style="display:none"><?php echo htmlspecialchars($liga['sigla']); ?></span>
                                    <?php else: ?>
                                        <span class="rl-sigla"><?php echo htmlspecialchars($liga['sigla']); ?></span>
                                    <?php endif; ?>
                                    <span class="rl-nome"><?php echo htmlspecialchars($liga['nome']); ?></span>
                                    <span class="rl-cta">Ver camisas ›</span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="button" class="rl-arrow rl-arrow-next" id="rareLeagueNext" aria-label="Próximas ligas">&#10095;</button>
                </div>
            </div>
        </section>
        <!-- / LIGAS EM DESTAQUE -->

        <section class="teams section" id="times">
            <div class="container-shell">
                <div class="section-head center">
                    <h2>Clubes Em Destaque</h2>
                </div>
                <div class="teams-marquee-shell">
                    <button type="button" class="teams-nav-arrow teams-nav-prev" id="teamsPrev" aria-label="Voltar clubes">&#10094;</button>
                    <div class="teams-marquee" id="teamsMarquee">
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
                    <button type="button" class="teams-nav-arrow teams-nav-next" id="teamsNext" aria-label="Avancar clubes">&#10095;</button>
                </div>
            </div>
        </section>

        <section class="vitrine section" id="vitrine">
            <div class="container-shell">
                <div class="section-head">
                    <h2><?php echo htmlspecialchars($launchTitle); ?></h2>
                </div>

                <div class="vitrine-stage">
                    <button class="slider-arrow" id="vitrinePrev" aria-label="Anterior"><span class="slider-arrow-icon" aria-hidden="true">&#10094;</span></button>
                    <div class="vitrine-cards" id="vitrineCards"></div>
                    <button class="slider-arrow" id="vitrineNext" aria-label="Proximo"><span class="slider-arrow-icon" aria-hidden="true">&#10095;</span></button>
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
            <img class="showcase-bg-image" id="showcaseImage" src="" alt="Banner principal" loading="eager" decoding="async">
            <div class="showcase-overlay"></div>
            <button type="button" class="showcase-arrow showcase-arrow-prev" id="showcasePrev" aria-label="Banner anterior">&#10094;</button>
            <button type="button" class="showcase-arrow showcase-arrow-next" id="showcaseNext" aria-label="Próximo banner">&#10095;</button>
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
                    <?php
                        $cardTags = getProductTags($product);
                        $primaryTag = !empty($cardTags) ? (string) ($cardTags[0]['label'] ?? '') : '';
                        $catalogDescription = trim((string) ($product['descricao'] ?? ''));
                        $catalogExcerpt = $catalogDescription !== '' ? mb_substr($catalogDescription, 0, 110) . (mb_strlen($catalogDescription) > 110 ? '...' : '') : 'Produto premium da Rare.';
                    ?>
                    <article class="product-card reveal" data-product-id="<?php echo (int)$product['id']; ?>" data-product-url="produto.php?id=<?php echo (int)$product['id']; ?>">
                            <div class="product-image-wrap">
                                <?php if (!empty($product['imagem_principal'])): ?>
                                <img src="../admin/assets/images/produtos/<?php echo htmlspecialchars($product['imagem_principal']); ?>" alt="<?php echo htmlspecialchars($product['nome']); ?>" loading="lazy">
                                <?php else: ?>
                                <div class="product-image-fallback">RARE</div>
                                <?php endif; ?>
                                <?php if ($primaryTag !== ''): ?>
                                <span class="product-badge"><?php echo htmlspecialchars($primaryTag); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['nome']); ?></h3>
                                <p><?php echo htmlspecialchars($catalogExcerpt); ?></p>
                                <?php $catalogSizeOptions = $productSizeOptionsMap[(int)$product['id']] ?? []; ?>
                                <?php if (!empty($catalogSizeOptions)): ?>
                                <div class="vitrine-size-selector" data-size-group="<?php echo (int)$product['id']; ?>">
                                    <?php foreach ($catalogSizeOptions as $sizeOption): ?>
                                        <?php $sizeStock = (int)($sizeOption['stock'] ?? 0); ?>
                                        <button type="button" class="vitrine-size-chip<?php echo $sizeStock <= 0 ? ' is-sold-out' : ''; ?>" data-vitrine-size="<?php echo htmlspecialchars((string)$sizeOption['label']); ?>" data-product-id="<?php echo (int)$product['id']; ?>" data-variation-id="<?php echo (int)($sizeOption['variation_id'] ?? 0); ?>" data-price="<?php echo (float)($sizeOption['price'] ?? 0); ?>" data-sale-price="<?php echo isset($sizeOption['sale_price']) && $sizeOption['sale_price'] !== null ? (float)$sizeOption['sale_price'] : ''; ?>" data-stock="<?php echo $sizeStock; ?>" <?php echo $sizeStock <= 0 ? 'disabled aria-disabled="true" aria-label="Tamanho indisponível"' : ''; ?>><?php echo htmlspecialchars((string)$sizeOption['label']); ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="price-line">
                                    <?php if (isOnSale($product)): ?>
                                    <span class="old-price"><?php echo formatPrice($product['preco']); ?></span>
                                    <span class="gold-price"><?php echo formatPrice($product['preco_promocional']); ?></span>
                                    <?php else: ?>
                                    <span class="gold-price"><?php echo formatPrice($product['preco']); ?></span>
                                    <?php endif; ?>
                                </div>
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
                    <p class="coupon-subtitle">Se cadastre para receber seu cupom de primeira compra no email.</p>
                    <p class="coupon-policy">Liberacao exclusiva para novos cadastros.</p>
                    <form class="coupon-form" id="couponForm">
                        <input type="text" name="nome" placeholder="Seu nome" required>
                        <input type="email" name="email" placeholder="Seu melhor email" required>
                        <button type="submit">Cadastrar e receber cupom</button>
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
            const teamsMarquee = document.getElementById('teamsMarquee');
            const teamsPrev = document.getElementById('teamsPrev');
            const teamsNext = document.getElementById('teamsNext');

            if (track && teamsMarquee) {
                if (!track.dataset.cloned) {
                    track.innerHTML += track.innerHTML;
                    track.dataset.cloned = '1';
                }

                const baseSpeed = 0.42;
                const holdSpeed = 2.2;
                let holdDirection = 0;
                let impulse = 0;
                let offset = 0;

                const getLoopPoint = () => Math.max(1, track.scrollWidth / 2);

                const normalizeOffset = () => {
                    const loopPoint = getLoopPoint();
                    while (offset >= loopPoint) offset -= loopPoint;
                    while (offset < 0) offset += loopPoint;
                };

                const paintOffset = () => {
                    track.style.transform = `translate3d(${-offset}px, 0, 0)`;
                };

                const step = () => {
                    offset += baseSpeed + (holdDirection * holdSpeed) + impulse;
                    normalizeOffset();
                    paintOffset();

                    impulse *= 0.9;
                    if (Math.abs(impulse) < 0.02) {
                        impulse = 0;
                    }

                    requestAnimationFrame(step);
                };

                const nudge = (direction) => {
                    impulse += direction * 18;
                };

                const startBoost = (direction) => {
                    holdDirection = direction;
                };

                const stopBoost = () => {
                    holdDirection = 0;
                };

                requestAnimationFrame(step);

                if (teamsPrev) {
                    teamsPrev.addEventListener('click', () => nudge(-1));
                    teamsPrev.addEventListener('mousedown', () => startBoost(-1));
                    teamsPrev.addEventListener('mouseup', stopBoost);
                    teamsPrev.addEventListener('mouseleave', stopBoost);
                    teamsPrev.addEventListener('touchstart', () => startBoost(-1), { passive: true });
                    teamsPrev.addEventListener('touchend', stopBoost);
                    teamsPrev.addEventListener('touchcancel', stopBoost);
                }

                if (teamsNext) {
                    teamsNext.addEventListener('click', () => nudge(1));
                    teamsNext.addEventListener('mousedown', () => startBoost(1));
                    teamsNext.addEventListener('mouseup', stopBoost);
                    teamsNext.addEventListener('mouseleave', stopBoost);
                    teamsNext.addEventListener('touchstart', () => startBoost(1), { passive: true });
                    teamsNext.addEventListener('touchend', stopBoost);
                    teamsNext.addEventListener('touchcancel', stopBoost);
                }

                window.addEventListener('mouseup', stopBoost);
                window.addEventListener('blur', stopBoost);
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
        let vitrineTransitionLock = false;
        const vitrineViewport = (() => {
          if (!cardsRoot || !cardsRoot.parentNode) return null;
          const viewport = document.createElement('div');
          viewport.className = 'vitrine-cards-viewport';
          cardsRoot.parentNode.insertBefore(viewport, cardsRoot);
          viewport.appendChild(cardsRoot);
          return viewport;
        })();

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

            function getVariantForSize(product, size) {
                const wanted = String(size || '').trim().toUpperCase();
                if (!wanted || !Array.isArray(product?.size_options)) {
                    return null;
                }

                return product.size_options.find((option) => String(option?.label || '').trim().toUpperCase() === wanted) || null;
            }

            function getPriceState(product, size) {
                const variant = getVariantForSize(product, size);
                const regular = variant && Number(variant.price || 0) > 0
                    ? Number(variant.price || 0)
                    : Number(product.price || 0);
                const sale = variant
                    ? (Number(variant.sale_price || 0) > 0 ? Number(variant.sale_price || 0) : 0)
                    : Number(product.sale_price || 0);

                if (sale > 0 && sale < regular) {
                    return { regular, current: sale, onSale: true, variant };
                }

                return { regular, current: regular, onSale: false, variant };
            }

            function getUnitPrice(product, size) {
                return Number(getPriceState(product, size).current || 0);
            }

            function updateCardPrice(card, product, size) {
                const priceLine = card?.querySelector('.price-line');
                if (!priceLine || !product) {
                    return;
                }

                const state = getPriceState(product, size);
                priceLine.innerHTML = state.onSale
                    ? `<span class="old-price">${formatPrice(state.regular)}</span><span class="gold-price">${formatPrice(state.current)}</span>`
                    : `<span class="gold-price">${formatPrice(state.current)}</span>`;
            }

            function updateSizeChipState(root, productId, selectedSize) {
                const group = root?.querySelector(`[data-size-group="${productId}"]`);
                if (!group) {
                    return;
                }

                group.querySelectorAll('.vitrine-size-chip').forEach((chip) => {
                    const chipSize = String(chip.dataset.vitrineSize || '').trim().toUpperCase();
                    const isActive = chipSize === String(selectedSize || '').trim().toUpperCase();
                    chip.classList.toggle('is-active', isActive);
                    chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            }

            function syncProductCardUI(root, product, productId) {
                const card = root?.querySelector(`[data-product-id="${productId}"]`);
                if (!card || !product) {
                    return;
                }

                const selectedSize = selectedSizes[productId] || '';
                updateSizeChipState(root, productId, selectedSize);
                updateCardPrice(card, product, selectedSize);
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
                const safeSize = String(size || '').trim().toUpperCase();
                const variant = getVariantForSize(product, safeSize);
                const unitPrice = getUnitPrice(product, safeSize);
                const variantText = safeSize ? `Tamanho: ${safeSize}` : '';
                const variantKey = safeSize ? `size::${safeSize}` : 'base';

                if (variant && Number(variant.stock || 0) <= 0) {
                    showCartNotice(`Tamanho esgotado: ${safeSize}`);
                    return false;
                }

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
                        variacao_id: variant ? Number(variant.variation_id || 0) : null,
                        estoque: variant ? Number(variant.stock || 0) : null,
                        variacao_texto: variantText,
                        variant: variantText,
                        variantKey: variantKey,
                        addedAt: new Date().toISOString()
                    });
                }

                setCart(cart);
                return true;
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

                if (!addProductToCart(product, selectedSize)) {
                    return;
                }

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

                if (!addProductToCart(catalogProduct, selectedSize)) {
                    return;
                }

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

            function getVitrineState(pageIndex = page) {
                const list = getCurrentList();
                const start = pageIndex * perPage;
                const safeStart = Math.min(start, Math.max(0, list.length - 1));
                const current = list.slice(safeStart, safeStart + perPage);
                return { list, safeStart, current };
            }

            function buildVitrineCardsMarkup(current) {
                return current.map((p) => {
                    const priceState = getPriceState(p, selectedSizes[Number(p.id || 0)] || '');
                    const priceHtml = priceState.onSale
                        ? `<span class="old-price">${formatPrice(priceState.regular)}</span><span class="gold-price">${formatPrice(priceState.current)}</span>`
                        : `<span class="gold-price">${formatPrice(priceState.current)}</span>`;

                    const primaryTag = Array.isArray(p.tags)
                        ? p.tags.find((t) => String(t || '').trim() !== '')
                        : '';

          const img = p.image
            ? `<img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" loading="lazy">`
            : '<div class="product-image-fallback">RARE</div>';

                    const badgeHtml = primaryTag
                        ? `<span class="product-badge">${escapeHtml(primaryTag)}</span>`
                        : '';

                    return `
                        <article class="product-card vitrine-card" data-product-id="${Number(p.id || 0)}" data-product-url="produto.php?id=${Number(p.id || 0)}">
                                <div class="product-image-wrap">${img}${badgeHtml}</div>
                                <div class="product-info">
                                    <h3>${escapeHtml(p.name)}</h3>
                                    <p>${escapeHtml((p.description || '').trim() || 'Produto premium da Rare.')}</p>
                                    ${Array.isArray(p.size_options) && p.size_options.length ? `<div class="vitrine-size-selector" data-size-group="${Number(p.id || 0)}">${p.size_options.map((size) => {
                                        const stock = Number(size?.stock || 0);
                                        const soldOutClass = stock <= 0 ? ' is-sold-out' : '';
                                        const disabledAttr = stock <= 0 ? ' disabled aria-disabled="true" aria-label="Tamanho indisponível"' : '';
                                        const saleValue = size?.sale_price === null || size?.sale_price === undefined ? '' : Number(size.sale_price || 0);
                                        const label = `${escapeHtml(size?.label || '')}`;
                                        return `<button type="button" class="vitrine-size-chip${soldOutClass}" data-vitrine-size="${escapeHtml(size?.label || '')}" data-product-id="${Number(p.id || 0)}" data-variation-id="${Number(size?.variation_id || 0)}" data-price="${Number(size?.price || 0)}" data-sale-price="${saleValue}" data-stock="${stock}"${disabledAttr}>${label}</button>`;
                                    }).join('')}</div>` : ''}
                                    <div class="price-line">${priceHtml}</div>
                                    <div class="vitrine-actions">
                                        <button type="button" class="vitrine-more" data-vitrine-action="add" data-product-id="${Number(p.id || 0)}">Adicionar</button>
                                        <button type="button" class="vitrine-buy" data-vitrine-action="buy" data-product-id="${Number(p.id || 0)}">Comprar</button>
                                    </div>
                                </div>
            </article>`;
                }).join('');
            }

            function syncRenderedVitrine(root, current) {
                current.forEach((product) => {
                    syncProductCardUI(root, product, Number(product.id || 0));
                });
            }

            function updateVitrineCounter(list, safeStart, current) {
                const x = list.length ? safeStart + 1 : 0;
                const y = Math.min(safeStart + current.length, list.length);
                counter.textContent = `Mostrando ${x}–${y} de ${list.length}`;
            }

            function updateVitrineViewportHeight() {
                if (!vitrineViewport || !cardsRoot) return;
                vitrineViewport.style.height = `${cardsRoot.offsetHeight}px`;
            }

            function normalizeCardImages(root) {
                if (!root) return;
                root.querySelectorAll('.product-image-wrap img').forEach((img) => {
                    const wrap = img.closest('.product-image-wrap');
                    if (!wrap) return;

                    const applyFitMode = () => {
                        const w = Number(img.naturalWidth || 0);
                        const h = Number(img.naturalHeight || 0);
                        if (!w || !h) return;

                        // Only very wide assets (banner/screenshot-like) should fill the frame.
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

            function renderVitrine() {
                const state = getVitrineState(page);
                cardsRoot.innerHTML = buildVitrineCardsMarkup(state.current);
                syncRenderedVitrine(cardsRoot, state.current);
                normalizeCardImages(cardsRoot);
                updateVitrineCounter(state.list, state.safeStart, state.current);
                updateVitrineViewportHeight();
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

                    if (sizeBtn.disabled || Number(sizeBtn.dataset.stock || 0) <= 0) {
                        return;
                    }

                    selectedSizes[productId] = size;
                    const product = getCurrentList().find((item) => Number(item.id) === Number(productId));
                    syncProductCardUI(cardsRoot, product, productId);
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

                        if (sizeBtn.disabled || Number(sizeBtn.dataset.stock || 0) <= 0) {
                            return;
                        }

                        selectedSizes[productId] = size;
                        const product = products.find((item) => Number(item.id) === Number(productId));
                        syncProductCardUI(catalogRoot, product, productId);
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

            if (filters) {
                filters.addEventListener('click', (event) => {
                    const btn = event.target.closest('button[data-category]');
                    if (!btn) return;
                    activeCategory = btn.dataset.category;
                    page = 0;
                    filters.querySelectorAll('button').forEach((item) => item.classList.remove('active'));
                    btn.classList.add('active');
                    renderVitrine();
                });
            }

            function animateVitrinePage(nextPage, direction) {
                if (!cardsRoot || !vitrineViewport || vitrineTransitionLock) {
                    page = nextPage;
                    renderVitrine();
                    return;
                }

                vitrineTransitionLock = true;
                const state = getVitrineState(nextPage);

                const incoming = document.createElement('div');
                incoming.className = `vitrine-cards vitrine-cards-panel vitrine-cards-panel-enter-${direction}`;
                incoming.innerHTML = buildVitrineCardsMarkup(state.current);
                vitrineViewport.appendChild(incoming);
                syncRenderedVitrine(incoming, state.current);
                normalizeCardImages(incoming);

                cardsRoot.classList.add('vitrine-cards-panel', `vitrine-cards-panel-leave-${direction}`);
                incoming.classList.add('is-prepared');

                requestAnimationFrame(() => {
                    cardsRoot.classList.add('is-active');
                    incoming.classList.add('is-active');
                });

                window.setTimeout(() => {
                    page = nextPage;
                    cardsRoot.innerHTML = incoming.innerHTML;
                    syncRenderedVitrine(cardsRoot, state.current);
                    normalizeCardImages(cardsRoot);
                    updateVitrineCounter(state.list, state.safeStart, state.current);

                    cardsRoot.classList.remove(
                      'vitrine-cards-panel',
                      'vitrine-cards-panel-leave-next',
                      'vitrine-cards-panel-leave-prev',
                      'is-active'
                    );

                    incoming.remove();
                    updateVitrineViewportHeight();
                    vitrineTransitionLock = false;
                }, 360);
            }

            btnPrev.addEventListener('click', () => {
                const list = getCurrentList();
                const maxPage = Math.max(0, Math.ceil(list.length / perPage) - 1);
                const nextPage = page <= 0 ? maxPage : page - 1;
                animateVitrinePage(nextPage, 'prev');
            });

            btnNext.addEventListener('click', () => {
                const list = getCurrentList();
                const maxPage = Math.max(0, Math.ceil(list.length / perPage) - 1);
                const nextPage = page >= maxPage ? 0 : page + 1;
                animateVitrinePage(nextPage, 'next');
            });

      renderVitrine();

            normalizeCardImages(catalogRoot);

            products.forEach((product) => {
                syncProductCardUI(catalogRoot, product, Number(product.id || 0));
            });

      const bannerSection = document.getElementById('showcaseBanner');
    const kicker = document.getElementById('showcaseKicker');
      const title = document.getElementById('showcaseTitle');
      const subtitle = document.getElementById('showcaseSub');
            const cta = document.getElementById('showcaseCta');
      const dotsRoot = document.getElementById('showcaseDots');
        const showcaseImage = document.getElementById('showcaseImage');
    const bannerPrev = document.getElementById('showcasePrev');
    const bannerNext = document.getElementById('showcaseNext');

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

                const hasTextContent = Boolean(
                  titleText ||
                  subtitleText ||
                  (cta && cta.style.display !== 'none')
                );
                bannerSection.classList.toggle('has-text-content', hasTextContent);

                if (slide.image) {
                    if (showcaseImage) {
                        showcaseImage.src = slide.image;
                        showcaseImage.style.display = 'block';
                    }
                    bannerSection.classList.add('has-banner-image');
                } else {
                    if (showcaseImage) {
                        showcaseImage.removeAttribute('src');
                        showcaseImage.style.display = 'none';
                    }
                    bannerSection.classList.remove('has-banner-image');
                }

        dotsRoot.querySelectorAll('button').forEach((dot, dotIndex) => {
          dot.classList.toggle('active', dotIndex === index);
        });
      }

                        const bannerIntervalMs = Math.max(3000, Math.min(30000, Number(window.__RARE_BANNER_INTERVAL__ || 4) * 1000));
                        let bannerTimerId = null;

                        function scheduleNextBanner() {
                                if (bannerTimerId) {
                                        window.clearTimeout(bannerTimerId);
                                        bannerTimerId = null;
                                }

                                if (bannerSlides.length <= 1) {
                                        return;
                                }

                                bannerTimerId = window.setTimeout(() => {
                                    goBanner(1, false);
                                    scheduleNextBanner();
                                }, bannerIntervalMs);
                        }

                        bannerSlides.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.addEventListener('click', () => {
          bannerIndex = index;
          paintBanner(bannerIndex);
                    scheduleNextBanner();
        });
        dotsRoot.appendChild(dot);
      });

      paintBanner(0);

            function goBanner(step, isManual = true) {
                if (!bannerSlides.length) return;
                bannerIndex = (bannerIndex + step + bannerSlides.length) % bannerSlides.length;
                paintBanner(bannerIndex);

                if (isManual) {
                    scheduleNextBanner();
                }
            }

            if (bannerPrev) {
                bannerPrev.addEventListener('click', () => goBanner(-1));
            }

            if (bannerNext) {
                bannerNext.addEventListener('click', () => goBanner(1));
            }

            scheduleNextBanner();

      const couponForm = document.getElementById('couponForm');
      const couponFeedback = document.getElementById('couponFeedback');
      couponForm.addEventListener('submit', (event) => {
        event.preventDefault();
                couponFeedback.textContent = 'Cadastro confirmado. Seu cupom de primeira compra sera enviado por email.';
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

    <!-- Ligas em Destaque – scroll -->
    <script>
    (function(){
        const viewport = document.getElementById('rareLeaguesViewport');
        const track    = document.getElementById('rareLeaguesTrack');
        const prev     = document.getElementById('rareLeaguePrev');
        const next     = document.getElementById('rareLeagueNext');
        if (!viewport || !track) return;

        function scrollBy(dir) {
            const cardW = (track.querySelector('.rl-card') || {offsetWidth: 220}).offsetWidth + 20;
            viewport.scrollBy({ left: dir * cardW * 2, behavior: 'smooth' });
        }
        if (prev) prev.addEventListener('click', () => scrollBy(-1));
        if (next) next.addEventListener('click', () => scrollBy(1));
    })();
    </script>
</body>
</html>

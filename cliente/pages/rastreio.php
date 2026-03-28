<?php
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

$freteGratisValor = getFreteGratisThreshold($pdo);
$usuarioLogado = isset($_SESSION['cliente']);
$nomeUsuario = $_SESSION['cliente']['nome'] ?? '';
$basePath = '../';
$currentPage = 'tracking';

$errors = [];
$trackingResult = null;
$activeMode = 'order';

function getTableColumns(PDO $pdo, string $table): array {
    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['Field'])) {
            $columns[] = (string) $row['Field'];
        }
    }
    return $columns;
}

function normalizeStatusDelivery(?string $deliveryStatus, ?string $orderStatus): string {
    $deliveryStatus = trim((string) $deliveryStatus);
    if ($deliveryStatus !== '') {
        return $deliveryStatus;
    }

    $orderStatus = mb_strtolower(trim((string) $orderStatus), 'UTF-8');
    if ($orderStatus === '') {
        return 'Aguardando postagem';
    }

    if (strpos($orderStatus, 'entreg') !== false) {
        return 'Entregue';
    }

    if (strpos($orderStatus, 'envi') !== false || strpos($orderStatus, 'transito') !== false) {
        return 'Em transporte';
    }

    if (strpos($orderStatus, 'cancel') !== false) {
        return 'Envio cancelado';
    }

    return 'Processando envio';
}

function buildExternalTrackingLink(?string $link, ?string $code): string {
    $link = trim((string) $link);
    if ($link !== '') {
        return $link;
    }

    $code = trim((string) $code);
    if ($code === '') {
        return '#';
    }

    return 'https://t.17track.net/pt#nums=' . rawurlencode($code);
}

function formatOrderNumber(array $row, bool $hasNumeroPedido): string {
    $id = (int) ($row['id'] ?? 0);
    return '#' . str_pad((string) max(1, $id), 6, '0', STR_PAD_LEFT);
}

$pedidoColumns = [];
try {
    $pedidoColumns = getTableColumns($pdo, 'pedidos');
} catch (Throwable $e) {
    error_log('Erro ao carregar colunas de pedidos: ' . $e->getMessage());
    $errors[] = 'Nao foi possivel carregar os dados de rastreio no momento.';
}

$hasColumn = static function (string $column) use ($pedidoColumns): bool {
    return in_array($column, $pedidoColumns, true);
};

$hasNumeroPedido = $hasColumn('numero_pedido');
$emailColumn = $hasColumn('email_cliente') ? 'email_cliente' : ($hasColumn('cliente_email') ? 'cliente_email' : '');
$statusPedidoColumn = $hasColumn('status_pedido') ? 'status_pedido' : ($hasColumn('status') ? 'status' : '');
$statusEntregaColumn = $hasColumn('status_entrega') ? 'status_entrega' : '';
$codigoRastreioColumn = $hasColumn('codigo_rastreio') ? 'codigo_rastreio' : '';
$linkRastreioColumn = $hasColumn('link_rastreio') ? 'link_rastreio' : '';
$transportadoraColumn = $hasColumn('transportadora') ? 'transportadora' : '';
$dataEnvioColumn = $hasColumn('data_envio') ? 'data_envio' : ($hasColumn('data_status_mudanca') ? 'data_status_mudanca' : '');
$ultimaAtualizacaoColumn = $hasColumn('ultima_atualizacao_rastreio') ? 'ultima_atualizacao_rastreio' : ($hasColumn('data_atualizacao') ? 'data_atualizacao' : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $activeMode = ($_POST['search_mode'] ?? 'order') === 'code' ? 'code' : 'order';

    $selectList = [
        'id',
        $hasNumeroPedido ? 'numero_pedido' : 'NULL AS numero_pedido',
        $emailColumn !== '' ? "{$emailColumn} AS email_cliente" : 'NULL AS email_cliente',
        $statusPedidoColumn !== '' ? "{$statusPedidoColumn} AS status_pedido" : 'NULL AS status_pedido',
        $statusEntregaColumn !== '' ? "{$statusEntregaColumn} AS status_entrega" : 'NULL AS status_entrega',
        $codigoRastreioColumn !== '' ? "{$codigoRastreioColumn} AS codigo_rastreio" : 'NULL AS codigo_rastreio',
        $transportadoraColumn !== '' ? "{$transportadoraColumn} AS transportadora" : 'NULL AS transportadora',
        $linkRastreioColumn !== '' ? "{$linkRastreioColumn} AS link_rastreio" : 'NULL AS link_rastreio',
        $dataEnvioColumn !== '' ? "{$dataEnvioColumn} AS data_envio" : 'NULL AS data_envio',
        $ultimaAtualizacaoColumn !== '' ? "{$ultimaAtualizacaoColumn} AS ultima_atualizacao_rastreio" : 'NULL AS ultima_atualizacao_rastreio',
    ];

    try {
        if ($activeMode === 'order') {
            $orderInput = trim((string) ($_POST['numero_pedido'] ?? ''));
            $emailInput = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');

            if ($orderInput === '' || $emailInput === '') {
                $errors[] = 'Informe numero do pedido (ex: #000005) e e-mail para continuar.';
            } elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Digite um e-mail valido.';
            } elseif ($emailColumn === '') {
                $errors[] = 'Este ambiente nao possui coluna de e-mail no pedido para validacao.';
            } else {
                $sql = 'SELECT ' . implode(', ', $selectList) . ' FROM pedidos WHERE LOWER(' . $emailColumn . ') = :email';
                $params = [':email' => $emailInput];

                // Remover # se houver
                $orderInput = str_replace('#', '', $orderInput);

                if (!preg_match('/\d+/', $orderInput, $matches)) {
                    $errors[] = 'Numero do pedido invalido. Use formato #000005.';
                } else {
                    $sql .= ' AND id = :id';
                    $params[':id'] = (int) $matches[0];
                }

                if (empty($errors)) {
                    $sql .= ' LIMIT 1';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $trackingResult = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                }
            }
        }

        if ($activeMode === 'code') {
            $trackingCode = trim((string) ($_POST['codigo_rastreio'] ?? ''));

            if ($trackingCode === '') {
                $errors[] = 'Informe o codigo de rastreio para consultar.';
            } elseif ($codigoRastreioColumn === '') {
                $errors[] = 'Este ambiente ainda nao possui campo de codigo de rastreio na tabela de pedidos.';
            } else {
                $sql = 'SELECT ' . implode(', ', $selectList) . ' FROM pedidos WHERE ' . $codigoRastreioColumn . ' = :codigo_rastreio LIMIT 1';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':codigo_rastreio' => $trackingCode]);
                $trackingResult = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }

        if (empty($errors) && $trackingResult === null) {
            $errors[] = 'Nao encontramos um pedido com os dados informados. Verifique e tente novamente.';
        }
    } catch (Throwable $e) {
        error_log('Erro ao consultar rastreio: ' . $e->getMessage());
        $errors[] = 'Nao foi possivel consultar o rastreio agora. Tente novamente em instantes.';
    }
}

$trackingView = null;
if ($trackingResult !== null) {
    $trackingView = [
        'numero_pedido' => formatOrderNumber($trackingResult, $hasNumeroPedido),
        'status_pedido' => trim((string) ($trackingResult['status_pedido'] ?? 'Em analise')),
        'status_entrega' => normalizeStatusDelivery(
            $trackingResult['status_entrega'] ?? null,
            $trackingResult['status_pedido'] ?? null
        ),
        'codigo_rastreio' => trim((string) ($trackingResult['codigo_rastreio'] ?? 'Aguardando postagem')),
        'data_envio' => !empty($trackingResult['data_envio']) ? date('d/m/Y H:i', strtotime((string) $trackingResult['data_envio'])) : 'Aguardando envio',
        'transportadora' => trim((string) ($trackingResult['transportadora'] ?? 'Nao informada')),
        'ultima_atualizacao' => !empty($trackingResult['ultima_atualizacao_rastreio'])
            ? date('d/m/Y H:i', strtotime((string) $trackingResult['ultima_atualizacao_rastreio']))
            : 'Sem atualizacao registrada',
        'link_externo' => buildExternalTrackingLink(
            $trackingResult['link_rastreio'] ?? null,
            $trackingResult['codigo_rastreio'] ?? null
        ),
    ];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastrear Pedido - RARE7</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Cinzel:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="../css/loja.css">

    <style>
        body { background: #0e0e0e; color: #fff; font-family: "Space Grotesk", sans-serif; margin: 0; overflow-x: hidden; }
    </style>
</head>
<body class="tracking-page">

<!-- NAVBAR — mesma estrutura do index.php -->
<header class="floating-navbar" id="floatingNavbar">
    <div class="nav-wrap container-shell">
        <a href="../index.php" class="nav-logo" aria-label="RARE7 - Início">
            <img src="../../image/logo_png.png" alt="Logo RARE7" class="nav-logo-mark" loading="lazy"
                 onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='../assets/images/logo.png';}else{this.style.display='none';}">
            <span class="nav-logo-text">RARE7</span>
        </a>
        <nav>
            <ul class="nav-links">
                <li><a href="../produtos.php">Todos Produtos</a></li>
                <li><a href="../produtos.php?tag=retro">Retro</a></li>
                <li><a href="../produtos.php?categoria=Times">Times</a></li>
                <li><a href="../produtos.php?categoria=Sele%C3%A7%C3%B5es">Seleções</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <form class="nav-search" id="navSearchForm" action="../produtos.php" method="get" role="search">
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
                    <div class="user-greeting">Olá, <?php echo htmlspecialchars($nomeUsuario); ?></div>
                    <a href="minha-conta.php">Minha conta</a>
                    <a href="minha-conta.php?tab=pedidos">Meus pedidos</a>
                    <a href="rastreio.php">Rastrear pedido</a>
                    <a href="logout.php">Sair</a>
                </div>
            </div>
            <?php else: ?>
            <a href="login.php" class="nav-icon-link" aria-label="Perfil">
                <span class="material-symbols-sharp">person</span>
            </a>
            <?php endif; ?>
            <a href="../pages/carrinho.php" class="nav-icon-link" aria-label="Carrinho" data-open-mini-cart>
                <span class="material-symbols-sharp">shopping_bag</span>
            </a>
        </div>
    </div>
</header>

<main class="tracking-shell section">
    <div class="container-shell">
        <div class="section-head tracking-head">
            <h2>Rastrear Pedido</h2>
            <p>Consulte seu envio em segundos usando numero do pedido + e-mail, ou apenas o codigo de rastreio.</p>
        </div>

        <section class="tracking-panel" aria-label="Consulta de rastreamento">
            <div class="tracking-tabs" role="tablist" aria-label="Tipo de busca">
                <button type="button" class="tracking-tab<?php echo $activeMode === 'order' ? ' is-active' : ''; ?>" data-track-tab="order" role="tab" aria-selected="<?php echo $activeMode === 'order' ? 'true' : 'false'; ?>">
                    Buscar por pedido
                </button>
                <button type="button" class="tracking-tab<?php echo $activeMode === 'code' ? ' is-active' : ''; ?>" data-track-tab="code" role="tab" aria-selected="<?php echo $activeMode === 'code' ? 'true' : 'false'; ?>">
                    Buscar por codigo
                </button>
            </div>

            <div class="tracking-tab-panels">
                <form method="post" class="tracking-form<?php echo $activeMode === 'order' ? ' is-active' : ''; ?>" data-track-form="order" novalidate>
                    <input type="hidden" name="search_mode" value="order">
                    <div class="tracking-grid">
                        <label class="tracking-field">
                            <span>Numero do pedido</span>
                            <input
                                type="text"
                                name="numero_pedido"
                                placeholder="Ex.: #000005"
                                value="<?php echo htmlspecialchars((string) ($_POST['numero_pedido'] ?? '')); ?>"
                                required
                            >
                        </label>
                        <label class="tracking-field">
                            <span>E-mail da compra</span>
                            <input
                                type="email"
                                name="email"
                                placeholder="seu@email.com"
                                value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? '')); ?>"
                                required
                            >
                        </label>
                    </div>
                    <button type="submit" class="tracking-submit" data-loading-label="Buscando pedido...">Buscar pedido</button>
                </form>

                <form method="post" class="tracking-form<?php echo $activeMode === 'code' ? ' is-active' : ''; ?>" data-track-form="code" novalidate>
                    <input type="hidden" name="search_mode" value="code">
                    <div class="tracking-grid tracking-grid-single">
                        <label class="tracking-field">
                            <span>Codigo de rastreio</span>
                            <input
                                type="text"
                                name="codigo_rastreio"
                                placeholder="Ex.: BR123456789CN"
                                value="<?php echo htmlspecialchars((string) ($_POST['codigo_rastreio'] ?? '')); ?>"
                                required
                            >
                        </label>
                    </div>
                    <button type="submit" class="tracking-submit" data-loading-label="Consultando codigo...">Rastrear codigo</button>
                </form>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="tracking-feedback tracking-feedback-error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($trackingView !== null): ?>
                <article class="tracking-result" aria-live="polite">
                    <header class="tracking-result-head">
                        <h3>Pedido encontrado</h3>
                        <p>Confira os dados de envio abaixo.</p>
                    </header>

                    <div class="tracking-result-grid">
                        <div class="tracking-item">
                            <span>Numero do pedido</span>
                            <strong><?php echo htmlspecialchars($trackingView['numero_pedido']); ?></strong>
                        </div>
                        <div class="tracking-item">
                            <span>Status do pedido</span>
                            <strong><?php echo htmlspecialchars($trackingView['status_pedido']); ?></strong>
                        </div>
                        <div class="tracking-item">
                            <span>Status de envio</span>
                            <strong><?php echo htmlspecialchars($trackingView['status_entrega']); ?></strong>
                        </div>
                        <div class="tracking-item">
                            <span>Codigo de rastreio</span>
                            <strong><?php echo htmlspecialchars($trackingView['codigo_rastreio']); ?></strong>
                        </div>
                        <div class="tracking-item">
                            <span>Data de envio</span>
                            <strong><?php echo htmlspecialchars($trackingView['data_envio']); ?></strong>
                        </div>
                        <div class="tracking-item">
                            <span>Transportadora</span>
                            <strong><?php echo htmlspecialchars($trackingView['transportadora']); ?></strong>
                        </div>
                        <div class="tracking-item tracking-item-full">
                            <span>Ultima atualizacao</span>
                            <strong><?php echo htmlspecialchars($trackingView['ultima_atualizacao']); ?></strong>
                        </div>
                    </div>

                    <a
                        class="tracking-link"
                        href="<?php echo htmlspecialchars($trackingView['link_externo']); ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        Acompanhar rastreio
                    </a>
                </article>
            <?php endif; ?>

            <p class="tracking-tip">O codigo pode levar alguns dias para comecar a exibir movimentacoes apos a postagem.</p>
        </section>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
(function () {
    const tabs = Array.from(document.querySelectorAll('[data-track-tab]'));
    const forms = Array.from(document.querySelectorAll('[data-track-form]'));

    function setActiveTab(mode) {
        tabs.forEach((tab) => {
            const active = tab.getAttribute('data-track-tab') === mode;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        forms.forEach((form) => {
            const active = form.getAttribute('data-track-form') === mode;
            form.classList.toggle('is-active', active);
        });
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const mode = tab.getAttribute('data-track-tab') || 'order';
            setActiveTab(mode);
        });
    });

    forms.forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('.tracking-submit');
            if (!button) return;
            button.dataset.originalLabel = button.textContent;
            button.textContent = button.getAttribute('data-loading-label') || 'Buscando...';
            button.disabled = true;
            button.classList.add('is-loading');
        });
    });
})();

(function () {
    const navbar = document.getElementById('floatingNavbar');
    const navSearchForm = document.getElementById('navSearchForm');
    const navSearchInput = document.getElementById('navSearchInput');
    const navSearchToggle = document.getElementById('navSearchToggle');

    if (navSearchForm && navSearchInput && navSearchToggle) {
        const closeSearch = () => navSearchForm.classList.remove('active');
        navSearchToggle.addEventListener('click', () => {
            if (!navSearchForm.classList.contains('active')) {
                navSearchForm.classList.add('active');
                requestAnimationFrame(() => navSearchInput.focus());
            } else if (navSearchInput.value.trim() !== '') {
                navSearchForm.submit();
            } else {
                closeSearch();
            }
        });
        document.addEventListener('click', (e) => { if (!navSearchForm.contains(e.target)) closeSearch(); });
        navSearchInput.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeSearch(); navSearchInput.blur(); } });
    }

    // Navbar aparece após 10px de scroll (página curta, sem hero)
    function toggleNavbar() {
        if (!navbar) return;
        if (window.scrollY > 10) {
            navbar.classList.add('visible');
        } else {
            navbar.classList.remove('visible');
        }
    }
    window.addEventListener('scroll', toggleNavbar, { passive: true });
    toggleNavbar();

    window.toggleUserDropdown = function (e) {
        e.preventDefault();
        e.stopPropagation();
        const dd = e.currentTarget.closest('.user-dropdown');
        if (!dd) return;
        dd.classList.toggle('active');
        e.currentTarget.setAttribute('aria-expanded', dd.classList.contains('active') ? 'true' : 'false');
    };
    document.addEventListener('click', function () {
        document.querySelectorAll('.user-dropdown.active').forEach(function (el) {
            el.classList.remove('active');
            const btn = el.querySelector('.user-dropdown-btn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    });
})();
</script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../../../../PHP/login.php');
    exit();
}

require_once '../../../../config/base.php';
require_once '../../../../PHP/conexao.php';
require_once '../helper-contador.php';

if (!isset($nao_lidas)) {
    $nao_lidas = 0;
    try {
        $result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM mensagens WHERE lida = FALSE AND remetente != 'admin'");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $nao_lidas = $row['total'];
        }
    } catch (Exception $e) {
        $nao_lidas = 0;
    }
}

// Auto-migrate: garantir tabela
mysqli_query($conexao, "
    CREATE TABLE IF NOT EXISTS cms_home_leagues (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        slug VARCHAR(120) NOT NULL,
        sigla VARCHAR(20) NOT NULL,
        classe VARCHAR(60) NOT NULL DEFAULT '',
        logo_path VARCHAR(255) NULL,
        ordem INT NOT NULL DEFAULT 1,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cms_home_leagues_ativo_ordem (ativo, ordem)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Seed inicial se tabela vazia
$checkEmpty = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM cms_home_leagues");
$emptyRow   = mysqli_fetch_assoc($checkEmpty);
if ((int)($emptyRow['total'] ?? 0) === 0) {
    $seeds = [
        ['Premier League',   'premier-league',   'PL',  'league-premier',     1],
        ['La Liga',          'la-liga',           'LL',  'league-laliga',      2],
        ['Brasileirão',      'brasileirao',       'BR',  'league-brasileirao', 3],
        ['Serie A',          'serie-a',           'SA',  'league-seriea',      4],
        ['Bundesliga',       'bundesliga',        'BL',  'league-bundesliga',  5],
        ['Champions League', 'champions-league',  'UCL', 'league-champions',   6],
    ];
    $seedStmt = mysqli_prepare($conexao,
        "INSERT INTO cms_home_leagues (nome, slug, sigla, classe, ordem) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($seeds as $s) {
        mysqli_stmt_bind_param($seedStmt, 'ssssi', $s[0], $s[1], $s[2], $s[3], $s[4]);
        mysqli_stmt_execute($seedStmt);
    }
    mysqli_stmt_close($seedStmt);
}

// Buscar ligas
$ligas = [];
$res = mysqli_query($conexao, "SELECT * FROM cms_home_leagues ORDER BY ordem ASC, id ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $ligas[] = $row;
    }
}

$totalAtivas = count(array_filter($ligas, fn($l) => $l['ativo'] == 1));
$totalCadastradas = count($ligas);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS – Ligas em Destaque | Rare7 Admin</title>
    <link rel="icon" type="image/png" href="../../../../assets/images/logo_png.png" sizes="any">
    <link rel="apple-touch-icon" href="../../../../assets/images/logo_png.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <link rel="stylesheet" href="../../../css/dashboard-sections.css">
    <link rel="stylesheet" href="../../../css/dashboard-cards.css">
    <style>
        .cms-league-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.2rem; margin-top: 1.5rem; }
        .cms-league-card { background: var(--color-white); border-radius: var(--border-radius-2); padding: 1.4rem; border: 1.5px solid var(--color-light); position: relative; transition: box-shadow .2s; }
        .cms-league-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .cms-league-card.is-new { border-style: dashed; border-color: var(--color-primary); }
        .cms-league-card.is-inactive { opacity: .6; }

        .league-card-header { display: flex; align-items: center; gap: .8rem; margin-bottom: 1rem; }
        .league-logo-preview { width: 52px; height: 52px; border-radius: 50%; object-fit: contain; border: 2px solid var(--color-light); background: var(--color-background); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        .league-logo-preview img { width: 100%; height: 100%; object-fit: contain; }
        .league-logo-preview span { font-size: .8rem; font-weight: 700; color: var(--color-dark-variant); }
        .league-card-title { font-weight: 600; font-size: 1rem; color: var(--color-dark); }
        .league-card-id { font-size: .75rem; color: var(--color-info-dark); }

        .league-fields { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }
        .league-fields .full { grid-column: 1 / -1; }
        .league-fields label { font-size: .75rem; color: var(--color-dark-variant); display: block; margin-bottom: .25rem; }
        .league-fields input[type="text"],
        .league-fields input[type="number"] {
            width: 100%; padding: .45rem .65rem; border: 1px solid var(--color-light); border-radius: var(--border-radius-1);
            font-size: .85rem; background: var(--color-background); color: var(--color-dark); box-sizing: border-box;
        }
        .league-fields input:focus { outline: none; border-color: var(--color-primary); }

        .league-upload-row { display: flex; align-items: center; gap: .6rem; grid-column: 1 / -1; }
        .league-upload-row input[type="file"] { flex: 1; font-size: .8rem; }
        .league-upload-btn { padding: .4rem .8rem; background: var(--color-primary); color: #fff; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-size: .8rem; white-space: nowrap; }
        .league-upload-btn:disabled { opacity: .5; cursor: not-allowed; }
        .league-upload-status { font-size: .75rem; color: var(--color-success); margin-top: .2rem; }

        .league-toggle-row { display: flex; align-items: center; gap: .5rem; grid-column: 1 / -1; margin-top: .2rem; }
        .cms-checkbox { width: 16px; height: 16px; cursor: pointer; }
        .league-toggle-label { font-size: .85rem; color: var(--color-dark-variant); }

        .league-card-actions { display: flex; gap: .5rem; margin-top: 1rem; }
        .btn-delete-league { padding: .4rem .8rem; background: transparent; color: var(--color-danger); border: 1px solid var(--color-danger); border-radius: var(--border-radius-1); cursor: pointer; font-size: .8rem; display: flex; align-items: center; gap: .3rem; }
        .btn-delete-league:hover { background: var(--color-danger); color: #fff; }

        .cms-leagues-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: .8rem; }
        .btn-add-league { padding: .8rem 1.5rem; background: var(--color-primary); color: #fff; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-size: .9rem; display: inline-flex; align-items: center; gap: .4rem; }
        .btn-save-leagues { padding: .8rem 1.8rem; background: var(--color-success); color: #fff; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-size: .9rem; display: inline-flex; align-items: center; gap: .4rem; }
        .btn-save-leagues:disabled { opacity: .5; cursor: not-allowed; }

        .toast { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; padding: .9rem 1.4rem; border-radius: var(--border-radius-1); color: #fff; font-size: .9rem; display: none; animation: fadeIn .3s; }
        .toast.success { background: var(--color-success); }
        .toast.error   { background: var(--color-danger); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .delete-modal { display: none; position: fixed; inset: 0; z-index: 9998; background: rgba(0,0,0,.5); align-items: center; justify-content: center; }
        .delete-modal.open { display: flex; }
        .delete-modal-box { background: var(--color-white); border-radius: var(--border-radius-2); padding: 2rem; max-width: 400px; width: 90%; text-align: center; }
        .delete-modal-box h3 { margin-bottom: .5rem; color: var(--color-dark); }
        .delete-modal-box p  { font-size: .9rem; color: var(--color-dark-variant); margin-bottom: 1.5rem; }
        .delete-modal-actions { display: flex; gap: 1rem; justify-content: center; }
        .btn-confirm-delete { padding: .6rem 1.4rem; background: var(--color-danger); color: #fff; border: none; border-radius: var(--border-radius-1); cursor: pointer; }
        .btn-cancel-delete  { padding: .6rem 1.4rem; background: var(--color-light); color: var(--color-dark); border: none; border-radius: var(--border-radius-1); cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <!-- SIDEBAR -->
        <aside>
            <div class="top">
                <div class="logo">
                    <img src="../../../../assets/images/logo_png.png" alt="Logo">
                    <a href="../index.php"><h2 class="danger">Rare7</h2></a>
                </div>
                <div class="close" id="close-btn">
                    <span class="material-symbols-sharp">close</span>
                </div>
            </div>

            <div class="sidebar">
                <a href="../index.php">
                    <span class="material-symbols-sharp">grid_view</span>
                    <h3>Painel</h3>
                </a>
                <a href="../customers.php">
                    <span class="material-symbols-sharp">group</span>
                    <h3>Clientes</h3>
                </a>
                <a href="../orders.php">
                    <span class="material-symbols-sharp">Orders</span>
                    <h3>Pedidos</h3>
                </a>
                <a href="../analytics.php">
                    <span class="material-symbols-sharp">Insights</span>
                    <h3>Gráficos</h3>
                </a>
                <a href="../menssage.php">
                    <span class="material-symbols-sharp">Mail</span>
                    <h3>Mensagens</h3>
                    <span class="message-count"><?php echo $nao_lidas; ?></span>
                </a>
                <a href="../products.php">
                    <span class="material-symbols-sharp">Inventory</span>
                    <h3>Produtos</h3>
                </a>
                <a href="../cupons.php">
                    <span class="material-symbols-sharp">sell</span>
                    <h3>Cupons</h3>
                </a>
                <a href="../gestao-fluxo.php">
                    <span class="material-symbols-sharp">account_tree</span>
                    <h3>Gestão de Fluxo</h3>
                </a>

                <div class="menu-item-container">
                  <a href="home.php" class="menu-item-with-submenu active">
                      <span class="material-symbols-sharp">web</span>
                      <h3>CMS</h3>
                  </a>
                  <div class="submenu">
                    <a href="home.php">
                      <span class="material-symbols-sharp">home</span>
                      <h3>Home (Textos)</h3>
                    </a>
                    <a href="banners.php">
                      <span class="material-symbols-sharp">view_carousel</span>
                      <h3>Banners</h3>
                    </a>
                    <a href="featured.php">
                      <span class="material-symbols-sharp">star</span>
                      <h3>Lançamentos</h3>
                    </a>
                    <a href="promos.php">
                      <span class="material-symbols-sharp">local_offer</span>
                      <h3>Promoções</h3>
                    </a>
                    <a href="testimonials.php">
                      <span class="material-symbols-sharp">format_quote</span>
                      <h3>Depoimentos</h3>
                    </a>
                    <a href="metrics.php">
                      <span class="material-symbols-sharp">speed</span>
                      <h3>Métricas</h3>
                    </a>
                    <a href="leagues.php" class="active">
                      <span class="material-symbols-sharp">emoji_events</span>
                      <h3>Ligas em Destaque</h3>
                    </a>
                  </div>
                </div>

                <div class="menu-item-container">
                  <a href="../geral.php" class="menu-item-with-submenu">
                      <span class="material-symbols-sharp">Settings</span>
                      <h3>Configurações</h3>
                  </a>
                  <div class="submenu">
                    <a href="../geral.php">
                      <span class="material-symbols-sharp">tune</span>
                      <h3>Geral</h3>
                    </a>
                    <a href="../pagamentos.php">
                      <span class="material-symbols-sharp">payments</span>
                      <h3>Pagamentos</h3>
                    </a>
                    <a href="../frete.php">
                      <span class="material-symbols-sharp">local_shipping</span>
                      <h3>Frete</h3>
                    </a>
                    <a href="../automacao.php">
                      <span class="material-symbols-sharp">automation</span>
                      <h3>Automação</h3>
                    </a>
                    <a href="../settings.php">
                      <span class="material-symbols-sharp">group</span>
                      <h3>Usuários</h3>
                    </a>
                  </div>
                </div>

                <a href="../revendedores.php">
                    <span class="material-symbols-sharp">handshake</span>
                    <h3>Revendedores</h3>
                </a>
                <a href="../../../../PHP/logout.php">
                    <span class="material-symbols-sharp">Logout</span>
                    <h3>Sair</h3>
                </a>
            </div>
        </aside>

        <!-- CONTEÚDO PRINCIPAL -->
        <main>
            <h1>CMS &rsaquo; Ligas em Destaque</h1>

            <div class="insights">
                <div class="sales" style="cursor:default;">
                    <span class="material-symbols-sharp">emoji_events</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Ligas Ativas</h3>
                            <h1><?php echo $totalAtivas; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="expenses" style="cursor:default;">
                    <span class="material-symbols-sharp">format_list_numbered</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total Cadastradas</h3>
                            <h1><?php echo $totalCadastradas; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="item" style="cursor:default;">
                    <span class="material-symbols-sharp">info</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Dica</h3>
                            <p style="font-size:.78rem;color:var(--color-dark-variant);margin-top:.3rem;">
                                O slug é usado para filtrar produtos.<br>
                                Cadastre o mesmo valor no campo Liga do produto.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="recent-orders" style="margin-top:2rem;">
                <div class="cms-leagues-toolbar">
                    <h2>Gerenciar Ligas</h2>
                    <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                        <button type="button" class="btn-add-league" onclick="adicionarLiga()">
                            <span class="material-symbols-sharp">add</span>
                            Nova Liga
                        </button>
                        <button type="button" class="btn-save-leagues" id="btn-salvar" onclick="salvarTodasLigas()">
                            <span class="material-symbols-sharp">save</span>
                            Salvar Alterações
                        </button>
                    </div>
                </div>

                <div class="cms-league-grid" id="ligas-container">
                    <?php foreach ($ligas as $liga): ?>
                    <?php
                        $logoResolvida = '';
                        $lp = trim((string)($liga['logo_path'] ?? ''));
                        if ($lp !== '') {
                            if (preg_match('/^https?:\/\//i', $lp)) {
                                $logoResolvida = $lp;
                            } else {
                                $logoResolvida = BASE_URL . ltrim($lp, '/');
                            }
                        }
                    ?>
                    <div class="cms-league-card<?php echo $liga['ativo'] ? '' : ' is-inactive'; ?>"
                         data-id="<?php echo (int)$liga['id']; ?>">

                        <div class="league-card-header">
                            <div class="league-logo-preview" id="preview-<?php echo (int)$liga['id']; ?>">
                                <?php if ($logoResolvida !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($logoResolvida); ?>"
                                         alt="<?php echo htmlspecialchars($liga['nome']); ?>"
                                         onerror="this.style.display='none';">
                                <?php else: ?>
                                    <span><?php echo htmlspecialchars($liga['sigla']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="league-card-title"><?php echo htmlspecialchars($liga['nome']); ?></div>
                                <div class="league-card-id">ID #<?php echo (int)$liga['id']; ?></div>
                            </div>
                        </div>

                        <div class="league-fields">
                            <div>
                                <label>Nome da Liga *</label>
                                <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][nome]"
                                       value="<?php echo htmlspecialchars($liga['nome']); ?>"
                                       placeholder="Ex: Premier League" required>
                            </div>
                            <div>
                                <label>Sigla *</label>
                                <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][sigla]"
                                       value="<?php echo htmlspecialchars($liga['sigla']); ?>"
                                       maxlength="10" placeholder="Ex: PL" required>
                            </div>
                            <div class="full">
                                <label>Slug (usado no filtro de produtos) *</label>
                                <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][slug]"
                                       value="<?php echo htmlspecialchars($liga['slug']); ?>"
                                       placeholder="Ex: premier-league">
                            </div>
                            <div class="full">
                                <label>Classe CSS de cor (opcional)</label>
                                <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][classe]"
                                       value="<?php echo htmlspecialchars($liga['classe'] ?? ''); ?>"
                                       placeholder="Ex: league-premier">
                            </div>
                            <div>
                                <label>Ordem</label>
                                <input type="number" name="ligas[<?php echo (int)$liga['id']; ?>][ordem]"
                                       value="<?php echo (int)$liga['ordem']; ?>" min="1">
                            </div>
                            <div style="display:flex;align-items:flex-end;">
                                <div class="league-toggle-row">
                                    <input class="cms-checkbox" type="checkbox"
                                           name="ligas[<?php echo (int)$liga['id']; ?>][ativo]"
                                           value="1" <?php echo $liga['ativo'] ? 'checked' : ''; ?>>
                                    <label class="league-toggle-label">Ativo</label>
                                </div>
                            </div>

                            <!-- Upload de logo -->
                            <div class="full">
                                <label>Logo da Liga (JPG, PNG, WEBP – máx 2MB)</label>
                                <div class="league-upload-row">
                                    <input type="file" accept="image/jpeg,image/png,image/webp"
                                           id="file-<?php echo (int)$liga['id']; ?>"
                                           onchange="uploadLogo(<?php echo (int)$liga['id']; ?>, this)">
                                    <button type="button" class="league-upload-btn"
                                            id="uploading-<?php echo (int)$liga['id']; ?>" disabled>
                                        <span class="material-symbols-sharp" style="font-size:1rem;vertical-align:middle;">upload</span>
                                        Enviando…
                                    </button>
                                </div>
                                <div class="league-upload-status" id="upload-status-<?php echo (int)$liga['id']; ?>">
                                    <?php echo $lp !== '' ? 'Logo atual: ' . htmlspecialchars($lp) : 'Sem logo – exibindo sigla'; ?>
                                </div>
                                <input type="hidden" name="ligas[<?php echo (int)$liga['id']; ?>][logo_path]"
                                       id="logo-path-<?php echo (int)$liga['id']; ?>"
                                       value="<?php echo htmlspecialchars($liga['logo_path'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="league-card-actions">
                            <button type="button" class="btn-delete-league"
                                    onclick="confirmarExclusao(<?php echo (int)$liga['id']; ?>, '<?php echo addslashes(htmlspecialchars($liga['nome'])); ?>')">
                                <span class="material-symbols-sharp" style="font-size:.95rem;">delete</span>
                                Excluir
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>

        <!-- RIGHT SECTION -->
        <div class="right">
            <div class="top">
                <button id="menu-btn">
                    <span class="material-symbols-sharp">menu</span>
                </button>
                <div class="theme-toggler">
                    <span class="material-symbols-sharp active">light_mode</span>
                    <span class="material-symbols-sharp">dark_mode</span>
                </div>
                <div class="profile">
                    <div class="info">
                        <p><?php echo htmlspecialchars($_SESSION['usuario_logado'] ?? 'Admin'); ?></p>
                        <small class="text-muted">Admin</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE EXCLUSÃO -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-modal-box">
            <h3>Excluir Liga</h3>
            <p id="deleteModalMsg">Tem certeza que deseja excluir esta liga?</p>
            <div class="delete-modal-actions">
                <button type="button" class="btn-cancel-delete" onclick="fecharModalExclusao()">Cancelar</button>
                <button type="button" class="btn-confirm-delete" id="btn-confirm-delete">Excluir</button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div class="toast" id="toast"></div>

    <script src="../../../js/dashboard.js"></script>
    <script>
    // ─── Contadores de novos cards ───────────────────────────────────────────
    let novaLigaIndex = 0;

    // ─── Toast ───────────────────────────────────────────────────────────────
    function showToast(msg, tipo = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast ' + tipo;
        t.style.display = 'block';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.style.display = 'none'; }, 4000);
    }

    // ─── Adicionar nova liga (card vazio) ────────────────────────────────────
    function adicionarLiga() {
        novaLigaIndex++;
        const key  = 'new_' + novaLigaIndex;
        const grid = document.getElementById('ligas-container');

        const card = document.createElement('div');
        card.className = 'cms-league-card is-new';
        card.dataset.id = key;
        card.innerHTML = `
            <div class="league-card-header">
                <div class="league-logo-preview" id="preview-${key}">
                    <span>?</span>
                </div>
                <div>
                    <div class="league-card-title">Nova Liga</div>
                    <div class="league-card-id">Ainda não salva</div>
                </div>
            </div>
            <div class="league-fields">
                <div>
                    <label>Nome da Liga *</label>
                    <input type="text" name="ligas[${key}][nome]" placeholder="Ex: Ligue 1" required>
                </div>
                <div>
                    <label>Sigla *</label>
                    <input type="text" name="ligas[${key}][sigla]" maxlength="10" placeholder="Ex: L1" required>
                </div>
                <div class="full">
                    <label>Slug (usado no filtro de produtos) *</label>
                    <input type="text" name="ligas[${key}][slug]" placeholder="Ex: ligue-1">
                </div>
                <div class="full">
                    <label>Classe CSS de cor (opcional)</label>
                    <input type="text" name="ligas[${key}][classe]" placeholder="Ex: league-ligue1">
                </div>
                <div>
                    <label>Ordem</label>
                    <input type="number" name="ligas[${key}][ordem]" value="${document.querySelectorAll('.cms-league-card').length + 1}" min="1">
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <div class="league-toggle-row">
                        <input class="cms-checkbox" type="checkbox" name="ligas[${key}][ativo]" value="1" checked>
                        <label class="league-toggle-label">Ativo</label>
                    </div>
                </div>
                <div class="full">
                    <label>Logo da Liga (JPG, PNG, WEBP – máx 2MB)</label>
                    <div class="league-upload-row">
                        <input type="file" accept="image/jpeg,image/png,image/webp"
                               id="file-${key}" onchange="uploadLogo('${key}', this)">
                        <button type="button" class="league-upload-btn"
                                id="uploading-${key}" disabled>
                            <span class="material-symbols-sharp" style="font-size:1rem;vertical-align:middle;">upload</span>
                            Enviando…
                        </button>
                    </div>
                    <div class="league-upload-status" id="upload-status-${key}">Sem logo – exibindo sigla</div>
                    <input type="hidden" name="ligas[${key}][logo_path]" id="logo-path-${key}" value="">
                </div>
            </div>
            <div class="league-card-actions">
                <button type="button" class="btn-delete-league" onclick="removerNovaLiga(this)">
                    <span class="material-symbols-sharp" style="font-size:.95rem;">delete</span>
                    Remover
                </button>
            </div>
        `;
        grid.appendChild(card);
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // ─── Remover card novo (ainda não salvo) ─────────────────────────────────
    function removerNovaLiga(btn) {
        btn.closest('.cms-league-card').remove();
    }

    // ─── Upload de logo via AJAX ──────────────────────────────────────────────
    function uploadLogo(id, input) {
        if (!input.files || !input.files[0]) return;

        const statusEl  = document.getElementById('upload-status-' + id);
        const pathInput = document.getElementById('logo-path-' + id);
        const preview   = document.getElementById('preview-' + id);
        const btnUpload = document.getElementById('uploading-' + id);

        btnUpload.disabled = false;
        btnUpload.textContent = 'Enviando…';

        const fd = new FormData();
        fd.append('action', 'upload_league_logo');
        fd.append('image', input.files[0]);

        fetch('cms_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    pathInput.value = data.path;
                    statusEl.textContent  = 'Logo salva: ' + data.path;
                    statusEl.style.color  = 'var(--color-success)';
                    // Atualizar preview
                    preview.innerHTML = `<img src="${data.url}" alt="logo" onerror="this.remove()">`;
                } else {
                    statusEl.textContent = 'Erro: ' + data.message;
                    statusEl.style.color = 'var(--color-danger)';
                }
                btnUpload.disabled = true;
                btnUpload.textContent = 'Enviando…';
            })
            .catch(() => {
                statusEl.textContent = 'Falha na conexão.';
                statusEl.style.color = 'var(--color-danger)';
                btnUpload.disabled = true;
            });
    }

    // ─── Salvar todas as ligas ────────────────────────────────────────────────
    function salvarTodasLigas() {
        const btn = document.getElementById('btn-salvar');
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-sharp">hourglass_empty</span> Salvando…';

        const cards = document.querySelectorAll('.cms-league-card');
        const fd    = new FormData();
        fd.append('action', 'update_home_leagues');

        cards.forEach(card => {
            card.querySelectorAll('input[name]').forEach(inp => {
                if (inp.type === 'checkbox') {
                    if (inp.checked) fd.append(inp.name, inp.value);
                } else {
                    fd.append(inp.name, inp.value);
                }
            });
        });

        fetch('cms_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Salvo com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.message || 'Erro ao salvar.', 'error');
                }
            })
            .catch(() => showToast('Falha na conexão.', 'error'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-sharp">save</span> Salvar Alterações';
            });
    }

    // ─── Confirmar exclusão ───────────────────────────────────────────────────
    let _pendingDeleteId = null;

    function confirmarExclusao(id, nome) {
        _pendingDeleteId = id;
        document.getElementById('deleteModalMsg').textContent =
            `Tem certeza que deseja excluir a liga "${nome}"? Esta ação não pode ser desfeita.`;
        document.getElementById('deleteModal').classList.add('open');
    }

    function fecharModalExclusao() {
        _pendingDeleteId = null;
        document.getElementById('deleteModal').classList.remove('open');
    }

    document.getElementById('btn-confirm-delete').addEventListener('click', function () {
        if (!_pendingDeleteId) return;
        const fd = new FormData();
        fd.append('action', 'delete_home_league');
        fd.append('id', _pendingDeleteId);

        fetch('cms_api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                fecharModalExclusao();
                if (data.success) {
                    showToast('Liga excluída com sucesso.', 'success');
                    document.querySelector(`.cms-league-card[data-id="${_pendingDeleteId}"]`)?.remove();
                } else {
                    showToast(data.message || 'Erro ao excluir.', 'error');
                }
            })
            .catch(() => { fecharModalExclusao(); showToast('Falha na conexão.', 'error'); });
    });

    // Fechar modal ao clicar fora
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) fecharModalExclusao();
    });
    </script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../../../../PHP/login.php');
    exit();
}

require_once '../../../../config/base.php';
require_once '../../../../PHP/conexao.php';
require_once '../helper-contador.php';

// Garantir que $nao_lidas existe
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

// Buscar configurações atuais
$settings_sql = "SELECT * FROM home_settings WHERE id = 1";
$settings_result = mysqli_query($conexao, $settings_sql);
$settings = mysqli_fetch_assoc($settings_result);

// Se não existir, criar registro padrão
if (!$settings) {
    $insert_sql = "INSERT INTO home_settings (id) VALUES (1)";
    mysqli_query($conexao, $insert_sql);
    $settings_result = mysqli_query($conexao, $settings_sql);
    $settings = mysqli_fetch_assoc($settings_result);
}

// Garantir os 4 cards padrão de benefícios para edição fixa no painel.
$beneficiosPadrao = [
    1 => ['titulo' => 'Entrega Grátis', 'subtitulo' => 'Acima de R$ 99', 'icone' => 'local_shipping', 'cor' => '#C6A75E'],
    2 => ['titulo' => 'Qualidade Premium', 'subtitulo' => 'Produtos originais', 'icone' => 'verified', 'cor' => '#C6A75E'],
    3 => ['titulo' => 'Troca Fácil', 'subtitulo' => 'Em até 30 dias', 'icone' => 'sync', 'cor' => '#C6A75E'],
    4 => ['titulo' => 'Suporte 24h', 'subtitulo' => 'Sempre disponível', 'icone' => 'support_agent', 'cor' => '#C6A75E']
];

foreach ($beneficiosPadrao as $ordemPadrao => $card) {
    $stmtCheck = mysqli_prepare($conexao, "SELECT id FROM cms_home_beneficios WHERE ordem = ? LIMIT 1");
    if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, 'i', $ordemPadrao);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);
        $exists = $resCheck && mysqli_num_rows($resCheck) > 0;
        mysqli_stmt_close($stmtCheck);

        if (!$exists) {
            $ativo = 1;
            $stmtInsert = mysqli_prepare(
                $conexao,
                "INSERT INTO cms_home_beneficios (titulo, subtitulo, icone, cor, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?)"
            );
            if ($stmtInsert) {
                mysqli_stmt_bind_param(
                    $stmtInsert,
                    'ssssii',
                    $card['titulo'],
                    $card['subtitulo'],
                    $card['icone'],
                    $card['cor'],
                    $ordemPadrao,
                    $ativo
                );
                mysqli_stmt_execute($stmtInsert);
                mysqli_stmt_close($stmtInsert);
            }
        }
    }
}

// Buscar benefícios padrão (fixos) para edição no painel.
$beneficios_sql = "SELECT * FROM cms_home_beneficios WHERE ordem BETWEEN 1 AND 4 ORDER BY ordem ASC, id ASC";
$beneficios_result = mysqli_query($conexao, $beneficios_sql);
$beneficios = [];
while ($row = mysqli_fetch_assoc($beneficios_result)) {
    $beneficios[] = $row;
}

// Garantir tabela de clubes em destaque
$createClubsTableSql = "
    CREATE TABLE IF NOT EXISTS cms_home_clubs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        sigla VARCHAR(20) NOT NULL,
        imagem_path VARCHAR(255) NULL,
        ordem INT NOT NULL DEFAULT 1,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cms_home_clubs_ordem_ativo (ativo, ordem)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
mysqli_query($conexao, $createClubsTableSql);

// Seed inicial de clubes se tabela estiver vazia
$clubsCountResult = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM cms_home_clubs");
$clubsCountData = $clubsCountResult ? mysqli_fetch_assoc($clubsCountResult) : ['total' => 0];
$clubsTotal = (int)($clubsCountData['total'] ?? 0);

if ($clubsTotal === 0) {
    $clubesPadrao = [
        ['Real Madrid', 'RMA', '', 1, 1],
        ['Barcelona', 'BAR', '', 2, 1],
        ['Manchester City', 'MCI', '', 3, 1],
        ['Bayern', 'BAY', '', 4, 1],
        ['PSG', 'PSG', '', 5, 1],
        ['Milan', 'MIL', '', 6, 1],
        ['Benfica', 'BEN', '', 7, 1],
        ['Inter', 'INT', '', 8, 1],
        ['Liverpool', 'LIV', '', 9, 1],
        ['Juventus', 'JUV', '', 10, 1]
    ];

    $insertClubStmt = mysqli_prepare(
        $conexao,
        "INSERT INTO cms_home_clubs (nome, sigla, imagem_path, ordem, ativo) VALUES (?, ?, ?, ?, ?)"
    );

    if ($insertClubStmt) {
        foreach ($clubesPadrao as $club) {
            mysqli_stmt_bind_param($insertClubStmt, 'sssii', $club[0], $club[1], $club[2], $club[3], $club[4]);
            mysqli_stmt_execute($insertClubStmt);
        }
        mysqli_stmt_close($insertClubStmt);
    }
}

// Buscar clubes para edição
$clubes = [];
$clubesResult = mysqli_query($conexao, "SELECT * FROM cms_home_clubs ORDER BY ordem ASC, id ASC");
if ($clubesResult) {
    while ($row = mysqli_fetch_assoc($clubesResult)) {
        $clubes[] = $row;
    }
}

// Garantir tabela de ligas em destaque
$createLeaguesTableSql = "
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
";
mysqli_query($conexao, $createLeaguesTableSql);

// Compatibilidade com bancos antigos
$checkLeagueSlug = mysqli_query($conexao, "SHOW COLUMNS FROM cms_home_leagues LIKE 'slug'");
if ($checkLeagueSlug && mysqli_num_rows($checkLeagueSlug) === 0) {
    mysqli_query($conexao, "ALTER TABLE cms_home_leagues ADD COLUMN slug VARCHAR(120) NOT NULL DEFAULT '' AFTER nome");
}

$checkLeagueClass = mysqli_query($conexao, "SHOW COLUMNS FROM cms_home_leagues LIKE 'classe'");
if ($checkLeagueClass && mysqli_num_rows($checkLeagueClass) === 0) {
    mysqli_query($conexao, "ALTER TABLE cms_home_leagues ADD COLUMN classe VARCHAR(60) NOT NULL DEFAULT '' AFTER sigla");
}

// Seed inicial se tabela estiver vazia
$leaguesCountResult = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM cms_home_leagues");
$leaguesCountData = $leaguesCountResult ? mysqli_fetch_assoc($leaguesCountResult) : ['total' => 0];
$leaguesTotal = (int)($leaguesCountData['total'] ?? 0);

if ($leaguesTotal === 0) {
    $ligasPadrao = [
        ['Premier League', 'premier-league', 'PL', 'league-premier', 1, 1],
        ['La Liga', 'la-liga', 'LL', 'league-laliga', 2, 1],
        ['Brasileirão', 'brasileirao', 'BR', 'league-brasileirao', 3, 1],
        ['Serie A', 'serie-a', 'SA', 'league-seriea', 4, 1],
        ['Bundesliga', 'bundesliga', 'BL', 'league-bundesliga', 5, 1],
        ['Champions League', 'champions-league', 'UCL', 'league-champions', 6, 1]
    ];

    $insertLeagueStmt = mysqli_prepare(
        $conexao,
        "INSERT INTO cms_home_leagues (nome, slug, sigla, classe, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?)"
    );

    if ($insertLeagueStmt) {
        foreach ($ligasPadrao as $liga) {
            mysqli_stmt_bind_param($insertLeagueStmt, 'ssssii', $liga[0], $liga[1], $liga[2], $liga[3], $liga[4], $liga[5]);
            mysqli_stmt_execute($insertLeagueStmt);
        }
        mysqli_stmt_close($insertLeagueStmt);
    }
}

// Buscar ligas para edição
$ligas = [];
$ligasResult = mysqli_query($conexao, "SELECT * FROM cms_home_leagues ORDER BY ordem ASC, id ASC");
if ($ligasResult) {
    while ($row = mysqli_fetch_assoc($ligasResult)) {
        $ligas[] = $row;
    }
}

// Buscar dados do footer
$footer_sql = "SELECT * FROM cms_footer WHERE id = 1";
$footer_result = mysqli_query($conexao, $footer_sql);
$footer = mysqli_fetch_assoc($footer_result);

if (!$footer) {
    mysqli_query($conexao, "INSERT INTO cms_footer (id) VALUES (1)");
    $footer_result = mysqli_query($conexao, $footer_sql);
    $footer = mysqli_fetch_assoc($footer_result);
}

// Buscar links do footer
$footer_links_sql = "SELECT * FROM cms_footer_links WHERE ativo = 1 ORDER BY coluna, ordem ASC";
$footer_links_result = mysqli_query($conexao, $footer_links_sql);
$footer_links = ['produtos' => [], 'atendimento' => []];
while ($row = mysqli_fetch_assoc($footer_links_result)) {
    $footer_links[$row['coluna']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS - Home (Textos) | Rare7 Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <link rel="stylesheet" href="../../../css/dashboard-sections.css">
    <link rel="stylesheet" href="../../../css/dashboard-cards.css">
    <style>
        /* Espaçamento entre seções principais */
        main h1 {
            margin-bottom: 2rem;
        }
        
        main .insights {
            margin-bottom: 2.5rem;
        }
        
        main form {
            margin-bottom: 3rem;
        }
        
        .settings-card {
            background: var(--color-white);
            border-radius: var(--border-radius-2);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .settings-card h3 {
            margin-bottom: 1.5rem;
            color: var(--color-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-group {
            margin-bottom: 2rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--color-dark);
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--color-light);
            border-radius: var(--border-radius-1);
            font-family: inherit;
            font-size: 0.95rem;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group small {
            display: block;
            margin-top: 0.3rem;
            color: var(--color-dark-variant);
        }
        .cms-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            cursor: pointer;
            user-select: none;
        }
        .cms-checkbox {
            appearance: auto !important;
            -webkit-appearance: checkbox !important;
            width: 16px;
            height: 16px;
            margin: 0;
            border: initial !important;
            accent-color: var(--color-primary);
            cursor: pointer;
        }
        .btn-save {
            background: var(--color-primary);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .btn-save:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .success-msg {
            background: var(--color-success);
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius-1);
            margin-bottom: 1.5rem;
            display: none;
        }
        .success-msg.show {
            display: block;
        }
        .clubs-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.25rem;
        }
        .clubs-toolbar-info {
            color: var(--color-dark-variant);
            font-size: 0.9rem;
        }
        .clubs-toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
        }
        .clubs-action-button {
            border: none;
            border-radius: 16px;
            cursor: pointer;
            font-size: 0.92rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            padding: 0.95rem 1.4rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease;
        }
        .clubs-action-button .material-symbols-sharp {
            font-size: 1.15rem;
        }
        .clubs-action-button:hover {
            transform: translateY(-2px);
        }
        .clubs-action-button.secondary {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            color: #1f2937;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.35), 0 10px 24px rgba(15, 23, 42, 0.06);
        }
        .clubs-action-button.secondary:hover {
            box-shadow: inset 0 0 0 1px rgba(100, 116, 139, 0.45), 0 14px 28px rgba(15, 23, 42, 0.1);
        }
        .clubs-action-button.primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, #b8913e 100%);
            color: #fff;
            box-shadow: 0 14px 28px rgba(198, 167, 94, 0.28);
        }
        .clubs-action-button.primary:hover {
            box-shadow: 0 18px 34px rgba(198, 167, 94, 0.36);
        }
        .cms-clubes-grid {
            display: grid;
            gap: 1rem;
        }
        .cms-clube-item {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 20px;
            padding: 1.2rem;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }
        .cms-clube-item.is-new {
            border-style: dashed;
            border-color: rgba(198, 167, 94, 0.5);
            background: linear-gradient(180deg, rgba(198, 167, 94, 0.08) 0%, rgba(255, 255, 255, 1) 100%);
        }
        .cms-clube-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .cms-clube-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }
        .cms-clube-badge {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(198, 167, 94, 0.14);
            color: var(--color-primary);
        }
        .cms-clube-badge .material-symbols-sharp {
            font-size: 1.2rem;
        }
        .cms-clube-title strong {
            display: block;
            font-size: 0.98rem;
            color: var(--color-dark);
        }
        .cms-clube-title span {
            display: block;
            font-size: 0.82rem;
            color: var(--color-dark-variant);
        }
        .cms-clube-remove {
            background: #fff;
            color: #b42318;
            box-shadow: inset 0 0 0 1px rgba(180, 35, 24, 0.18);
            padding: 0.7rem 1rem;
        }
        .cms-clube-fields {
            display: grid;
            grid-template-columns: 1.4fr 0.7fr 2fr 0.6fr 0.7fr;
            gap: 0.9rem;
            align-items: end;
        }
        .cms-clube-item .form-group {
            margin: 0;
        }
        .cms-clube-item .form-group label {
            font-size: 0.82rem;
            margin-bottom: 0.35rem;
        }
        .cms-clube-item .form-group .cms-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0;
            padding-top: 0.8rem;
            font-weight: 500;
            width: auto;
        }
        .cms-clube-item .form-group input {
            background: #fff;
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 12px;
            min-height: 46px;
        }
        .cms-clube-item .form-group .cms-checkbox {
            width: 18px;
            height: 18px;
            min-height: 18px;
            flex: 0 0 18px;
        }
        .cms-clube-item .form-group input:focus {
            outline: none;
            border-color: rgba(198, 167, 94, 0.75);
            box-shadow: 0 0 0 4px rgba(198, 167, 94, 0.12);
        }

        .cms-league-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .cms-league-card {
            background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            padding: 1rem;
        }
        .cms-league-card.is-new {
            border-style: dashed;
            border-color: rgba(198, 167, 94, 0.6);
        }
        .league-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.9rem;
        }
        .league-logo-preview {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid var(--color-light);
            background: var(--color-background);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .league-logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .league-logo-preview span {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--color-dark-variant);
        }
        .league-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--color-dark);
        }
        .league-card-id {
            font-size: 0.75rem;
            color: var(--color-dark-variant);
        }
        .league-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .league-fields .full {
            grid-column: 1 / -1;
        }
        .league-fields .form-group {
            margin: 0;
        }
        .league-fields .form-group label {
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }
        .league-fields .form-group input[type="text"],
        .league-fields .form-group input[type="number"] {
            min-height: 42px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.32);
            background: #fff;
            padding: 0.6rem 0.7rem;
            font-size: 0.86rem;
        }
        .league-upload-row {
            display: flex;
            gap: 0.6rem;
            align-items: center;
        }
        .league-upload-row input[type="file"] {
            flex: 1;
            font-size: 0.8rem;
        }
        .league-upload-btn {
            border: none;
            border-radius: 10px;
            background: var(--color-primary);
            color: #fff;
            padding: 0.6rem 0.9rem;
            font-size: 0.8rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .league-upload-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .league-upload-status {
            font-size: 0.75rem;
            color: var(--color-dark-variant);
            margin-top: 0.35rem;
        }
        .league-card-actions {
            margin-top: 0.9rem;
        }
        .btn-delete-league {
            border: 1px solid var(--color-danger);
            background: transparent;
            color: var(--color-danger);
            border-radius: 10px;
            padding: 0.55rem 0.85rem;
            font-size: 0.82rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        @media (max-width: 1100px) {
            .cms-clube-fields {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 700px) {
            .clubs-toolbar {
                align-items: stretch;
            }
            .clubs-toolbar-actions {
                width: 100%;
                flex-direction: column;
            }
            .clubs-action-button {
                width: 100%;
            }
            .cms-clube-header {
                flex-direction: column;
                align-items: stretch;
            }
            .cms-clube-fields {
                grid-template-columns: 1fr;
            }
            .league-fields {
                grid-template-columns: 1fr;
            }
            .league-fields .full {
                grid-column: auto;
            }
        }
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
                  <a href="home.php" class="menu-item-with-submenu">
                      <span class="material-symbols-sharp">web</span>
                      <h3>CMS</h3>
                  </a>
                  
                  <div class="submenu">
                    <a href="home.php" class="active">
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
                    <a href="../metricas.php">
                      <span class="material-symbols-sharp">analytics</span>
                      <h3>Métricas</h3>
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

        <!-- CONTE�sDO PRINCIPAL -->
        <main>
            <h1>CMS > Home (Textos e Configurações)</h1>

            <div class="insights">
                <div class="sales" style="cursor: default;">
                    <span class="material-symbols-sharp">edit_note</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Seção Hero</h3>
                            <small class="text-muted">Banner principal</small>
                        </div>
                    </div>
                </div>
                <div class="expenses" style="cursor: default;">
                    <span class="material-symbols-sharp">star</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Lançamentos</h3>
                            <small class="text-muted">Produtos em destaque</small>
                        </div>
                    </div>
                </div>
                <div class="income" style="cursor: default;">
                    <span class="material-symbols-sharp">update</span>
                    <div class="middle">
                        <div class="left">
                            <h3>�sltima Atualização</h3>
                            <small class="text-muted"><?php echo $settings['updated_at'] ? date('d/m/Y H:i', strtotime($settings['updated_at'])) : 'Nunca'; ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div id="successMsg" class="success-msg">Configurações salvas com sucesso!</div>

            <form id="homeSettingsForm">
                <!-- SE�?�fO HERO -->
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">sentiment_very_satisfied</span>
                        Seção Hero (Banner Principal)
                    </h3>

                    <div class="form-group">
                        <label>Texto Superior (Kicker)</label>
                        <input type="text" name="hero_kicker" value="<?php echo htmlspecialchars($settings['hero_kicker'] ?? 'RARE EXPERIENCE'); ?>">
                        <small>Texto pequeno acima do título principal (ex: RARE EXPERIENCE)</small>
                    </div>

                    <div class="form-group">
                        <label>Caminho da Logo do Hero</label>
                        <input type="text" name="hero_logo_path" value="<?php echo htmlspecialchars($settings['hero_logo_path'] ?? 'assets/images/logo.png'); ?>">
                        <small>Caminho relativo da imagem exibida acima do título (ex: assets/images/logo.png)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Título Principal *</label>
                        <input type="text" name="hero_title" value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Bem-vindo à Rare7'); ?>" required>
                        <small>Título principal que aparece no topo da página</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Subtítulo</label>
                        <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($settings['hero_subtitle'] ?? 'Moda e Estilo'); ?>">
                        <small>Texto secundário abaixo do título</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="hero_description"><?php echo htmlspecialchars($settings['hero_description'] ?? 'Descubra as últimas tendências em moda'); ?></textarea>
                        <small>Texto descritivo adicional</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Texto do Botão</label>
                        <input type="text" name="hero_button_text" value="<?php echo htmlspecialchars($settings['hero_button_text'] ?? 'Ver Produtos'); ?>">
                        <small>Texto exibido no botão de ação</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Link do Botão</label>
                        <input type="text" name="hero_button_link" value="<?php echo htmlspecialchars($settings['hero_button_link'] ?? '/produtos'); ?>">
                        <small>URL para onde o botão redireciona</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Intervalo do Carrossel (segundos)</label>
                        <input type="number" name="banner_interval" min="3" max="30" step="1" value="<?php echo htmlspecialchars($settings['banner_interval'] ?? '6'); ?>">
                        <small>Tempo em segundos entre cada troca de banner (3-30 segundos)</small>
                    </div>
                </div>

                <!-- SE�?�fO LAN�?AMENTOS -->
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">new_releases</span>
                        Seção de Lançamentos
                    </h3>
                    
                    <div class="form-group">
                        <label>Título da Seção *</label>
                        <input type="text" name="launch_title" value="<?php echo htmlspecialchars($settings['launch_title'] ?? 'Lançamentos'); ?>" required>
                        <small>Título da seção de produtos em destaque</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Subtítulo</label>
                        <input type="text" name="launch_subtitle" value="<?php echo htmlspecialchars($settings['launch_subtitle'] ?? 'Novidades que acabaram de chegar'); ?>">
                        <small>Descrição da seção de lançamentos</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Texto do Botão</label>
                        <input type="text" name="launch_button_text" value="<?php echo htmlspecialchars($settings['launch_button_text'] ?? 'Ver Todos os Lançamentos'); ?>">
                        <small>Texto que aparece no botão</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Link do Botão</label>
                        <input type="text" name="launch_button_link" value="<?php echo htmlspecialchars($settings['launch_button_link'] ?? '#catalogo'); ?>">
                        <small>URL ou âncora (#catalogo, #produtos, etc)</small>
                    </div>
                </div>

                <!-- SEÇÃO BENEFÍCIOS -->
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">verified</span>
                        Seção Benefícios (Cabeçalho)
                    </h3>

                    <div class="form-group">
                        <label>Título da Seção *</label>
                        <input type="text" name="benefits_title" value="<?php echo htmlspecialchars($settings['benefits_title'] ?? 'Beneficios Rare'); ?>" required>
                        <small>Título exibido acima dos cards de benefícios</small>
                    </div>

                    <div class="form-group">
                        <label>Descrição/Subtítulo</label>
                        <input type="text" name="benefits_subtitle" value="<?php echo htmlspecialchars($settings['benefits_subtitle'] ?? 'Acabamento premium e experiencia de compra refinada.'); ?>">
                        <small>Texto exibido abaixo do título da seção</small>
                    </div>
                </div>

                <!-- SE�?�fO TODOS OS PRODUTOS -->
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">inventory_2</span>
                        Seção Todos os Produtos
                    </h3>
                    
                    <div class="form-group">
                        <label>Título da Seção *</label>
                        <input type="text" name="products_title" value="<?php echo htmlspecialchars($settings['products_title'] ?? 'Todos os Produtos'); ?>" required>
                        <small>Título da seção catálogo completo</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Subtítulo</label>
                        <input type="text" name="products_subtitle" value="<?php echo htmlspecialchars($settings['products_subtitle'] ?? 'Toda a nossa coleção premium em um só lugar'); ?>">
                        <small>Descrição da seção</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Texto do Botão</label>
                        <input type="text" name="products_button_text" value="<?php echo htmlspecialchars($settings['products_button_text'] ?? 'Ver Depoimentos'); ?>">
                        <small>Texto que aparece no botão</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Link do Botão</label>
                        <input type="text" name="products_button_link" value="<?php echo htmlspecialchars($settings['products_button_link'] ?? '#depoimentos'); ?>">
                        <small>URL ou âncora (#depoimentos, etc)</small>
                    </div>
                    
                    <button type="submit" class="btn-save">
                        <span class="material-symbols-sharp">save</span>
                        Salvar Configurações da Home
                    </button>
                </div>

                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">verified</span>
                        Cards de Benefícios
                    </h3>
                    <p style="margin-bottom: 1.5rem; color: var(--color-dark-variant);">
                        Os cards de benefícios exibidos abaixo do banner principal.
                    </p>
                    
                    <form id="formBeneficios" style="margin-bottom: 2rem;">
                        <div id="beneficios-container">
                            <?php foreach ($beneficios as $idx => $b): ?>
                            <div class="beneficio-item" style="background: var(--color-light); padding: 1.5rem; border-radius: var(--border-radius-1); margin-bottom: 1rem; position: relative;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <strong style="color: var(--color-dark);">Card #<?php echo $b['ordem']; ?></strong>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <label class="cms-checkbox-label" style="margin: 0;">
                                            <input class="cms-checkbox" type="checkbox" name="beneficios[<?php echo $b['id']; ?>][ativo]" value="1" <?php echo $b['ativo'] ? 'checked' : ''; ?>>
                                            <span style="font-size: 0.875rem;">Ativo</span>
                                        </label>
                                        <input type="hidden" name="beneficios[<?php echo $b['id']; ?>][id]" value="<?php echo $b['id']; ?>">
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group" style="margin: 0;">
                                        <label style="font-size: 0.875rem; margin-bottom: 0.3rem;">Título</label>
                                        <input type="text" name="beneficios[<?php echo $b['id']; ?>][titulo]" value="<?php echo htmlspecialchars($b['titulo']); ?>" required style="width: 100%;">
                                    </div>
                                    
                                    <div class="form-group" style="margin: 0;">
                                        <label style="font-size: 0.875rem; margin-bottom: 0.3rem;">Ícone (material-symbols-sharp)</label>
                                        <input type="text" name="beneficios[<?php echo $b['id']; ?>][icone]" value="<?php echo htmlspecialchars($b['icone']); ?>" required style="width: 100%;">
                                        <small>Ex: local_shipping, verified, sync, support_agent</small>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                                    <div class="form-group" style="margin: 0;">
                                        <label style="font-size: 0.875rem; margin-bottom: 0.3rem;">Subtítulo/Descrição</label>
                                        <input type="text" name="beneficios[<?php echo $b['id']; ?>][subtitulo]" value="<?php echo htmlspecialchars($b['subtitulo']); ?>" required style="width: 100%;">
                                    </div>
                                    
                                    <div class="form-group" style="margin: 0;">
                                        <label style="font-size: 0.875rem; margin-bottom: 0.3rem;">Cor (hex)</label>
                                        <input type="color" name="beneficios[<?php echo $b['id']; ?>][cor]" value="<?php echo htmlspecialchars($b['cor']); ?>" style="width: 100%; height: 38px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin: 0;">
                                        <label style="font-size: 0.875rem; margin-bottom: 0.3rem;">Ordem</label>
                                        <input type="number" name="beneficios[<?php echo $b['id']; ?>][ordem]" value="<?php echo $b['ordem']; ?>" min="1" required style="width: 100%;">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" onclick="salvarBeneficios()" class="btn-primary" style="margin-top: 1rem;">
                            <span class="material-symbols-sharp">save</span>
                            Salvar Alterações nos Cards
                        </button>
                    </form>
                </div>

                <!-- CLUBES EM DESTAQUE -->
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">sports_soccer</span>
                        Clubes em Destaque (Escudos)
                    </h3>
                    <p style="margin-bottom: 1.5rem; color: var(--color-dark-variant);">
                        Cadastre os clubes que aparecem na faixa da home. Para mostrar escudo, informe o caminho da imagem.
                    </p>

                    <form id="formClubes" style="margin-bottom: 1rem;">
                        <div id="clubes-container" class="cms-clubes-grid">
                            <?php foreach ($clubes as $clube): ?>
                            <div class="cms-clube-item">
                                <div class="cms-clube-header">
                                    <div class="cms-clube-title">
                                        <div class="cms-clube-badge">
                                            <span class="material-symbols-sharp">sports_soccer</span>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($clube['nome']); ?></strong>
                                            <span>Clube cadastrado para exibição na home</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="cms-clube-fields">
                                    <div class="form-group">
                                        <label>Nome</label>
                                        <input type="text" name="clubes[<?php echo $clube['id']; ?>][nome]" value="<?php echo htmlspecialchars($clube['nome']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Sigla</label>
                                        <input type="text" name="clubes[<?php echo $clube['id']; ?>][sigla]" value="<?php echo htmlspecialchars($clube['sigla']); ?>" maxlength="20" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Imagem do escudo</label>
                                        <input type="text" name="clubes[<?php echo $clube['id']; ?>][imagem_path]" value="<?php echo htmlspecialchars($clube['imagem_path'] ?? ''); ?>" placeholder="Ex: assets/images/escudos/real-madrid.png">
                                    </div>

                                    <div class="form-group">
                                        <label>Ordem</label>
                                        <input type="number" name="clubes[<?php echo $clube['id']; ?>][ordem]" value="<?php echo (int)$clube['ordem']; ?>" min="1" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Ativo</label>
                                        <label class="cms-checkbox-label">
                                            <input class="cms-checkbox" type="checkbox" name="clubes[<?php echo $clube['id']; ?>][ativo]" value="1" <?php echo ((int)$clube['ativo'] === 1) ? 'checked' : ''; ?>>
                                            <span style="font-size: 0.85rem;">Sim</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="clubs-toolbar">
                            <div class="clubs-toolbar-info">Adicione, reorganize e ative apenas os clubes que devem aparecer na faixa da home.</div>
                            <div class="clubs-toolbar-actions">
                                <button type="button" onclick="adicionarClube()" class="clubs-action-button secondary">
                                    <span class="material-symbols-sharp">add_circle</span>
                                    Adicionar clube
                                </button>

                                <button type="button" onclick="salvarClubes()" class="clubs-action-button primary">
                                    <span class="material-symbols-sharp">save</span>
                                    Salvar Clubes em Destaque
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- LIGAS EM DESTAQUE -->
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">emoji_events</span>
                        Ligas em Destaque (Cards)
                    </h3>
                    <p style="margin-bottom: 1.25rem; color: var(--color-dark-variant);">
                        Edite aqui os cards da seção de ligas da home: nome, sigla, slug, classe, ordem, ativo e logo.
                    </p>

                    <form id="formLigas" style="margin-bottom: 0;">
                        <div class="clubs-toolbar" style="margin-top: 0; margin-bottom: 1rem;">
                            <div class="clubs-toolbar-info">O slug deve ser o mesmo cadastrado no campo Liga dos produtos para o filtro funcionar.</div>
                            <div class="clubs-toolbar-actions">
                                <button type="button" onclick="adicionarLiga()" class="clubs-action-button secondary">
                                    <span class="material-symbols-sharp">add_circle</span>
                                    Adicionar liga
                                </button>
                                <button type="button" onclick="salvarLigasHome()" class="clubs-action-button primary" id="btnSalvarLigasHome">
                                    <span class="material-symbols-sharp">save</span>
                                    Salvar Ligas em Destaque
                                </button>
                            </div>
                        </div>

                        <div class="cms-league-grid" id="ligas-container-home">
                            <?php foreach ($ligas as $liga): ?>
                            <?php
                                $logoResolvida = '';
                                $lp = trim((string)($liga['logo_path'] ?? ''));
                                if ($lp !== '') {
                                    if (preg_match('/^https?:\/\//i', $lp)) {
                                        $logoResolvida = $lp;
                                    } else {
                                        $normalizedLogoPath = ltrim($lp, '/');
                                        if (strpos($normalizedLogoPath, '/') === false) {
                                            $normalizedLogoPath = 'image/' . $normalizedLogoPath;
                                        }
                                        $logoResolvida = BASE_URL . $normalizedLogoPath;
                                    }
                                }
                            ?>
                            <div class="cms-league-card" data-id="<?php echo (int)$liga['id']; ?>">
                                <div class="league-card-header">
                                    <div class="league-logo-preview" id="preview-home-<?php echo (int)$liga['id']; ?>">
                                        <?php if ($logoResolvida !== ''): ?>
                                            <img src="<?php echo htmlspecialchars($logoResolvida); ?>" alt="<?php echo htmlspecialchars($liga['nome']); ?>" onerror="this.style.display='none';">
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
                                    <div class="form-group">
                                        <label>Nome *</label>
                                        <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][nome]" value="<?php echo htmlspecialchars($liga['nome']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Sigla *</label>
                                        <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][sigla]" value="<?php echo htmlspecialchars($liga['sigla']); ?>" maxlength="10" required>
                                    </div>

                                    <div class="form-group full">
                                        <label>Slug *</label>
                                        <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][slug]" value="<?php echo htmlspecialchars($liga['slug']); ?>" required>
                                    </div>

                                    <div class="form-group full">
                                        <label>Classe CSS (opcional)</label>
                                        <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][classe]" value="<?php echo htmlspecialchars($liga['classe'] ?? ''); ?>" placeholder="Ex: league-premier">
                                    </div>

                                    <div class="form-group">
                                        <label>Ordem</label>
                                        <input type="number" name="ligas[<?php echo (int)$liga['id']; ?>][ordem]" value="<?php echo (int)$liga['ordem']; ?>" min="1">
                                    </div>

                                    <div class="form-group" style="display: flex; align-items: flex-end;">
                                        <label class="cms-checkbox-label" style="margin-bottom: 0.55rem;">
                                            <input class="cms-checkbox" type="checkbox" name="ligas[<?php echo (int)$liga['id']; ?>][ativo]" value="1" <?php echo ((int)$liga['ativo'] === 1) ? 'checked' : ''; ?>>
                                            <span style="font-size: 0.86rem;">Ativo</span>
                                        </label>
                                    </div>

                                    <div class="form-group full" style="margin-top: 0.1rem;">
                                        <label>Caminho da logo</label>
                                        <input type="text" name="ligas[<?php echo (int)$liga['id']; ?>][logo_path]" value="<?php echo htmlspecialchars($liga['logo_path'] ?? ''); ?>" placeholder="Ex: image/premier.png">
                                        <small>Use o caminho da imagem igual ao padrão dos clubes em destaque.</small>
                                    </div>
                                </div>

                                <div class="league-card-actions">
                                    <button type="button" class="btn-delete-league" onclick="excluirLigaHome(<?php echo (int)$liga['id']; ?>, '<?php echo addslashes(htmlspecialchars($liga['nome'])); ?>', this)">
                                        <span class="material-symbols-sharp" style="font-size:.95rem;">delete</span>
                                        Excluir
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                
                <script>
                function salvarBeneficios() {
                    console.log('�Y"� Iniciando salvarBeneficios()');
                    const form = document.getElementById('formBeneficios');
                    const formData = new FormData(form);
                    formData.append('action', 'update_benefits');
                    
                    // Debug: mostrar dados enviados
                    console.log('�Y"� Dados do formulário:');
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                    
                    // Adicionar valores não-checkados como 0
                    const checkboxes = form.querySelectorAll('input[type="checkbox"][name*="[ativo]"]');
                    checkboxes.forEach(cb => {
                        if (!cb.checked) {
                            const name = cb.name;
                            formData.set(name, '0');
                            console.log('Checkbox desmarcado:', name, '=> 0');
                        }
                    });
                    
                    console.log('�YO� Enviando requisição para cms_api.php...');
                    
                    fetch('cms_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('�Y"� Resposta recebida. Status:', response.status);
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('�o. Dados JSON:', data);
                        if (data.success) {
                            alert('�o. Cards salvos com sucesso!');
                            location.reload();
                        } else {
                            console.error('�O Erro do servidor:', data);
                            alert('�O Erro: ' + (data.message || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisição:', error);
                        alert('�O Erro na comunicação: ' + error.message);
                    });
                }

                let novoClubeIndex = 0;

                function obterProximaOrdemClube() {
                    const ordemInputs = document.querySelectorAll('#clubes-container input[name$="[ordem]"]');
                    let maiorOrdem = 0;

                    ordemInputs.forEach(input => {
                        const valor = parseInt(input.value, 10);
                        if (!Number.isNaN(valor) && valor > maiorOrdem) {
                            maiorOrdem = valor;
                        }
                    });

                    return maiorOrdem + 1;
                }

                function removerNovoClube(button) {
                    const card = button.closest('.cms-clube-item');
                    if (card) {
                        card.remove();
                    }
                }

                function adicionarClube() {
                    novoClubeIndex += 1;

                    const clubeKey = 'new_' + novoClubeIndex;
                    const ordem = obterProximaOrdemClube();
                    const container = document.getElementById('clubes-container');
                    const card = document.createElement('div');

                    card.className = 'cms-clube-item is-new';

                    card.innerHTML = `
                        <div class="cms-clube-header">
                            <div class="cms-clube-title">
                                <div class="cms-clube-badge">
                                    <span class="material-symbols-sharp">add_circle</span>
                                </div>
                                <div>
                                    <strong>Novo clube</strong>
                                    <span>Preencha os dados para incluir mais um time na home</span>
                                </div>
                            </div>
                            <button type="button" onclick="removerNovoClube(this)" class="clubs-action-button cms-clube-remove">
                                <span class="material-symbols-sharp">delete</span>
                                Remover
                            </button>
                        </div>
                        <div class="cms-clube-fields">
                            <div class="form-group">
                                <label>Nome</label>
                                <input type="text" name="clubes[${clubeKey}][nome]" required>
                            </div>

                            <div class="form-group">
                                <label>Sigla</label>
                                <input type="text" name="clubes[${clubeKey}][sigla]" maxlength="20" required>
                            </div>

                            <div class="form-group">
                                <label>Imagem do escudo</label>
                                <input type="text" name="clubes[${clubeKey}][imagem_path]" placeholder="Ex: image/meu-clube.png">
                            </div>

                            <div class="form-group">
                                <label>Ordem</label>
                                <input type="number" name="clubes[${clubeKey}][ordem]" value="${ordem}" min="1" required>
                            </div>

                            <div class="form-group">
                                <label>Ativo</label>
                                <label class="cms-checkbox-label">
                                    <input class="cms-checkbox" type="checkbox" name="clubes[${clubeKey}][ativo]" value="1" checked>
                                    <span style="font-size: 0.85rem;">Sim</span>
                                </label>
                            </div>
                        </div>
                    `;

                    container.appendChild(card);

                    const primeiroInput = card.querySelector('input[type="text"]');
                    if (primeiroInput) {
                        primeiroInput.focus();
                    }
                }

                function salvarClubes() {
                    const form = document.getElementById('formClubes');
                    const formData = new FormData(form);
                    formData.append('action', 'update_home_clubs');

                    const checkboxes = form.querySelectorAll('input[type="checkbox"][name*="[ativo]"]');
                    checkboxes.forEach(cb => {
                        if (!cb.checked) {
                            formData.set(cb.name, '0');
                        }
                    });

                    fetch('cms_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Clubes salvos com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro: ' + (data.message || 'Não foi possível salvar os clubes.'));
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao salvar clubes:', error);
                        alert('Erro na comunicação ao salvar clubes.');
                    });
                }

                let novaLigaIndex = 0;

                function adicionarLiga() {
                    novaLigaIndex += 1;
                    const key = 'new_' + novaLigaIndex;
                    const container = document.getElementById('ligas-container-home');
                    const ordemAtual = container.querySelectorAll('.cms-league-card').length + 1;

                    const card = document.createElement('div');
                    card.className = 'cms-league-card is-new';
                    card.dataset.id = key;

                    card.innerHTML = `
                        <div class="league-card-header">
                            <div class="league-logo-preview" id="preview-home-${key}"><span>?</span></div>
                            <div>
                                <div class="league-card-title">Nova liga</div>
                                <div class="league-card-id">Ainda não salva</div>
                            </div>
                        </div>

                        <div class="league-fields">
                            <div class="form-group">
                                <label>Nome *</label>
                                <input type="text" name="ligas[${key}][nome]" required>
                            </div>

                            <div class="form-group">
                                <label>Sigla *</label>
                                <input type="text" name="ligas[${key}][sigla]" maxlength="10" required>
                            </div>

                            <div class="form-group full">
                                <label>Slug *</label>
                                <input type="text" name="ligas[${key}][slug]" required>
                            </div>

                            <div class="form-group full">
                                <label>Classe CSS (opcional)</label>
                                <input type="text" name="ligas[${key}][classe]" placeholder="Ex: league-premier">
                            </div>

                            <div class="form-group">
                                <label>Ordem</label>
                                <input type="number" name="ligas[${key}][ordem]" value="${ordemAtual}" min="1">
                            </div>

                            <div class="form-group" style="display: flex; align-items: flex-end;">
                                <label class="cms-checkbox-label" style="margin-bottom: 0.55rem;">
                                    <input class="cms-checkbox" type="checkbox" name="ligas[${key}][ativo]" value="1" checked>
                                    <span style="font-size: 0.86rem;">Ativo</span>
                                </label>
                            </div>

                            <div class="form-group full" style="margin-top: 0.1rem;">
                                <label>Caminho da logo</label>
                                <input type="text" name="ligas[${key}][logo_path]" placeholder="Ex: image/minha-liga.png">
                                <small>Use o caminho da imagem igual ao padrão dos clubes em destaque.</small>
                            </div>
                        </div>

                        <div class="league-card-actions">
                            <button type="button" class="btn-delete-league" onclick="this.closest('.cms-league-card').remove()">
                                <span class="material-symbols-sharp" style="font-size:.95rem;">delete</span>
                                Remover
                            </button>
                        </div>
                    `;

                    container.appendChild(card);
                }

                function salvarLigasHome() {
                    const btn = document.getElementById('btnSalvarLigasHome');
                    if (btn) {
                        btn.disabled = true;
                    }

                    const cards = document.querySelectorAll('#ligas-container-home .cms-league-card');
                    const fd = new FormData();
                    fd.append('action', 'update_home_leagues');

                    cards.forEach(card => {
                        card.querySelectorAll('input[name]').forEach(inp => {
                            if (inp.type === 'checkbox') {
                                fd.append(inp.name, inp.checked ? '1' : '0');
                            } else {
                                fd.append(inp.name, inp.value);
                            }
                        });
                    });

                    fetch('cms_api.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                alert('Ligas salvas com sucesso!');
                                location.reload();
                            } else {
                                alert('Erro ao salvar ligas: ' + (data.message || 'erro desconhecido'));
                            }
                        })
                        .catch(() => {
                            alert('Falha na comunicação ao salvar ligas.');
                        })
                        .finally(() => {
                            if (btn) {
                                btn.disabled = false;
                            }
                        });
                }

                function excluirLigaHome(id, nome, button) {
                    if (!confirm('Excluir a liga "' + nome + '"?')) {
                        return;
                    }

                    const fd = new FormData();
                    fd.append('action', 'delete_home_league');
                    fd.append('id', String(id));

                    fetch('cms_api.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                const card = button ? button.closest('.cms-league-card') : null;
                                if (card) {
                                    card.remove();
                                }
                                alert('Liga excluída com sucesso!');
                            } else {
                                alert('Erro ao excluir: ' + (data.message || 'erro desconhecido'));
                            }
                        })
                        .catch(() => {
                            alert('Falha na comunicação ao excluir liga.');
                        });
                }
                
                </script>

                <!-- FOOTER - MARCA E CONTATO -->
                <form id="footerSettingsForm">
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">contact_page</span>
                        Footer - Marca e Contato
                    </h3>
                    
                    <div class="form-group">
                        <label>Título da Marca *</label>
                        <input type="text" name="footer_marca_titulo" value="<?php echo htmlspecialchars($footer['marca_titulo'] ?? 'Rare7'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Subtítulo da Marca</label>
                        <input type="text" name="footer_marca_subtitulo" value="<?php echo htmlspecialchars($footer['marca_subtitulo'] ?? 'Beauty & Style'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Descrição da Marca</label>
                        <textarea name="footer_marca_descricao" rows="3"><?php echo htmlspecialchars($footer['marca_descricao'] ?? ''); ?></textarea>
                        <small>Texto que aparece abaixo da marca no footer</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="footer_telefone" value="<?php echo htmlspecialchars($footer['telefone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>WhatsApp</label>
                        <input type="text" name="footer_whatsapp" value="<?php echo htmlspecialchars($footer['whatsapp'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="text" name="footer_email" value="<?php echo htmlspecialchars($footer['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Instagram (URL)</label>
                        <input type="text" name="footer_instagram" value="<?php echo htmlspecialchars($footer['instagram'] ?? '#'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>TikTok (URL)</label>
                        <input type="text" name="footer_tiktok" value="<?php echo htmlspecialchars($footer['tiktok'] ?? '#'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Facebook (URL)</label>
                        <input type="text" name="footer_facebook" value="<?php echo htmlspecialchars($footer['facebook'] ?? '#'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Texto do Copyright</label>
                        <input type="text" name="footer_copyright" value="<?php echo htmlspecialchars($footer['copyright_texto'] ?? '© 2026 Rare7 Beauty �?� Todos os direitos reservados'); ?>">
                    </div>
                </div>

                <!-- LINKS DO FOOTER -->
                <div class="settings-card">
                    <h3>
                        <span class="material-symbols-sharp">link</span>
                        Links do Footer
                    </h3>
                    <p style="margin-bottom: 1.5rem; color: var(--color-dark-variant);">
                        Links organizados em colunas. Para gerenciar (adicionar/remover/editar/reordenar), use o gerenciador completo.
                    </p>
                    
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <a href="footer-links.php" style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--color-primary); color: white; padding: 0.8rem 1.5rem; border-radius: var(--border-radius-1); text-decoration: none; font-weight: 600; transition: all 0.3s;">
                            <span class="material-symbols-sharp">edit</span>
                            Gerenciar Links do Footer
                        </a>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <h4 style="margin-bottom: 0.5rem;">Coluna: Produtos</h4>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($footer_links['produtos'] as $link): ?>
                                <li style="padding: 0.3rem 0;">�?' <?php echo htmlspecialchars($link['texto']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.5rem;">Coluna: Atendimento</h4>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($footer_links['atendimento'] as $link): ?>
                                <li style="padding: 0.3rem 0;">�?' <?php echo htmlspecialchars($link['texto']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 2rem;">
                    <button type="submit" class="btn-save">
                        <span class="material-symbols-sharp">save</span>
                        Salvar Configurações do Footer
                    </button>
                </div>
                </form>
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
                        <p>Olá, <b><?php echo isset($_SESSION['nome_usuario']) ? $_SESSION['nome_usuario'] : 'Admin'; ?></b></p>
                        <small class="text-muted">Admin</small>
                    </div>
                    <div class="profile-photo">
                        <img src="../../../../assets/images/logo_png.png" alt="Logo Rare7">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Garantir tema dark
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('darkTheme');
            if (savedTheme === 'true') {
                document.body.classList.add('dark-theme-variables');
                // Atualizar ícones do toggler
                const themeToggler = document.querySelector('.theme-toggler');
                themeToggler.querySelector('span:nth-child(1)').classList.remove('active');
                themeToggler.querySelector('span:nth-child(2)').classList.add('active');
            }
            
            // Theme toggler click handler
            const themeToggler = document.querySelector('.theme-toggler');
            themeToggler.addEventListener('click', () => {
                document.body.classList.toggle('dark-theme-variables');
                
                themeToggler.querySelector('span:nth-child(1)').classList.toggle('active');
                themeToggler.querySelector('span:nth-child(2)').classList.toggle('active');
                
                // Salvar preferência
                if (document.body.classList.contains('dark-theme-variables')) {
                    localStorage.setItem('darkTheme', 'true');
                } else {
                    localStorage.setItem('darkTheme', 'false');
                }
            });
        });

        // Salvar configurações (função genérica)
        async function salvarConfiguracoes(formElement) {
            const formData = new FormData(formElement);
            formData.append('action', 'update_home_settings');
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Resposta da API:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    console.error('Erro ao fazer parse do JSON:', parseError);
                    console.error('Resposta recebida:', text);
                    alert('Erro ao processar resposta do servidor. Verifique o console para detalhes.');
                    return;
                }
                
                if (result.success) {
                    const successMsg = document.getElementById('successMsg');
                    successMsg.classList.add('show');
                    setTimeout(() => {
                        successMsg.classList.remove('show');
                    }, 3000);
                    
                    // Atualizar timestamp
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar configurações');
            }
        }
        
        // Aplicar aos formulários
        document.getElementById('homeSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            salvarConfiguracoes(this);
        });
        
        document.getElementById('footerSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            salvarConfiguracoes(this);
        });
    </script>
</body>
</html>

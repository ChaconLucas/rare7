<?php
// FORÇAR SEM CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

// Verificar se está logado
if (!isset($_SESSION['cliente'])) {
    header('Location: login.php');
    exit;
}

$nomeUsuario = htmlspecialchars($_SESSION['cliente']['nome']);
$clienteId = $_SESSION['cliente']['id'];
$usuarioLogado = true;
$pageTitle = 'Meus Pedidos - RARE7';

// Buscar configuração de frete grátis
$freteGratisValor = getFreteGratisThreshold($pdo);

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

function normalizarStatusKey($status) {
    $valor = trim((string) $status);
    if ($valor === '') {
        return '';
    }

    $valor = mb_strtolower($valor, 'UTF-8');
    $valor = strtr($valor, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c',
    ]);

    return preg_replace('/[^a-z0-9]+/u', '', $valor);
}

// Buscar cores dos status da gestão de fluxo
$statusMap = [];
$statusAliases = [
    'pedidoconfirmado' => 'pagamentoconfirmado',
    'pago' => 'pagamentoconfirmado',
    'cancelado' => 'pedidocancelado',
];
try {
    $stmtStatus = $pdo->query("SELECT nome, cor_hex FROM status_fluxo ORDER BY ordem ASC");
    while ($row = $stmtStatus->fetch(PDO::FETCH_ASSOC)) {
        $nome = trim((string)($row['nome'] ?? ''));
        $cor = trim((string)($row['cor_hex'] ?? ''));
        if ($nome === '') {
            continue;
        }
        $key = normalizarStatusKey($nome);
        if ($key === '') {
            continue;
        }
        $statusMap[$key] = [
            'nome' => $nome,
            'cor' => $cor !== '' ? $cor : '#757575',
        ];
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar status: " . $e->getMessage());
}

// Buscar pedidos do cliente
$pedidos = [];
try {
    $stmt = $pdo->prepare("SELECT p.id, p.numero_pedido, p.valor_total, p.status, p.data_pedido, p.observacoes, p.forma_pagamento, p.parcelas, p.valor_parcela FROM pedidos p WHERE p.cliente_id = ? ORDER BY p.data_pedido DESC");
    
    if (!$stmt) {
        die("ERRO: prepare() falhou");
    }
    
    $resultado = $stmt->execute([$clienteId]);
    
    if (!$resultado) {
        die("ERRO: execute() falhou: " . print_r($stmt->errorInfo(), true));
    }
    
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar itens de cada pedido
    foreach ($pedidos as $key => $pedido) {
        try {
            $stmtItens = $pdo->prepare("
                SELECT 
                    ip.quantidade, 
                    ip.preco_unitario, 
                    COALESCE(ip.nome_produto, pr.nome) as nome,
                    pr.imagem_principal as imagem, 
                    pr.categoria 
                FROM itens_pedido ip 
                LEFT JOIN produtos pr ON ip.produto_id = pr.id 
                WHERE ip.pedido_id = ?
            ");
            $stmtItens->execute([$pedido['id']]);
            $pedidos[$key]['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $eItens) {
            $pedidos[$key]['itens'] = [];
            error_log("Erro ao buscar itens: " . $eItens->getMessage());
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar pedidos: " . $e->getMessage());
    $pedidos = [];
}

// Função para traduzir status
function traduzirStatus($status) {
    global $statusMap, $statusAliases;

    $key = normalizarStatusKey($status);
    if ($key !== '' && isset($statusAliases[$key])) {
        $key = $statusAliases[$key];
    }

    if ($key !== '' && isset($statusMap[$key]['nome'])) {
        return $statusMap[$key]['nome'];
    }

    return $status;
}

// Função para cor do status
function corStatus($status) {
    global $statusMap, $statusAliases;

    $key = normalizarStatusKey($status);
    if ($key !== '' && isset($statusAliases[$key])) {
        $key = $statusAliases[$key];
    }

    // Primeiro tenta buscar exatamente as cores da gestão de fluxo do admin
    if ($key !== '' && isset($statusMap[$key]['cor'])) {
        return $statusMap[$key]['cor'];
    }

    // Fallback alinhado com os padrões do painel admin
    $cores = [
        'Pedido Recebido' => '#C6A75E',
        'Pagamento Pendente' => '#ff9800',
        'Pedido Confirmado' => '#41f1b6',
        'Pagamento Confirmado' => '#41f1b6',
        'Em Preparação' => '#ffbb55',
        'Enviado' => '#00BCD4',
        'Entregue' => '#28a745',
        'Estornado' => '#9c27b0',
        'Cancelado' => '#f44336',
        'Pedido Cancelado' => '#f44336',
        'pendente' => '#757575'
    ];
    return $cores[$status] ?? '#757575';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../image/logo_png.png" sizes="any">
    <link rel="apple-touch-icon" href="../../image/logo_png.png">
    
    <!-- Material Symbols (ícones) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <!-- Link para css/loja.css que contém a base de estilos -->
    <link rel="stylesheet" href="../css/loja.css?v=<?php echo filemtime(__DIR__ . '/../css/loja.css'); ?>">
    
    <title><?php echo $pageTitle; ?></title>
    <style>
        :root {
            --color-magenta: #E6007E;
            --color-magenta-dark: #C4006A;
            --color-rose-light: #FDF2F8;
            --rare-scroll-track: linear-gradient(180deg, rgba(10, 12, 18, 0.96) 0%, rgba(12, 20, 34, 0.96) 100%);
            --rare-scroll-thumb: linear-gradient(180deg, rgba(216, 185, 112, 0.95) 0%, rgba(198, 167, 94, 0.92) 100%);
            --rare-scroll-thumb-hover: linear-gradient(180deg, rgba(234, 205, 135, 0.98) 0%, rgba(214, 181, 106, 0.96) 100%);
            --rare-scroll-edge: rgba(8, 10, 16, 0.85);
        }
        
        /* Configurações globais premium */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }
        
        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #ffffff;
            color: #333333;
            line-height: 1.6;
            overflow-x: hidden;
            padding-top: 85px;
            margin: 0;
        }
        
        /* Scrollbar padrão RARE7: escuro + dourado */

        html,
        body {
            scrollbar-width: thin;
            scrollbar-color: rgba(198, 167, 94, 0.95) rgba(10, 12, 18, 0.9);
        }

        *::-webkit-scrollbar {
            width: 11px;
            height: 11px;
        }

        *::-webkit-scrollbar-track {
            background: var(--rare-scroll-track);
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        *::-webkit-scrollbar-thumb {
            background: var(--rare-scroll-thumb);
            border-radius: 999px;
            border: 2px solid var(--rare-scroll-edge);
            box-shadow: 0 0 0 1px rgba(198, 167, 94, 0.2);
        }

        *::-webkit-scrollbar-thumb:hover {
            background: var(--rare-scroll-thumb-hover);
            box-shadow: 0 0 0 1px rgba(234, 205, 135, 0.4);
        }
        
        .header-loja {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(230, 0, 126, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 12px 0;
        }
        
        .header-loja a {
            text-decoration: none !important;
        }
        
        /* Esconder botão mobile por padrão */
        .mobile-menu-toggle {
            display: none !important;
        }
        
        /* Esconder menu mobile e overlay por padrão */
        .mobile-menu,
        .mobile-menu-overlay {
            display: none !important;
            position: fixed;
            z-index: -1;
        }
        
        /* Ajustes responsivos para telas médias */
        @media (max-width: 968px) {
            header.header-loja .container-header,
            header.header-loja #navbar .container-header,
            .header-loja .container-header {
                padding: 0 8px !important;
                gap: 8px !important;
            }
            
            header.header-loja .nav-loja,
            .header-loja .nav-loja {
                margin: 0 10px 0 0 !important;
            }
            
            header.header-loja .nav-loja > ul,
            .header-loja .nav-loja > ul {
                gap: 13px !important;
            }
            
            .nav-loja a {
                font-size: 0.8rem !important;
                padding: 8px 10px !important;
            }
            
            header.header-loja .nav-right,
            .header-loja .nav-right {
                gap: 6px !important;
            }
            
            header.header-loja .search-panel.active,
            .header-loja .search-panel.active {
                min-width: 100px !important;
                max-width: 130px !important;
            }
            
            header.header-loja .search-panel input,
            .header-loja .search-panel input {
                min-width: 100px !important;
                max-width: 130px !important;
                font-size: 0.8rem !important;
                padding: 8px 10px !important;
            }
        }
        
        /* Apenas em mobile, permitir que apareça */
        @media (max-width: 768px) {
            .header-loja {
                padding: 8px 0 !important;
            }
            
            header.header-loja .container-header,
            header.header-loja #navbar .container-header,
            .header-loja .container-header {
                padding: 0 12px !important;
                gap: 6px !important;
                justify-content: space-between !important;
            }
            .mobile-menu-toggle {
                display: flex !important;
                width: 44px !important;
                height: 44px !important;
                align-items: center !important;
                justify-content: center !important;
                border: none;
                background: rgba(230, 0, 126, 0.1);
                border-radius: 22px;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                z-index: 10;
                pointer-events: auto;
            }
            
            .mobile-menu-toggle:hover {
                background: var(--color-magenta);
            }
            
            .hamburger {
                width: 20px;
                height: 20px;
                position: relative;
                transform: rotate(0deg);
                transition: 0.3s ease-in-out;
                cursor: pointer;
            }
            
            .hamburger span {
                display: block;
                position: absolute;
                height: 2px;
                width: 100%;
                background: var(--color-magenta);
                border-radius: 2px;
                opacity: 1;
                left: 0;
                transform: rotate(0deg);
                transition: 0.25s ease-in-out;
            }
            
            .mobile-menu-toggle:hover .hamburger span {
                background: white;
            }
            
            .hamburger span:nth-child(1) {
                top: 0px;
                transform-origin: left center;
            }
            
            .hamburger span:nth-child(2) {
                top: 9px;
                transform-origin: left center;
            }
            
            .hamburger span:nth-child(3) {
                top: 18px;
                transform-origin: left center;
            }
            
            .hamburger.open span:nth-child(1) {
                transform: rotate(45deg);
                top: -1px;
                left: 4px;
            }
            
            .hamburger.open span:nth-child(2) {
                width: 0%;
                opacity: 0;
            }
            
            .hamburger.open span:nth-child(3) {
                transform: rotate(-45deg);
                top: 17px;
                left: 4px;
            }
            
            .mobile-menu {
                position: fixed;
                top: 0;
                right: -100%;
                width: 300px;
                height: 100%;
                background: linear-gradient(135deg, #ffffff 0%, #fef7ff 100%);
                padding: 100px 30px 40px;
                z-index: 1001;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
                transform: translateX(100%);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }
            
            .mobile-menu.active {
                right: 0;
                transform: translateX(0);
                opacity: 1;
                visibility: visible;
                pointer-events: all;
            }
            
            .mobile-menu-overlay {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: all 0.3s ease;
            }
            
            .mobile-menu-overlay.active {
                opacity: 1;
                visibility: visible;
                pointer-events: all;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(10px);
                z-index: 999;
            }
            
            .mobile-menu ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .mobile-menu li {
                margin-bottom: 8px;
            }
            
            .mobile-menu a {
                display: block;
                padding: 16px 20px;
                color: #2d3748;
                text-decoration: none;
                font-weight: 600;
                font-size: 1.1rem;
                border-radius: 15px;
                transition: all 0.3s ease;
                opacity: 0;
                transform: translateX(30px);
            }
            
            .mobile-menu.active a {
                opacity: 1;
                transform: translateX(0);
            }
            
            .mobile-menu.active li:nth-child(1) a { transition-delay: 0.1s; }
            .mobile-menu.active li:nth-child(2) a { transition-delay: 0.15s; }
            .mobile-menu.active li:nth-child(3) a { transition-delay: 0.2s; }
            .mobile-menu.active li:nth-child(4) a { transition-delay: 0.25s; }
            
            .mobile-menu a:hover {
                background: rgba(230, 0, 126, 0.1);
                color: var(--color-magenta);
                transform: translateX(10px);
            }
            
            .mobile-menu,
            .mobile-menu-overlay {
                display: block !important;
            }
            
            .logo-dz-oficial {
                height: 35px;
            }
            
            .logo-text {
                font-size: 1.4rem;
            }
            
            .header-loja.scrolled .logo-dz-oficial {
                height: 30px;
            }
            
            .header-loja.scrolled .logo-text {
                font-size: 1.2rem;
            }
            
            .nav-loja {
                display: none !important;
            }
            
            .logo-dz-fallback {
                font-size: 2rem;
            }
            
            .logo-dz-fallback::before {
                width: 16px;
                height: 16px;
            }
            
            .user-area {
                gap: 8px;
            }
            
            .btn-icon {
                width: 40px;
                height: 40px;
            }
            
            .btn-cart span:not(.cart-count) {
                display: none;
            }
            
            body {
                padding-top: 70px;
            }
        }
        
        .header-loja.scrolled {
            padding: 8px 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.12);
        }
        
        /* ===== LAYOUT NAVBAR - ALTA ESPECIFICIDADE ===== */
        header.header-loja #navbar .container-header,
        header.header-loja .container-header,
        .header-loja .container-header {
            max-width: 1400px !important;
            margin: 0 auto !important;
            padding: 0 4px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            flex-wrap: nowrap !important;
        }
        
        header.header-loja .logo-container,
        .header-loja .logo-container {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            cursor: pointer !important;
            transition: transform 0.3s ease !important;
            flex-shrink: 0 !important;
            flex: 0 0 auto !important;
            min-width: 0 !important;
            margin-left: 0 !important;
        }
        
        .logo-container:hover {
            transform: scale(1.05);
        }
        
        .logo-dz-oficial {
            height: 45px;
            width: auto;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(230, 0, 126, 0.2));
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--color-magenta);
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .header-loja.scrolled .logo-dz-oficial {
            height: 35px;
        }
        
        .header-loja.scrolled .logo-text {
            font-size: 1.5rem;
        }
        
        .logo-dz-fallback {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--color-magenta);
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            text-shadow: 0 2px 4px rgba(230, 0, 126, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logo-dz-fallback::before {
            content: '';
            width: 20px;
            height: 20px;
            background: radial-gradient(circle, var(--color-magenta) 0%, var(--color-magenta-dark) 50%, transparent 70%);
            border-radius: 50%;
            box-shadow: 
                -8px -8px 0 -4px rgba(230, 0, 126, 0.6),
                8px -8px 0 -4px rgba(230, 0, 126, 0.6),
                -6px 6px 0 -4px rgba(230, 0, 126, 0.4),
                6px 6px 0 -4px rgba(230, 0, 126, 0.4);
        }
        
        .header-loja.scrolled .logo-dz-fallback {
            font-size: 2rem;
        }
        
        header.header-loja nav.nav-loja,
        .header-loja .nav-loja {
            flex: 1 1 auto !important;
            display: flex !important;
            justify-content: center !important;
            min-width: 0 !important;
            max-width: none !important;
            overflow: visible !important;
            margin: 0 16px 0 0 !important;
            flex-shrink: 0 !important;
        }
        
        header.header-loja nav.nav-loja > ul,
        .header-loja .nav-loja > ul {
            display: flex !important;
            align-items: center !important;
            gap: 18px !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
            flex-wrap: nowrap !important;
            white-space: nowrap !important;
        }
        
        header.header-loja .nav-loja > ul > li,
        .header-loja .nav-loja > ul > li {
            flex-shrink: 0 !important;
        }
        
        .nav-loja > ul > li {
            position: relative;
        }
        
        .nav-loja a {
            color: #2d3748;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 10px 13px;
            border-radius: 25px;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .dropdown-icon {
            font-size: 0.7rem;
            transition: transform 0.3s ease;
        }
        
        .has-dropdown:hover .dropdown-icon {
            transform: rotate(180deg);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 220px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            padding: 12px 0;
            margin-top: 8px;
        }
        
        .has-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: block;
        }
        
        .dropdown-menu li {
            position: relative;
        }
        
        .dropdown-menu a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            color: #2d3748;
            border-radius: 0;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .dropdown-menu a:hover {
            background: rgba(230, 0, 126, 0.08);
            color: var(--color-magenta);
            transform: translateX(4px);
        }
        
        .submenu-arrow {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .submenu {
            position: absolute;
            left: 100%;
            top: 0;
            background: white;
            min-width: 200px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateX(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
            padding: 12px 0;
            margin-left: 4px;
        }
        
        .has-submenu:hover .submenu {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }
        
        .nav-loja a:hover {
            color: var(--color-magenta);
            background: rgba(230, 0, 126, 0.08);
            transform: translateY(-2px);
        }
        
        header.header-loja .nav-right,
        .header-loja .nav-right {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            flex-shrink: 1 !important;
            flex: 0 1 auto !important;
            margin-right: 0 !important;
        }
        
        header.header-loja .nav-right .user-area,
        .header-loja .user-area {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            flex-shrink: 0 !important;
            margin-right: 0 !important;
            position: relative;
            z-index: 5;
        }

        header.header-loja .nav-right .search-panel,
        .header-loja .search-panel {
            width: 0 !important;
            max-width: 0 !important;
            opacity: 0 !important;
            overflow: hidden !important;
            transition: width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), 
                        opacity 0.5s ease-in-out,
                        max-width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            display: flex !important;
            align-items: center !important;
            flex: 0 0 auto !important;
            will-change: width, opacity, max-width;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
        }

        header.header-loja .nav-right .search-panel.active,
        .header-loja .search-panel.active {
            width: auto !important;
            min-width: 160px !important;
            max-width: 220px !important;
            opacity: 1 !important;
            flex: 1 1 auto !important;
            transition: width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1),
                        opacity 0.5s ease-in-out,
                        max-width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        }

        header.header-loja .nav-right .search-panel input,
        .header-loja .search-panel input {
            width: 100% !important;
            min-width: 160px !important;
            max-width: 220px !important;
            padding: 10px 14px !important;
            border-radius: 20px !important;
            border: 1px solid rgba(230, 0, 126, 0.2) !important;
            background: white !important;
            color: #1e293b !important;
            font-size: 0.9rem !important;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
            outline: none !important;
            transition: border-color 0.3s ease, box-shadow 0.3s ease !important;
        }
        
        .search-panel input:focus {
            border-color: var(--color-magenta);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.2);
        }
        
        .btn-icon {
            width: 44px;
            height: 44px;
            border-radius: 22px;
            border: none;
            background: rgba(230, 0, 126, 0.1);
            color: var(--color-magenta);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
            z-index: 10;
            pointer-events: auto;
        }
        
        .btn-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .btn-icon:hover {
            background: var(--color-magenta);
            color: white;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .btn-icon:active {
            transform: translateY(0) scale(0.98);
            transition: all 0.15s ease;
        }
        
        .btn-icon:hover::before {
            left: 100%;
        }
        
        .btn-cart {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 20px rgba(230, 0, 126, 0.25);
            position: relative;
            overflow: visible;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            z-index: 1;
            pointer-events: auto;
        }
        
        .btn-cart::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
            border-radius: 25px;
            z-index: 0;
        }

        .btn-cart > *:not(.cart-count) {
            position: relative;
            z-index: 1;
            pointer-events: none;
        }

        .btn-cart .cart-count {
            position: absolute !important;
            top: -6px !important;
            right: -6px !important;
            z-index: 10;
            pointer-events: none;
        }
        
        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .btn-cart:active {
            transform: translateY(0);
        }
        
        .btn-cart:hover::before {
            left: 100%;
        }
        
        .cart-count {
            background: white;
            color: #E6007E;
            border-radius: 50%;
            width: 20px;height: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            line-height: 1;
            padding: 0;
            text-align: center;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 2px 8px rgba(230, 0, 126, 0.4); }
            50% { box-shadow: 0 4px 16px rgba(230, 0, 126, 0.8); }
        }
        
        /* Botões de Autenticação */
        .btn-auth {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .btn-login {
            background: transparent;
            color: var(--color-magenta);
            border: 2px solid var(--color-magenta);
        }
        
        .btn-login:hover {
            background: var(--color-magenta);
            color: white;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            border: none;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 0, 126, 0.3);
        }
        
        /* Dropdown do usuário */
        .user-dropdown {
            position: relative;
        }
        
        .user-dropdown-btn {
            width: 44px;
            height: 44px;
            border-radius: 22px;
            border: none;
            background: rgba(230, 0, 126, 0.1);
            color: var(--color-magenta);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s;
            position: relative;
            z-index: 10;
            pointer-events: auto;
        }
        
        .user-dropdown-btn:hover {
            background: var(--color-magenta);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1001;
        }
        
        .user-dropdown.active .user-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown-menu::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 20px;
            width: 12px;
            height: 12px;
            background: white;
            transform: rotate(45deg);
        }
        
        .user-greeting {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            color: var(--color-magenta);
        }
        
        .user-dropdown-menu a {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        
        .user-dropdown-menu a:hover {
            background: rgba(230, 0, 126, 0.05);
            color: var(--color-magenta);
        }
        
        .user-dropdown-menu a:last-child {
            border-radius: 0 0 12px 12px;
            color: #ef4444;
        }
        
        .user-dropdown-menu a:last-child:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            color: var(--color-magenta);
            font-size: 2.5rem;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .pedidos-lista {
            display: grid;
            gap: 24px;
        }
        
        .pedido-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid rgba(230, 0, 126, 0.05);
        }
        
        .pedido-card:hover {
            box-shadow: 0 8px 24px rgba(230, 0, 126, 0.15);
            transform: translateY(-4px);
            border-color: rgba(230, 0, 126, 0.2);
        }
        
        .pedido-info-compacta {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .pedido-numero {
            font-size: 1.05rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            min-width: 110px;
            letter-spacing: 0.3px;
        }
        
        .pedido-status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            min-width: 130px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            letter-spacing: 0.3px;
        }
        
        .pedido-data {
            font-size: 0.9rem;
            color: #64748b;
            min-width: 100px;
            font-weight: 500;
        }
        
        .pedido-valor {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--color-magenta);
            min-width: 110px;
            text-align: right;
            letter-spacing: 0.3px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 16px;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.05rem;
        }
        
        .pedido-itens {
            margin-top: 20px;
        }
        
        .itens-header {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(230, 0, 126, 0.1);
        }
        
        .item-produto {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: linear-gradient(135deg, rgba(230, 0, 126, 0.02) 0%, rgba(230, 0, 126, 0.05) 100%);
            border-radius: 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(230, 0, 126, 0.1);
            transition: all 0.3s;
        }
        
        .item-produto:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(230, 0, 126, 0.1);
            border-color: rgba(230, 0, 126, 0.2);
        }
        
        .item-imagem {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(230, 0, 126, 0.3);
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-nome {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            font-size: 1rem;
        }
        
        .item-detalhes {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .item-preco {
            text-align: right;
            font-weight: 700;
            color: var(--color-magenta);
            font-size: 1.1rem;
        }
        
        .pedido-total {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #f0f0f0;
            text-align: right;
        }
        
        .total-label {
            font-size: 0.875rem;
            color: #666;
        }
        
        .total-valor {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-magenta);
            margin-top: 4px;
        }
        
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 80px 40px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }
        
        .empty-icon {
            font-size: 5rem;
            margin-bottom: 24px;
            opacity: 0.7;
        }
        
        .empty-state h2 {
            color: #1e293b;
            font-size: 1.8rem;
            margin-bottom: 12px;
        }
        
        .empty-state p {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 32px;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(230, 0, 126, 0.25);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(230, 0, 126, 0.4);
        }
        
        .btn-ver-detalhes {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(230, 0, 126, 0.25);
        }
        
        .btn-ver-detalhes:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(230, 0, 126, 0.4);
        }
        
        /* MODAL PREMIUM */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 10000;
            animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(4px);
        }
        
        .modal-overlay.ativo {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(60px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        @keyframes bounce {
            0%, 100% { 
                transform: scale(1); 
            }
            50% { 
                transform: scale(1.2); 
            }
        }
        
        .modal-header {
            padding: 28px;
            border-bottom: 1px solid rgba(230, 0, 126, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(230, 0, 126, 0.03) 0%, rgba(230, 0, 126, 0.08) 100%);
        }
        
        .modal-titulo {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-fechar {
            background: rgba(230, 0, 126, 0.1);
            border: none;
            font-size: 1.5rem;
            color: var(--color-magenta);
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
            font-weight: 300;
        }
        
        .btn-fechar:hover {
            background: var(--color-magenta);
            color: white;
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 28px;
            background: white;
        }
        
        .info-item {
            margin-bottom: 16px;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #666;
            font-weight: 500;
            display: block;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 600;
        }
        
        /* RESPONSIVIDADE - Estilos específicos da página */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .page-container {
                padding: 24px 16px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .page-header p {
                font-size: 1rem;
            }
            
            .pedido-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 16px;
            }
            
            .pedido-info-compacta {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                gap: 12px;
            }
            
            .pedido-numero,
            .pedido-status,
            .pedido-data,
            .pedido-valor {
                min-width: auto;
                text-align: left;
            }
            
            .btn-ver-detalhes {
                width: 100%;
                justify-content: center;
            }
            
            .modal-content {
                width: 95%;
                max-height: 85vh;
            }
            
            .modal-header {
                padding: 20px;
            }
            
            .modal-titulo {
                font-size: 1.3rem;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .item-produto {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }
            
            .item-preco {
                text-align: left;
            }
        }
        
        /* ===== CHAT STYLES ===== */
        
        /* Chat Button */
        .chat-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #E6007E, #b8005f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 8px 25px rgba(230, 0, 126, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            color: white;
            font-size: 1.8rem;
        }
        
        .chat-button:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(230, 0, 126, 0.6);
        }
        
        .chat-button.chat-hidden,
        .chat-modal.chat-hidden {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        
        /* Chat Tooltip */
        .chat-tooltip {
            position: absolute;
            right: 70px;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .chat-button:hover .chat-tooltip {
            opacity: 1;
        }
        
        /* Chat Modal */
        .chat-modal {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .chat-modal.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        
        /* Chat Header */
        .chat-header {
            background: linear-gradient(135deg, #E6007E, #b8005f);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .chat-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .chat-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Online Status */
        .online-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            margin-top: 4px;
        }
        
        .online-indicator {
            width: 8px;
            height: 8px;
            background: #00ff88;
            border-radius: 50%;
        }
        
        /* Chat Messages */
        .chat-messages {
            height: 320px;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .chat-message {
            background: white;
            padding: 12px 16px;
            border-radius: 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .chat-message.bot {
            margin-right: 40px;
            color: #2d3748;
            background: linear-gradient(135deg, #f3e5f5, #fce4ec);
        }
        
        .chat-message.user {
            background: linear-gradient(135deg, #E6007E, #b8005f);
            color: white;
            margin-left: 40px;
            text-align: right;
        }
        
        .chat-message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        /* Chat Input */
        .chat-input-container {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 25px;
            font-size: 0.9rem;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: #E6007E;
            background: white;
        }
        
        .chat-send {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #E6007E, #b8005f);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .chat-send:hover {
            transform: scale(1.05);
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: none;
            padding: 12px 16px;
            background: white;
            border-radius: 15px;
            margin-bottom: 12px;
            margin-right: 40px;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #666;
            border-radius: 50%;
            animation: typingAnimation 1.5s infinite;
        }
        
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typingAnimation {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
            30% { transform: translateY(-10px); opacity: 1; }
        }
    </style>
    <script>
        // Funções do Modal de Pedidos
        function abrirModal(pedidoId) {
            const modal = document.getElementById('modal-' + pedidoId);
            if (modal) {
                modal.classList.add('ativo');
                document.body.style.overflow = 'hidden';
            } else {
                console.error('Modal não encontrado: modal-' + pedidoId);
            }
        }
        
        function fecharModal(pedidoId) {
            const modal = document.getElementById('modal-' + pedidoId);
            if (modal) {
                modal.classList.remove('ativo');
                document.body.style.overflow = 'auto';
            }
        }
        
        // Fechar ao clicar fora do modal
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('ativo');
                document.body.style.overflow = 'auto';
            }
        });
        
        // Prevenir que cliques no conteúdo do modal o fechem
        document.addEventListener('click', function(e) {
            if (e.target.closest('.modal-content')) {
                e.stopPropagation();
            }
        });
        
        // Menu Mobile
        function toggleMobileMenu() {
            const hamburger = document.querySelector('.hamburger');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const menu = document.querySelector('.mobile-menu');
            
            if (hamburger && overlay && menu) {
                hamburger.classList.toggle('open');
                overlay.classList.toggle('active');
                menu.classList.toggle('active');
                
                // Prevenir scroll do body quando menu está aberto
                document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
            }
        }
        
        function closeMobileMenu() {
            const hamburger = document.querySelector('.hamburger');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const menu = document.querySelector('.mobile-menu');
            
            if (hamburger && overlay && menu) {
                hamburger.classList.remove('open');
                overlay.classList.remove('active');
                menu.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Toggle do Dropdown do Usuário
        function toggleUserDropdown() {
            const dropdown = document.querySelector('.user-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }
        
        // Efeito de scroll na navbar
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.user-dropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        // Toggle da Busca
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
                const isOpen = searchPanel.classList.toggle('active');
                searchToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                if (isOpen && searchInput) {
                    searchInput.focus();
                }
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
        
        // Funcionalidade de Busca
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && this.value.trim()) {
                        // Redirecionar para index.php com termo de busca
                        window.location.href = '../index.php?search=' + encodeURIComponent(this.value.trim());
                    }
                });
            }
        });
        
        // Funções do Carrinho
        function getCart() {
            const cart = localStorage.getItem('dz_cart');
            return cart ? JSON.parse(cart) : [];
        }
        
        function updateCartBadge() {
            const cart = getCart();
            const totalItems = cart.reduce((total, item) => total + item.qty, 0);
            const badge = document.getElementById('cartBadge');
            
            if (badge) {
                badge.textContent = totalItems;
                badge.style.animation = 'none';
                setTimeout(() => {
                    badge.style.animation = 'bounce 0.5s ease';
                }, 10);
            }
        }
    </script>
</head>
<body>
    <!-- ===== NAVBAR PREMIUM RARE7 ===== -->
    <header class="header-loja" id="navbar">
        <div class="container-header">
            <!-- Logo RARE7 -->
            <a href="../index.php" class="logo-container" title="Voltar à página inicial" style="text-decoration: none; color: inherit;">
                <img src="../assets/images/logo.png" alt="RARE7" class="logo-dz-oficial">
                <span class="logo-text">RARE7</span>
            </a>
            
            <!-- Navegação -->
            <nav class="nav-loja">
                <ul>
                    <li><a href="../produtos.php">TODOS</a></li>
                    
                    <li class="has-dropdown">
                        <a href="../produtos.php?menu=unhas">UNHAS <span class="dropdown-icon">▼</span></a>
                        <div class="dropdown-menu">
                            <ul>
                                <li><a href="../produtos.php?categoria=Esmaltes">Esmaltes</a></li>
                                <li><a href="../produtos.php?categoria=Géis">Géis</a></li>
                                <li><a href="../produtos.php?categoria=Preparadores">Preparadores</a></li>
                                <li><a href="../produtos.php?categoria=Molde">Molde</a></li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="has-dropdown">
                        <a href="../produtos.php?menu=cilios">CÍLIOS <span class="dropdown-icon">▼</span></a>
                        <div class="dropdown-menu">
                            <ul>
                                <li><a href="../produtos.php?categoria=Cola">Cola</a></li>
                                <li><a href="../produtos.php?categoria=Removedor">Removedor</a></li>
                                <li><a href="../produtos.php?categoria=Fio a fio">Fio a fio</a></li>
                                <li><a href="../produtos.php?categoria=Postiço">Postiço</a></li>
                                <li><a href="../produtos.php?categoria=Tufo">Tufo</a></li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="has-dropdown">
                        <a href="../produtos.php?menu=eletronicos">ELETRÔNICOS <span class="dropdown-icon">▼</span></a>
                        <div class="dropdown-menu">
                            <ul>
                                <li><a href="../produtos.php?categoria=Cabine">Cabine</a></li>
                                <li><a href="../produtos.php?categoria=Motor">Motor</a></li>
                                <li><a href="../produtos.php?categoria=Luminária">Luminária</a></li>
                                <li><a href="../produtos.php?categoria=Coletor">Coletor</a></li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="has-dropdown">
                        <a href="../produtos.php?menu=ferramentas">FERRAMENTAS <span class="dropdown-icon">▼</span></a>
                        <div class="dropdown-menu">
                            <ul>
                                <li><a href="../produtos.php?categoria=Alicates">Alicates</a></li>
                                <li><a href="../produtos.php?categoria=Espátulas">Espátulas</a></li>
                                <li><a href="../produtos.php?categoria=Tesouras">Tesouras</a></li>
                                <li><a href="../produtos.php?categoria=Cortadores">Cortadores</a></li>
                                <li><a href="../produtos.php?categoria=Lixas">Lixas</a></li>
                                <li><a href="../produtos.php?categoria=Empurradores">Empurradores</a></li>
                                <li><a href="../produtos.php?categoria=Pincéis">Pincéis</a></li>
                                <li><a href="../produtos.php?categoria=Pinças">Pinças</a></li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="has-dropdown">
                        <a href="../produtos.php?secao=marcas">MARCAS <span class="dropdown-icon">▼</span></a>
                        <div class="dropdown-menu">
                            <ul>
                                <li><a href="../produtos.php?marca=D%26Z">RARE7</a></li>
                                <li><a href="../produtos.php?marca=Sioux">Sioux</a></li>
                                <li><a href="../produtos.php?marca=Sunny%27s">Sunny's</a></li>
                                <li><a href="../produtos.php?marca=Crush">Crush</a></li>
                                <li><a href="../produtos.php?marca=XD">XD</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </nav>
            
            <!-- Lado direito: Busca + Ícones -->
            <div class="nav-right">
                <div class="search-panel" id="searchPanel">
                    <input type="search" id="searchInput" placeholder="Buscar produtos" aria-label="Buscar produtos">
                </div>
                
                <!-- Área do usuário -->
                <div class="user-area">
                    <!-- Menu Mobile Toggle (apenas para mobile) -->
                    <button class="mobile-menu-toggle" onclick="toggleMobileMenu(event)">
                        <div class="hamburger">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </button>
                    
                    <button class="btn-icon btn-search" id="searchToggle" title="Pesquisar" aria-expanded="false" aria-controls="searchPanel">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                </button>
                
                <?php if (!$usuarioLogado): ?>
                    <!-- Botões de Login e Cadastro (usuário NÃO logado) -->
                    <a href="login.php" class="btn-auth btn-login">Entrar</a>
                    <a href="register.php" class="btn-auth btn-register">Cadastrar</a>
                <?php else: ?>
                    <!-- Dropdown do usuário logado -->
                    <div class="user-dropdown">
                        <button class="user-dropdown-btn" onclick="toggleUserDropdown(event)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </button>
                        <div class="user-dropdown-menu">
                            <div class="user-greeting">Olá, <?php echo $nomeUsuario; ?></div>
                            <a href="minha-conta.php">Minha conta</a>
                            <a href="minha-conta.php?tab=pedidos">Meus pedidos</a>
                            <a href="rastreio.php">Rastrear pedido</a>
                            <a href="logout.php">Sair</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <button class="btn-cart" id="cartButton" data-open-mini-cart title="Carrinho">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                    <span>Carrinho</span>
                    <span class="cart-count" id="cartBadge">0</span>
                </button>
            </div>
            <!-- Fim user-area -->
            </div>
            <!-- Fim nav-right -->
        </div>
        <!-- Fim container-header -->
    </header>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" onclick="closeMobileMenu(event)"></div>
    
    <!-- Mobile Menu -->
    <nav class="mobile-menu">
        <ul>
            <li><a href="../index.php#unhas" onclick="closeMobileMenu()">Unhas</a></li>
            <li><a href="../index.php#cilios" onclick="closeMobileMenu()">Cílios</a></li>
            <li><a href="../index.php#kits" onclick="closeMobileMenu()">Kits</a></li>
            <li><a href="../index.php#novidades" onclick="closeMobileMenu()">Novidades</a></li>
        </ul>
    </nav>

    <div class="page-container">
        <div class="page-header">
            <h1>Meus Pedidos</h1>
            <p>Olá, <?php echo $nomeUsuario; ?>! Aqui estão todos os seus pedidos.</p>
        </div>
        
        <?php if (empty($pedidos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h2>Você ainda não tem pedidos</h2>
                <p>Que tal começar a explorar nossos produtos?</p>
                <a href="../index.php" class="btn-primary">Começar a Comprar</a>
            </div>
        <?php else: ?>
            <div class="pedidos-lista">
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="pedido-card">
                        <div class="pedido-info-compacta">
                            <span class="pedido-numero">Pedido <?php echo '#' . str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            <span class="pedido-status" style="background-color: <?php echo corStatus($pedido['status']); ?>">
                                <?php echo traduzirStatus($pedido['status']); ?>
                            </span>
                            <span class="pedido-data"><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></span>
                            <span class="pedido-valor">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                        </div>
                        <button class="btn-ver-detalhes" onclick="event.stopPropagation(); abrirModal(<?php echo $pedido['id']; ?>)">
                            Ver Detalhes
                        </button>
                    </div>
                    
                    <!-- Modal do Pedido -->
                    <div class="modal-overlay" id="modal-<?php echo $pedido['id']; ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 class="modal-titulo">Pedido #<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></h2>
                                <button class="btn-fechar" onclick="event.stopPropagation(); fecharModal(<?php echo $pedido['id']; ?>)">×</button>
                            </div>
                            <div class="modal-body">
                                <div class="info-item">
                                    <span class="info-label">Status</span>
                                    <span class="pedido-status" style="background-color: <?php echo corStatus($pedido['status']); ?>">
                                        <?php echo traduzirStatus($pedido['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Data do Pedido</span>
                                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Valor Total</span>
                                    <span class="info-value" style="color: var(--color-magenta); font-size: 1.5rem;">
                                        R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($pedido['forma_pagamento'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Forma de Pagamento</span>
                                        <span class="info-value">
                                            <?php 
                                            $formasPagamentoMap = [
                                                'cartao' => 'Cartão de Crédito',
                                                'debito' => 'Cartão de Débito',
                                                'pix' => 'Pix',
                                                'boleto' => 'Boleto Bancário'
                                            ];
                                            $formaPagamento = strtolower($pedido['forma_pagamento']);
                                            echo $formasPagamentoMap[$formaPagamento] ?? htmlspecialchars($pedido['forma_pagamento']);
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pedido['parcelas']) && $pedido['parcelas'] > 1): ?>
                                    <div class="info-item">
                                        <span class="info-label">Parcelamento</span>
                                        <span class="info-value">
                                            <?php 
                                            $valorParcela = !empty($pedido['valor_parcela']) && $pedido['valor_parcela'] > 0 
                                                ? $pedido['valor_parcela'] 
                                                : ($pedido['valor_total'] / $pedido['parcelas']);
                                            echo $pedido['parcelas'] . 'x de R$ ' . number_format($valorParcela, 2, ',', '.');
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pedido['observacoes'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Observações</span>
                                        <span class="info-value"><?php echo htmlspecialchars($pedido['observacoes']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($pedido['itens'])): ?>
                                    <div style="margin-top: 24px;">
                                        <div class="itens-header">Produtos</div>
                                        <div class="pedido-itens">
                                            <?php foreach ($pedido['itens'] as $item): ?>
                                                <div class="item-produto">
                                                    <div class="item-imagem">
                                                        <?php if (!empty($item['imagem'])): ?>
                                                            <img src="../../admin/assets/images/produtos/<?php echo htmlspecialchars($item['imagem']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($item['nome'] ?? 'Produto'); ?>"
                                                                 style="width: 100%; height: 100%; object-fit: cover;"
                                                                 onerror="this.parentElement.innerHTML='📦';">
                                                        <?php else: ?>
                                                            📦
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="item-info">
                                                        <div class="item-nome"><?php echo htmlspecialchars($item['nome'] ?? 'Produto'); ?></div>
                                                        <div class="item-detalhes">
                                                            Quantidade: <?php echo $item['quantidade']; ?>
                                                            <?php if (!empty($item['categoria'])): ?>
                                                                • Categoria: <?php echo htmlspecialchars($item['categoria']); ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="item-preco">
                                                        <div style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">
                                                            R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?> un.
                                                        </div>
                                                        <div>
                                                            R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // ===== FUNÇÕES DA NAVBAR =====
        
        // Menu Mobile
        function closeMobileMenu(event) {
            if (event) {
                event.stopPropagation();
            }
            
            const hamburger = document.querySelector('.hamburger');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const menu = document.querySelector('.mobile-menu');
            
            if (hamburger) hamburger.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            if (menu) menu.classList.remove('active');
            
            document.body.style.overflow = '';
        }
        
        function toggleMobileMenu(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            const hamburger = document.querySelector('.hamburger');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const menu = document.querySelector('.mobile-menu');
            
            hamburger.classList.toggle('open');
            overlay.classList.toggle('active');
            menu.classList.toggle('active');
            
            if (menu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        // Dropdown do usuário
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
        
        // Função para busca
        document.addEventListener('DOMContentLoaded', function() {
            const searchToggle = document.getElementById('searchToggle');
            const searchPanel = document.getElementById('searchPanel');
            
            if (searchToggle && searchPanel) {
                searchToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    searchPanel.classList.toggle('active');
                    
                    if (searchPanel.classList.contains('active')) {
                        setTimeout(() => {
                            const searchInput = document.getElementById('searchInput');
                            if (searchInput) searchInput.focus();
                        }, 300);
                    }
                });
            }
            
            // Logo - voltar para index
            const logoContainer = document.querySelector('.logo-container');
            if (logoContainer) {
                logoContainer.addEventListener('click', function() {
                    window.location.href = '../index.php';
                });
                logoContainer.style.cursor = 'pointer';
            }
            
            // ===== MONITORAR MINI-CART E ESCONDER CHAT =====
            function monitorMiniCart() {
                const miniCart = document.getElementById('miniCartDrawer');
                
                if (!miniCart) {
                    console.log('Mini-cart não encontrado');
                    return;
                }
                
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.attributeName === 'class') {
                            const chatBtn = document.querySelector('.chat-button');
                            const chatModal = document.getElementById('chatModal');
                            
                            if (miniCart.classList.contains('active')) {
                                if (chatBtn) chatBtn.classList.add('chat-hidden');
                                if (chatModal) {
                                    chatModal.classList.add('chat-hidden');
                                    chatModal.classList.remove('active');
                                }
                            } else {
                                if (chatBtn) chatBtn.classList.remove('chat-hidden');
                                if (chatModal) chatModal.classList.remove('chat-hidden');
                            }
                        }
                    });
                });
                
                observer.observe(miniCart, { attributes: true });
            }
            
            // ===== INICIALIZAR CHAT =====
            createChatButton();
            setTimeout(monitorMiniCart, 500);
            
            // ===== INICIALIZAR MINI CARRINHO =====
            console.log('🛒 Inicializando mini carrinho...');
            updateCartBadgeUI();
            
            const closeMiniCartBtn = document.getElementById('closeMiniCart');
            const miniCartOverlay = document.getElementById('miniCartOverlay');
            const cartButton = document.getElementById('cartButton');
            
            console.log('🔎 Verificando elementos:', {
                closeMiniCartBtn: !!closeMiniCartBtn,
                miniCartOverlay: !!miniCartOverlay,
                cartButton: !!cartButton
            });
            
            if (closeMiniCartBtn) {
                closeMiniCartBtn.addEventListener('click', closeMiniCart);
                console.log('✅ Event listener adicionado ao botão de fechar');
            }
            
            if (miniCartOverlay) {
                miniCartOverlay.addEventListener('click', closeMiniCart);
                console.log('✅ Event listener adicionado ao overlay');
            }
            
            if (cartButton) {
                cartButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🛍️ Clique no botão do carrinho! Abrindo mini cart...');
                    openMiniCart();
                });
                console.log('✅ Event listener adicionado ao botão do carrinho');
            } else {
                console.error('❌ Botão do carrinho não encontrado!');
            }
        });
        
        // ===== FUNÇÕES DO CHAT =====
        
        function createChatButton() {
            const chatBtn = document.createElement('button');
            chatBtn.className = 'chat-button';
            chatBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12c0 1.821.487 3.53 1.338 5L2.5 21.5l4.5-.838A9.955 9.955 0 0 0 12 22Z"/>
                    <path d="M8 12h.01M12 12h.01M16 12h.01" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <div class="chat-tooltip">Fale conosco!</div>
            `;
            
            chatBtn.addEventListener('click', function() {
                toggleChatModal();
            });
            
            document.body.appendChild(chatBtn);
            createChatModal();
        }
        
        function createChatModal() {
            const chatModal = document.createElement('div');
            chatModal.className = 'chat-modal';
            chatModal.id = 'chatModal';
            
            chatModal.innerHTML = `
                <div class="chat-header">
                    <div>
                        <h3>RARE7 Atendimento</h3>
                        <div class="online-status"><div class="online-indicator"></div><span>Online agora</span></div>
                    </div>
                    <button class="chat-close" onclick="toggleChatModal()">×</button>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message bot">
                        <div>Olá! 😊 Seja bem-vinda à RARE7! Como posso te ajudar hoje?</div>
                        <div class="chat-message-time">${getCurrentTime()}</div>
                    </div>
                    
                    <div class="typing-indicator" id="typingIndicator">
                        <div class="typing-dots">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                    </div>
                </div>
                
                <div class="chat-input-container">
                    <input type="text" class="chat-input" id="chatInput" placeholder="Digite sua mensagem..." maxlength="500">
                    <button class="chat-send" onclick="sendMessage()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="m2 21 21-9L2 3v7l15 2-15 2v7z"/>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(chatModal);
            
            const chatInput = chatModal.querySelector('#chatInput');
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
        
        function toggleChatModal() {
            const modal = document.getElementById('chatModal');
            modal.classList.toggle('active');
            
            if (modal.classList.contains('active')) {
                setTimeout(() => {
                    const input = document.getElementById('chatInput');
                    input.focus();
                }, 300);
            }
        }
        
        function getCurrentTime() {
            const now = new Date();
            return `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
        }
        
        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (message === '') return;
            
            addMessage(message, 'user');
            input.value = '';
            
            setTimeout(() => {
                showTyping();
                setTimeout(() => {
                    hideTyping();
                    respondToMessage(message);
                }, Math.random() * 2000 + 1000);
            }, 500);
        }
        
        function addMessage(text, sender) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${sender}`;
            
            messageDiv.innerHTML = `
                <div>${text}</div>
                <div class="chat-message-time">${getCurrentTime()}</div>
            `;
            
            messagesContainer.insertBefore(messageDiv, document.getElementById('typingIndicator'));
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function showTyping() {
            const typingIndicator = document.getElementById('typingIndicator');
            typingIndicator.style.display = 'block';
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        function hideTyping() {
            const typingIndicator = document.getElementById('typingIndicator');
            typingIndicator.style.display = 'none';
        }
        
        function respondToMessage(userMessage) {
            const responses = getResponseForMessage(userMessage.toLowerCase());
            const randomResponse = responses[Math.floor(Math.random() * responses.length)];
            addMessage(randomResponse, 'bot');
        }
        
        function getResponseForMessage(message) {
            if (message.includes('preço') || message.includes('valor') || message.includes('quanto custa')) {
                return [
                    'Nossas camisas têm preços a partir de R$ 99,90! 😊 Você busca clube, seleção ou modelo retrô?',
                    'Temos opções para todos os orçamentos, com modelos premium e versões torcedor. 💰'
                ];
            }
            
            if (message.includes('entrega') || message.includes('frete') || message.includes('envio')) {
                return [
                    'Entrega grátis para compras acima de R$ 299! 🚚 Entregamos em todo o Brasil em até 5 dias úteis.',
                    'Para pedidos abaixo do frete grátis, o valor varia conforme o CEP e a transportadora. 🚚'
                ];
            }
            
            if (message.includes('time') || message.includes('clube') || message.includes('camisa')) {
                return [
                    'Temos camisas de clubes nacionais e internacionais, além de versões retrô incríveis! ⚽',
                    'Se quiser, te ajudo a encontrar camisa por time, temporada ou faixa de preço. 👕'
                ];
            }
            
            if (message.includes('seleção') || message.includes('selecao')) {
                return [
                    'Temos camisas de seleções clássicas e atuais para você vestir sua paixão em dias de jogo! 🇧🇷',
                    'Posso te mostrar opções de seleções por tamanho e disponibilidade em estoque. 🔎'
                ];
            }
            
            if (message.includes('desconto') || message.includes('promoção') || message.includes('cupom')) {
                return [
                    'Sempre temos campanhas especiais em dias de jogo e lançamentos da temporada! 🎉',
                    'Posso te avisar das promoções ativas e cupons disponíveis no momento. 😎'
                ];
            }
            
            if (message.includes('whatsapp') || message.includes('telefone') || message.includes('contato')) {
                return [
                    'Nosso WhatsApp é (11) 99999-9999! Mas aqui no chat também consigo te ajudar perfeitamente! 😊',
                    'Para contato direto: contato@rare7.com.br ou (11) 99999-9999. Como posso te ajudar agora? 💬'
                ];
            }
            
            return [
                'Que interessante! Posso te ajudar com informações sobre camisas de clubes e seleções. O que você procura? 😊',
                'Claro! Estou aqui para esclarecer suas dúvidas sobre tamanhos, modelos e envio. ✨',
                'Entendi! Quer que eu te indique os modelos mais procurados no momento? ⚽'
            ];
        }
        
        // ===== MINI CARRINHO - JAVASCRIPT =====
        const FREE_SHIPPING_THRESHOLD = <?php echo $freteGratisValor; ?>;

        // Funções de gerenciamento do carrinho
        function getCart() {
            const cart = localStorage.getItem('dz_cart');
            return cart ? JSON.parse(cart) : [];
        }

        function setCart(cart) {
            localStorage.setItem('dz_cart', JSON.stringify(cart));
        }
        
        function removeFromCart(itemId, variant = '') {
            console.log('🗑️ Tentando remover produto:', itemId, variant);
            let cart = getCart();
            
            const numericItemId = (itemId === 0 || itemId === '0') ? 0 : (parseInt(itemId) || itemId);
            const initialLength = cart.length;
            
            cart = cart.filter((item) => {
                const itemNumericId = (item.id === 0 || item.id === '0') ? 0 : (parseInt(item.id) || item.id);
                const itemVariant = item.variant || '';
                return !(itemNumericId === numericItemId && itemVariant === variant);
            });
            
            const removedCount = initialLength - cart.length;
            setCart(cart);
            updateCartBadgeUI();
            
            if (removedCount > 0) {
                renderMiniCart();
                showNotification('Produto removido do carrinho', 'info');
            }
        }

        function updateQty(itemId, variant, newQty) {
            const cart = getCart();
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
                    updateCartBadgeUI();
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

        function updateCartBadgeUI() {
            const cart = getCart();
            const totalItems = cart.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
            
            const cartBadge = document.getElementById('cartBadge') || document.querySelector('.cart-count');
            const cartButton = document.getElementById('cartButton');
            
            if (cartBadge) {
                cartBadge.textContent = totalItems;
                cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
                if (cartButton) {
                    if (totalItems > 0) {
                        cartButton.classList.add('has-items');
                    } else {
                        cartButton.classList.remove('has-items');
                    }
                }
            }
        }

        function renderMiniCart() {
            const cart = getCart();
            const body = document.getElementById('miniCartBody');
            const subtotalEl = document.getElementById('miniCartSubtotal');
            const freeShippingBar = document.getElementById('freeShippingBar');
            
            if (!body) return;
            
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
                    <div class="shipping-progress"><div class="shipping-progress-bar" style="width: 0%"></div></div>
                `;
                return;
            }
            
            body.innerHTML = cart.map((item) => {
                const itemPrice = (typeof item.price === 'number' && !isNaN(item.price)) ? item.price : 0;
                const itemQty = parseInt(item.qty) || 1;
                const itemId = item.id || 0;
                const itemVariant = item.variant || '';
                const itemName = item.name || 'Produto';
                const itemImage = item.image || '';
                
                const escapedName = itemName.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                
                return `
                <div class="cart-item" data-product-id="${itemId}">
                    <div class="cart-item-image">
                        ${itemImage && itemImage.startsWith('http') ? `<img src="${itemImage}" alt="${escapedName}" loading="lazy">` : `<span style="font-size: 2rem;">${itemImage || '💅'}</span>`}
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-name">${itemName}</div>
                        ${itemVariant ? `<div class="cart-item-variant">${itemVariant}</div>` : ''}
                        <div class="cart-item-price">R$ ${itemPrice.toFixed(2).replace('.', ',')}</div>
                        <div class="cart-item-actions">
                            <div class="qty-control">
                                <button class="qty-btn" onclick="updateQty(${itemId}, '', ${itemQty - 1})" ${itemQty <= 1 ? 'disabled' : ''}>−</button>
                                <span class="qty-value">${itemQty}</span>
                                <button class="qty-btn" onclick="updateQty(${itemId}, '', ${itemQty + 1})">+</button>
                            </div>
                            <button class="btn-remove-item" onclick="removeFromCart(${itemId}, '')" title="Remover">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            }).join('');
            
            const subtotal = getSubtotal();
            subtotalEl.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
            
            const remaining = FREE_SHIPPING_THRESHOLD - subtotal;
            const progress = Math.min((subtotal / FREE_SHIPPING_THRESHOLD) * 100, 100);
            
            if (remaining > 0) {
                freeShippingBar.innerHTML = `
                    <div class="shipping-text">Faltam R$ ${remaining.toFixed(2).replace('.', ',')} para frete grátis</div>
                    <div class="shipping-progress"><div class="shipping-progress-bar" style="width: ${progress}%"></div></div>
                `;
            } else {
                freeShippingBar.innerHTML = `
                    <div class="shipping-unlocked">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                        </svg>
                        Parabéns! Você ganhou frete grátis! 🎉
                    </div>
                `;
            }
        }

        function openMiniCart() {
            console.log('🚪 Tentando abrir mini cart...');
            const overlay = document.getElementById('miniCartOverlay');
            const drawer = document.getElementById('miniCartDrawer');
            console.log('🔎 Elementos encontrados:', { overlay: !!overlay, drawer: !!drawer });
            
            if (overlay && drawer) {
                overlay.classList.add('active');
                drawer.classList.add('active');
                document.body.style.overflow = 'hidden';
                renderMiniCart();
                console.log('✨ Mini cart aberto com sucesso!');
                
                // Esconder chat quando carrinho abre
                const chatBtn = document.querySelector('.chat-button');
                const chatModal = document.getElementById('chatModal');
                if (chatBtn) chatBtn.classList.add('chat-hidden');
                if (chatModal) {
                    chatModal.classList.add('chat-hidden');
                    chatModal.classList.remove('active');
                }
            } else {
                console.error('❌ Não foi possível abrir o mini cart - elementos não encontrados!');
            }
        }

        function closeMiniCart() {
            const overlay = document.getElementById('miniCartOverlay');
            const drawer = document.getElementById('miniCartDrawer');
            if (overlay && drawer) {
                overlay.classList.remove('active');
                drawer.classList.remove('active');
                document.body.style.overflow = '';
                
                // Mostrar chat quando carrinho fecha
                const chatBtn = document.querySelector('.chat-button');
                const chatModal = document.getElementById('chatModal');
                if (chatBtn) chatBtn.classList.remove('chat-hidden');
                if (chatModal) chatModal.classList.remove('chat-hidden');
            }
        }

        function showNotification(message, type = 'success') {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    </script>
    
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
            <a href="carrinho.php" class="btn-view-cart">Ver carrinho completo</a>
        </div>
    </div>

    <style>
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

        .mini-cart-body {
            flex: 1;
            overflow-y: auto;
            padding: 14px;
            background: #f8fafc;
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
            cursor: pointer;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 70px 1fr;
            gap: 12px;
            padding: 14px;
            background: white;
            border-radius: 12px;
            margin-bottom: 10px;
            border: 1px solid #f1f5f9;
        }

        .cart-item:hover {
            background: #fafafa;
            border-color: #e2e8f0;
            box-shadow: 0 2px 8px rgba(230, 0, 126, 0.08);
        }

        .cart-item-image {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cart-item-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .cart-item-variant {
            font-size: 0.75rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .cart-item-price {
            font-weight: 700;
            color: var(--color-magenta);
            font-size: 1.05rem;
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
            font-size: 15px;
        }

        .qty-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
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
        }

        .btn-remove-item {
            width: 30px;
            height: 30px;
            border-radius: 15px;
            border: none;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove-item:hover {
            background: #ef4444;
            color: white;
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
        }

        .shipping-progress {
            height: 6px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 3px;
            overflow: hidden;
        }

        .shipping-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            border-radius: 4px;
            transition: width 0.5s ease;
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
        }

        .mini-cart-subtotal strong {
            color: var(--color-magenta);
            font-size: 1.3rem;
            font-weight: 700;
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
            box-shadow: 0 4px 12px rgba(230, 0, 126, 0.25);
        }

        .btn-view-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(230, 0, 126, 0.4);
        }

        @media (max-width: 480px) {
            .mini-cart-drawer {
                width: 100%;
            }
        }
    </style>

<?php require_once '../includes/mini-cart.php'; ?>
<?php require_once '../includes/footer.php'; ?>

</body>
</html>

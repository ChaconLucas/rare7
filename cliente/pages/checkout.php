<?php
/**
 * Checkout - FinalizaÃ§Ã£o de Compra
 * Sistema de pagamento integrado com admin
 */

session_start();
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

// Instanciar CMS Provider
$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

// Verificar se usuÃ¡rio estÃ¡ logado
$usuarioLogado = isset($_SESSION['cliente']);
$clienteData = $usuarioLogado ? $_SESSION['cliente'] : null;
$nomeUsuario = $usuarioLogado ? htmlspecialchars($clienteData['nome']) : '';

// Buscar dados do cliente se logado
$clienteCompleto = null;
if ($usuarioLogado && isset($clienteData['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$clienteData['id']]);
    $clienteCompleto = $stmt->fetch();
}

// Buscar configuraÃ§Ãµes de gateway/pagamento do admin
$gatewayAtivo = false;
$gatewayConfigurado = false;
$formasPagamento = []; // SerÃ¡ populado dinamicamente
$paymentConfig = null;

try {
    // Verificar se existe tabela payment_settings
    $checkTable = $pdo->query("SHOW TABLES LIKE 'payment_settings'");
    if ($checkTable->rowCount() > 0) {
        $stmt = $pdo->query("SELECT * FROM payment_settings WHERE id = 1 LIMIT 1");
        $paymentConfig = $stmt->fetch();
        
        if ($paymentConfig) {
            $gatewayAtivo = (bool)$paymentConfig['gateway_active'];
            
            // Verificar se as credenciais do gateway estÃ£o configuradas
            $hasPublicKey = !empty($paymentConfig['public_key']);
            $hasSecretKey = !empty($paymentConfig['secret_key']);
            $hasClientId = !empty($paymentConfig['client_id']);
            $hasClientSecret = !empty($paymentConfig['client_secret']);
            
            // Gateway estÃ¡ realmente configurado se tiver pelo menos um par de credenciais
            $gatewayConfigurado = ($hasPublicKey && $hasSecretKey) || ($hasClientId && $hasClientSecret);
            
            // Construir array de formas de pagamento baseado nas configuraÃ§Ãµes
            if ($paymentConfig['method_pix']) {
                $formasPagamento[] = 'Pix';
            }
            if ($paymentConfig['method_credit_card']) {
                $formasPagamento[] = 'CartÃ£o de CrÃ©dito';
            }
            if ($paymentConfig['method_debit_card']) {
                $formasPagamento[] = 'CartÃ£o de DÃ©bito';
            }
            if ($paymentConfig['method_boleto']) {
                $formasPagamento[] = 'Boleto';
            }
        }
    }
    
    // Se nÃ£o encontrou configuraÃ§Ãµes ou nenhum mÃ©todo estÃ¡ ativo, usar padrÃ£o
    if (empty($formasPagamento)) {
        $formasPagamento = ['Pix', 'CartÃ£o de CrÃ©dito', 'CartÃ£o de DÃ©bito', 'Boleto'];
    }
} catch (PDOException $e) {
    // Tabela nÃ£o existe ou erro - usar mÃ©todos padrÃ£o
    error_log("Payment settings check error: " . $e->getMessage());
    $formasPagamento = ['Pix', 'CartÃ£o de CrÃ©dito', 'CartÃ£o de DÃ©bito', 'Boleto'];
}

$pageTitle = 'Finalizar Compra - D&Z';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/Logodz.png">
    
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <!-- Estilos CSS (removido para evitar conflitos) -->
    
    <style>
        :root {
            --color-magenta: #E6007E;
            --color-magenta-dark: #C4006A;
            --color-rose-light: #FDF2F8;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-error: #ef4444;
        }
        
        /* ==== ESTILOS COMPLETOS DO NAVBAR ==== */
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
        
        .header-loja.scrolled {
            padding: 8px 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.12);
        }
        
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
            overflow: visible !important;
            position: relative !important;
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
            position: static !important;
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
        .header-loja .nav-loja > ul > li,
        .nav-loja > ul > li {
            flex-shrink: 0 !important;
            position: relative !important;
        }
        
        .has-dropdown {
            position: relative !important;
        }
        
        .nav-loja a {
            color: #2d3748;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 10px 13px;
            border-radius: 25px;
            position: relative;
            transition: all 0.3s;
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
            transition: opacity 0.3s ease, 
                        visibility 0.3s ease, 
                        transform 0.3s ease;
            z-index: 1001;
            padding: 12px 0;
            margin-top: 8px;
            pointer-events: none;
        }
        
        .has-dropdown:hover .dropdown-menu,
        .dropdown-menu:hover {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .dropdown-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .dropdown-menu li {
            display: block;
            width: 100%;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 10px 20px;
            color: #2d3748;
            border-radius: 0;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .dropdown-menu a:hover {
            background: rgba(230, 0, 126, 0.08);
            color: var(--color-magenta);
            transform: translateX(4px);
        }
        
        .nav-loja a:hover {
            color: var(--color-magenta);
            background: rgba(230, 0, 126, 0.08);
            transform: translateY(-2px);
        }
        
        .has-dropdown > a:hover {
            transform: none !important;
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
            position: relative;
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
        }

        header.header-loja .nav-right .search-panel.active,
        .header-loja .search-panel.active {
            width: auto !important;
            min-width: 160px !important;
            max-width: 220px !important;
            opacity: 1 !important;
            flex: 1 1 auto !important;
        }

        header.header-loja .nav-right .search-panel input,
        .header-loja .search-panel input {
            width: 100% !important;
            min-width: 160px !important;
            max-width: 220px !important;
            padding: 10px 40px 10px 14px !important;
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
        
        .search-submit-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            padding: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: color 0.3s ease;
            border-radius: 4px;
        }
        
        .search-submit-btn:hover {
            color: var(--color-magenta);
            background: rgba(230, 0, 126, 0.1);
        }
        
        .search-submit-btn svg {
            width: 18px;
            height: 18px;
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
        
        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .cart-count {
            background: white;
            color: #E6007E;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .btn-auth {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
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
        
        .user-dropdown {
            position: relative;
            z-index: 100;
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
            transition: all 0.3s;
            position: relative;
            z-index: 101;
            pointer-events: auto;
        }
        
        .user-dropdown-btn svg {
            pointer-events: none;
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
            transition: all 0.3s;
            z-index: 1001;
        }
        
        .user-dropdown.active .user-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
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
        }
        
        .user-dropdown-menu a:hover {
            background: rgba(230, 0, 126, 0.05);
            color: var(--color-magenta);
        }
        
        .mobile-menu-toggle,
        .mobile-menu,
        .mobile-menu-overlay {
            display: none !important;
        }
        
        /* Responsividade Navbar - Tablets */
        @media (max-width: 968px) {
            header.header-loja .container-header,
            .header-loja .container-header {
                padding: 0 4px !important;
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
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-loja {
                padding: 8px 0 !important;
            }
            
            .logo-dz-oficial {
                height: 35px !important;
            }
            
            .logo-text {
                font-size: 1.4rem !important;
            }
            
            .nav-loja {
                display: none !important;
            }
            
            .btn-search {
                display: none !important;
            }
        }
        
        /* ==== ESTILOS DO CHECKOUT ==== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f9fafb;
            color: #1f2937;
            line-height: 1.6;
        }
        
        /* CONTAINER */
        .checkout-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .checkout-header h1 {
            font-size: 2rem;
            color: var(--color-magenta);
            margin-bottom: 12px;
        }
        
        .checkout-header p {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #ecfdf5;
            color: var(--color-success);
            padding: 10px 18px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-top: 18px;
        }
        
        /* GRID LAYOUT */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 32px;
        }
        
        /* SECTIONS */
        .checkout-section {
            background: white;
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }
        
        .checkout-section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title .material-symbols-sharp {
            color: var(--color-magenta);
            font-size: 28px;
        }
        
        /* FORM */
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 0.875rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--color-magenta);
            box-shadow: 0 0 0 3px rgba(230, 0, 126, 0.1);
        }
        
        .form-group input:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        
        /* RESUMO DO PEDIDO */
        .order-summary {
            position: sticky;
            top: 100px;
        }
        
        .summary-item {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 20px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .summary-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .summary-item-info {
            flex: 1;
        }
        
        .summary-item-name {
            font-weight: 600;
            color: #111827;
            font-size: 0.875rem;
            margin-bottom: 4px;
        }
        
        .summary-item-qty {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .summary-item-price {
            font-weight: 700;
            color: var(--color-magenta);
            white-space: nowrap;
        }
        
        .summary-totals {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 2px solid #f3f4f6;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            font-size: 0.875rem;
        }
        
        .summary-row.total {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }
        
        .summary-row.total .value {
            color: var(--color-magenta);
        }
        
        .summary-row .label {
            color: #6b7280;
        }
        
        .summary-row .value {
            font-weight: 600;
            color: #111827;
        }
        
        .summary-row.discount .value {
            color: var(--color-success);
        }
        
        /* FORMAS DE PAGAMENTO */
        .payment-methods {
            display: grid;
            gap: 18px;
        }
        
        .payment-method {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        
        .payment-method:hover {
            border-color: var(--color-magenta);
            background: var(--color-rose-light);
        }
        
        .payment-method.selected {
            border-color: var(--color-magenta);
            background: var(--color-rose-light);
            box-shadow: 0 0 0 3px rgba(230, 0, 126, 0.1);
        }
        
        .payment-method input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: var(--color-magenta);
        }
        
        .payment-method-info {
            flex: 1;
        }
        
        .payment-method-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .payment-method-desc {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .payment-method-icon {
            font-size: 32px;
            color: var(--color-magenta);
        }
        
        /* BOTÃ•ES */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            width: 100%;
            margin-top: 24px;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        /* AVISOS */
        .alert {
            padding: 18px 22px;
            border-radius: 12px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        
        .alert .material-symbols-sharp {
            font-size: 24px;
        }
        
        /* LOADING */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loading-content {
            background: white;
            padding: 48px;
            border-radius: 16px;
            text-align: center;
        }
        
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--color-magenta);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 24px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* RESPONSIVE */
        @media (max-width: 968px) {
            .checkout-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .order-summary {
                position: relative;
                top: 0;
                order: -1;
            }
            
            .form-row,
            .form-row-3 {
                grid-template-columns: 1fr;
            }
            
            .checkout-section {
                padding: 28px;
            }
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                margin-top: 100px;
            }
            
            .checkout-header h1 {
                font-size: 1.5rem;
            }
            
            .checkout-section {
                padding: 24px;
            }
            
            .payment-method {
                padding: 20px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
        }
        
        /* ===== FOOTER STYLES ===== */
        .footer-modern {
            background: linear-gradient(135deg, #fefefe 0%, #f8f9fa 100%);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 60px 0 0;
            margin-top: 100px;
            position: relative;
        }
        
        .footer-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, var(--color-magenta) 50%, transparent 100%);
            opacity: 0.4;
        }
        
        .footer-content {
            position: relative;
        }
        
        .footer-top {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 60px;
            margin-bottom: 50px;
        }
        
        .footer-brand {
            max-width: 400px;
        }
        
        .brand-logo h3 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--color-magenta), #d946ef);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .brand-tagline {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        
        .brand-description {
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        
        .footer-social-main {
            padding: 20px;
        }
        
        .social-links-grid {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: rgba(230, 0, 126, 0.05);
            border-radius: 12px;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(230, 0, 126, 0.1);
        }
        
        .social-btn:hover {
            background: var(--color-magenta);
            color: white;
            transform: translateY(-2px);
            border-color: var(--color-magenta);
        }
        
        .social-btn .social-icon {
            font-size: 1.3rem;
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }
        
        .social-btn:hover .social-icon {
            transform: scale(1.1);
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 50px;
        }
        
        .footer-column h5 {
            color: #1f2937;
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
        }
        
        .footer-column h5::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 25px;
            height: 2px;
            background: var(--color-magenta);
            border-radius: 1px;
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 12px;
        }
        
        .footer-column a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .footer-column a:hover {
            color: var(--color-magenta);
            transform: translateX(3px);
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .contact-icon {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        .footer-security {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-bottom: 40px;
            padding: 24px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .payment-methods-section,
        .shipping-methods-section,
        .security-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        
        .footer-security h6 {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 0;
            text-align: center;
        }
        
        .payment-icons,
        .shipping-icons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .payment-icon,
        .shipping-icon {
            transition: all 0.3s ease;
            opacity: 0.7;
            filter: grayscale(30%);
        }
        
        .payment-icon:hover,
        .shipping-icon:hover {
            transform: scale(1.05);
            opacity: 1;
            filter: grayscale(0%);
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .security-badge:hover {
            opacity: 1;
            transform: scale(1.02);
        }
        
        .security-icon {
            transition: all 0.3s ease;
        }
        
        .security-text {
            font-size: 0.75rem;
            font-weight: 600;
            color: #28A745;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .ssl-protection {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ssl-icon,
        .trust-icon {
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }
        
        .ssl-text {
            font-size: 0.75rem;
            font-weight: 600;
            color: #2ECC71;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        
        .trust-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            min-width: 90px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .trust-badge:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .trust-badge:hover .trust-icon,
        .trust-badge:hover .ssl-icon {
            transform: scale(1.1);
        }
        
        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            background: #fafafa;
            margin: 0 -30px;
            padding-left: 30px;
            padding-right: 30px;
        }
        
        .copyright {
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .container-dz {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        @media (max-width: 768px) {
            .footer-modern {
                padding: 40px 0 0;
            }
            
            .footer-top {
                grid-template-columns: 1fr;
                gap: 35px;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                margin: 0 -15px;
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .footer-security {
                gap: 12px;
                padding: 20px 0;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .trust-badge {
                min-width: 70px;
                padding: 6px 8px;
                gap: 4px;
            }
            
            .payment-icons,
            .shipping-icons {
                gap: 14px;
            }
            
            .payment-icon,
            .shipping-icon {
                width: 20px;
                height: 13px;
            }
            
            .ssl-icon,
            .trust-icon {
                width: 20px;
                height: 20px;
            }
            
            .ssl-text {
                font-size: 0.65rem;
            }
            
            .security-icon {
                width: 14px;
                height: 14px;
            }
            
            .security-text {
                font-size: 0.7rem;
            }
        }
        
        /* Card Payment Brick Container */
        #cardPaymentBrick_container {
            transition: all 0.3s ease;
        }
        
        .brick-loader {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
    
    <?php if ($gatewayConfigurado && $paymentConfig): ?>
    <!-- Mercado Pago SDK -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <!-- SweetAlert2 para feedback visual -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const MP_PUBLIC_KEY = '<?php echo htmlspecialchars($paymentConfig['public_key']); ?>';
    </script>
    <?php endif; ?>
</head>
<body>
    
    <!-- NAVBAR -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- LOADING OVERLAY -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Processando seu pedido...</p>
        </div>
    </div>
    
    <!-- CHECKOUT CONTAINER -->
    <div class="checkout-container">
        
        <!-- HEADER -->
        <div class="checkout-header">
            <h1>Finalizar Compra</h1>
            <p>Revise seus dados e confirme seu pedido</p>
            <div class="secure-badge">
                <span class="material-symbols-sharp">lock</span>
                Compra 100% Segura e Protegida
            </div>
        </div>
        
        <!-- GRID -->
        <div class="checkout-grid">
            
            <!-- COLUNA PRINCIPAL: FORMULÃRIOS -->
            <div class="checkout-main">
                
                <!-- DADOS DO CLIENTE -->
                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="material-symbols-sharp">person</span>
                        Seus Dados
                    </h2>
                    
                    <?php if (!$usuarioLogado): ?>
                    <div class="alert alert-info">
                        <span class="material-symbols-sharp">info</span>
                        VocÃª nÃ£o estÃ¡ logado. Preencha seus dados para continuar.
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nome Completo *</label>
                            <input type="text" id="nome" placeholder="Seu nome completo" 
                                   value="<?php echo $clienteCompleto['nome'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>E-mail *</label>
                            <input type="email" id="email" placeholder="seu@email.com" 
                                   value="<?php echo $clienteCompleto['email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Telefone *</label>
                            <input type="tel" id="telefone" placeholder="(00) 00000-0000" 
                                   value="<?php echo $clienteCompleto['telefone'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>CPF/CNPJ *</label>
                            <input type="text" id="cpf_cnpj" placeholder="000.000.000-00" 
                                   value="<?php echo $clienteCompleto['cpf_cnpj'] ?? ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <!-- ENDEREÃ‡O DE ENTREGA -->
                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="material-symbols-sharp">local_shipping</span>
                        EndereÃ§o de Entrega
                    </h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>CEP *</label>
                            <input type="text" id="cep" placeholder="00000-000" required>
                        </div>
                        <div class="form-group">
                            <label>Rua *</label>
                            <input type="text" id="rua" placeholder="Nome da rua" 
                                   value="<?php echo $clienteCompleto['endereco'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>NÃºmero *</label>
                            <input type="text" id="numero" placeholder="NÂº" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Complemento</label>
                            <input type="text" id="complemento" placeholder="Apto, bloco, etc">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bairro *</label>
                            <input type="text" id="bairro" placeholder="Bairro" required>
                        </div>
                        <div class="form-group">
                            <label>Cidade *</label>
                            <input type="text" id="cidade" placeholder="Cidade" 
                                   value="<?php echo $clienteCompleto['cidade'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Estado *</label>
                        <select id="estado" required>
                            <option value="">Selecione o estado</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">AmapÃ¡</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">CearÃ¡</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">EspÃ­rito Santo</option>
                            <option value="GO">GoiÃ¡s</option>
                            <option value="MA">MaranhÃ£o</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">ParÃ¡</option>
                            <option value="PB">ParaÃ­ba</option>
                            <option value="PR">ParanÃ¡</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">PiauÃ­</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">RondÃ´nia</option>
                            <option value="RR">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP" <?php echo ($clienteCompleto['estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>SÃ£o Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                    </div>
                </div>
                
                <!-- FORMA DE PAGAMENTO -->
                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="material-symbols-sharp">credit_card</span>
                        Forma de Pagamento
                    </h2>
                    
                    <?php if (!$gatewayConfigurado): ?>
                    <div class="alert alert-warning">
                        <span class="material-symbols-sharp">warning</span>
                        <div>
                            <strong>Gateway de pagamento nÃ£o configurado</strong><br>
                            Configure as credenciais (chaves API/tokens) no painel administrativo para habilitar pagamentos online.
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($formasPagamento)): ?>
                    <div class="alert alert-warning">
                        <span class="material-symbols-sharp">info</span>
                        <div>
                            <strong>Nenhuma forma de pagamento disponÃ­vel</strong><br>
                            Ative ao menos um mÃ©todo de pagamento no painel administrativo.
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($gatewayConfigurado && !empty($formasPagamento)): ?>
                    <div class="payment-methods">
                        <?php 
                        // Mapeamento de mÃ©todos de pagamento para exibiÃ§Ã£o
                        $paymentMethods = [
                            'Pix' => [
                                'value' => 'pix',
                                'name' => 'Pix',
                                'desc' => 'AprovaÃ§Ã£o imediata',
                                'icon' => 'qr_code'
                            ],
                            'CartÃ£o de CrÃ©dito' => [
                                'value' => 'cartao',
                                'name' => 'CartÃ£o de CrÃ©dito',
                                'desc' => 'Parcelamento disponÃ­vel',
                                'icon' => 'credit_card'
                            ],
                            'CartÃ£o de DÃ©bito' => [
                                'value' => 'debito',
                                'name' => 'CartÃ£o de DÃ©bito',
                                'desc' => 'AprovaÃ§Ã£o imediata',
                                'icon' => 'contactless'
                            ],
                            'Boleto' => [
                                'value' => 'boleto',
                                'name' => 'Boleto BancÃ¡rio',
                                'desc' => 'Vencimento em 3 dias Ãºteis',
                                'icon' => 'receipt'
                            ]
                        ];
                        
                        // Exibir apenas os mÃ©todos habilitados
                        foreach ($formasPagamento as $metodo) {
                            if (isset($paymentMethods[$metodo])) {
                                $pm = $paymentMethods[$metodo];
                                ?>
                                <label class="payment-method" onclick="selectPayment('<?php echo $pm['value']; ?>')">
                                    <input type="radio" name="payment" value="<?php echo $pm['value']; ?>" id="payment_<?php echo $pm['value']; ?>">
                                    <div class="payment-method-info">
                                        <div class="payment-method-name"><?php echo $pm['name']; ?></div>
                                        <div class="payment-method-desc"><?php echo $pm['desc']; ?></div>
                                    </div>
                                    <span class="material-symbols-sharp payment-method-icon"><?php echo $pm['icon']; ?></span>
                                </label>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <?php elseif (!$gatewayConfigurado): ?>
                    <div class="alert alert-info" style="margin-top: 20px;">
                        <span class="material-symbols-sharp">info</span>
                        <div>
                            Para habilitar pagamentos online, acesse o painel administrativo e configure:<br>
                            <strong>ConfiguraÃ§Ãµes > Pagamentos > Credenciais do Gateway</strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Container para Card Payment Brick (Checkout Transparente) -->
                    <div id="cardPaymentBrick_container" style="display: none; margin-top: 24px; padding: 20px; background: #f8f9fa; border-radius: 12px;">
                        <div class="brick-loader">
                            <span class="material-symbols-sharp" style="font-size: 32px; display: block; margin-bottom: 8px;">credit_card</span>
                            Carregando formulÃ¡rio de pagamento...
                        </div>
                    </div>
                    
                    <!-- Container para Pix (QR Code) -->
                    <div id="pixContainer" style="display: none; margin-top: 24px; padding: 30px; background: #fff; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div id="pixLoading" style="display: block;">
                            <span class="material-symbols-sharp" style="font-size: 48px; color: #00bcb4;">qr_code</span>
                            <h3 style="margin: 16px 0 8px 0;">Gerando cÃ³digo Pix...</h3>
                            <p style="color: #666;">Aguarde alguns instantes</p>
                        </div>
                        <div id="pixContent" style="display: none;">
                            <h3 style="margin-bottom: 16px;">Pague com Pix</h3>
                            <div id="pixQRCode" style="margin: 20px auto; max-width: 300px;"></div>
                            <p style="margin: 16px 0; color: #666;">Escaneie o QR Code com seu app de pagamentos</p>
                            <div style="border-top: 1px solid #eee; margin: 24px 0; padding-top: 24px;">
                                <p style="font-weight: 600; margin-bottom: 8px;">Ou copie o cÃ³digo:</p>
                                <div style="position: relative;">
                                    <input type="text" id="pixCopyPaste" readonly 
                                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: monospace; font-size: 11px; background: #f8f9fa;">
                                    <button onclick="copiarCodigoPix()" 
                                            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); padding: 8px 16px; background: #00bcb4; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        Copiar
                                    </button>
                                </div>
                            </div>
                            <p style="margin-top: 16px; font-size: 13px; color: #999;">O pagamento serÃ¡ confirmado automaticamente apÃ³s a aprovaÃ§Ã£o</p>
                        </div>
                    </div>
                    
                    <!-- Container para Boleto -->
                    <div id="boletoContainer" style="display: none; margin-top: 24px; padding: 30px; background: #fff; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div id="boletoLoading" style="display: block;">
                            <span class="material-symbols-sharp" style="font-size: 48px; color: #ff6d00;">receipt</span>
                            <h3 style="margin: 16px 0 8px 0;">Gerando boleto...</h3>
                            <p style="color: #666;">Aguarde alguns instantes</p>
                        </div>
                        <div id="boletoContent" style="display: none;">
                            <h3 style="margin-bottom: 16px;">Boleto BancÃ¡rio Gerado</h3>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                                <p style="font-size: 14px; color: #666; margin-bottom: 8px;">Vencimento:</p>
                                <p id="boletoDueDate" style="font-size: 20px; font-weight: 600; color: #333; margin: 0;"></p>
                            </div>
                            <div style="border-top: 1px solid #eee; margin: 24px 0; padding-top: 24px;">
                                <p style="font-weight: 600; margin-bottom: 8px;">Linha digitÃ¡vel:</p>
                                <div style="position: relative;">
                                    <input type="text" id="boletoDigitableLine" readonly 
                                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: monospace; font-size: 11px; background: #f8f9fa;">
                                    <button onclick="copiarLinhaDigitavel()" 
                                            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); padding: 8px 16px; background: #ff6d00; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                        Copiar
                                    </button>
                                </div>
                            </div>
                            <div style="margin-top: 24px;">
                                <a id="boletoPdfLink" href="#" target="_blank" 
                                   style="display: inline-block; padding: 14px 32px; background: #ff6d00; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s;">
                                    <span class="material-symbols-sharp" style="vertical-align: middle; margin-right: 8px;">download</span>
                                    Baixar Boleto PDF
                                </a>
                            </div>
                            <p style="margin-top: 16px; font-size: 13px; color: #999;">O pagamento serÃ¡ confirmado automaticamente apÃ³s compensaÃ§Ã£o bancÃ¡ria (atÃ© 3 dias Ãºteis)</p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- COLUNA LATERAL: RESUMO -->
            <div class="checkout-sidebar">
                <div class="checkout-section order-summary">
                    <h2 class="section-title">
                        <span class="material-symbols-sharp">shopping_cart</span>
                        Resumo do Pedido
                    </h2>
                    
                    <div id="summaryItems">
                        <!-- Preenchido via JavaScript -->
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span class="label">Subtotal</span>
                            <span class="value" id="summarySubtotal">R$ 0,00</span>
                        </div>
                        <div class="summary-row discount" id="summaryDiscountRow" style="display: none;">
                            <span class="label">Desconto (Cupom)</span>
                            <span class="value" id="summaryDiscount">- R$ 0,00</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Frete</span>
                            <span class="value" id="summaryFrete">A calcular</span>
                        </div>
                        <div class="summary-row total">
                            <span class="label">Total</span>
                            <span class="value" id="summaryTotal">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary" id="btnFinalizarCompra" 
                            onclick="finalizarCompra()" 
                            <?php echo (!$gatewayConfigurado ? 'disabled title="Configure o gateway de pagamento no painel admin"' : ''); ?>>
                        <span class="material-symbols-sharp">check_circle</span>
                        <?php echo ($gatewayConfigurado ? 'Finalizar Compra' : 'Gateway nÃ£o configurado'); ?>
                    </button>
                    
                    <a href="carrinho.php" class="btn btn-secondary" style="margin-top: 12px;">
                        <span class="material-symbols-sharp">arrow_back</span>
                        Voltar ao Carrinho
                    </a>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- FOOTER -->
    <?php include '../includes/footer.php'; ?>
    
    <script>
        const __noopLog = (...args) => {};
        // ConfiguraÃ§Ãµes do servidor
        const GATEWAY_ATIVO = <?php echo $gatewayAtivo ? 'true' : 'false'; ?>;
        const GATEWAY_CONFIGURADO = <?php echo $gatewayConfigurado ? 'true' : 'false'; ?>;
        const USUARIO_LOGADO = <?php echo $usuarioLogado ? 'true' : 'false'; ?>;
        const CLIENTE_ID = <?php echo $usuarioLogado && isset($clienteData['id']) ? $clienteData['id'] : 'null'; ?>;
        
        // Dados do carrinho
        let carrinho = {
            items: [],
            subtotal: 0,
            desconto: 0,
            frete: null,
            cupom: null,
            total: 0
        };
        
        // Forma de pagamento selecionada
        let formaPagamento = null;
        
        /**
         * Carregar dados do carrinho do localStorage
         */
        function carregarCarrinho() {
            // Buscar do localStorage
            const cartData = localStorage.getItem('dz_cart');
            const freteData = localStorage.getItem('dz_frete');
            const cupomData = localStorage.getItem('dz_cupom');
            
            if (!cartData || cartData === '[]') {
                alert('Seu carrinho estÃ¡ vazio!');
                window.location.href = 'carrinho.php';
                return;
            }
            
            // Parse dos dados
            carrinho.items = JSON.parse(cartData);
            
            // Calcular subtotal
            carrinho.subtotal = carrinho.items.reduce((sum, item) => {
                return sum + (item.price * item.qty);
            }, 0);
            
            // Processar cupom
            if (cupomData) {
                const cupom = JSON.parse(cupomData);
                carrinho.cupom = cupom;
                carrinho.desconto = cupom.desconto || 0;
            }
            
            // Processar frete
            if (freteData) {
                const frete = JSON.parse(freteData);
                carrinho.frete = frete;
                
                // Preencher CEP automaticamente se disponÃ­vel
                if (frete.cep) {
                    const cepInput = document.getElementById('cep');
                    if (cepInput && !cepInput.value) {
                        cepInput.value = frete.cep;
                    }
                }
            }
            
            // Calcular total
            calcularTotal();
            
            // Renderizar resumo
            renderizarResumo();
            
            // Verificar e alertar se frete nÃ£o foi calculado
            if (!carrinho.frete) {
                setTimeout(() => {
                    const freteElement = document.getElementById('summaryFrete');
                    if (freteElement) {
                        freteElement.innerHTML = '<span style="color: #f59e0b;">âš ï¸ A calcular no carrinho</span>';
                    }
                }, 100);
            }
        }
        
        /**
         * Calcular total do pedido
         */
        function calcularTotal() {
            let total = carrinho.subtotal;
            
            // Aplicar desconto
            if (carrinho.desconto > 0) {
                total -= carrinho.desconto;
            }
            
            // Adicionar frete
            if (carrinho.frete && !carrinho.frete.gratis) {
                total += carrinho.frete.valor;
            }
            
            carrinho.total = total;
        }
        
        /**
         * Renderizar resumo do pedido
         */
        function renderizarResumo() {
            const container = document.getElementById('summaryItems');
            
            container.innerHTML = carrinho.items.map(item => `
                <div class="summary-item">
                    <div class="summary-item-image">
                        <img src="${item.image}" alt="${item.name}" onerror="this.src='../assets/images/placeholder.jpg'">
                    </div>
                    <div class="summary-item-info">
                        <div class="summary-item-name">${item.name}</div>
                        <div class="summary-item-qty">Quantidade: ${item.qty}</div>
                    </div>
                    <div class="summary-item-price">R$ ${formatarDinheiro(item.price * item.qty)}</div>
                </div>
            `).join('');
            
            // Atualizar totais
            document.getElementById('summarySubtotal').textContent = 'R$ ' + formatarDinheiro(carrinho.subtotal);
            
            if (carrinho.desconto > 0) {
                document.getElementById('summaryDiscountRow').style.display = 'flex';
                document.getElementById('summaryDiscount').textContent = '- R$ ' + formatarDinheiro(carrinho.desconto);
            }
            
            if (carrinho.frete) {
                let freteTexto = '';
                if (carrinho.frete.gratis) {
                    freteTexto = 'GRÃTIS';
                } else {
                    freteTexto = 'R$ ' + formatarDinheiro(carrinho.frete.valor);
                }
                
                // Adicionar nome do serviÃ§o e prazo se disponÃ­vel
                if (carrinho.frete.nome) {
                    freteTexto += ` (${carrinho.frete.nome})`;
                }
                if (carrinho.frete.prazo) {
                    freteTexto += ` - ${carrinho.frete.prazo} dias`;
                }
                
                document.getElementById('summaryFrete').textContent = freteTexto;
            } else {
                document.getElementById('summaryFrete').textContent = 'A calcular';
                document.getElementById('summaryFrete').style.color = '#f59e0b';
            }
            
            document.getElementById('summaryTotal').textContent = 'R$ ' + formatarDinheiro(carrinho.total);
        }
        
        /**
         * Formatar nÃºmero como dinheiro
         */
        function formatarDinheiro(valor) {
            return parseFloat(valor).toFixed(2).replace('.', ',');
        }
        
        /**
         * Selecionar forma de pagamento
         */
        function selectPayment(tipo) {
            formaPagamento = tipo;
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
            document.getElementById('payment_' + tipo).closest('.payment-method').classList.add('selected');
            
            // Gerenciar visibilidade dos containers
            const brickContainer = document.getElementById('cardPaymentBrick_container');
            const pixContainer = document.getElementById('pixContainer');
            const boletoContainer = document.getElementById('boletoContainer');
            
            // Resetar todos os containers
            if (brickContainer) brickContainer.style.display = 'none';
            if (pixContainer) {
                pixContainer.style.display = 'none';
                document.getElementById('pixLoading').style.display = 'block';
                document.getElementById('pixContent').style.display = 'none';
            }
            if (boletoContainer) {
                boletoContainer.style.display = 'none';
                document.getElementById('boletoLoading').style.display = 'block';
                document.getElementById('boletoContent').style.display = 'none';
            }
            
            // Mostrar container apropriado
            if (tipo === 'cartao' || tipo === 'debito') {
                // Card Payment Brick
                if (brickContainer) {
                    brickContainer.style.display = 'block';
                    if (!brickController) {
                        __noopLog('Inicializando Card Payment Brick...');
                        initializeCardPaymentBrick();
                    }
                }
            } else if (tipo === 'pix') {
                // Mostrar container do Pix
                if (pixContainer) {
                    pixContainer.style.display = 'block';
                }
            } else if (tipo === 'boleto') {
                // Mostrar container do Boleto
                if (boletoContainer) {
                    boletoContainer.style.display = 'block';
                }
            }
        }
        
        /**
         * Validar formulÃ¡rio
         */
        function validarFormulario() {
            const campos = [
                { id: 'nome', nome: 'Nome' },
                { id: 'email', nome: 'E-mail' },
                { id: 'telefone', nome: 'Telefone' },
                { id: 'cpf_cnpj', nome: 'CPF/CNPJ' },
                { id: 'cep', nome: 'CEP' },
                { id: 'rua', nome: 'Rua' },
                { id: 'numero', nome: 'NÃºmero' },
                { id: 'bairro', nome: 'Bairro' },
                { id: 'cidade', nome: 'Cidade' },
                { id: 'estado', nome: 'Estado' }
            ];
            
            for (const campo of campos) {
                const valor = document.getElementById(campo.id).value.trim();
                if (!valor) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campo obrigatÃ³rio',
                        text: `Por favor, preencha o campo: ${campo.nome}`,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ff00d4'
                    });
                    document.getElementById(campo.id).focus();
                    return false;
                }
            }
            
            if (!formaPagamento) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Forma de pagamento',
                    text: 'Por favor, selecione uma forma de pagamento',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                return false;
            }
            
            if (!carrinho.frete) {
                Swal.fire({
                    icon: 'warning',
                    title: 'âš ï¸ Frete nÃ£o calculado',
                    text: 'Por favor, volte ao carrinho e calcule o frete antes de finalizar a compra.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                return false;
            }
            
            return true;
        }
        
        /**
         * Processar pagamento transparente (chamado pelo callback do Brick)
         */
        async function processarPagamentoTransparente() {
            try {
                if (!paymentData) {
                    throw new Error('Dados do pagamento nÃ£o disponÃ­veis');
                }
                
                __noopLog('âœ… Dados do cartÃ£o validados:', paymentData);
                
                // Obter carrinho
                const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                
                __noopLog('ðŸ›’ Carrinho:', carrinho);
                __noopLog('ðŸ“¦ Frete:', frete);
                __noopLog('ðŸŽ« Cupom:', cupom);
                
                // Validar se carrinho nÃ£o estÃ¡ vazio
                if (!carrinho || carrinho.length === 0) {
                    throw new Error('Carrinho vazio! Adicione produtos antes de finalizar a compra.');
                }
                
                // Coletar dados do pedido + dados do pagamento
                const dadosPedido = {
                    cliente: {
                        id: CLIENTE_ID,
                        nome: document.getElementById('nome').value,
                        email: document.getElementById('email').value,
                        telefone: document.getElementById('telefone').value,
                        cpf_cnpj: document.getElementById('cpf_cnpj').value
                    },
                    endereco: {
                        cep: document.getElementById('cep').value,
                        rua: document.getElementById('rua').value,
                        numero: document.getElementById('numero').value,
                        complemento: document.getElementById('complemento').value,
                        bairro: document.getElementById('bairro').value,
                        cidade: document.getElementById('cidade').value,
                        estado: document.getElementById('estado').value
                    },
                    carrinho: {
                        items: carrinho,
                        frete: frete,
                        desconto: cupom.valor || 0,
                        cupom: cupom  // Enviar cupom completo tambÃ©m
                    },
                    pagamento: {
                        forma: formaPagamento,
                        transparente: true,  // Flag para indicar checkout transparente
                        payment_data: paymentData  // Dados tokenizados do Brick
                    }
                };
                
                __noopLog('ðŸ“¤ Enviando dados do pedido para o backend...', dadosPedido);
                
                // Enviar para backend processar pagamento
                const response = await fetch('../api/processar-pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dadosPedido)
                });
                
                // Limpar paymentData apÃ³s enviar
                paymentData = null;
                
                // Verificar se resposta Ã© JSON vÃ¡lido
                const responseText = await response.text();
                __noopLog('ðŸ“¥ Resposta bruta do backend:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('âŒ Erro ao fazer parse da resposta:', parseError);
                    throw new Error('Erro no servidor. Verifique o console do PHP para detalhes.');
                }
                
                __noopLog('ðŸ“¥ Resposta do backend:', result);
                
                if (result.success) {
                    __noopLog('âœ… Pedido criado com sucesso');
                    
                    // Verificar status do pagamento
                    const paymentStatus = result.data.payment_status;
                    const pedidoId = result.data.pedido_id;
                    __noopLog('ðŸ’³ Status do pagamento:', paymentStatus);
                    
                    // Ocultar loading
                    document.getElementById('loadingOverlay').classList.remove('active');
                    document.getElementById('btnFinalizarCompra').disabled = false;
                    
                    if (paymentStatus === 'approved') {
                        __noopLog('âœ… Pagamento APROVADO');
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                        
                        // SweetAlert de sucesso
                        await Swal.fire({
                            icon: 'success',
                            title: 'ðŸŽ‰ Pagamento Aprovado!',
                            html: `
                                <p><strong>Seu pedido foi confirmado com sucesso!</strong></p>
                                <p>NÃºmero do pedido: <strong>#${pedidoId}</strong></p>
                                <p>VocÃª receberÃ¡ um e-mail com todos os detalhes.</p>
                            `,
                            confirmButtonText: 'Ver Meus Pedidos',
                            confirmButtonColor: '#ff00d4',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });
                        
                        window.location.href = 'pedidos.php?status=success&pedido=' + pedidoId;
                    } else if (paymentStatus === 'pending' || paymentStatus === 'in_process') {
                        __noopLog('â³ Pagamento PENDENTE');
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                        
                        // SweetAlert de pendente
                        await Swal.fire({
                            icon: 'info',
                            title: 'â³ Pagamento Pendente',
                            html: `
                                <p><strong>Seu pedido estÃ¡ sendo processado!</strong></p>
                                <p>NÃºmero do pedido: <strong>#${pedidoId}</strong></p>
                                <p>Aguarde a confirmaÃ§Ã£o do pagamento. VocÃª receberÃ¡ um e-mail assim que for aprovado.</p>
                            `,
                            confirmButtonText: 'Ver Meus Pedidos',
                            confirmButtonColor: '#ff00d4'
                        });
                        
                        window.location.href = 'pedidos.php?status=pending&pedido=' + pedidoId;
                    } else {
                        // rejected ou outros status
                        __noopLog('âŒ Pagamento RECUSADO:', paymentStatus);
                        
                        // Restaurar carrinho (NÃƒO limpar)
                        localStorage.setItem('dz_cart', JSON.stringify(carrinho));
                        if (frete) localStorage.setItem('dz_frete', JSON.stringify(frete));
                        if (cupom) localStorage.setItem('dz_cupom', JSON.stringify(cupom));
                        
                        // Mensagem especÃ­fica do Mercado Pago
                        const mensagemErro = result.data.payment_message || 'O pagamento foi recusado. Verifique os dados do cartÃ£o e tente novamente.';
                        const detalheErro = result.data.payment_detail || '';
                        
                        // SweetAlert de erro
                        await Swal.fire({
                            icon: 'error',
                            title: 'âŒ Pagamento Recusado',
                            html: `
                                <p><strong>${mensagemErro}</strong></p>
                                ${detalheErro ? `<p style="color: #666; font-size: 0.9em;">${detalheErro}</p>` : ''}
                                <p style="margin-top: 15px;">Por favor, tente:</p>
                                <ul style="text-align: left; display: inline-block;">
                                    <li>Verificar os dados do cartÃ£o</li>
                                    <li>Usar outro cartÃ£o</li>
                                    <li>Escolher outra forma de pagamento</li>
                                </ul>
                            `,
                            confirmButtonText: 'Tentar Novamente',
                            confirmButtonColor: '#ff00d4'
                        });
                        
                        // Habilitar botÃ£o novamente
                        document.getElementById('loadingOverlay').classList.remove('active');
                        document.getElementById('btnFinalizarCompra').disabled = false;
                    }
                } else {
                    throw new Error(result.message || 'Erro ao processar pagamento');
                }
            } catch (error) {
                console.error('âŒ Erro:', error);
                
                // Ocultar loading
                document.getElementById('loadingOverlay').classList.remove('active');
                document.getElementById('btnFinalizarCompra').disabled = false;
                
                // SweetAlert de erro genÃ©rico
                Swal.fire({
                    icon: 'error',
                    title: 'âŒ Erro ao processar pagamento',
                    text: error.message || 'Ocorreu um erro inesperado. Por favor, tente novamente.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
            }
        }
        
        /**
         * Finalizar compra
         */
        async function finalizarCompra() {
            // Validar se gateway estÃ¡ configurado
            if (!GATEWAY_CONFIGURADO) {
                Swal.fire({
                    icon: 'warning',
                    title: 'âš ï¸ Gateway nÃ£o configurado',
                    text: 'Por favor, configure as credenciais do gateway no painel administrativo antes de processar pagamentos.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                return;
            }
            
            // Validar formulÃ¡rio
            if (!validarFormulario()) {
                return;
            }
            
            // Mostrar loading
            document.getElementById('loadingOverlay').classList.add('active');
            document.getElementById('btnFinalizarCompra').disabled = true;
            
            try {
                // Se for pagamento com cartÃ£o (transparente), disparar o botÃ£o do Brick
                if (formaPagamento === 'cartao' || formaPagamento === 'debito') {
                    // Verificar se o Brick foi inicializado
                    if (!brickController) {
                        throw new Error('FormulÃ¡rio de pagamento ainda nÃ£o carregou. Aguarde alguns segundos e tente novamente.');
                    }
                    
                    __noopLog('ðŸ”„ Disparando submit do Card Payment Brick...');
                    
                    // Encontrar e clicar no botÃ£o de submit do Brick
                    const brickSubmitButton = document.querySelector('#cardPaymentBrick_container button[type="submit"]');
                    if (brickSubmitButton) {
                        brickSubmitButton.click();
                        // O callback onSubmit serÃ¡ chamado automaticamente e processarÃ¡ o pagamento
                    } else {
                        throw new Error('BotÃ£o de pagamento nÃ£o encontrado. Recarregue a pÃ¡gina e tente novamente.');
                    }
                } else if (formaPagamento === 'pix') {
                    // ===== PIX NATIVO (TRANSPARENTE) =====
                    __noopLog('Processando pagamento Pix...');
                    
                    const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                    const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                    const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                    
                    const dadosPedido = {
                        cliente: {
                            id: CLIENTE_ID,
                            nome: document.getElementById('nome').value,
                            email: document.getElementById('email').value,
                            telefone: document.getElementById('telefone').value,
                            cpf_cnpj: document.getElementById('cpf_cnpj').value
                        },
                        endereco: {
                            cep: document.getElementById('cep').value,
                            rua: document.getElementById('rua').value,
                            numero: document.getElementById('numero').value,
                            complemento: document.getElementById('complemento').value,
                            bairro: document.getElementById('bairro').value,
                            cidade: document.getElementById('cidade').value,
                            estado: document.getElementById('estado').value
                        },
                        carrinho: {
                            items: carrinho,
                            frete: frete,
                            desconto: cupom.valor || 0,
                            cupom: cupom
                        },
                        pagamento: {
                            forma: 'pix',
                            transparente: true, // PIX NATIVO
                            payment_method_id: 'pix'
                        }
                    };
                    
                    const response = await fetch('../api/processar-pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dadosPedido)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.data.pix_qr_code) {
                        // Exibir QR Code do Pix
                        exibirQRCodePix(result.data.pix_qr_code, result.data.pix_qr_code_base64);
                        
                        // Esconder loading e botÃ£o finalizar
                        document.getElementById('loadingOverlay').classList.remove('active');
                        document.getElementById('btnFinalizarCompra').style.display = 'none';
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                    } else {
                        throw new Error(result.message || 'Erro ao gerar cÃ³digo Pix');
                    }
                    
                } else if (formaPagamento === 'boleto') {
                    // ===== BOLETO NATIVO (TRANSPARENTE) =====
                    __noopLog('Processando pagamento Boleto...');
                    
                    const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                    const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                    const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                    
                    const dadosPedido = {
                        cliente: {
                            id: CLIENTE_ID,
                            nome: document.getElementById('nome').value,
                            email: document.getElementById('email').value,
                            telefone: document.getElementById('telefone').value,
                            cpf_cnpj: document.getElementById('cpf_cnpj').value
                        },
                        endereco: {
                            cep: document.getElementById('cep').value,
                            rua: document.getElementById('rua').value,
                            numero: document.getElementById('numero').value,
                            complemento: document.getElementById('complemento').value,
                            bairro: document.getElementById('bairro').value,
                            cidade: document.getElementById('cidade').value,
                            estado: document.getElementById('estado').value
                        },
                        carrinho: {
                            items: carrinho,
                            frete: frete,
                            desconto: cupom.valor || 0,
                            cupom: cupom
                        },
                        pagamento: {
                            forma: 'boleto',
                            transparente: true, // BOLETO NATIVO
                            payment_method_id: 'bolbancario'
                        }
                    };
                    
                    const response = await fetch('../api/processar-pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dadosPedido)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.data.boleto_url) {
                        // Exibir dados do Boleto
                        exibirBoleto(
                            result.data.boleto_url,
                            result.data.boleto_digitable_line,
                            result.data.boleto_due_date
                        );
                        
                        // Esconder loading e botÃ£o finalizar
                        document.getElementById('loadingOverlay').classList.remove('active');
                        document.getElementById('btnFinalizarCompra').style.display = 'none';
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                    } else {
                        throw new Error(result.message || 'Erro ao gerar Boleto');
                    }
                    
                } else {
                    // ===== OUTROS (FUTURO) =====
                    __noopLog('Processando pagamento com redirect...');
                    
                    const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                    const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                    const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                    
                    const dadosPedido = {
                        cliente: {
                            id: CLIENTE_ID,
                            nome: document.getElementById('nome').value,
                            email: document.getElementById('email').value,
                            telefone: document.getElementById('telefone').value,
                            cpf_cnpj: document.getElementById('cpf_cnpj').value
                        },
                        endereco: {
                            cep: document.getElementById('cep').value,
                            rua: document.getElementById('rua').value,
                            numero: document.getElementById('numero').value,
                            complemento: document.getElementById('complemento').value,
                            bairro: document.getElementById('bairro').value,
                            cidade: document.getElementById('cidade').value,
                            estado: document.getElementById('estado').value
                        },
                        carrinho: {
                            items: carrinho,
                            frete: frete,
                            desconto: cupom.valor || 0,
                            cupom: cupom
                        },
                        pagamento: {
                            forma: formaPagamento,
                            transparente: false
                        }
                    };
                    
                    const response = await fetch('../api/processar-pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dadosPedido)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                        
                        // Redirecionar para Mercado Pago se houver init_point
                        if (result.data.init_point) {
                            window.location.href = result.data.init_point;
                        } else {
                            await Swal.fire({
                                icon: 'success',
                                title: 'âœ… Pedido realizado!',
                                text: 'NÃºmero do pedido: #' + result.data.pedido_id,
                                confirmButtonText: 'Ver Meus Pedidos',
                                confirmButtonColor: '#ff00d4'
                            });
                            window.location.href = 'pedidos.php';
                        }
                    } else {
                        throw new Error(result.message || 'Erro desconhecido');
                    }
                }
            } catch (error) {
                console.error('âŒ Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'âŒ Erro ao finalizar compra',
                    text: error.message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                
                // Ocultar loading
                document.getElementById('loadingOverlay').classList.remove('active');
                document.getElementById('btnFinalizarCompra').disabled = false;
            }
        }
        
        /**
         * Buscar CEP na API ViaCEP
         */
        document.getElementById('cep').addEventListener('blur', async function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) return;
            
            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();
                
                if (!data.erro) {
                    document.getElementById('rua').value = data.logradouro || '';
                    document.getElementById('bairro').value = data.bairro || '';
                    document.getElementById('cidade').value = data.localidade || '';
                    document.getElementById('estado').value = data.uf || '';
                    document.getElementById('numero').focus();
                }
            } catch (error) {
                console.error('Erro ao buscar CEP:', error);
            }
        });
        
        /**
         * MÃ¡scaras de formataÃ§Ã£o
         */
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substr(0, 11);
            if (value.length > 6) {
                value = `(${value.substr(0, 2)}) ${value.substr(2, 5)}-${value.substr(7)}`;
            } else if (value.length > 2) {
                value = `(${value.substr(0, 2)}) ${value.substr(2)}`;
            }
            e.target.value = value;
        });
        
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.substr(0, 8);
            if (value.length > 5) {
                value = `${value.substr(0, 5)}-${value.substr(5)}`;
            }
            e.target.value = value;
        });
        
        document.getElementById('cpf_cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                // CPF
                if (value.length > 9) {
                    value = `${value.substr(0, 3)}.${value.substr(3, 3)}.${value.substr(6, 3)}-${value.substr(9)}`;
                } else if (value.length > 6) {
                    value = `${value.substr(0, 3)}.${value.substr(3, 3)}.${value.substr(6)}`;
                } else if (value.length > 3) {
                    value = `${value.substr(0, 3)}.${value.substr(3)}`;
                }
            } else {
                // CNPJ
                if (value.length > 14) value = value.substr(0, 14);
                if (value.length > 12) {
                    value = `${value.substr(0, 2)}.${value.substr(2, 3)}.${value.substr(5, 3)}/${value.substr(8, 4)}-${value.substr(12)}`;
                } else if (value.length > 8) {
                    value = `${value.substr(0, 2)}.${value.substr(2, 3)}.${value.substr(5, 3)}/${value.substr(8)}`;
                } else if (value.length > 5) {
                    value = `${value.substr(0, 2)}.${value.substr(2, 3)}.${value.substr(5)}`;
                } else if (value.length > 2) {
                    value = `${value.substr(0, 2)}.${value.substr(2)}`;
                }
            }
            e.target.value = value;
        });
        
        // Desabilitar botÃ£o se gateway inativo
        if (!GATEWAY_ATIVO) {
            document.getElementById('btnFinalizarCompra').disabled = true;
            document.querySelectorAll('.payment-method').forEach(el => {
                el.style.opacity = '0.5';
                el.style.cursor = 'not-allowed';
                el.onclick = () => {};
            });
        }
        
        // ===== FUNÃ‡Ã•ES DO NAVBAR =====
        
        /**
         * Toggle do dropdown do usuÃ¡rio
         */
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
        
        /**
         * Toggle do menu mobile
         */
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
        
        /**
         * Barra de pesquisa
         */
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
                
                // Se jÃ¡ estÃ¡ aberto e tem valor, fazer busca
                if (isOpen && searchValue) {
                    window.location.href = '../produtos.php?busca=' + encodeURIComponent(searchValue);
                    return;
                }
                
                // Se nÃ£o estÃ¡ aberto, abrir
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
                
                // Fechar se estiver aberto
                closeSearchPanel();
            });
        }

        // Fechar search ao clicar fora
        document.addEventListener('click', (e) => {
            if (!searchPanel || !searchToggle) return;
            if (!searchPanel.classList.contains('active')) return;
            if (searchPanel.contains(e.target) || searchToggle.contains(e.target)) return;
            closeSearchPanel();
        });
        
        // ===== FIM FUNÃ‡Ã•ES DO NAVBAR =====
        
        /**
         * Atualizar contador do carrinho no navbar
         */
        function updateCartBadge() {
            const cart = localStorage.getItem('dz_cart');
            const items = cart ? JSON.parse(cart) : [];
            const totalItems = items.reduce((sum, item) => sum + (parseInt(item.qty) || 1), 0);
            
            const badge = document.getElementById('cartBadge');
            if (badge) {
                badge.textContent = totalItems;
                badge.style.display = totalItems > 0 ? 'flex' : 'none';
            }
        }
        
        // ===== MERCADO PAGO - CHECKOUT TRANSPARENTE =====
        let mpBrickInstance = null;
        let brickController = null;
        let paymentData = null; // Armazenar dados do pagamento tokenizado
        let isInitializing = false; // Flag para prevenir mÃºltiplas inicializaÃ§Ãµes simultÃ¢neas
        
        /**
         * Inicializar Mercado Pago SDK
         */
        async function initializeMercadoPago() {
            if (typeof MP_PUBLIC_KEY === 'undefined' || !MP_PUBLIC_KEY) {
                console.error('âŒ Public Key do Mercado Pago nÃ£o configurada');
                return false;
            }
            
            if (typeof MercadoPago === 'undefined') {
                console.error('âŒ SDK do Mercado Pago nÃ£o carregado. Verifique sua conexÃ£o com a internet.');
                return false;
            }
            
            try {
                __noopLog('ðŸ”„ Inicializando Mercado Pago SDK...');
                __noopLog('Public Key:', MP_PUBLIC_KEY.substring(0, 20) + '...');
                
                mpBrickInstance = new MercadoPago(MP_PUBLIC_KEY, {
                    locale: 'pt-BR'
                });
                
                __noopLog('âœ… Mercado Pago SDK inicializado com sucesso');
                return true;
            } catch (error) {
                console.error('âŒ Erro ao inicializar Mercado Pago:', error);
                return false;
            }
        }
        
        /**
         * Inicializar Card Payment Brick
         */
        async function initializeCardPaymentBrick() {
            __noopLog('ðŸ”„ Tentando inicializar Card Payment Brick...');
            
            // Verificar se jÃ¡ estÃ¡ inicializado ou em processo de inicializaÃ§Ã£o
            if (brickController) {
                __noopLog('âœ… Brick jÃ¡ estÃ¡ inicializado');
                return;
            }
            
            if (isInitializing) {
                __noopLog('â³ Brick jÃ¡ estÃ¡ sendo inicializado, aguardando...');
                return;
            }
            
            // Marcar como em processo de inicializaÃ§Ã£o
            isInitializing = true;
            
            if (!mpBrickInstance) {
                console.error('âŒ Mercado Pago SDK nÃ£o inicializado. Inicializando agora...');
                const initialized = await initializeMercadoPago();
                if (!initialized) {
                    isInitializing = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro no sistema de pagamento',
                        text: 'Erro ao carregar sistema de pagamento. Recarregue a pÃ¡gina e tente novamente.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ff00d4'
                    });
                    return;
                }
            }
            
            try {
                __noopLog('ðŸ”„ Criando Card Payment Brick...');
                
                // Obter valor total do carrinho
                const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                
                let subtotal = carrinho.reduce((sum, item) => sum + (item.price * item.qty), 0);
                let desconto = cupom.valor || 0;
                let valorFrete = frete.gratis ? 0 : (frete.valor || 0);
                let total = subtotal - desconto + valorFrete;
                
                __noopLog('ðŸ’° Valor total do pedido: R$', total.toFixed(2));
                
                // Garantir que o valor seja maior que zero
                if (total <= 0) {
                    console.error('âŒ Valor total invÃ¡lido:', total);
                    throw new Error('Valor do pedido deve ser maior que zero');
                }
                
                const bricksBuilder = mpBrickInstance.bricks();
                
                __noopLog('ðŸ“‹ ConfiguraÃ§Ã£o do Brick:', {
                    amount: parseFloat(total.toFixed(2)),
                    locale: 'pt-BR'
                });
                
                brickController = await bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', {
                    initialization: {
                        amount: parseFloat(total.toFixed(2))
                    },
                    locale: 'pt-BR',
                    customization: {
                        visual: {
                            style: {
                                theme: 'default'
                            }
                        }
                    },
                    callbacks: {
                        onReady: () => {
                            __noopLog('âœ… Card Payment Brick pronto para uso');
                            __noopLog('ðŸ” Inspecionando formulÃ¡rio do Brick...');
                            
                            // Esconder botÃ£o padrÃ£o do Brick VISUALMENTE (mas manter funcional)
                            const brickButton = document.querySelector('#cardPaymentBrick_container button[type="submit"]');
                            if (brickButton) {
                                brickButton.style.position = 'absolute';
                                brickButton.style.left = '-9999px';
                                brickButton.style.width = '1px';
                                brickButton.style.height = '1px';
                                brickButton.style.opacity = '0';
                                __noopLog('   - BotÃ£o padrÃ£o escondido (mas funcional)');
                            }
                            
                            // Debug: verificar estrutura do formulÃ¡rio apÃ³s 2 segundos
                            window.setTimeout(function() {
                                const container = document.getElementById('cardPaymentBrick_container');
                                if (container) {
                                    const selects = container.querySelectorAll('select');
                                    const inputs = container.querySelectorAll('input');
                                    __noopLog('ðŸ“Š Campos encontrados no Brick:');
                                    __noopLog('   - Total de <select>:', selects.length);
                                    __noopLog('   - Total de <input>:', inputs.length);
                                    
                                    selects.forEach(function(sel, idx) {
                                        const name = sel.name || sel.getAttribute('name') || '(sem name)';
                                        __noopLog('   - Select #' + idx + ': name="' + name + '" id="' + sel.id + '" options=' + sel.options.length);
                                        if (sel.options.length > 0) {
                                            __noopLog('     Primeira opÃ§Ã£o: "' + sel.options[0].text + '"');
                                        }
                                    });
                                }
                            }, 2000);
                        },
                        onSubmit: async (formData) => {
                            __noopLog('ðŸ“ Dados do formulÃ¡rio recebidos do Brick:', formData);
                            paymentData = formData;
                            __noopLog('âœ… paymentData armazenado, processando pagamento...');
                            
                            // Processar pagamento imediatamente
                            await processarPagamentoTransparente();
                            
                            return new Promise((resolve) => {
                                resolve();
                            });
                        },
                        onError: (error) => {
                            console.error('âŒ Erro no Card Payment Brick:', error);
                            // NÃ£o mostrar alert aqui pois pode ser validaÃ§Ã£o de campo
                        }
                    }
                });
                
                __noopLog('âœ… Card Payment Brick inicializado com sucesso');
                isInitializing = false; // Liberar flag apÃ³s sucesso
            } catch (error) {
                isInitializing = false; // Liberar flag em caso de erro
                console.error('âŒ Erro ao inicializar Card Payment Brick:', error);
                console.error('Detalhes do erro:', error.message, error.stack);
                document.getElementById('cardPaymentBrick_container').innerHTML = 
                    '<div class="alert alert-danger" style="padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00;">' +
                    '<strong>âŒ Erro ao carregar formulÃ¡rio de pagamento</strong><br>' +
                    'Detalhes: ' + error.message + '<br><br>' +
                    '<button onclick="location.reload()" style="padding: 10px 20px; background: #c00; color: white; border: none; border-radius: 4px; cursor: pointer;">Recarregar PÃ¡gina</button>' +
                    '</div>';
            }
        }
        
        // Inicializar ao carregar
        window.addEventListener('DOMContentLoaded', () => {
            __noopLog('ðŸ“„ PÃ¡gina carregada');
            __noopLog('MercadoPago disponÃ­vel?', typeof MercadoPago !== 'undefined');
            __noopLog('MP_PUBLIC_KEY disponÃ­vel?', typeof MP_PUBLIC_KEY !== 'undefined');
            
            carregarCarrinho();
            updateCartBadge(); // Atualizar contador do carrinho
            
            // Aguardar SDK do Mercado Pago estar disponÃ­vel
            const waitForMercadoPago = setInterval(() => {
                if (typeof MercadoPago !== 'undefined' && typeof MP_PUBLIC_KEY !== 'undefined') {
                    clearInterval(waitForMercadoPago);
                    __noopLog('ðŸ”„ SDK do Mercado Pago detectado, inicializando...');
                    initializeMercadoPago();
                }
            }, 100); // Verificar a cada 100ms
            
            // Timeout de seguranÃ§a (10 segundos)
            setTimeout(() => {
                clearInterval(waitForMercadoPago);
                if (typeof MercadoPago === 'undefined') {
                    console.error('âŒ Timeout: SDK do Mercado Pago nÃ£o carregou');
                    console.error('Verifique sua conexÃ£o com a internet');
                }
            }, 10000);
        });
        
        /**
         * Exibir QR Code do Pix
         */
        function exibirQRCodePix(qrCode, qrCodeBase64) {
            const pixContainer = document.getElementById('pixContainer');
            const pixLoading = document.getElementById('pixLoading');
            const pixContent = document.getElementById('pixContent');
            const pixQRCode = document.getElementById('pixQRCode');
            const pixCopyPaste = document.getElementById('pixCopyPaste');
            
            // Criar imagem do QR Code
            pixQRCode.innerHTML = `<img src="data:image/png;base64,${qrCodeBase64}" alt="QR Code Pix" style="width: 100%; max-width: 300px; border: 2px solid #eee; border-radius: 12px;">`;
            
            // Inserir cÃ³digo copia-e-cola
            pixCopyPaste.value = qrCode;
            
            // Exibir conteÃºdo
            pixLoading.style.display = 'none';
            pixContent.style.display = 'block';
            
            __noopLog('âœ… QR Code Pix exibido com sucesso');
        }
        
        /**
         * Copiar cÃ³digo Pix
         */
        function copiarCodigoPix() {
            const pixCopyPaste = document.getElementById('pixCopyPaste');
            pixCopyPaste.select();
            document.execCommand('copy');
            
            Swal.fire({
                icon: 'success',
                title: 'âœ… CÃ³digo copiado!',
                text: 'Cole no seu app de pagamentos',
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        /**
         * Exibir dados do Boleto
         */
        function exibirBoleto(boletoUrl, digitableLine, dueDate) {
            const boletoContainer = document.getElementById('boletoContainer');
            const boletoLoading = document.getElementById('boletoLoading');
            const boletoContent = document.getElementById('boletoContent');
            const boletoPdfLink = document.getElementById('boletoPdfLink');
            const boletoDigitableLine = document.getElementById('boletoDigitableLine');
            const boletoDueDate = document.getElementById('boletoDueDate');
            
            // Inserir dados do boleto
            boletoPdfLink.href = boletoUrl;
            boletoDigitableLine.value = digitableLine;
            boletoDueDate.textContent = dueDate;
            
            // Exibir conteÃºdo
            boletoLoading.style.display = 'none';
            boletoContent.style.display = 'block';
            
            __noopLog('âœ… Boleto exibido com sucesso');
        }
        
        /**
         * Copiar linha digitÃ¡vel do boleto
         */
        function copiarLinhaDigitavel() {
            const boletoDigitableLine = document.getElementById('boletoDigitableLine');
            boletoDigitableLine.select();
            document.execCommand('copy');
            
            Swal.fire({
                icon: 'success',
                title: 'âœ… Linha digitÃ¡vel copiada!',
                text: 'Cole no app do seu banco para pagar',
                timer: 2000,
                showConfirmButton: false
            });
        }
    </script>
    
</body>
</html>


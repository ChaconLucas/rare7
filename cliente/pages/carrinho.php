<?php
/**
 * Carrinho de Compras Completo - D&Z E-commerce
 * Funcionalidades: cupom, frete por CEP, validaÃ§Ã£o de estoque, integraÃ§Ã£o com admin
 */

session_start();
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

// Instanciar CMS Provider para footer
$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

// Verificar se o usuÃ¡rio estÃ¡ logado
$usuarioLogado = isset($_SESSION['cliente']);
$clienteNome = $usuarioLogado ? htmlspecialchars($_SESSION['cliente']['nome']) : '';
$nomeUsuario = $clienteNome; // Para compatibilidade com navbar.php

// Buscar configuraÃ§Ã£o de frete grÃ¡tis
$freteGratisValor = getFreteGratisThreshold($pdo);

$pageTitle = 'Carrinho - D&Z';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/Logodz.png">
    
    <!-- Material Symbols (Ã­cones) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <style>
        :root {
            --color-magenta: #E6007E;
            --color-magenta-dark: #C4006A;
            --color-rose-light: #FDF2F8;
        }
        
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
            padding-top: 100px;
            padding-bottom: 60px;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f8f9fa;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--color-magenta-dark) 0%, #a0005a 100%);
        }
        
        /* ===== NAVBAR PREMIUM D&Z ===== */
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
            transition: all 0.3s;
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
        }
        
        .dropdown-menu a {
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
            width: 20px;
            height: 20px;
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
            font-size: 0.95rem;
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
        
        /* Responsividade Navbar - Tablets e Desktop Pequeno */
        @media (max-width: 968px) {
            header.header-loja .container-header,
            header.header-loja #navbar .container-header,
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
        
        /* Mobile Responsivo */
        @media (max-width: 768px) {
            .header-loja {
                padding: 8px 0 !important;
            }
            
            header.header-loja .container-header,
            header.header-loja #navbar .container-header,
            .header-loja .container-header {
                padding: 0 4px !important;
                gap: 6px !important;
                justify-content: space-between !important;
            }
            
            .nav-loja {
                display: none !important;
            }
            
            .mobile-menu-toggle {
                display: flex !important;
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
        }
        
        /* === BARRA DE FRETE GRÃTIS === */
        .free-shipping-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        
        .free-shipping-bar.achieved {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }
        
        .shipping-text {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: #333;
            font-weight: 500;
        }
        
        .shipping-text.achieved {
            color: #065f46;
        }
        
        .progress-container {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
            position: relative;
        }
        
        .progress-bar.achieved {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .cart-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
        }
        
        /* === LISTA DE PRODUTOS === */
        .cart-items {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 8px;
        }
        
        .item-variant {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .item-price {
            color: var(--color-magenta);
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 12px;
        }
        
        .price-original {
            text-decoration: line-through;
            color: #999;
            font-size: 0.95rem;
            margin-right: 8px;
        }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .qty-btn {
            width: 32px;
            height: 32px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.2s;
            font-size: 1.1rem;
        }
        
        .qty-btn:hover {
            border-color: var(--color-magenta);
            color: var(--color-magenta);
        }
        
        .qty-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .qty-number {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .btn-remove {
            margin-left: auto;
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .btn-remove:hover {
            background: #fee2e2;
        }
        
        .stock-warning {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 4px;
        }
        
        /* === CUPOM === */
        .cupom-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-top: 24px;
        }
        
        .cupom-section h4 {
            color: #333;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        
        .cupom-input-group {
            display: flex;
            gap: 12px;
        }
        
        .cupom-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            text-transform: uppercase;
        }
        
        .cupom-input:focus {
            outline: none;
            border-color: var(--color-magenta);
        }
        
        .btn-apply-cupom {
            padding: 12px 24px;
            background: var(--color-magenta);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-apply-cupom:hover {
            background: var(--color-magenta-dark);
        }
        
        .btn-apply-cupom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .cupom-message {
            margin-top: 12px;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: none;
        }
        
        .cupom-message.success {
            background: #d1fae5;
            color: #065f46;
            display: block;
        }
        
        .cupom-message.error {
            background: #fee2e2;
            color: #991b1b;
            display: block;
        }
        
        .cupom-applied {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
        }
        
        .btn-remove-cupom {
            background: none;
            border: none;
            color: #991b1b;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .btn-remove-cupom:hover {
            background: rgba(0,0,0,0.1);
        }
        
        /* === FRETE === */
        .frete-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-top: 24px;
        }
        
        .frete-section h4 {
            color: #333;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        
        .frete-input-group {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .cep-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .cep-input:focus {
            outline: none;
            border-color: var(--color-magenta);
        }
        
        .btn-calc-frete {
            padding: 12px 24px;
            background: var(--color-magenta);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-calc-frete:hover {
            background: var(--color-magenta-dark);
        }
        
        .btn-calc-frete:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .frete-options {
            display: none;
        }
        
        .frete-option {
            background: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .frete-option:hover {
            border-color: var(--color-magenta);
        }
        
        .frete-option.selected {
            border-color: var(--color-magenta);
            background: var(--color-rose-light);
        }
        
        .frete-option-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        
        .frete-nome {
            font-weight: 600;
            color: #333;
        }
        
        .frete-valor {
            color: var(--color-magenta);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .frete-valor.gratis {
            color: #059669;
        }
        
        .frete-prazo {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* === RESUMO === */
        .cart-summary {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .cart-summary h3 {
            color: var(--color-magenta);
            margin-bottom: 20px;
        }
        
        /* === INPUTS MINIMALISTAS === */
        .summary-inputs {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        /* === INFORMAÃ‡Ã•ES APLICADAS === */
        .applied-info {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .applied-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .applied-item-cupom {
            background: #fef3c7;
            border-color: #fcd34d;
        }
        
        .applied-item-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #065f46;
        }
        
        .applied-item-cupom .applied-item-left {
            color: #92400e;
        }
        
        .applied-item strong {
            font-weight: 600;
        }
        
        .btn-remove-applied {
            background: none;
            border: none;
            color: #dc2626;
            cursor: pointer;
            padding: 4px 8px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.2s;
            border-radius: 4px;
        }
        
        .btn-remove-applied:hover {
            background: #fee2e2;
        }
        
        /* === OPÃ‡Ã•ES DE FRETE === */
        .frete-options-mini {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .frete-option-mini {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .frete-option-mini:hover {
            border-color: var(--color-magenta);
            background: #fdf2f8;
        }
        
        .frete-option-mini.selected {
            border-color: var(--color-magenta);
            background: #fdf2f8;
            border-width: 2px;
        }
        
        .frete-option-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .frete-nome-mini {
            font-weight: 600;
            font-size: 0.9rem;
            color: #333;
        }
        
        .frete-prazo-mini {
            font-size: 0.8rem;
            color: #666;
        }
        
        .frete-valor-mini {
            font-weight: 700;
            color: var(--color-magenta);
            font-size: 0.95rem;
        }
        
        .frete-valor-mini.gratis {
            color: #059669;
        }
        
        .input-group-mini {
            display: flex;
            gap: 6px;
            align-items: stretch;
        }
        
        .input-group-mini input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .input-group-mini input:focus {
            outline: none;
            border-color: var(--color-magenta);
            box-shadow: 0 0 0 3px rgba(230, 0, 126, 0.1);
        }
        
        .input-group-mini input:disabled {
            cursor: not-allowed;
            opacity: 1;
            border: 2px solid #fcd34d;
        }
        
        .input-group-mini input::placeholder {
            color: #9ca3af;
        }
        
        .btn-mini {
            padding: 10px 16px;
            background: var(--color-magenta);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-mini:hover {
            background: var(--color-magenta-dark);
            transform: translateY(-1px);
        }
        
        .btn-mini:active {
            transform: translateY(0);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            color: #666;
        }
        
        .summary-row strong {
            color: #333;
        }
        
        .summary-row.desconto strong {
            color: #059669;
        }
        
        .summary-total {
            border-top: 2px solid #f1f5f9;
            margin-top: 16px;
            padding-top: 16px;
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .summary-total strong {
            color: var(--color-magenta);
        }
        
        .free-shipping-progress {
            background: #f3f4f6;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            color: #666;
            text-align: center;
        }
        
        .free-shipping-progress.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            transition: all 0.3s;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .btn-checkout:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* === CARRINHO VAZIO === */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 5rem;
            margin-bottom: 24px;
            opacity: 0.5;
        }
        
        .empty-cart h2 {
            color: #333;
            margin-bottom: 12px;
        }
        
        .empty-cart p {
            color: #666;
            margin-bottom: 24px;
        }
        
        .btn-continue {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        /* === LOADING === */
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--color-magenta);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            body {
                padding-top: 80px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .cart-container {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .item-image {
                width: 100%;
                height: 200px;
            }
            
            .cupom-input-group,
            .frete-input-group {
                flex-direction: column;
            }
            
            .btn-apply-cupom,
            .btn-calc-frete {
                width: 100%;
            }
            
            /* Inputs minimalistas responsivos */
            .input-group-mini {
                gap: 8px;
            }
            
            .btn-mini {
                min-width: 80px;
            }
            
            /* Barra de frete grÃ¡tis responsiva */
            .free-shipping-bar {
                padding: 16px;
            }
            
            .shipping-text {
                font-size: 0.85rem;
            }
            
            /* OpÃ§Ãµes de frete responsivas */
            .frete-option-mini {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
        
        /* ===== FOOTER STYLES ===== */
        .footer-modern {
            background: linear-gradient(135deg, #fefefe 0%, #f8f9fa 100%);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 60px 0 0;
            margin-top: 80px;
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
        
        /* Footer Security Section */
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
        
        /* SSL Protection Styles */
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
        
        /* Trust Badges Styles */
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
        
        /* Responsive Design Footer Security */
        @media (max-width: 768px) {
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
        }
    </style>
</head>
<body>
    
    <?php include '../includes/navbar.php'; ?>

    <div class="page-container">
        <div class="page-header">
            <h1>Seu Carrinho</h1>
            <p>Revise seus produtos antes de finalizar a compra</p>
        </div>
        
        <div class="cart-container">
            
            <!-- COLUNA ESQUERDA: PRODUTOS -->
            <div>
                <!-- BARRA DE FRETE GRÃTIS -->
                <div id="freeShippingBar" class="free-shipping-bar" style="display: none;">
                    <div class="shipping-text" id="shippingText">
                        ðŸšš Faltam <strong id="shippingRemaining">R$ 0,00</strong> para frete grÃ¡tis!
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="cart-items">
                <div id="cartItemsContainer">
                    <div class="empty-cart">
                        <div class="empty-icon">ðŸ›’</div>
                        <h2>Carregando carrinho...</h2>
                    </div>
                </div>
                </div>
            </div>
            
            <!-- COLUNA DIREITA: RESUMO -->
            <div class="cart-summary">
                <h3>Resumo do Pedido</h3>
                
                <!-- INFORMAÃ‡Ã•ES APLICADAS (cupom e frete selecionado) -->
                <div id="appliedInfo" class="applied-info" style="display: none;"></div>
                
                <!-- INPUTS MINIMALISTAS -->
                <div id="inputsArea" class="summary-inputs">
                    <div class="input-group-mini">
                        <input 
                            type="text" 
                            id="cupomInput" 
                            placeholder="CÃ³digo do cupom"
                            maxlength="20"
                            onkeypress="if(event.key==='Enter') aplicarCupom()"
                        >
                        <button class="btn-mini" onclick="aplicarCupom()">Aplicar</button>
                    </div>
                    
                    <div class="input-group-mini">
                        <input 
                            type="text" 
                            id="cepInput" 
                            placeholder="CEP (ex: 01001-000)"
                            maxlength="10"
                            onkeyup="formatarCEPMini(this)"
                            onkeypress="if(event.key==='Enter') calcularFreteMini()"
                        >
                        <button class="btn-mini" onclick="calcularFreteMini()">Calcular</button>
                    </div>
                </div>
                
                <!-- OPÃ‡Ã•ES DE FRETE -->
                <div id="freteOptionsMini" class="frete-options-mini" style="display: none;"></div>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong id="subtotalValue">R$ 0,00</strong>
                </div>
                
                <div class="summary-row desconto" id="descontoRow" style="display: none;">
                    <span>Desconto (cupom)</span>
                    <strong id="descontoValue">- R$ 0,00</strong>
                </div>
                
                <div class="summary-row">
                    <span>Frete</span>
                    <strong id="freteValue">Calcular CEP</strong>
                </div>
                
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <strong id="totalValue">R$ 0,00</strong>
                </div>
                
                <button class="btn-checkout" id="btnCheckout" onclick="finalizarCompra()" disabled>
                    Finalizar Compra
                </button>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        const __noopLog = (...args) => {};
        // ===== DROPDOWN DO USUÃRIO =====
        function toggleUserDropdown(event) {
            __noopLog('toggleUserDropdown chamado');
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            const dropdown = document.querySelector('.user-dropdown');
            __noopLog('Dropdown encontrado:', dropdown);
            if (dropdown) {
                const isActive = dropdown.classList.contains('active');
                dropdown.classList.toggle('active');
                __noopLog('Dropdown agora estÃ¡:', dropdown.classList.contains('active') ? 'ABERTO' : 'FECHADO');
            } else {
                console.warn('Elemento .user-dropdown nÃ£o encontrado!');
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

        // ===== NAVBAR SCROLL EFFECT =====
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });

        // ===== BARRA DE PESQUISA =====
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
                
                if (isOpen && searchValue) {
                    window.location.href = '../produtos.php?busca=' + encodeURIComponent(searchValue);
                    return;
                }
                
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
                
                closeSearchPanel();
            });
        }

        document.addEventListener('click', (e) => {
            if (!searchPanel || !searchToggle) return;
            if (!searchPanel.classList.contains('active')) return;
            if (searchPanel.contains(e.target) || searchToggle.contains(e.target)) return;
            closeSearchPanel();
        });
        
        // ===== ESTADO GLOBAL DO CARRINHO =====
        let carrinho = {
            items: [],
            cupom: null,
            frete: null,
            subtotal: 0,
            desconto: 0,
            freteValor: 0,
            total: 0
        };

        const CONFIG = {
            freteGratisLimite: <?php echo $freteGratisValor; ?>
        };

        // ===== ATUALIZAR CONTADOR DO CARRINHO =====
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

        // ===== INICIALIZAÃ‡ÃƒO =====
        document.addEventListener('DOMContentLoaded', function() {
            __noopLog('='.repeat(50));
            __noopLog('âœ… JavaScript do carrinho carregado!');
            __noopLog('ðŸ“¦ localStorage dz_cart:', localStorage.getItem('dz_cart'));
            __noopLog('ðŸ” Tipo:', typeof localStorage.getItem('dz_cart'));
            
            // Verificar e analisar o conteÃºdo
            const cartRaw = localStorage.getItem('dz_cart');
            if (cartRaw) {
                try {
                    const parsed = JSON.parse(cartRaw);
                    __noopLog('âœ… Parse OK! Items:', parsed);
                    __noopLog('ðŸ“Š Total de items:', parsed.length);
                } catch (e) {
                    console.error('âŒ Erro ao fazer parse:', e);
                }
            } else {
                __noopLog('âš ï¸ localStorage estÃ¡ vazio ou null');
            }
            __noopLog('='.repeat(50));
            
            updateCartBadge();
            carregarCarrinho();
        });
        
        // Adicionar atalhos no console
        __noopLog('ðŸ’¡ ATALHOS DISPONÃVEIS:');
        __noopLog('   localStorage.getItem("dz_cart") - Ver carrinho');
        __noopLog('   localStorage.removeItem("dz_cart") - Limpar carrinho');


        // ===== CARREGAR CARRINHO DO LOCALSTORAGE =====
        async function carregarCarrinho() {
            __noopLog('=== Iniciando carregamento do carrinho ===');
            const cartData = localStorage.getItem('dz_cart');
            __noopLog('Dados do localStorage:', cartData);
            
            if (!cartData || cartData === '[]') {
                __noopLog('Carrinho vazio');
                mostrarCarrinhoVazio();
                return;
            }

            try {
                const items = JSON.parse(cartData);
                
                if (!Array.isArray(items) || items.length === 0) {
                    mostrarCarrinhoVazio();
                    return;
                }

                // Converter items do formato do localStorage para o formato interno
                __noopLog('Total de itens no carrinho:', items.length);
                carrinho.items = items.map((item) => {
                    // Formato vindo do index.php: {id, name, price, qty, image}
                    // Converter para formato interno
                    
                    // Ajustar caminho da imagem se necessÃ¡rio
                    let imagemAjustada = item.image || null;
                    if (imagemAjustada && !imagemAjustada.startsWith('http') && !imagemAjustada.startsWith('../')) {
                        imagemAjustada = '../' + imagemAjustada;
                    }
                    
                    return {
                        produto_id: item.id,
                        variacao_id: item.variacao_id || null,
                        nome: item.name,
                        variacao_texto: item.variacao_texto || null,
                        preco: parseFloat(item.price) || 0,
                        preco_original: parseFloat(item.price) || 0,
                        imagem: imagemAjustada,
                        estoque: 999, // Estoque alto para nÃ£o bloquear (validar no backend)
                        quantidade: parseInt(item.qty) || 1,
                        tem_promocao: false
                    };
                });

                __noopLog('Carrinho carregado com sucesso:', carrinho.items);
                renderizarCarrinho();
                calcularTotais();

            } catch (error) {
                console.error('Erro ao carregar carrinho:', error);
                mostrarCarrinhoVazio();
            }
        }

        // ===== BUSCAR DADOS DO PRODUTO NA API =====
        async function buscarDadosProduto(produtoId, variacaoId = null) {
            try {
                __noopLog('Fazendo fetch para:', '../api/carrinho-api.php');
                const response = await fetch('../api/carrinho-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'getProductData',
                        produto_id: produtoId,
                        variacao_id: variacaoId
                    })
                });

                __noopLog('Status da resposta:', response.status);
                
                if (!response.ok) {
                    throw new Error(`Erro HTTP: ${response.status}`);
                }

                const result = await response.json();
                __noopLog('Resultado da API:', result);
                
                if (!result.success) {
                    throw new Error(result.message);
                }

                return result.data;
            } catch (error) {
                console.error('ERRO em buscarDadosProduto:', error);
                throw error;
            }
        }

        // ===== RENDERIZAR CARRINHO =====
        function renderizarCarrinho() {
            const container = document.getElementById('cartItemsContainer');
            
            container.innerHTML = carrinho.items.map((item, index) => {
                const subtotalItem = item.preco * item.quantidade;
                const temEstoque = item.quantidade <= item.estoque;
                
                return `
                    <div class="cart-item">
                        <div class="item-image">
                            ${item.imagem ? `<img src="${item.imagem}" alt="${item.nome}" onerror="this.parentElement.innerHTML='ðŸ’…'">` : 'ðŸ’…'}
                        </div>
                        <div class="item-details">
                            <div class="item-name">${item.nome}</div>
                            ${item.variacao_texto ? `<div class="item-variant">${item.variacao_texto}</div>` : ''}
                            <div class="item-price">
                                ${item.tem_promocao ? `<span class="price-original">R$ ${formatarDinheiro(item.preco_original)}</span>` : ''}
                                R$ ${formatarDinheiro(item.preco)}
                                ${item.quantidade > 1 ? ` <span style="color: #666; font-size: 0.9rem;">(${item.quantidade}x)</span>` : ''}
                            </div>
                            ${!temEstoque ? `<div class="stock-warning">âš ï¸ Estoque insuficiente (disponÃ­vel: ${item.estoque})</div>` : ''}
                            <div class="qty-controls">
                                <button class="qty-btn" onclick="alterarQuantidade(${index}, -1)" ${item.quantidade <= 1 ? 'disabled' : ''}>âˆ’</button>
                                <span class="qty-number">${item.quantidade}</span>
                                <button class="qty-btn" onclick="alterarQuantidade(${index}, 1)" ${!temEstoque ? 'disabled' : ''}>+</button>
                                <button class="btn-remove" onclick="removerItem(${index})">
                                    ðŸ—‘ï¸ Remover
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // ===== ALTERAR QUANTIDADE =====
        function alterarQuantidade(index, delta) {
            const item = carrinho.items[index];
            const novaQtd = item.quantidade + delta;

            if (novaQtd <= 0) {
                removerItem(index);
                return;
            }

            if (novaQtd > item.estoque) {
                alert(`Estoque insuficiente. DisponÃ­vel: ${item.estoque}`);
                return;
            }

            carrinho.items[index].quantidade = novaQtd;
            salvarCarrinhoLocalStorage();
            renderizarCarrinho();
            calcularTotais();
        }

        // ===== REMOVER ITEM =====
        function removerItem(index) {
            if (confirm('Deseja remover este item do carrinho?')) {
                carrinho.items.splice(index, 1);
                
                if (carrinho.items.length === 0) {
                    localStorage.removeItem('dz_cart');
                    updateCartBadge(); // Atualizar contador
                    mostrarCarrinhoVazio();
                    return;
                }

                salvarCarrinhoLocalStorage();
                renderizarCarrinho();
                calcularTotais();
            }
        }

        // ===== SALVAR NO LOCALSTORAGE =====
        function salvarCarrinhoLocalStorage() {
            // Salvar no formato compatÃ­vel com index.php: {id, name, price, qty, image}
            const simplificado = carrinho.items.map(item => ({
                id: item.produto_id,
                name: item.nome,
                price: item.preco,
                qty: item.quantidade,
                image: item.imagem,
                variacao_id: item.variacao_id || null,
                variacao_texto: item.variacao_texto || null
            }));
            localStorage.setItem('dz_cart', JSON.stringify(simplificado));
            updateCartBadge(); // Atualizar contador
        }

        // ===== CALCULAR TOTAIS =====
        function calcularTotais() {
            // Subtotal
            carrinho.subtotal = carrinho.items.reduce((total, item) => {
                return total + (item.preco * item.quantidade);
            }, 0);

            // Desconto do cupom
            carrinho.desconto = 0;
            if (carrinho.cupom) {
                // Aceita tanto 'percentual' quanto 'porcentagem'
                if (carrinho.cupom.tipo === 'percentual' || carrinho.cupom.tipo === 'porcentagem') {
                    carrinho.desconto = (carrinho.subtotal * carrinho.cupom.valor) / 100;
                    __noopLog(`ðŸŽ¯ Aplicando desconto ${carrinho.cupom.valor}% de R$ ${carrinho.subtotal.toFixed(2)} = R$ ${carrinho.desconto.toFixed(2)}`);
                } else {
                    carrinho.desconto = carrinho.cupom.valor;
                    __noopLog(`ðŸŽ¯ Aplicando desconto fixo: R$ ${carrinho.desconto.toFixed(2)}`);
                }
                carrinho.desconto = Math.min(carrinho.desconto, carrinho.subtotal);
            }

            // Frete
            carrinho.freteValor = carrinho.frete ? carrinho.frete.valor : 0;

            // Total
            carrinho.total = carrinho.subtotal - carrinho.desconto + carrinho.freteValor;

            // Atualizar interface
            atualizarResumo();
        }

        // ===== ATUALIZAR RESUMO =====
        function atualizarResumo() {
            document.getElementById('subtotalValue').textContent = `R$ ${formatarDinheiro(carrinho.subtotal)}`;

            // Desconto
            if (carrinho.cupom && carrinho.desconto > 0) {
                document.getElementById('descontoRow').style.display = 'flex';
                document.getElementById('descontoValue').textContent = `- R$ ${formatarDinheiro(carrinho.desconto)}`;
            } else {
                document.getElementById('descontoRow').style.display = 'none';
            }

            // Frete
            if (carrinho.frete) {
                if (carrinho.frete.gratis) {
                    document.getElementById('freteValue').textContent = 'GRÃTIS';
                    document.getElementById('freteValue').style.color = '#059669';
                } else {
                    document.getElementById('freteValue').textContent = `R$ ${formatarDinheiro(carrinho.freteValor)}`;
                    document.getElementById('freteValue').style.color = '#333';
                }
            } else {
                document.getElementById('freteValue').textContent = 'Calcular CEP';
                document.getElementById('freteValue').style.color = '#666';
            }

            // Total
            document.getElementById('totalValue').textContent = `R$ ${formatarDinheiro(carrinho.total)}`;

            // Progresso frete grÃ¡tis
            atualizarProgressoFreteGratis();
            
            // InformaÃ§Ãµes aplicadas (cupom e frete)
            atualizarInfoAplicadas();

            // Habilitar/desabilitar botÃ£o checkout
            const btnCheckout = document.getElementById('btnCheckout');
            if (carrinho.frete && carrinho.items.length > 0) {
                btnCheckout.disabled = false;
            } else {
                btnCheckout.disabled = true;
            }
        }
        
        // ===== ATUALIZAR INFORMA\u00c7\u00d5ES APLICADAS =====
        function atualizarInfoAplicadas() {
            const container = document.getElementById('appliedInfo');
            let html = '';
            
            // Cupom aplicado
            if (carrinho.cupom) {
                html += `
                    <div class="applied-item applied-item-cupom">
                        <div class="applied-item-left">
                            \ud83c\udfab <strong>${carrinho.cupom.codigo}</strong>
                            ${carrinho.cupom.descricao ? ` - ${carrinho.cupom.descricao}` : ''}
                        </div>
                        <button class="btn-remove-applied" onclick="removerCupom()" title="Remover cupom">\u00d7</button>
                    </div>
                `;
            }
            
            // Frete selecionado
            if (carrinho.frete) {
                const valorTexto = carrinho.frete.gratis ? 'GR\u00c1TIS' : `R$ ${formatarDinheiro(carrinho.frete.valor)}`;
                html += `
                    <div class="applied-item">
                        <div class="applied-item-left">
                            \ud83d\ude9a <strong>${carrinho.frete.nome}</strong> - ${valorTexto}
                        </div>
                        <button class="btn-remove-applied" onclick="removerFrete()" title="Recalcular frete">\u00d7</button>
                    </div>
                `;
            }
            
            if (html) {
                container.innerHTML = html;
                container.style.display = 'flex';
            } else {
                container.style.display = 'none';
            }
        }

        // ===== PROGRESSO FRETE GRÃTIS =====
        function atualizarProgressoFreteGratis() {
            const bar = document.getElementById('freeShippingBar');
            const text = document.getElementById('shippingText');
            const progressBar = document.getElementById('progressBar');
            
            if (!bar || !text || !progressBar) return;
            
            if (carrinho.subtotal >= CONFIG.freteGratisLimite) {
                // Frete grÃ¡tis atingido
                bar.classList.add('achieved');
                text.classList.add('achieved');
                text.innerHTML = 'âœ… <strong>ParabÃ©ns!</strong> VocÃª ganhou frete grÃ¡tis!';
                progressBar.classList.add('achieved');
                progressBar.style.width = '100%';
                bar.style.display = 'block';
            } else if (carrinho.subtotal > 0) {
                // Mostrando progresso
                const falta = CONFIG.freteGratisLimite - carrinho.subtotal;
                const porcentagem = (carrinho.subtotal / CONFIG.freteGratisLimite) * 100;
                
                bar.classList.remove('achieved');
                text.classList.remove('achieved');
                text.innerHTML = `ðŸšš Faltam <strong>R$ ${formatarDinheiro(falta)}</strong> para frete grÃ¡tis!`;
                progressBar.classList.remove('achieved');
                progressBar.style.width = `${porcentagem}%`;
                bar.style.display = 'block';
            } else {
                bar.style.display = 'none';
            }
        }

        // ===== APLICAR CUPOM =====
        async function aplicarCupom() {
            const codigo = document.getElementById('cupomInput').value.trim().toUpperCase();
            const btnApply = event ? event.target : document.querySelector('.btn-mini');

            __noopLog('ðŸŽŸï¸ Aplicando cupom:', codigo);
            __noopLog('ðŸ’° Subtotal:', carrinho.subtotal);

            if (!codigo) {
                alert('Digite um cÃ³digo de cupom');
                return;
            }

            const btnText = btnApply.textContent;
            btnApply.disabled = true;
            btnApply.textContent = 'Validando...';

            try {
                const requestData = {
                    action: 'validate',
                    codigo: codigo,
                    subtotal: carrinho.subtotal
                };
                
                __noopLog('ðŸ“¤ Enviando para API:', requestData);
                
                const response = await fetch('../api/cupom-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestData)
                });

                __noopLog('ðŸ“¡ Status da resposta:', response.status);
                
                const result = await response.json();
                
                __noopLog('ðŸ“Š Resposta completa da API:', result);
                
                // Mostrar logs de debug se existirem
                if (result.debug && Array.isArray(result.debug)) {
                    __noopLog('ðŸ” DEBUG da API de Cupom:');
                    result.debug.forEach(log => __noopLog('  ' + log));
                }

                if (result.success) {
                    __noopLog('âœ… Cupom vÃ¡lido!', result.data);
                    carrinho.cupom = result.data;
                    calcularTotais();
                    
                    // Desabilitar input e botÃ£o, manter cupom visÃ­vel
                    const inputCupom = document.getElementById('cupomInput');
                    inputCupom.value = codigo;
                    inputCupom.disabled = true;
                    inputCupom.style.background = '#fef3c7';
                    inputCupom.style.color = '#92400e';
                    inputCupom.style.fontWeight = '600';
                    
                    btnApply.textContent = 'âœ“';
                    btnApply.disabled = true;
                    btnApply.style.background = '#10b981';
                    btnApply.style.cursor = 'not-allowed';
                } else {
                    __noopLog('âŒ Cupom invÃ¡lido:', result.message);
                    alert(result.message);
                    btnApply.disabled = false;
                    btnApply.textContent = btnText;
                }

            } catch (error) {
                console.error('âŒ ERRO ao validar cupom:', error);
                alert('Erro ao validar cupom. Tente novamente.');
                btnApply.disabled = false;
                btnApply.textContent = btnText;
            }
        }

        // ===== REMOVER CUPOM =====
        function removerCupom() {
            carrinho.cupom = null;
            
            // Reabilitar input e botÃ£o
            const inputCupom = document.getElementById('cupomInput');
            const btnApply = document.querySelector('.btn-mini');
            
            inputCupom.value = '';
            inputCupom.placeholder = 'CÃ³digo do cupom';
            inputCupom.disabled = false;
            inputCupom.style.background = '';
            inputCupom.style.color = '';
            inputCupom.style.fontWeight = '';
            
            if (btnApply) {
                btnApply.textContent = 'Aplicar';
                btnApply.disabled = false;
                btnApply.style.background = '';
                btnApply.style.cursor = '';
            }
            
            calcularTotais();
        }

        // ===== CALCULAR FRETE (DESATIVADA - use calcularFreteMini) =====
        async function calcularFrete() {
            // FunÃ§Ã£o antiga das seÃ§Ãµes grandes - nÃ£o mais utilizada
            console.warn('calcularFrete() antiga chamada. Use calcularFreteMini()');
        }

        // ===== MOSTRAR OPÃ‡Ã•ES DE FRETE (DESATIVADA) =====
        function mostrarOpcoesFrete(opcoes) {
            // FunÃ§Ã£o antiga das seÃ§Ãµes grandes - nÃ£o mais utilizada
            console.warn('mostrarOpcoesFrete() antiga chamada. O sistema minimalista seleciona automaticamente.');
        }

        // ===== SELECIONAR FRETE (DESATIVADA) =====
        function selecionarFrete(id, valor, nome, gratis) {
            // FunÃ§Ã£o antiga das seÃ§Ãµes grandes - nÃ£o mais utilizada
            console.warn('selecionarFrete() antiga chamada.');
            carrinho.frete = { id, valor, nome, gratis };
            calcularTotais();
        }

        // ===== FINALIZAR COMPRA =====
        async function finalizarCompra() {
            // Validar estoque
            try {
                const response = await fetch('../api/carrinho-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'validateStock',
                        items: carrinho.items.map(item => ({
                            produto_id: item.produto_id,
                            variacao_id: item.variacao_id,
                            quantidade: item.quantidade,
                            nome: item.nome
                        }))
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    alert('Erro de estoque:\n' + result.message);
                    return;
                }

            } catch (error) {
                console.error('Erro ao validar estoque:', error);
                alert('Erro ao validar estoque. Tente novamente.');
                return;
            }

            // Verificar se estÃ¡ logado
            <?php if (!$usuarioLogado): ?>
                if (confirm('VocÃª precisa estar logado para finalizar a compra. Deseja fazer login agora?')) {
                    sessionStorage.setItem('redirect_after_login', 'carrinho.php');
                    window.location.href = 'login.php';
                }
                return;
            <?php endif; ?>

            // Salvar dados do pedido na sessÃ£o
            sessionStorage.setItem('pedido_carrinho', JSON.stringify(carrinho));

            // Redirecionar para checkout
            window.location.href = 'checkout.php';
        }

        // ===== MOSTRAR CARRINHO VAZIO =====
        // ===== MOSTRAR CARRINHO VAZIO =====
        function mostrarCarrinhoVazio() {
            document.getElementById('cartItemsContainer').innerHTML = `
                <div class="empty-cart">
                    <div class="empty-icon">ðŸ›’</div>
                    <h2>Seu carrinho estÃ¡ vazio</h2>
                    <p>Adicione produtos para comeÃ§ar suas compras!</p>
                    <a href="../index.php" class="btn-continue">ComeÃ§ar a Comprar</a>
                </div>
            `;
            document.getElementById('btnCheckout').disabled = true;
        }

        // ===== UTILITÃRIOS =====
        function formatarDinheiro(valor) {
            return parseFloat(valor).toFixed(2).replace('.', ',');
        }

        function formatarCEP(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            input.value = value;
        }
        
        // ===== FUNÃ‡Ã•ES PARA INPUTS MINIMALISTAS =====
        function formatarCEPMini(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            input.value = value;
        }
        
        async function calcularFreteMini() {
            const cep = document.getElementById('cepInput').value.replace(/\D/g, '');

            if (cep.length !== 8) {
                alert('CEP invÃ¡lido. Digite um CEP vÃ¡lido.');
                return;
            }

            if (carrinho.items.length === 0) {
                alert('Adicione produtos ao carrinho antes de calcular o frete.');
                return;
            }

            const btnCalc = event.target;
            btnCalc.disabled = true;
            btnCalc.textContent = 'Calculando...';

            try {
                __noopLog('\nðŸšš ===== CALCULANDO FRETE (MINI) =====');
                __noopLog('ðŸ“ CEP:', cep);
                __noopLog('ðŸ’° Subtotal:', carrinho.subtotal);
                
                const requestData = {
                    action: 'calculate',
                    cep: cep,
                    subtotal: carrinho.subtotal,
                    items: carrinho.items.map(item => ({
                        produto_id: item.produto_id || item.id,
                        variacao_id: item.variacao_id || null,
                        quantidade: item.quantidade || item.qty || 1,
                        preco: item.preco || item.price
                    }))
                };
                
                __noopLog('ðŸ“¤ Enviando para API:', requestData);
                
                const response = await fetch('../api/frete-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();
                __noopLog('ðŸ“Š Resposta da API:', result);

                if (result.success) {
                    __noopLog('âœ… OpÃ§Ãµes recebidas:', result.data.opcoes.length);
                    
                    // Mostrar opÃ§Ãµes de frete
                    mostrarOpcoesFreteMini(result.data.opcoes);
                    
                    // Feedback visual
                    btnCalc.textContent = 'âœ“';
                    btnCalc.style.background = '#10b981';
                    setTimeout(() => {
                        btnCalc.textContent = 'Calcular';
                        btnCalc.style.background = '';
                    }, 2000);
                } else {
                    console.error('âŒ Erro:', result.message);
                    
                    // Mostrar debug completo no console
                    if (result.debug && result.debug.length > 0) {
                        console.group('ðŸ” Debug da API:');
                        result.debug.forEach(log => __noopLog(log));
                        console.groupEnd();
                    }
                    
                    // Mostrar mensagem amigÃ¡vel ao usuÃ¡rio
                    alert(result.message || 'Frete incorreto. Verifique o CEP e tente novamente.');
                    document.getElementById('cepInput').value = '';
                    document.getElementById('cepInput').focus();
                }

            } catch (error) {
                console.error('âŒ Erro ao calcular frete:', error);
                alert('Erro ao processar. Verifique o CEP e tente novamente.');
            } finally {
                btnCalc.disabled = false;
                if (btnCalc.textContent !== 'âœ“') {
                    btnCalc.textContent = 'Calcular';
                }
            }
        }

        // ===== MOSTRAR OPÃ‡Ã•ES DE FRETE MINI =====
        function mostrarOpcoesFreteMini(opcoes) {
            const container = document.getElementById('freteOptionsMini');
            
            if (!opcoes || opcoes.length === 0) {
                container.style.display = 'none';
                return;
            }
            
            container.innerHTML = opcoes.map((opcao, index) => {
                const isGratis = opcao.gratis || opcao.valor === 0;
                const valor = isGratis ? 'GRÃTIS' : `R$ ${formatarDinheiro(opcao.valor)}`;
                
                return `
                    <div class="frete-option-mini" data-index="${index}" onclick="selecionarFreteMini(${index})">
                        <div class="frete-option-info">
                            <div class="frete-nome-mini">${opcao.nome}</div>
                            <div class="frete-prazo-mini">Entrega em ${opcao.prazo_dias || opcao.prazo || '?'} dias Ãºteis</div>
                        </div>
                        <div class="frete-valor-mini ${isGratis ? 'gratis' : ''}">${valor}</div>
                    </div>
                `;
            }).join('');
            
            container.style.display = 'flex';
            
            // Armazenar opÃ§Ãµes globalmente para reutilizar
            window.freteOpcoesAtual = opcoes;
        }
        
        // ===== SELECIONAR FRETE MINI =====
        function selecionarFreteMini(index) {
            // Remover seleÃ§Ã£o anterior
            document.querySelectorAll('.frete-option-mini').forEach(el => el.classList.remove('selected'));
            
            // Selecionar atual
            const opcaoElement = document.querySelector(`.frete-option-mini[data-index="${index}"]`);
            if (opcaoElement) {
                opcaoElement.classList.add('selected');
            }
            
            // Buscar opÃ§Ã£o do array armazenado
            const opcao = window.freteOpcoesAtual[index];
            
            // Atualizar carrinho
            carrinho.frete = {
                id: opcao.id,
                valor: opcao.valor || 0,
                nome: opcao.nome,
                gratis: opcao.gratis || opcao.valor === 0,
                prazo: opcao.prazo_dias || opcao.prazo,
                cep: document.getElementById('cepInput').value.trim() // Salvar CEP usado
            };
            
            __noopLog('ðŸšš Frete selecionado:', carrinho.frete);
            
            // Salvar frete no localStorage
            localStorage.setItem('dz_frete', JSON.stringify(carrinho.frete));
            __noopLog('ðŸ’¾ Frete salvo no localStorage:', JSON.stringify(carrinho.frete));
            
            calcularTotais();
        }
        
        // ===== REMOVER FRETE =====
        function removerFrete() {
            carrinho.frete = null;
            document.getElementById('cepInput').value = '';
            document.getElementById('freteOptionsMini').style.display = 'none';
            document.querySelectorAll('.frete-option-mini').forEach(el => el.classList.remove('selected'));
            
            // Remover do localStorage
            localStorage.removeItem('dz_frete');
            __noopLog('ðŸ—‘ï¸ Frete removido do localStorage');
            
            calcularTotais();
        }

        function showMessage(element, message, type) {
            element.textContent = message;
            element.className = 'cupom-message ' + type;
            element.style.display = 'block';
        }
        
        // ===== REDIRECIONAR BOTÃƒO CARRINHO =====
        // Remove listener do mini cart e mantÃ©m na pÃ¡gina atual
        document.addEventListener('DOMContentLoaded', function() {
            const cartButton = document.getElementById('cartButton');
            if (cartButton) {
                // Adicionar evento antes de clonar
                cartButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // NÃ£o faz nada, jÃ¡ estamos no carrinho
                    __noopLog('VocÃª jÃ¡ estÃ¡ na pÃ¡gina do carrinho');
                });
                
                // Estilo de desabilitado
                cartButton.style.cursor = 'default';
                cartButton.style.opacity = '0.7';
                cartButton.title = 'VocÃª jÃ¡ estÃ¡ no carrinho';
            }
        });
    </script>
</body>
</html>


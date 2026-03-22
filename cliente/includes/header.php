<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>D&Z - Beleza Premium para Você</title>
    
    <!-- Material Symbols (ícones) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="D&Z - E-commerce premium de beleza. Unhas profissionais, cílios e kits completos para elevar sua beleza ao próximo nível.">
    <meta name="keywords" content="unhas, cílios, beleza, kit beleza, D&Z, e-commerce premium">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    
    <!-- Cores customizadas para o tema D&Z -->
    <style>
        :root {
            --color-magenta: #E6007E;
            --color-magenta-dark: #C4006A;
            --color-rose-light: #FDF2F8;
        }
        
        /* Configurações globais premium */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px; /* Para navegação com header fixo */
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
            padding-top: 85px; /* Compensa a altura do header fixo */
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
        
        /* Apenas em mobile, permitir que apareça */
        @media (max-width: 768px) {
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
            text-decoration: none !important;
            color: inherit !important;
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
            z-index: 5; /* Garantir que toda a área está na frente */
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
        
        /* Botão de submit da busca */
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
            z-index: 10; /* Maior que btn-cart */
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
            z-index: 1; /* Menor que outros botões */
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
            pointer-events: none; /* Não interceptar cliques */
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
            z-index: 10; /* Maior que btn-cart */
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
        
        /* Banner Carrossel Moderno */
        .banner-carousel {
            position: relative;
            width: 100%;
            overflow: visible;
        }
        
        .carousel-container {
            position: relative;
            width: 100%;
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        
        .carousel-slide {
            min-width: 100%;
            height: 100%;
            padding: 60px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }
        
        .carousel-content {
            flex: 1;
            max-width: 500px;
            z-index: 2;
        }
        
        .carousel-title {
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #1e293b;
            letter-spacing: -0.02em;
        }
        
        .carousel-subtitle {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .carousel-btn {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(230, 0, 126, 0.25);
        }
        
        .carousel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.35);
        }
        
        .carousel-visual {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        
        .carousel-image {
            width: 300px;
            height: 300px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        /* === NAVEGAÇÃO DO CARROSSEL (DOTS) === */
        .carousel-navigation {
            position: absolute;
            bottom: 35px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 14px;
            z-index: 10;
        }
        
        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }
        
        .carousel-dot:hover {
            background: rgba(255, 255, 255, 0.7);
            transform: scale(1.15);
        }
        
        .carousel-dot.active {
            background: var(--color-magenta);
            transform: scale(1.35);
            box-shadow: 0 0 0 3px rgba(230, 0, 126, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        /* === SETAS DO CARROSSEL === */
        .carousel-arrows {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
            color: #ffffff;
            opacity: 0.7;
        }
        
        .carousel-arrows:hover {
            background: rgba(230, 0, 126, 0.9);
            backdrop-filter: blur(15px);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-50%) scale(1.1);
            opacity: 1;
            box-shadow: 0 6px 25px rgba(230, 0, 126, 0.4);
        }
        
        .carousel-arrows:active {
            transform: translateY(-50%) scale(1.05);
        }
        
        .carousel-prev {
            left: 30px;
        }
        
        .carousel-next {
            right: 30px;
        }
        
        /* === RESPONSIVIDADE NAVEGAÇÃO === */
        @media (max-width: 768px) {
            
            .carousel-slide {
                padding: 30px 20px;
                flex-direction: column;
                text-align: center;
            }
            
            .carousel-title {
                font-size: 2rem;
                line-height: 1.3;
            }
            
            .carousel-content {
                max-width: 100%;
                margin-bottom: 30px;
            }
            
            .carousel-image {
                width: 200px;
                height: 200px;
            }
            
            .carousel-arrows {
                width: 42px;
                height: 42px;
                opacity: 0.6;
            }
            
            .carousel-arrows:hover {
                opacity: 1;
            }
            
            .carousel-prev {
                left: 15px;
            }
            
            .carousel-next {
                right: 15px;
            }
            
            .carousel-dot {
                width: 10px;
                height: 10px;
            }
            
            .carousel-navigation {
                bottom: 25px;
                gap: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .carousel-title {
                font-size: 1.6rem;
            }
            
            .carousel-subtitle {
                font-size: 1rem;
            }
            
            .carousel-btn {
                padding: 12px 20px;
                font-size: 0.9rem;
            }
        }
        
        .container-dz {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: #1e293b;
            letter-spacing: -0.03em;
            line-height: 1.1;
            position: relative;
            background: linear-gradient(135deg, #1e293b 0%, var(--color-magenta) 50%, #1e293b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 200%;
            animation: gradient-shift 4s ease-in-out infinite;
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .section-title h2::before {
            content: '';
            position: absolute;
            top: 50%;
            left: -60px;
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--color-magenta));
            transform: translateY(-50%);
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -60px;
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, var(--color-magenta), transparent);
            transform: translateY(-50%);
        }
        
        .section-title p {
            font-size: 1.2rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
            font-weight: 400;
        }
        
        /* Responsivo Titles */
        @media (max-width: 768px) {
            .section-title h2 {
                font-size: 2.5rem;
            }
            
            .section-title h2::before,
            .section-title h2::after {
                display: none;
            }
            
            .section-title p {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 480px) {
            .section-title h2 {
                font-size: 2rem;
                letter-spacing: -0.02em;
            }
            
            .section-title p {
                font-size: 1rem;
            }
        }
        .categorias-dz {
            padding: 100px 0;
            background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
        }
        
        /* Cards de Produtos - Lançamentos */
        .lancamentos-carousel-container {
            position: relative;
            margin-bottom: 40px;
        }
        
        .lancamentos-grid {
            display: flex;
            gap: 30px;
            overflow: hidden;
            scroll-behavior: smooth;
            margin-bottom: 40px;
        }
        
        .produto-card {
            flex: 0 0 280px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
        }
        
        .produto-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
            border-color: rgba(230, 0, 126, 0.2);
        }
        
        .produto-image {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .produto-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Badges - aplicar APENAS quando houver classe específica */
        .produto-image.novo::before { 
            content: 'NOVO'; 
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 12px;
            min-height: 28px;
            max-width: calc(100% - 20px);
            background: #10b981;
            color: white;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            text-transform: uppercase;
            z-index: 3;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        .produto-image.promocao::before { 
            content: 'PROMOÇÃO'; 
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 12px;
            min-height: 28px;
            max-width: calc(100% - 20px);
            background: #ef4444;
            color: white;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            text-transform: uppercase;
            z-index: 3;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        .produto-image.lancamento::before { 
            content: 'LANÇAMENTO'; 
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 12px;
            min-height: 28px;
            max-width: calc(100% - 20px);
            background: #f59e0b;
            color: white;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            text-transform: uppercase;
            z-index: 3;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        .produto-image.exclusivo::before { 
            content: 'EXCLUSIVO'; 
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 6px 12px;
            min-height: 28px;
            max-width: calc(100% - 20px);
            background: #8b5cf6;
            color: white;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            text-transform: uppercase;
            z-index: 3;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        
        .produto-placeholder {
            font-size: 3rem;
            color: var(--color-magenta);
            opacity: 0.6;
        }
        
        .produto-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .produto-title {
            font-size: 1.3rem;
            font-weight: 600;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 3.38rem;
            min-height: 3.38rem;
            max-height: 3.38rem;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .produto-description {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 15px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 2.7rem;
            min-height: 2.7rem;
            max-height: 2.7rem;
        }
        
        .produto-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-magenta);
            margin-bottom: 15px;
            height: 2.25rem;
            min-height: 2.25rem;
            max-height: 2.25rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .produto-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            border: none;
            padding: 12px 0;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            margin-top: auto;
        }
        
        .produto-btn:hover {
            background: linear-gradient(135deg, var(--color-magenta-dark) 0%, #a0005a 100%);
            transform: translateY(-1px);
        }
        
        /* Botões de ação do produto */
        .produto-actions {
            display: flex;
            gap: 8px;
            width: 100%;
            margin-top: auto;
        }
        
        .btn-add-cart {
            flex: 1;
            background: white;
            color: var(--color-magenta);
            border: 2px solid var(--color-magenta);
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        
        .btn-add-cart:hover {
            background: var(--color-rose-light);
            transform: translateY(-1px);
        }
        
        .btn-buy-now {
            flex: 1;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .btn-buy-now:hover {
            background: linear-gradient(135deg, var(--color-magenta-dark) 0%, #a0005a 100%);
            transform: translateY(-1px);
        }
        
        .carousel-nav-arrows {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border: 2px solid #f1f5f9;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 5;
            color: #64748b;
        }
        
        .carousel-nav-arrows:hover {
            background: var(--color-magenta);
            color: white;
            border-color: var(--color-magenta);
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-nav-prev {
            left: -25px;
        }
        
        .carousel-nav-next {
            right: -25px;
        }
        
        .ver-todos-btn {
            text-align: center;
            margin-top: 30px;
        }
        
        .ver-todos-btn button {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .ver-todos-btn button:hover {
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(71, 85, 105, 0.3);
        }
        
        /* Responsivo Cards Produtos */
        @media (max-width: 768px) {
            .lancamentos-grid {
                gap: 20px;
                padding: 0 20px;
            }
            
            .produto-card {
                flex: 0 0 250px;
            }
            
            .produto-image {
                height: 180px;
            }
            
            .produto-content {
                padding: 15px;
            }
            
            .produto-title {
                font-size: 1.1rem;
            }
            
            .produto-description {
                font-size: 0.85rem;
            }
            
            .produto-price {
                font-size: 1.3rem;
            }
            
            .carousel-nav-arrows {
                width: 40px;
                height: 40px;
            }
            
            .carousel-nav-prev {
                left: -10px;
            }
            
            .carousel-nav-next {
                right: -10px;
            }
        }
        
        @media (max-width: 480px) {
            .lancamentos-grid {
                gap: 15px;
                padding: 0 15px;
            }
            
            .produto-card {
                flex: 0 0 220px;
            }
            
            .produto-image {
                height: 160px;
            }
            
            .produto-placeholder {
                font-size: 2.5rem;
            }
            
            .produto-content {
                padding: 12px;
            }
            
            .produto-title {
                font-size: 1rem;
                margin-bottom: 6px;
            }
            
            .produto-description {
                font-size: 0.8rem;
                margin-bottom: 12px;
            }
            
            .produto-price {
                font-size: 1.2rem;
                margin-bottom: 12px;
            }
            
            .produto-btn {
                padding: 10px 0;
                font-size: 0.85rem;
            }
            
            .produto-actions {
                flex-direction: column;
                gap: 6px;
            }
            
            .btn-add-cart,
            .btn-buy-now {
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            
            .ver-todos-btn button {
                padding: 12px 24px;
                font-size: 0.9rem;
            }
        }
        
        /* Responsivo Categorias */
        @media (max-width: 768px) {
            .categorias-grid-dz {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .categoria-card-dz {
                padding: 30px 20px;
            }
            
            .categoria-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 15px;
            }
            
            .categoria-icon svg {
                width: 24px;
                height: 24px;
            }
            
            .categoria-card-dz h3 {
                font-size: 1.1rem;
            }
            
            .categoria-card-dz p {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .categorias-grid-dz {
                grid-template-columns: 1fr;
            }
        }
        .produtos-carousel-container {
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .carousel-btn {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(230, 0, 126, 0.3);
            z-index: 2;
            flex-shrink: 0;
        }
        
        .carousel-btn:hover {
            background: linear-gradient(135deg, var(--color-magenta-dark) 0%, #a0005a 100%);
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(230, 0, 126, 0.4);
        }
        
        .carousel-btn:active {
            transform: scale(0.95);
        }
        
        .carousel-btn span {
            font-size: 1.5rem;
        }
        .produtos-dz {
            padding: 70px 0;
            background: #f9fafb;
        }
        
        .produtos-grid-dz {
            display: flex;
            overflow: hidden;
            gap: 20px;
            scroll-behavior: smooth;
            flex: 1;
            padding: 10px 0;
            transition: transform 0.3s ease;
        }
        
        /* Scrollbar customizada para produtos */
        .produtos-grid-dz::-webkit-scrollbar {
            height: 8px;
        }
        
        .produtos-grid-dz::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }
        
        .produtos-grid-dz::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            border-radius: 4px;
        }
        
        .produtos-grid-dz::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--color-magenta-dark) 0%, #a0005a 100%);
        }
        
        .produto-card-dz {
            background: linear-gradient(145deg, #ffffff 0%, #fefefe 100%);
            border-radius: 16px;
            box-shadow: 
                0 4px 16px rgba(0, 0, 0, 0.05),
                0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            flex: 0 0 220px;
            min-width: 220px;
            height: 380px;
            display: flex;
            flex-direction: column;
        }
        
        .produto-card-dz:hover {
            transform: translateY(-8px);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.12),
                0 8px 16px rgba(230, 0, 126, 0.06);
        }
        
        .produto-img {
            background: linear-gradient(145deg, #fafafa 0%, #f5f5f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: #999;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            height: 200px;
            flex-shrink: 0;
        }
        
        .produto-info-dz {
            padding: 20px 16px 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .produto-info-dz h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #1a1a1a;
            line-height: 1.3;
            letter-spacing: -0.01em;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .produto-price {
            margin-bottom: 16px;
            display: flex;
            align-items: baseline;
            gap: 8px;
            min-height: 1.95rem;
        }
        
        .price-current {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--color-magenta);
            letter-spacing: -0.02em;
        }
        
        .price-old {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
            font-weight: 500;
        }
        
        .discount-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            box-shadow: 
                0 6px 16px rgba(239, 68, 68, 0.35),
                inset 0 1px 1px rgba(255, 255, 255, 0.2);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            animation: badgePulse 3s ease-in-out infinite;
            transform: rotate(-12deg);
        }
        
        @keyframes badgePulse {
            0%, 100% { 
                transform: rotate(-12deg) scale(1); 
                box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.2);
            }
            50% { 
                transform: rotate(-12deg) scale(1.08); 
                box-shadow: 0 8px 20px rgba(239, 68, 68, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.2);
            }
        }
        
        .btn-add-cart {
            width: 100%;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            padding: 10px 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.2px;
            position: relative;
            overflow: hidden;
            margin-top: auto;
        }
        
        .btn-add-cart::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-add-cart:hover {
            background: linear-gradient(135deg, var(--color-magenta-dark) 0%, #a0005a 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(230, 0, 126, 0.3);
        }
        
        .btn-add-cart:hover::before {
            left: 100%;
        }
        
        /* Banner */
        .banner-dz {
            padding: 100px 0;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 50%, #f3e8ff 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .banner-dz::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(230, 0, 126, 0.05) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .banner-dz h2 {
            font-size: 3.2rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1a1a1a;
            position: relative;
            z-index: 1;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        
        .banner-dz .magenta {
            background: linear-gradient(135deg, var(--color-magenta) 0%, #ff1493 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }
        
        .banner-dz p {
            font-size: 1.4rem;
            color: #4a4a4a;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            position: relative;
            z-index: 1;
            font-weight: 400;
        }

        
        /* Footer Modern */
        .footer-modern {
            background: linear-gradient(135deg, #fefefe 0%, #f8f9fa 100%);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 60px 0 0;  
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
        
        /* Responsive Design */
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
        
        .payment-methods {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .payment-icons {
            display: flex;
            gap: 8px;
        }
        
        .payment-icons span {
            font-size: 1.2rem;
        }
        
        /* Responsividade */
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
            
            .trust-section {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 20px 0;
            }
            
            .payment-icons-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .shipping-icons-grid {
                gap: 8px;
            }
            
            .shipping-icon {
                width: 40px;
                height: 40px;
                padding: 8px;
                font-size: 1.1rem;
            }
            
            .social-links-grid {
                gap: 10px;
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
        
        /* Animações Premium */
        .fade-in-up {
            opacity: 0;
            transform: translateY(50px) scale(0.98);
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        /* Adiciona micro-interação aos elementos com hover */
        .fade-in-up.visible:hover {
            transform: translateY(-2px) scale(1.01);
        }
        
        /* Delay escalonado para animações */
        .fade-in-up:nth-child(1) { transition-delay: 0.1s; }
        .fade-in-up:nth-child(2) { transition-delay: 0.2s; }
        .fade-in-up:nth-child(3) { transition-delay: 0.3s; }
        .fade-in-up:nth-child(4) { transition-delay: 0.4s; }
        .fade-in-up:nth-child(5) { transition-delay: 0.5s; }
        
        /* ===== NOVOS ELEMENTOS E-COMMERCE ===== */
        
        /* Badges de benefícios minimalistas */
        .benefit-badge {
            transition: all 0.3s ease;
            border-radius: 12px;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.06);
            padding: 24px 20px;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-height: 140px;
        }
        
        .benefit-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--color-magenta);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .benefit-badge:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border-color: rgba(230, 0, 126, 0.1);
        }
        
        .benefit-badge:hover::before {
            opacity: 1;
        }
        
        .benefit-icon {
            width: 48px;
            height: 48px;
            background: #f8fafc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        
        .benefit-badge:hover .benefit-icon {
            background: var(--color-rose-light);
            transform: scale(1.1);
        }
        
        .benefit-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #1a202c;
            letter-spacing: -0.01em;
        }
        
        .benefit-description {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0;
        }
        
        /* Grid responsivo para benefícios */
        .benefits-grid {
            display: grid !important;
        }
        
        @media (max-width: 1024px) {
            .benefits-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 18px !important;
            }
        }
        
        @media (max-width: 640px) {
            .benefits-grid {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
        }
        
        /* Badge "Novo" para produtos */
        .badge-novo {
            position: absolute;
            top: 16px;
            left: 16px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            z-index: 3;
            animation: pulseGlow 3s ease-in-out infinite;
        }
        
        @keyframes pulseGlow {
            0%, 100% { 
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                transform: scale(1); 
            }
            50% { 
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
                transform: scale(1.05); 
            }
        }
        
        /* CTAs melhorados */
        .btn-cta-primary {
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 12px 24px rgba(230, 0, 126, 0.35);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            min-width: 200px;
        }
        
        .btn-cta-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }
        
        .btn-cta-primary:hover {
            background: linear-gradient(135deg, var(--color-magenta-dark) 0%, #a0005a 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 40px rgba(230, 0, 126, 0.45);
        }
        
        .btn-cta-primary:hover::before {
            left: 100%;
        }
        

        
        /* Contador em tempo real */
        .live-counter {
            display: inline-flex;
            align-items: center;
            background: rgba(239, 68, 68, 0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #ef4444;
            margin-left: 10px;
            animation: pulse 2s infinite;
        }
        
        .live-counter::before {
            content: '🔥';
            margin-right: 5px;
        }
        
        /* Selo de qualidade */
        .quality-seal {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
            z-index: 4;
            animation: rotate 10s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Melhorias nos cards de produto */
        .produto-card-dz {
            background: linear-gradient(145deg, #ffffff 0%, #fefefe 100%);
            border-radius: 20px;
            box-shadow: 
                0 4px 16px rgba(0, 0, 0, 0.05),
                0 1px 2px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            flex: 0 0 220px;
            min-width: 220px;
            height: 420px; /* Aumentado para acomodar avaliações */
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .produto-card-dz::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(230, 0, 126, 0.03) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 1;
        }
        
        .produto-card-dz:hover::before {
            opacity: 1;
        }
        
        .produto-card-dz:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 10px 20px rgba(230, 0, 126, 0.08);
        }
        
        /* Botão de favorito */
        .btn-favorite {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 5;
            backdrop-filter: blur(10px);
            color: #666;
        }
        
        .btn-favorite:hover {
            background: rgba(255, 255, 255, 1);
            color: #ef4444;
            transform: scale(1.1);
        }
        
        .btn-favorite.favorited {
            background: #ef4444;
            color: white;
        }
        
        /* Chat Button */
        .chat-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
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
            background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
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
            background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
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
        
        /* Online Status Indicator */
        .online-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            opacity: 1 !important;
            margin-top: 4px;
        }
        
        .online-indicator {
            width: 8px;
            height: 8px;
            background: #00ff88;
            border-radius: 50%;
            /* Completamente estático - sem qualquer efeito */
        }
        
        .online-status span {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            /* Texto estático - sem animação */
            animation: none !important;
            transition: none !important;
            opacity: 1 !important;
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
            position: relative;
        }
        
        .chat-message.bot {
            margin-right: 40px;
            color: #2d3748;
        }
        
        .chat-message.bot:nth-child(odd) {
            background: linear-gradient(135deg, #e0f2fe, #e1f5fe);
        }
        
        .chat-message.bot:nth-child(even) {
            background: linear-gradient(135deg, #f3e5f5, #fce4ec);
        }
        
        .chat-message.bot:nth-child(3n) {
            background: linear-gradient(135deg, #e8f5e8, #f1f8e9);
        }
        
        .chat-message.user {
            background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
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
            border-color: var(--color-magenta);
            background: white;
        }
        
        .chat-send {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark));
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
            background: linear-gradient(135deg, var(--color-magenta-dark), #a0005a);
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
        
        /* Responsividade do Chat */
        @media (max-width: 768px) {
            .chat-modal {
                width: calc(100vw - 20px);
                right: 10px;
                left: 10px;
                bottom: 100px;
                height: 450px;
            }
            
            .chat-button {
                bottom: 20px;
                right: 20px;
                width: 55px;
                height: 55px;
            }
        }
        
        .chat-button::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            /* Sem animação - totalmente estático */
        }
        
        /* Chat Tooltip */
        .chat-tooltip {
            position: absolute;
            left: -155px;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            padding: 12px 16px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            font-size: 0.9rem;
            font-weight: 600;
            color: #2d3748;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            white-space: nowrap;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .chat-tooltip::after {
            content: '';
            position: absolute;
            right: -8px;
            top: 50%;
            transform: translateY(-50%);
            border: 8px solid transparent;
            border-left-color: white;
        }
        
        .chat-button:hover .chat-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        /* Quick view button */
        .btn-quick-view {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(50px);
            background: rgba(255, 255, 255, 0.95);
            color: var(--color-magenta);
            border: 2px solid var(--color-magenta);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            backdrop-filter: blur(10px);
            z-index: 6;
        }
        
        .produto-card-dz:hover .btn-quick-view {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        
        .btn-quick-view:hover {
            background: var(--color-magenta);
            color: white;
            transform: translateX(-50%) translateY(-2px);
        }
        
        /* Responsivo */
        @media (min-width: 1400px) {
            header.header-loja .search-panel.active,
            .header-loja .search-panel.active {
                min-width: 200px !important;
                max-width: 260px !important;
            }
            
            header.header-loja .search-panel input,
            .header-loja .search-panel input {
                min-width: 200px !important;
                max-width: 260px !important;
            }
        }
        
        @media (max-width: 1200px) {
            header.header-loja .nav-loja,
            .header-loja .nav-loja {
                margin: 0 14px 0 0 !important;
            }
            
            header.header-loja .search-panel.active,
            .header-loja .search-panel.active {
                min-width: 140px !important;
                max-width: 180px !important;
            }
            
            header.header-loja .search-panel input,
            .header-loja .search-panel input {
                min-width: 140px !important;
                max-width: 180px !important;
            }
            
            header.header-loja .nav-loja > ul,
            .header-loja .nav-loja > ul {
                gap: 16px !important;
            }
        }
        
        @media (max-width: 1024px) {
            header.header-loja .nav-loja,
            .header-loja .nav-loja {
                margin: 0 12px 0 0 !important;
            }
            
            header.header-loja .search-panel.active,
            .header-loja .search-panel.active {
                min-width: 120px !important;
                max-width: 150px !important;
            }
            
            header.header-loja .search-panel input,
            .header-loja .search-panel input {
                min-width: 120px !important;
                max-width: 150px !important;
                font-size: 0.85rem !important;
                padding: 8px 10px !important;
            }
            
            header.header-loja .nav-loja > ul,
            .header-loja .nav-loja > ul {
                gap: 15px !important;
            }
            
            .nav-loja a {
                font-size: 0.85rem !important;
                padding: 8px 11px !important;
            }
        }
        
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
            
            .logo-dz-oficial {
                height: 35px;
            }
            
            .header-loja.scrolled .logo-dz-oficial {
                height: 30px;
            }
            
            .logo-dz-fallback {
                font-size: 2rem;
            }
            
            .logo-dz-fallback::before {
                width: 16px;
                height: 16px;
            }
            
            .nav-loja {
                display: none !important; /* Força esconder navegação principal */
            }
            
            /* Menu Hambúrguer Premium */
            .mobile-menu-toggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                border: none;
                background: rgba(230, 0, 126, 0.1);
                border-radius: 22px;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                z-index: 10; /* Maior que btn-cart */
                pointer-events: auto;
            }
            
            .mobile-menu-toggle:hover {
                background: var(--color-magenta);
            }
            
            /* Menu Hambúrguer Premium */
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                border: none;
                background: rgba(230, 0, 126, 0.1);
                border-radius: 22px;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                z-index: 10; /* Maior que btn-cart */
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
                transform: translateX(100%); /* Força o menu para fora da tela */
                opacity: 0;
                visibility: hidden;
                pointer-events: none; /* Impede interações quando escondido */
            }
            
            .mobile-menu.active {
                right: 0;
                transform: translateX(0);
                opacity: 1;
                visibility: visible;
                pointer-events: all;
            }
            
            /* Mobile menu overlay - inicialmente escondido */
            .mobile-menu-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(10px);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: all 0.3s ease;
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
                opacity: 0; /* Inicialmente invisível */
                transform: translateX(30px);
            }
            
            .mobile-menu.active a {
                opacity: 1;
                transform: translateX(0);
            }
            
            /* Delays para animação escalonada */
            .mobile-menu.active li:nth-child(1) a { transition-delay: 0.1s; }
            .mobile-menu.active li:nth-child(2) a { transition-delay: 0.15s; }
            .mobile-menu.active li:nth-child(3) a { transition-delay: 0.2s; }
            .mobile-menu.active li:nth-child(4) a { transition-delay: 0.25s; }
            
            .mobile-menu a:hover {
                background: rgba(230, 0, 126, 0.1);
                color: var(--color-magenta);
                transform: translateX(10px);
            }
            
            /* Mostrar botão mobile apenas em tablets/celulares - REMOVIDO DUPLICATA */
            
            .user-area {
                gap: 8px;
            }
            
            .btn-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .btn-cart {
                padding: 10px 16px;
                font-size: 0.85rem;
            }

            .btn-cart span:not(.cart-count) {
                display: none;
            }

            .btn-cart {
                padding: 10px 14px;
            }
            
            body {
                padding-top: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-content-dz {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero-text h1 {
                font-size: 2.5rem;
            }
            
            .categorias-grid-dz {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .produtos-carousel-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .carousel-btn {
                display: none;
            }
            
            .produtos-grid-dz {
                overflow-x: auto;
                padding: 10px 0 20px 0;
            }
            
            .produtos-grid-dz::-webkit-scrollbar {
                height: 8px;
            }
            
            .produto-card-dz {
                flex: 0 0 calc(50% - 10px);
                min-width: calc(50% - 10px);
                height: 350px;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
        
        @media (max-width: 480px) {
            .categorias-grid-dz {
                grid-template-columns: 1fr;
            }
            
            .produtos-carousel-container {
                flex-direction: column;
            }
            
            .carousel-btn {
                display: none;
            }
            
            .produtos-grid-dz {
                flex-direction: column;
                align-items: center;
                overflow: visible;
            }
            
            .produto-card-dz {
                flex: 0 0 auto;
                min-width: 220px;
                width: 100%;
                max-width: 300px;
                height: 380px;
            }
        }

        /* =====================================================
           CARROSSEL DE BANNERS HERO - D&Z PREMIUM
           ===================================================== */

        /* === SLIDE BASE === */
        .dz-hero-slide {
            position: relative;
            width: 100%;
            height: 65vh;
            min-height: 450px;
            max-height: none;
            background-size: cover;
            background-position: 60% center;
            background-repeat: no-repeat;
            border-radius: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        
        .dz-hero-slide.clickable {
            cursor: pointer;
            transition: transform 0.4s ease;
        }
        
        .dz-hero-slide.clickable:hover {
            transform: scale(1.002);
        }
        
        .dz-hero-slide.clickable:active {
            transform: scale(0.999);
        }

        /* === OVERLAY GRADIENTE SUAVE === */
        .dz-hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(0, 0, 0, 0.45) 0%,
                rgba(0, 0, 0, 0.15) 40%,
                rgba(0, 0, 0, 0) 70%
            );
            z-index: 1;
        }

        /* === CONTEÚDO DE TEXTO === */
        .dz-hero-content {
            position: relative;
            z-index: 2;
            padding: 5rem 6rem;
            max-width: 680px;
            color: #ffffff;
        }

        /* === TIPOGRAFIA PREMIUM === */
        .dz-hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.05;
            margin: 0 0 1.25rem 0;
            letter-spacing: -0.03em;
            animation: fadeInUp 0.8s ease-out;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .dz-hero-subtitle {
            font-size: 1.65rem;
            font-weight: 500;
            margin: 0 0 1rem 0;
            opacity: 0.98;
            letter-spacing: 0.01em;
            animation: fadeInUp 0.8s ease-out 0.1s both;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        .dz-hero-desc {
            font-size: 1.15rem;
            font-weight: 400;
            line-height: 1.7;
            margin: 0 0 2.5rem 0;
            opacity: 0.92;
            max-width: 580px;
            animation: fadeInUp 0.8s ease-out 0.2s both;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        /* === BOTÃO D&Z ROSA === */
        .dz-hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 1.1rem 2.8rem;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #ffffff;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            border: 2px solid transparent;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 6px 24px rgba(230, 0, 126, 0.35);
            text-decoration: none;
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }

        .dz-hero-btn:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #ff1a8c 0%, var(--color-magenta) 100%);
            box-shadow: 0 10px 35px rgba(230, 0, 126, 0.5);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .dz-hero-btn:active {
            transform: translateY(-1px);
        }
        
        .dz-hero-btn-static {
            pointer-events: none;
            cursor: default;
        }

        /* === FALLBACK SEM IMAGEM === */
        .dz-hero-slide.no-image {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #3d566e 100%);
        }

        /* === ANIMAÇÃO === */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }

        /* =====================================================
           RESPONSIVIDADE HERO
           ===================================================== */

        /* === TABLET GRANDE (1024px) === */
        @media (max-width: 1024px) {
            .dz-hero-slide {
                height: 60vh;
                min-height: 420px;
                background-position: 55% center;
            }
            
            .dz-hero-content {
                padding: 4rem 4rem;
                max-width: 600px;
            }
            
            .dz-hero-title {
                font-size: 3.2rem;
            }
            
            .dz-hero-subtitle {
                font-size: 1.4rem;
            }
            
            .dz-hero-desc {
                font-size: 1.05rem;
            }
            
            .dz-hero-btn {
                padding: 1rem 2.4rem;
            }
        }

        /* === TABLET/MOBILE (768px) === */
        @media (max-width: 768px) {
            .dz-hero-slide {
                height: 50vh;
                min-height: 360px;
                background-position: center center;
            }
            
            .dz-hero-overlay {
                background: linear-gradient(
                    to bottom,
                    rgba(0, 0, 0, 0.35) 0%,
                    rgba(0, 0, 0, 0.25) 50%,
                    rgba(0, 0, 0, 0.15) 100%
                );
            }
            
            .dz-hero-content {
                padding: 3rem 2.5rem;
                max-width: 100%;
                text-align: center;
            }
            
            .dz-hero-title {
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }
            
            .dz-hero-subtitle {
                font-size: 1.2rem;
            }
            
            .dz-hero-desc {
                font-size: 1rem;
                margin: 0 auto 2rem;
                max-width: 90%;
            }
            
            .dz-hero-btn {
                padding: 0.95rem 2.2rem;
                font-size: 1rem;
            }
        }

        /* === MOBILE PEQUENO (480px) === */
        @media (max-width: 480px) {
            .dz-hero-slide {
                height: 48vh;
                min-height: 340px;
            }
            
            .dz-hero-content {
                padding: 2.5rem 1.8rem;
            }
            
            .dz-hero-title {
                font-size: 2rem;
                line-height: 1.15;
            }
            
            .dz-hero-subtitle {
                font-size: 1.05rem;
            }
            
            .dz-hero-desc {
                font-size: 0.95rem;
                line-height: 1.6;
            }
            
            .dz-hero-btn {
                padding: 0.9rem 2rem;
                font-size: 0.95rem;
            }
        }
    </style>
    
    <!-- ===== RESET MOBILE MENU FORCE ===== -->
    <style>
        /* FORÇA ESCONDER MENU MOBILE EM TODAS AS CONDIÇÕES */
        .mobile-menu,
        .mobile-menu-overlay {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            z-index: -1000 !important;
        }
        
        /* SÓ MOSTRA EM DISPOSITIVOS MÓVEIS */
        @media (max-width: 768px) {
            .mobile-menu,
            .mobile-menu-overlay {
                display: block !important;
                z-index: 1000 !important;
            }
            
            /* Mas ainda escondido até ser ativado */
            .mobile-menu:not(.active),
            .mobile-menu-overlay:not(.active) {
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        }
    </style>
</head>
<body>

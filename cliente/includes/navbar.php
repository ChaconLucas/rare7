<!-- ===== RESET MOBILE MENU FORCE ===== -->
<?php
// Detectar se estamos em subdiretório (pages/) ou raiz
$isSubdir = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$basePath = $isSubdir ? '../' : '';
$logoPrincipalPath = $isSubdir ? '../../image/logo_png.png' : '../image/logo_png.png';

$usuarioLogado = isset($usuarioLogado) ? (bool) $usuarioLogado : isset($_SESSION['cliente']);
$nomeUsuario = $nomeUsuario ?? ($_SESSION['cliente']['nome'] ?? 'Cliente');

if (in_array(($currentPage ?? ''), ['login', 'register', 'cart'], true)):
?>
<header class="floating-navbar" id="floatingNavbar">
    <div class="nav-wrap container-shell">
        <a href="<?php echo $basePath; ?>index.php" class="nav-logo" aria-label="Rare - Inicio">
            <img src="<?php echo $logoPrincipalPath; ?>" alt="Logo Rare" class="nav-logo-mark" loading="lazy">
            <span class="nav-logo-text">RARE7</span>
        </a>
        <nav>
            <ul class="nav-links">
                <li><a href="<?php echo $basePath; ?>produtos.php">Todos Produtos</a></li>
                <li><a href="<?php echo $basePath; ?>produtos.php?tag=retro">Retro</a></li>
                <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Times">Times</a></li>
                <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Sele%C3%A7%C3%B5es">Seleções</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <form class="nav-search" id="navSearchForm" action="<?php echo $basePath; ?>produtos.php" method="get" role="search">
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
                    <a href="<?php echo $basePath; ?>pages/minha-conta.php">Minha conta</a>
                    <a href="<?php echo $basePath; ?>pages/minha-conta.php?tab=pedidos">Meus pedidos</a>
                    <a href="<?php echo $basePath; ?>pages/rastreio.php">Rastrear pedido</a>
                    <a href="<?php echo $basePath; ?>pages/logout.php">Sair</a>
                </div>
            </div>
            <?php else: ?>
            <a href="<?php echo $basePath; ?>pages/login.php" class="nav-icon-link" aria-label="Perfil">
                <span class="material-symbols-sharp">person</span>
            </a>
            <?php endif; ?>
            <button type="button" class="nav-icon-link floating-mobile-toggle" id="floatingMobileMenuToggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="floatingMobileMenu">
                <span class="material-symbols-sharp">menu</span>
            </button>
            <a href="<?php echo $basePath; ?>pages/carrinho.php" class="nav-icon-link" aria-label="Carrinho" data-open-mini-cart>
                <span class="material-symbols-sharp">shopping_bag</span>
            </a>
        </div>
    </div>
</header>

<div class="floating-mobile-overlay" id="floatingMobileOverlay"></div>
<aside class="floating-mobile-menu" id="floatingMobileMenu" aria-hidden="true">
    <div class="floating-mobile-menu-head">
        <span>Menu</span>
        <button type="button" class="nav-icon-link floating-mobile-close" id="floatingMobileMenuClose" aria-label="Fechar menu">
            <span class="material-symbols-sharp">close</span>
        </button>
    </div>
    <nav aria-label="Menu principal mobile">
        <div class="floating-mobile-menu-section">
            <p class="floating-mobile-menu-label">Loja</p>
            <ul>
                <li><a href="<?php echo $basePath; ?>produtos.php">Todos Produtos</a></li>
                <li><a href="<?php echo $basePath; ?>produtos.php?tag=retro">Retro</a></li>
                <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Times">Times</a></li>
                <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Sele%C3%A7%C3%B5es">Seleções</a></li>
            </ul>
        </div>

        <div class="floating-mobile-menu-section floating-mobile-menu-section-account">
            <p class="floating-mobile-menu-label">Conta</p>
            <ul>
                <?php if ($usuarioLogado): ?>
                <li><a href="<?php echo $basePath; ?>pages/minha-conta.php">Minha conta</a></li>
                <li><a href="<?php echo $basePath; ?>pages/minha-conta.php?tab=pedidos">Meus pedidos</a></li>
                <li><a href="<?php echo $basePath; ?>pages/rastreio.php">Rastrear pedido</a></li>
                <li><a href="<?php echo $basePath; ?>pages/logout.php">Sair</a></li>
                <?php else: ?>
                <li><a href="<?php echo $basePath; ?>pages/login.php">Entrar</a></li>
                <li><a href="<?php echo $basePath; ?>pages/register.php">Criar conta</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</aside>

<?php include __DIR__ . '/mini-cart.php'; ?>

<script>
(function () {
    const navbar = document.getElementById('floatingNavbar');
    const searchForm = document.getElementById('navSearchForm');
    const searchInput = document.getElementById('navSearchInput');
    const searchToggle = document.getElementById('navSearchToggle');
    const mobileMenuToggle = document.getElementById('floatingMobileMenuToggle');
    const mobileMenu = document.getElementById('floatingMobileMenu');
    const mobileOverlay = document.getElementById('floatingMobileOverlay');
    const mobileMenuClose = document.getElementById('floatingMobileMenuClose');

    function closeMobileMenu() {
        if (!mobileMenu || !mobileOverlay || !mobileMenuToggle) return;
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        mobileMenu.setAttribute('aria-hidden', 'true');
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('floating-menu-open');
    }

    function openMobileMenu() {
        if (!mobileMenu || !mobileOverlay || !mobileMenuToggle) return;
        mobileMenu.classList.add('active');
        mobileOverlay.classList.add('active');
        mobileMenu.setAttribute('aria-hidden', 'false');
        mobileMenuToggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('floating-menu-open');
    }

    function toggleNavbar() {
        if (!navbar) return;
        const threshold = Math.max(8, Math.min(48, window.innerHeight * 0.08));
        if (window.scrollY > threshold) {
            navbar.classList.add('visible');
        } else {
            navbar.classList.remove('visible');
        }
    }

    window.addEventListener('scroll', toggleNavbar, { passive: true });
    toggleNavbar();

    if (searchForm && searchInput && searchToggle) {
        searchToggle.addEventListener('click', function () {
            searchForm.classList.toggle('active');
            if (searchForm.classList.contains('active')) {
                searchInput.focus();
            }
        });

        document.addEventListener('click', function (event) {
            if (!searchForm.contains(event.target) && !searchToggle.contains(event.target)) {
                searchForm.classList.remove('active');
            }
        });
    }

    if (mobileMenuToggle && mobileMenu && mobileOverlay) {
        mobileMenuToggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const isActive = mobileMenu.classList.contains('active');
            if (isActive) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });

        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', function (event) {
                event.preventDefault();
                closeMobileMenu();
            });
        }

        mobileOverlay.addEventListener('click', closeMobileMenu);

        mobileMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMobileMenu);
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) {
                closeMobileMenu();
            }
        });
    }

    window.toggleUserDropdown = function (event) {
        event.preventDefault();
        event.stopPropagation();

        const dropdown = event.currentTarget.closest('.user-dropdown');
        if (!dropdown) return;

        dropdown.classList.toggle('active');
        const expanded = dropdown.classList.contains('active');
        event.currentTarget.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };

    document.addEventListener('click', function () {
        document.querySelectorAll('.user-dropdown.active').forEach(function (el) {
            el.classList.remove('active');
            const btn = el.querySelector('.user-dropdown-btn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileMenu();
        }
    });
})();
</script>
<?php
return;
endif;
?>
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
    
    /* ===== BOTÃO DE SUBMIT DA BUSCA ===== */
    .search-panel {
        position: relative;
        display: flex;
        align-items: center;
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

</style>

<!-- ===== NAVBAR PREMIUM RARE7 ===== -->
<header class="header-loja" id="navbar">
    <div class="container-header">
        <!-- Logo RARE7 -->
        <a href="<?php echo $basePath; ?>index.php" class="logo-container" title="Voltar à página inicial">
            <img src="<?php echo $logoPrincipalPath; ?>" alt="RARE7" class="logo-dz-oficial">
            <span class="logo-text">RARE7</span>
        </a>
        
        <!-- Navegação -->
        <nav class="nav-loja">
            <ul>
                <li><a href="<?php echo $basePath; ?>produtos.php">TODOS</a></li>
                
                <li class="has-dropdown">
                    <a href="<?php echo $basePath; ?>produtos.php?menu=unhas">UNHAS <span class="dropdown-icon">▼</span></a>
                    <div class="dropdown-menu">
                        <ul>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Esmaltes">Esmaltes</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Géis">Géis</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Preparadores">Preparadores</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Molde">Molde</a></li>
                        </ul>
                    </div>
                </li>
                
                <li class="has-dropdown">
                    <a href="<?php echo $basePath; ?>produtos.php?menu=cilios">CÍLIOS <span class="dropdown-icon">▼</span></a>
                    <div class="dropdown-menu">
                        <ul>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Colas para Cílios">Cola</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Removedores">Removedor</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Fio a Fio">Fio a fio</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Postiços">Postiço</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Tufos">Tufo</a></li>
                        </ul>
                    </div>
                </li>
                
                <li class="has-dropdown">
                    <a href="<?php echo $basePath; ?>produtos.php?menu=eletronicos">ELETRÔNICOS <span class="dropdown-icon">▼</span></a>
                    <div class="dropdown-menu">
                        <ul>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Cabine">Cabine</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Motores para Unha">Motor</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Iluminação LED">Luminária</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Coletores de Pó">Coletor</a></li>
                        </ul>
                    </div>
                </li>
                
                <li class="has-dropdown">
                    <a href="<?php echo $basePath; ?>produtos.php?menu=ferramentas">FERRAMENTAS <span class="dropdown-icon">▼</span></a>
                    <div class="dropdown-menu">
                        <ul>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Alicates">Alicates</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Espátulas">Espátulas</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Tesouras">Tesouras</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Cortadores">Cortadores</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Lixas">Lixas</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Empurradores">Empurradores</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Pincéis">Pincéis</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?categoria=Pinças">Pinças</a></li>
                        </ul>
                    </div>
                </li>
                
                <li class="has-dropdown">
                    <a href="<?php echo $basePath; ?>produtos.php?secao=marcas">MARCAS <span class="dropdown-icon">▼</span></a>
                    <div class="dropdown-menu">
                        <ul>
                            <li><a href="<?php echo $basePath; ?>produtos.php?marca=D%26Z">RARE7</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?marca=Sioux">Sioux</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?marca=Sunny%27s">Sunny's</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?marca=Crush">Crush</a></li>
                            <li><a href="<?php echo $basePath; ?>produtos.php?marca=XD">XD</a></li>
                        </ul>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- Lado direito: Busca + Ícones -->
        <div class="nav-right">
            <form action="<?php echo $basePath; ?>produtos.php" method="GET" class="search-panel" id="searchPanel">
                <input type="search" id="searchInput" name="busca" placeholder="Buscar produtos" aria-label="Buscar produtos">
                <button type="submit" class="search-submit-btn" aria-label="Pesquisar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                </button>
            </form>
            
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
                <a href="<?php echo $basePath; ?>pages/login.php" class="btn-auth btn-login">Entrar</a>
                <a href="<?php echo $basePath; ?>pages/register.php" class="btn-auth btn-register">Cadastrar</a>
            <?php else: ?>
                <!-- Dropdown do usuário logado -->
                <div class="user-dropdown">
                    <button class="user-dropdown-btn" onclick="toggleUserDropdown(event)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </button>
                    <div class="user-dropdown-menu">
                        <div class="user-greeting">Olá, <?php echo isset($nomeUsuario) ? $nomeUsuario : 'Cliente'; ?></div>
                        <a href="<?php echo $basePath; ?>pages/minha-conta.php">Minha conta</a>
                        <a href="<?php echo $basePath; ?>pages/minha-conta.php?tab=pedidos">Meus pedidos</a>
                        <a href="<?php echo $basePath; ?>pages/rastreio.php">Rastrear pedido</a>
                        <a href="<?php echo $basePath; ?>pages/logout.php">Sair</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <button class="btn-cart" id="cartButton" title="Carrinho" type="button" data-open-mini-cart>
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
        <li><a href="<?php echo $basePath; ?>index.php" onclick="closeMobileMenu()">Início</a></li>
        <li><a href="<?php echo $basePath; ?>produtos.php" onclick="closeMobileMenu()">Todos os Produtos</a></li>
        <li><a href="<?php echo $basePath; ?>produtos.php?menu=unhas" onclick="closeMobileMenu()">Unhas</a></li>
        <li><a href="<?php echo $basePath; ?>produtos.php?menu=cilios" onclick="closeMobileMenu()">Cílios</a></li>
        <li><a href="<?php echo $basePath; ?>produtos.php?menu=eletronicos" onclick="closeMobileMenu()">Eletrônicos</a></li>
        <li><a href="<?php echo $basePath; ?>produtos.php?menu=ferramentas" onclick="closeMobileMenu()">Ferramentas</a></li>
        <li><a href="<?php echo $basePath; ?>produtos.php?secao=marcas" onclick="closeMobileMenu()">Marcas</a></li>
    </ul>
</nav>

<?php include __DIR__ . '/mini-cart.php'; ?>

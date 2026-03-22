<!-- ===== RESET MOBILE MENU FORCE ===== -->
<?php
// Detectar se estamos em subdiretório (pages/) ou raiz
$isSubdir = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$basePath = $isSubdir ? '../' : '';
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

<!-- ===== NAVBAR PREMIUM D&Z ===== -->
<header class="header-loja" id="navbar">
    <div class="container-header">
        <!-- Logo D&Z Oficial -->
        <a href="<?php echo $basePath; ?>index.php" class="logo-container" title="Voltar à página inicial">
            <img src="<?php echo $basePath; ?>assets/images/Logodz.png" alt="D&Z" class="logo-dz-oficial">
            <span class="logo-text">D&Z</span>
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
                            <li><a href="<?php echo $basePath; ?>produtos.php?marca=D%26Z">D&Z</a></li>
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
                        <a href="<?php echo $basePath; ?>pages/pedidos.php">Meus pedidos</a>
                        <a href="<?php echo $basePath; ?>pages/logout.php">Sair</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <button class="btn-cart" id="cartButton" title="Carrinho">
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
        <li><a href="produtos.php?menu=ferramentas" onclick="closeMobileMenu()">Ferramentas</a></li>
        <li><a href="produtos.php?secao=marcas" onclick="closeMobileMenu()">Marcas</a></li>
    </ul>
</nav>

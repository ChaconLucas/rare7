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

// Buscar estatísticas
$stats_sql = "SELECT COUNT(*) as total FROM home_featured_products WHERE section_key = 'launches'";
$stats_result = mysqli_query($conexao, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result) ?? ['total' => 0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS - Produtos em Destaque | Rare7 Admin</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>admin/assets/images/logo_png.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <link rel="stylesheet" href="../../../css/dashboard-sections.css">
    <link rel="stylesheet" href="../../../css/dashboard-cards.css">
    <style>

        .dual-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .panel-card {
            background: var(--color-white);
            border-radius: var(--border-radius-2);
            padding: 1.5rem;
        }
        .panel-card h3 {
            margin-bottom: 1rem;
            color: var(--color-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .search-box {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--color-light);
            border-radius: var(--border-radius-1);
            margin-bottom: 1rem;
        }
        .product-list {
            max-height: 500px;
            overflow-y: auto;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--color-light);
            border-radius: var(--border-radius-1);
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        .product-item:hover {
            background: var(--color-light);
        }
        .product-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius-1);
            margin-right: 1rem;
        }
        .product-info {
            flex: 1;
        }
        .product-info strong {
            display: block;
            color: var(--color-dark);
        }
        .product-info small {
            color: var(--color-dark-variant);
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-icon {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .btn-icon.danger {
            background: var(--color-danger);
        }
        .btn-icon.move {
            background: var(--color-info-dark);
        }
        .btn-icon.featured {
            background: var(--color-warning);
            color: #fff;
        }
        .product-item.is-main-featured {
            border-color: rgba(244, 188, 52, 0.55);
            background: linear-gradient(120deg, rgba(244, 188, 52, 0.08), rgba(255,255,255,0));
        }
        .main-featured-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.35rem;
            padding: 0.18rem 0.5rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: rgba(244, 188, 52, 0.16);
            color: #b38a17;
        }
        .main-featured-pill .material-symbols-sharp {
            font-size: 0.9rem;
        }
        .btn-icon:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--color-dark-variant);
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
                    <a href="home.php">
                      <span class="material-symbols-sharp">home</span>
                      <h3>Home (Textos)</h3>
                    </a>
                    <a href="banners.php">
                      <span class="material-symbols-sharp">view_carousel</span>
                      <h3>Banners</h3>
                    </a>
                    <a href="featured.php" class="active">
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
            <h1>CMS > Produtos em Destaque (Lançamentos)</h1>

            <div class="insights">
                <div class="sales" style="cursor: default;">
                    <span class="material-symbols-sharp">star</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Produtos Selecionados</h3>
                            <h1 id="totalSelected"><?php echo $stats['total']; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="expenses" style="cursor: default;">
                    <span class="material-symbols-sharp">inventory</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Produtos Disponíveis</h3>
                            <h1 id="totalAvailable">-</h1>
                        </div>
                    </div>
                </div>
                <div class="income" style="cursor: default;">
                    <span class="material-symbols-sharp">info</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Recomendação</h3>
                            <small class="text-muted">4-8 produtos</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Painéis Duplos -->
            <div class="dual-panel">
                <!-- Painel Esquerdo: Produtos Disponíveis -->
                <div class="panel-card">
                    <h3>
                        <span class="material-symbols-sharp">inventory_2</span>
                        Produtos Disponíveis
                    </h3>
                    <input type="text" 
                           id="searchProducts" 
                           class="search-box" 
                           placeholder="Buscar produtos...">
                    <div id="availableProducts" class="product-list">
                        <div class="empty-state">
                            <span class="material-symbols-sharp" style="font-size: 3rem;">search</span>
                            <p>Carregando produtos...</p>
                        </div>
                    </div>
                </div>

                <!-- Painel Direito: Produtos Selecionados -->
                <div class="panel-card">
                    <h3>
                        <span class="material-symbols-sharp">checklist</span>
                        Produtos Selecionados
                    </h3>
                    <p style="color: var(--color-dark-variant); margin-bottom: 1rem;">
                        Arraste para reordenar ou use os botões. O item na posição #1 vira o produto grande da vitrine.
                    </p>
                    <div id="selectedProducts" class="product-list">
                        <div class="empty-state">
                            <span class="material-symbols-sharp" style="font-size: 3rem;">pending</span>
                            <p>Carregando selecionados...</p>
                        </div>
                    </div>
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
        let allProducts = [];
        let selectedProducts = [];

        // Garantir tema dark
        document.addEventListener('DOMContentLoaded', function() {
            console.log('�Y"" Página carregada - Iniciando featured.php');
            
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
            
            // Carregar produtos na ordem correta
            initProducts();
        });

        // Inicializar produtos na ordem correta
        async function initProducts() {
            await loadSelectedProducts(); // Primeiro carrega os selecionados
            await loadAllProducts(); // Depois carrega os disponíveis (que filtra pelos selecionados)
        }

        // Carregar todos os produtos
        async function loadAllProducts() {
            console.log('�Y"� Iniciando carregamento de todos os produtos...');
            try {
                const response = await fetch('cms_api.php?action=list_products');
                const result = await response.json();
                
                console.log('�Y"� Todos os produtos retornados pela API:', result);
                
                if (result.success) {
                    allProducts = result.data;
                    document.getElementById('totalAvailable').textContent = allProducts.length;
                    renderAvailableProducts();
                }
            } catch (error) {
                console.error('Erro ao carregar produtos:', error);
            }
        }

        // Carregar produtos selecionados
        async function loadSelectedProducts() {
            try {
                const response = await fetch('cms_api.php?action=list_featured_products');
                const result = await response.json();
                
                console.log('�Y"� Produtos selecionados retornados pela API:', result);
                
                if (result.success) {
                    selectedProducts = result.data;
                    console.log('�o. Total de produtos selecionados:', selectedProducts.length);
                    document.getElementById('totalSelected').textContent = selectedProducts.length;
                    renderSelectedProducts();
                } else {
                    console.error('�O API retornou erro:', result.message);
                }
            } catch (error) {
                console.error('�O Erro ao carregar produtos selecionados:', error);
            }
        }

        // Renderizar produtos disponíveis
        function renderAvailableProducts(searchTerm = '') {
            const container = document.getElementById('availableProducts');
            // Converter para número para garantir comparação correta
            const selectedIds = selectedProducts.map(p => parseInt(p.produto_id));
            
            // Placeholder SVG inline (base64) - leve e sem dependência de arquivo
            const placeholderSVG = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjZjVmNWY1Ii8+CjxwYXRoIGQ9Ik00MCAzMEw1MCA0NUgzMEw0MCAzMFoiIGZpbGw9IiNjY2MiLz4KPGNpcmNsZSBjeD0iNDUiIGN5PSIyNSIgcj0iNCIgZmlsbD0iI2NjYyIvPgo8dGV4dCB4PSI0MCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSI5IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Qcm9kdXRvPC90ZXh0Pgo8L3N2Zz4=';
            
            // Converter ID do produto também para número na comparação
            let filtered = allProducts.filter(p => !selectedIds.includes(parseInt(p.id)));
            
            if (searchTerm) {
                const search = searchTerm.toLowerCase();
                filtered = filtered.filter(p => 
                    p.nome.toLowerCase().includes(search) || 
                    (p.sku && p.sku.toLowerCase().includes(search))
                );
            }
            
            // Atualizar contador de produtos disponíveis
            document.getElementById('totalAvailable').textContent = filtered.length;
            
            if (filtered.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>Nenhum produto disponível</p></div>';
                return;
            }
            
            container.innerHTML = filtered.map(product => {
                const imgSrc = product.imagem ? `../../../../assets/images/produtos/${product.imagem}` : placeholderSVG;
                return `
                <div class="product-item">
                    <img src="${imgSrc}" 
                         alt="${product.nome}"
                         onerror="this.src='${placeholderSVG}'">
                    <div class="product-info">
                        <strong>${product.nome}</strong>
                        <small>SKU: ${product.sku || product.id}</small>
                    </div>
                    <div class="product-actions">
                        <button onclick="addProduct(${product.id})" class="btn-icon" title="Adicionar">
                            <span class="material-symbols-sharp">add</span>
                        </button>
                    </div>
                </div>
                `;
            }).join('');
        }

        // Renderizar produtos selecionados
        function renderSelectedProducts() {
            const container = document.getElementById('selectedProducts');
            
            console.log('�YZ� Renderizando produtos selecionados:', selectedProducts);
            
            // Placeholder SVG inline (base64) - leve e sem dependência de arquivo
            const placeholderSVG = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjZjVmNWY1Ii8+CjxwYXRoIGQ9Ik00MCAzMEw1MCA0NUgzMEw0MCAzMFoiIGZpbGw9IiNjY2MiLz4KPGNpcmNsZSBjeD0iNDUiIGN5PSIyNSIgcj0iNCIgZmlsbD0iI2NjYyIvPgo8dGV4dCB4PSI0MCIgeT0iNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSI5IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5Qcm9kdXRvPC90ZXh0Pgo8L3N2Zz4=';
            
            if (selectedProducts.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>Nenhum produto selecionado</p></div>';
                return;
            }
            
            container.innerHTML = selectedProducts.map((item, index) => {
                const imgSrc = item.produto_imagem ? `../../../../assets/images/produtos/${item.produto_imagem}` : placeholderSVG;
                const isMainFeatured = Number(item.position) === 1;
                return `
                <div class="product-item ${isMainFeatured ? 'is-main-featured' : ''}">
                    <img src="${imgSrc}" 
                         alt="${item.produto_nome}"
                         onerror="this.src='${placeholderSVG}'">
                    <div class="product-info">
                        <strong>${item.produto_nome}</strong>
                        <small>Posição: #${item.position}</small>
                        ${isMainFeatured ? '<div class="main-featured-pill"><span class="material-symbols-sharp">star</span> Produto grande atual</div>' : ''}
                    </div>
                    <div class="product-actions">
                        <button onclick="setMainFeatured(${item.id})" class="btn-icon featured" title="Marcar como produto grande">
                            <span class="material-symbols-sharp">star</span>
                        </button>
                        <button onclick="moveProduct(${item.id}, 'up')" class="btn-icon move" title="Subir">
                            <span class="material-symbols-sharp">arrow_upward</span>
                        </button>
                        <button onclick="moveProduct(${item.id}, 'down')" class="btn-icon move" title="Descer">
                            <span class="material-symbols-sharp">arrow_downward</span>
                        </button>
                        <button onclick="removeProduct(${item.id})" class="btn-icon danger" title="Remover">
                            <span class="material-symbols-sharp">close</span>
                        </button>
                    </div>
                </div>
                `;
            }).join('');
        }

        // Busca
        document.getElementById('searchProducts').addEventListener('input', function(e) {
            renderAvailableProducts(e.target.value);
        });

        // Adicionar produto
        async function addProduct(productId) {
            console.log('�z. Adicionando produto ID:', productId);
            
            const formData = new FormData();
            formData.append('action', 'add_featured_product');
            formData.append('product_id', productId);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                console.log('�z. Resposta da API ao adicionar:', result);
                
                if (result.success) {
                    await loadSelectedProducts();
                    await loadAllProducts(); // Recarregar produtos disponíveis
                    document.getElementById('searchProducts').value = ''; // Limpar busca
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('�O Erro ao adicionar produto:', error);
                alert('Erro ao adicionar produto');
            }
        }

        // Remover produto
        async function removeProduct(id) {
            if (!confirm('Remover este produto dos lançamentos?')) return;
            
            const formData = new FormData();
            formData.append('action', 'remove_featured_product');
            formData.append('id', id);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    await loadSelectedProducts();
                    await loadAllProducts(); // Recarregar produtos disponíveis
                    document.getElementById('searchProducts').value = ''; // Limpar busca
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Erro ao remover produto');
            }
        }

        // Mover produto
        async function moveProduct(id, direction) {
            const formData = new FormData();
            formData.append('action', 'move_featured_product');
            formData.append('id', id);
            formData.append('direction', direction);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    await loadSelectedProducts();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Erro ao mover produto');
            }
        }

        // Definir produto principal (posição #1) para vitrine de lançamentos
        async function setMainFeatured(id) {
            const formData = new FormData();
            formData.append('action', 'set_featured_product');
            formData.append('id', id);

            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    await loadSelectedProducts();
                } else {
                    alert(result.message || 'Não foi possível definir o destaque');
                }
            } catch (error) {
                alert('Erro ao definir produto em destaque');
            }
        }
    </script>
</body>
</html>

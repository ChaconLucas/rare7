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
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(is_active = 1) as ativos,
    SUM(is_active = 0) as inativos
FROM home_banners";
$stats_result = mysqli_query($conexao, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result) ?? ['total' => 0, 'ativos' => 0, 'inativos' => 0];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS - Banners do Carrossel | Rare7 Admin</title>
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>admin/favicon.ico">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <link rel="stylesheet" href="../../../css/dashboard-sections.css">
    <link rel="stylesheet" href="../../../css/dashboard-cards.css">
    <style>

        .banners-table {
            width: 100%;
            background: var(--color-white);
            border-radius: var(--border-radius-2);
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .banners-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .banners-table th {
            text-align: left;
            padding: 1rem;
            border-bottom: 2px solid var(--color-light);
            color: var(--color-dark);
            font-weight: 600;
        }
        .banners-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--color-light);
        }
        .banner-img-preview {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius-1);
        }
        .action-btn {
            padding: 0.5rem 0.8rem;
            margin: 0 0.2rem;
            border: none;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .action-btn.edit { background: var(--color-primary); color: white; }
        .action-btn.delete { background: var(--color-danger); color: white; }
        .action-btn.toggle { background: var(--color-warning); color: white; }
        .action-btn.move { background: var(--color-info-dark); color: white; }
        .action-btn:hover { opacity: 0.8; transform: translateY(-2px); }
        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: var(--border-radius-1);
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge.active { background: var(--color-success); color: white; }
        .badge.inactive { background: var(--color-light); color: var(--color-dark-variant); }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: var(--color-white);
            padding: 2rem;
            border-radius: var(--border-radius-2);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .form-group {
            margin-bottom: 1.5rem;
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
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .btn-primary {
            background: var(--color-primary);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            font-weight: 600;
        }
        .btn-secondary {
            background: var(--color-light);
            color: var(--color-dark);
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- SIDEBAR (código idêntico ao original) -->
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
                    <a href="banners.php" class="active">
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
            <h1>CMS > Banners do Carrossel</h1>

            <div class="insights">
                <div class="sales" style="cursor: default;">
                    <span class="material-symbols-sharp">view_carousel</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Banners Ativos</h3>
                            <h1><?php echo $stats['ativos']; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="expenses" style="cursor: default;">
                    <span class="material-symbols-sharp">add_photo_alternate</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total de Banners</h3>
                            <h1><?php echo $stats['total']; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="income" style="cursor: default;">
                    <span class="material-symbols-sharp">visibility_off</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Inativos</h3>
                            <h1><?php echo $stats['inativos']; ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Banners -->
            <div class="recent-orders" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>Gerenciar Banners</h2>
                    <button onclick="openAddModal()" class="btn-primary">
                        <span class="material-symbols-sharp" style="vertical-align: middle;">add</span>
                        Adicionar Banner
                    </button>
                </div>
                
                <div class="banners-table">
                    <table id="bannersTable">
                        <thead>
                            <tr>
                                <th>Imagem</th>
                                <th>Título</th>
                                <th>Status</th>
                                <th>Posição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="bannersTableBody">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem;">
                                    Carregando banners...
                                </td>
                            </tr>
                        </tbody>
                    </table>
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

    <!-- MODAL ADICIONAR/EDITAR BANNER -->
    <div id="bannerModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Adicionar Banner</h2>
            <form id="bannerForm" enctype="multipart/form-data">
                <input type="hidden" name="banner_id" id="banner_id">
                
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="title" id="title">
                </div>
                
                <div class="form-group">
                    <label>Subtítulo</label>
                    <input type="text" name="subtitle" id="subtitle">
                </div>
                
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="description" id="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Imagem (JPG, PNG, WEBP - Máx 2MB)</label>
                    <input type="file" name="image" id="image" accept="image/*">
                    <div id="currentImage" style="margin-top: 0.5rem;"></div>
                </div>
                
                <div class="form-group">
                    <label>Texto do Botão</label>
                    <input type="text" name="button_text" id="button_text" placeholder="Ex: Comprar Agora">
                </div>
                
                <div class="form-group">
                    <label>Link do Botão</label>
                    <input type="text" name="button_link" id="button_link" placeholder="Ex: /produtos">
                </div>
                
                <div style="margin-top: 2rem; text-align: right;">
                    <button type="button" onclick="closeModal()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Banner</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Definir BASE_URL para uso no JavaScript
        window.BASE_URL = '<?php echo BASE_URL; ?>';
        
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
            
            loadBanners();
        });

        // Carregar lista de banners
        async function loadBanners() {
            try {
                const response = await fetch('cms_api.php?action=list_banners');
                const result = await response.json();
                
                if (result.success) {
                    renderBanners(result.data);
                } else {
                    alert(result.message || 'Erro ao carregar banners');
                }
            } catch (error) {
                console.error('Erro:', error);
                if (error && error.name === 'AbortError') {
                    return;
                }
                alert('Erro ao carregar banners');
            }
        }

        // Renderizar banners na tabela
        function renderBanners(banners) {
            const tbody = document.getElementById('bannersTableBody');

            function normalizeBannerUrl(rawPath) {
                if (!rawPath) return null;

                const normalized = String(rawPath).trim().replace(/^\/+/, '');
                if (/^https?:\/\//i.test(normalized)) {
                    return normalized;
                }
                if (normalized.includes('uploads/banners/')) {
                    return `${window.BASE_URL}${normalized}`;
                }
                return `${window.BASE_URL}uploads/banners/${normalized}`;
            }
            
            if (banners.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">Nenhum banner cadastrado</td></tr>';
                return;
            }
            
            // Placeholder SVG inline (base64) - leve e sem dependência de arquivo
            const placeholderSVG = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiB2aWV3Qm94PSIwIDAgMTAwIDYwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZjVmNWY1Ii8+CjxwYXRoIGQ9Ik01MCAyNUw2MCA0MEg0MEw1MCAyNVoiIGZpbGw9IiNjY2MiLz4KPGNpcmNsZSBjeD0iNTUiIGN5PSIyMCIgcj0iMyIgZmlsbD0iI2NjYyIvPgo8dGV4dCB4PSI1MCIgeT0iNTUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSI4IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5TZW0gaW1hZ2VtPC90ZXh0Pgo8L3N2Zz4=';
            
            tbody.innerHTML = banners.map(banner => {
                const imagePath = normalizeBannerUrl(banner.image_path) || placeholderSVG;
                const displayTitle = String(banner.title || '').trim()
                    || '(Sem titulo)';
                const displaySubtitle = String(banner.subtitle || '').trim()
                    || String(banner.description || '').trim()
                    || '';
                
                return `
                <tr>
                    <td><img src="${imagePath}" class="banner-img-preview" alt="${displayTitle}" onerror="this.src='${placeholderSVG}'"></td>
                    <td>
                        <strong>${displayTitle}</strong><br>
                        <small style="color: var(--color-dark-variant);">${displaySubtitle}</small>
                    </td>
                    <td>
                        <span class="badge ${banner.is_active == 1 ? 'active' : 'inactive'}">
                            ${banner.is_active == 1 ? 'Ativo' : 'Inativo'}
                        </span>
                    </td>
                    <td>#${banner.position}</td>
                    <td>
                        <button onclick="editBanner(${banner.id})" class="action-btn edit" title="Editar">
                            <span class="material-symbols-sharp" style="font-size: 1rem; vertical-align: middle;">edit</span>
                        </button>
                        <button onclick="toggleBanner(${banner.id})" class="action-btn toggle" title="${banner.is_active == 1 ? 'Desativar' : 'Ativar'}">
                            <span class="material-symbols-sharp" style="font-size: 1rem; vertical-align: middle;">${banner.is_active == 1 ? 'visibility_off' : 'visibility'}</span>
                        </button>
                        <button onclick="moveBanner(${banner.id}, 'up')" class="action-btn move" title="Subir">
                            <span class="material-symbols-sharp" style="font-size: 1rem; vertical-align: middle;">arrow_upward</span>
                        </button>
                        <button onclick="moveBanner(${banner.id}, 'down')" class="action-btn move" title="Descer">
                            <span class="material-symbols-sharp" style="font-size: 1rem; vertical-align: middle;">arrow_downward</span>
                        </button>
                        <button onclick="deleteBanner(${banner.id})" class="action-btn delete" title="Excluir">
                            <span class="material-symbols-sharp" style="font-size: 1rem; vertical-align: middle;">delete</span>
                        </button>
                    </td>
                </tr>
                `;
            }).join('');
        }

        // Abrir modal para adicionar
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Adicionar Banner';
            document.getElementById('bannerForm').reset();
            document.getElementById('banner_id').value = '';
            document.getElementById('currentImage').innerHTML = '';
            document.getElementById('bannerModal').classList.add('show');
        }

        // Editar banner
        async function editBanner(id) {
            const response = await fetch('cms_api.php?action=list_banners');
            const result = await response.json();
            const banner = result.data.find(b => b.id == id);
            
            if (!banner) return;
            
            document.getElementById('modalTitle').textContent = 'Editar Banner';
            document.getElementById('banner_id').value = banner.id;
            document.getElementById('title').value = (banner.title || '').trim();
            document.getElementById('subtitle').value = (banner.subtitle || '').trim();
            document.getElementById('description').value = (banner.description || '').trim();
            document.getElementById('button_text').value = banner.button_text || '';
            document.getElementById('button_link').value = banner.button_link || '';
            const currentImageUrl = banner.image_path
                ? (/^https?:\/\//i.test(String(banner.image_path).trim())
                    ? String(banner.image_path).trim()
                    : (String(banner.image_path).includes('uploads/banners/')
                        ? `${window.BASE_URL}${String(banner.image_path).replace(/^\/+/, '')}`
                        : `${window.BASE_URL}uploads/banners/${String(banner.image_path).replace(/^\/+/, '')}`))
                : '';
            document.getElementById('currentImage').innerHTML = currentImageUrl
                ? `<img src="${currentImageUrl}" style="width: 200px; border-radius: 8px;">`
                : '';
            document.getElementById('bannerModal').classList.add('show');
        }

        // Fechar modal
        function closeModal() {
            document.getElementById('bannerModal').classList.remove('show');
        }

        // Salvar banner (form submit)
        document.getElementById('bannerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const bannerId = document.getElementById('banner_id').value;
            formData.append('action', bannerId ? 'edit_banner' : 'add_banner');
            if (bannerId) formData.append('id', bannerId);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    closeModal();
                    location.reload(); // Atualizar stats
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar banner');
            }
        });

        // Toggle ativo/inativo
        async function toggleBanner(id) {
            if (!confirm('Alterar status deste banner?')) return;
            
            const formData = new FormData();
            formData.append('action', 'toggle_banner');
            formData.append('id', id);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Erro ao alterar status');
            }
        }

        // Mover banner
        async function moveBanner(id, direction) {
            const formData = new FormData();
            formData.append('action', 'move_banner');
            formData.append('id', id);
            formData.append('direction', direction);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    loadBanners();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Erro ao mover banner');
            }
        }

        // Excluir banner
        async function deleteBanner(id) {
            if (!confirm('Tem certeza que deseja excluir este banner? Esta ação não pode ser desfeita.')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_banner');
            formData.append('id', id);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Erro ao excluir banner');
            }
        }

        // Fechar modal clicando fora
        document.getElementById('bannerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

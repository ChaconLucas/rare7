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

/*
TODO - IMPLEMENTAR:
- Tabela: cms_testimonials (id, nome_cliente, cargo, empresa, depoimento, avatar, avaliacao, ativo, ordem, created_at, updated_at)
- CRUD completo de depoimentos:
  * Nome do cliente
  * Cargo/função (opcional)
  * Empresa (opcional)
  * Texto do depoimento
  * Upload de foto/avatar
  * Avaliação (1-5 estrelas)
  * Ordenação
  * Ativar/desativar
- Preview da seção de depoimentos
- Validação de texto (limite de caracteres)
*/
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS - Depoimentos | Rare7 Admin</title>
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>admin/favicon.ico">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <link rel="stylesheet" href="../../../css/dashboard-sections.css">
    <link rel="stylesheet" href="../../../css/dashboard-cards.css">
    <style>
        /* Override mínimo: mudar cor do item CMS de verde para rosa + aplicar layout padrão */
        aside .sidebar .menu-item-container a.menu-item-with-submenu.active {
            background: rgba(198, 167, 94, 0.1) !important;
            color: #C6A75E !important;
            margin-left: 1.5rem !important;
            margin-right: 0.5rem !important;
            border-left: 5px solid #C6A75E !important;
            border-radius: 0 8px 8px 0 !important;
        }
        
        aside .sidebar .menu-item-container a.menu-item-with-submenu.active span,
        aside .sidebar .menu-item-container a.menu-item-with-submenu.active h3 {
            color: #C6A75E !important;
            font-weight: 600 !important;
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
                    <a href="featured.php">
                      <span class="material-symbols-sharp">star</span>
                      <h3>Lançamentos</h3>
                    </a>
                    <a href="promos.php">
                      <span class="material-symbols-sharp">local_offer</span>
                      <h3>Promoções</h3>
                    </a>
                    <a href="testimonials.php" class="active">
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

        <!-- CONTEÚDO PRINCIPAL -->
        <main>
            <h1>CMS > Depoimentos de Clientes</h1>

            <div class="insights">
                <div class="sales" style="cursor: default;">
                    <span class="material-symbols-sharp">format_quote</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Depoimentos Ativos</h3>
                            <h1 id="count-active">0</h1>
                        </div>
                    </div>
                </div>
                <div class="expenses" style="cursor: default;">
                    <span class="material-symbols-sharp">sentiment_satisfied</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total Cadastrados</h3>
                            <h1 id="count-total">0</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Principal -->
            <div class="recent-orders" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>Gerenciar Depoimentos</h2>
                    <button id="btn-novo" class="btn" style="padding: 0.8rem 1.5rem; background: var(--color-primary); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer;">
                        <span class="material-symbols-sharp" style="vertical-align: middle;">add</span>
                        Novo Depoimento
                    </button>
                </div>
                <div id="container-tabela" style="padding: 2rem; background: var(--color-white); border-radius: var(--border-radius-2); overflow-x: auto;">
                    <!-- Carregará via JavaScript -->
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

    <!-- Modal para Criar/Editar Depoimento -->
    <div id="modal-depoimento" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: var(--color-white); padding: 2rem; border-radius: var(--border-radius-2); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-light); padding-bottom: 1rem;">
                <h2 id="modal-title" style="margin: 0;">Novo Depoimento</h2>
                <button id="btn-modal-close" style="background: none; border: none; cursor: pointer; font-size: 1.5rem; color: var(--color-dark);">✕</button>
            </div>
            
            <form id="form-depoimento" enctype="multipart/form-data">
                <input type="hidden" id="edit-id" name="id">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                        Nome do Cliente <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input type="text" id="input-nome" name="nome" required maxlength="120" 
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-light); border-radius: var(--border-radius-1); font-size: 1rem;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                        Cargo/Função (opcional)
                    </label>
                    <input type="text" id="input-cargo" name="cargo_empresa" maxlength="120" placeholder="Ex: Cliente verificada"
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-light); border-radius: var(--border-radius-1); font-size: 1rem;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                        Texto do Depoimento <span style="color: var(--color-danger);">*</span>
                    </label>
                    <textarea id="input-texto" name="texto" required maxlength="600" rows="5"
                              style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-light); border-radius: var(--border-radius-1); font-size: 1rem; resize: vertical;"></textarea>
                    <small id="char-count" style="color: var(--color-dark-variant);">0 / 600 caracteres</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                            Avaliação <span style="color: var(--color-danger);">*</span>
                        </label>
                        <select id="input-rating" name="rating" required
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-light); border-radius: var(--border-radius-1); font-size: 1rem;">
                            <option value="5">⭐⭐⭐⭐⭐ (5 estrelas)</option>
                            <option value="4">⭐⭐⭐⭐ (4 estrelas)</option>
                            <option value="3">⭐⭐⭐ (3 estrelas)</option>
                            <option value="2">⭐⭐ (2 estrelas)</option>
                            <option value="1">⭐ (1 estrela)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                            Ordem
                        </label>
                        <input type="number" id="input-ordem" name="ordem" value="0" min="0"
                               style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-light); border-radius: var(--border-radius-1); font-size: 1rem;">
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                        Avatar do Cliente (opcional)
                    </label>
                    <input type="file" id="input-avatar" name="avatar" accept="image/jpeg,image/jpg,image/png,image/webp"
                           style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-light); border-radius: var(--border-radius-1); font-size: 1rem;">
                    <small style="color: var(--color-dark-variant);">Formatos: JPG, PNG, WEBP (opcional - se vazio exibe inicial)</small>
                    <div id="avatar-preview" style="margin-top: 0.5rem;"></div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; cursor: pointer; user-select: none;">
                        <input type="checkbox" id="input-ativo" name="ativo" checked
                               style="width: 20px; height: 20px; margin-right: 0.5rem; cursor: pointer;">
                        <span style="font-weight: 600; color: var(--color-dark);">Depoimento Ativo</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--color-light); padding-top: 1.5rem;">
                    <button type="button" id="btn-cancelar" style="padding: 0.75rem 1.5rem; background: var(--color-light); color: var(--color-dark); border: none; border-radius: var(--border-radius-1); cursor: pointer; font-weight: 600;">
                        Cancelar
                    </button>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--color-primary); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-weight: 600;">
                        <span class="material-symbols-sharp" style="vertical-align: middle; font-size: 1.2rem;">save</span>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let depoimentos = [];
        let setupNeeded = false;
        
        // Funções auxiliares
        function showMessage(message, type = 'success') {
            const color = type === 'success' ? 'var(--color-success)' : 'var(--color-danger)';
            const messageDiv = document.createElement('div');
            messageDiv.style.cssText = `position: fixed; top: 20px; right: 20px; background: ${color}; color: white; padding: 1rem 1.5rem; border-radius: var(--border-radius-1); box-shadow: 0 4px 20px rgba(0,0,0,0.2); z-index: 10001; animation: slideIn 0.3s;`;
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);
            setTimeout(() => {
                messageDiv.style.animation = 'slideOut 0.3s';
                setTimeout(() => messageDiv.remove(), 300);
            }, 3000);
        }
        
        function renderStars(rating) {
            const stars = parseInt(rating) || 5;
            return '⭐'.repeat(stars);
        }
        
        function truncateText(text, maxLength = 80) {
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        }
        
        // Carregar depoimentos
        async function carregarDepoimentos() {
            try {
                const response = await fetch('cms_api.php?action=list_testimonials');
                const data = await response.json();
                
                if (data.setup_needed) {
                    setupNeeded = true;
                    renderizarSetupNeeded();
                    return;
                }
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                depoimentos = data.items || [];
                
                // Atualizar contadores
                document.getElementById('count-active').textContent = data.counts?.active || 0;
                document.getElementById('count-total').textContent = data.counts?.total || 0;
                
                renderizarTabela();
            } catch (error) {
                console.error('Erro ao carregar depoimentos:', error);
                showMessage('Erro ao carregar depoimentos: ' + error.message, 'error');
            }
        }
        
        function renderizarSetupNeeded() {
            document.getElementById('container-tabela').innerHTML = `
                <div style="text-align: center; padding: 3rem 2rem;">
                    <span class="material-symbols-sharp" style="font-size: 4rem; color: var(--color-warning); display: block; margin-bottom: 1rem;">warning</span>
                    <h3 style="margin-bottom: 1rem;">Tabela Não Encontrada</h3>
                    <p style="color: var(--color-dark-variant); margin-bottom: 2rem;">
                        A tabela cms_testimonials ainda não foi criada no banco de dados.<br>
                        Execute o script de setup para criar a estrutura necessária.
                    </p>
                    <a href="setup_testimonials_debug.php" class="btn" style="padding: 1rem 2rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: var(--border-radius-1); display: inline-block;">
                        <span class="material-symbols-sharp" style="vertical-align: middle;">construction</span>
                        Executar Setup
                    </a>
                </div>
            `;
            document.getElementById('btn-novo').disabled = true;
            document.getElementById('btn-novo').style.opacity = '0.5';
            document.getElementById('btn-novo').style.cursor = 'not-allowed';
        }
        
        function renderizarTabela() {
            const container = document.getElementById('container-tabela');
            
            if (depoimentos.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem 2rem; color: var(--color-dark-variant);">
                        <span class="material-symbols-sharp" style="font-size: 4rem; color: var(--color-primary); display: block; margin-bottom: 1rem;">reviews</span>
                        <h3 style="margin-bottom: 0.8rem;">Nenhum Depoimento Cadastrado</h3>
                        <p>Clique no botão "+ Novo Depoimento" para adicionar o primeiro depoimento de cliente.</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-light); text-align: left;">
                            <th style="padding: 1rem; font-weight: 600;">Cliente</th>
                            <th style="padding: 1rem; font-weight: 600;">Depoimento</th>
                            <th style="padding: 1rem; font-weight: 600; text-align: center;">Avaliação</th>
                            <th style="padding: 1rem; font-weight: 600; text-align: center;">Ordem</th>
                            <th style="padding: 1rem; font-weight: 600; text-align: center;">Status</th>
                            <th style="padding: 1rem; font-weight: 600; text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            depoimentos.forEach(dep => {
                const inicial = dep.nome.charAt(0).toUpperCase();
                const avatarHtml = dep.avatar_path 
                    ? `<img src="../../../../../${dep.avatar_path}" alt="${dep.nome}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">`
                    : `<div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--color-magenta), var(--color-magenta-dark)); color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 10px;">${inicial}</div>`;
                
                html += `
                    <tr style="border-bottom: 1px solid var(--color-light);">
                        <td style="padding: 1rem;">
                            <div style="display: flex; align-items: center;">
                                ${avatarHtml}
                                <div>
                                    <div style="font-weight: 600;">${dep.nome}</div>
                                    ${dep.cargo_empresa ? `<small style="color: var(--color-dark-variant);">${dep.cargo_empresa}</small>` : ''}
                                </div>
                            </div>
                        </td>
                        <td style="padding: 1rem; color: var(--color-dark-variant); font-style: italic; max-width: 300px;">
                            "${truncateText(dep.texto, 100)}"
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            ${renderStars(dep.rating)}
                        </td>
                        <td style="padding: 1rem; text-align: center; font-weight: 600;">
                            ${dep.ordem}
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <span class="status-badge status-${dep.ativo == 1 ? 'active' : 'inactive'}" style="padding: 0.3rem 0.8rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; ${dep.ativo == 1 ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;'}">
                                ${dep.ativo == 1 ? 'Ativo' : 'Inativo'}
                            </span>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <button onclick="editarDepoimento(${dep.id})" title="Editar" style="background: var(--color-primary); color: white; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">
                                    <span class="material-symbols-sharp" style="font-size: 1.2rem;">edit</span>
                                </button>
                                <button onclick="toggleDepoimento(${dep.id})" title="${dep.ativo == 1 ? 'Desativar' : 'Ativar'}" style="background: ${dep.ativo == 1 ? 'var(--color-warning)' : 'var(--color-success)'}; color: white; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">
                                    <span class="material-symbols-sharp" style="font-size: 1.2rem;">${dep.ativo == 1 ? 'visibility_off' : 'visibility'}</span>
                                </button>
                                <button onclick="excluirDepoimento(${dep.id}, '${dep.nome.replace(/'/g, "\\'")}')" title="Excluir" style="background: var(--color-danger); color: white; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">
                                    <span class="material-symbols-sharp" style="font-size: 1.2rem;">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            container.innerHTML = html;
        }
        
        // Modal
        function abrirModal(editandoId = null) {
            const modal = document.getElementById('modal-depoimento');
            const form = document.getElementById('form-depoimento');
            const title = document.getElementById('modal-title');
            
            form.reset();
            document.getElementById('char-count').textContent = '0 / 600 caracteres';
            document.getElementById('avatar-preview').innerHTML = '';
            
            if (editandoId) {
                const dep = depoimentos.find(d => d.id == editandoId);
                if (dep) {
                    title.textContent = 'Editar Depoimento';
                    document.getElementById('edit-id').value = dep.id;
                    document.getElementById('input-nome').value = dep.nome;
                    document.getElementById('input-cargo').value = dep.cargo_empresa || '';
                    document.getElementById('input-texto').value = dep.texto;
                    document.getElementById('input-rating').value = dep.rating;
                    document.getElementById('input-ordem').value = dep.ordem;
                    document.getElementById('input-ativo').checked = dep.ativo == 1;
                    document.getElementById('char-count').textContent = `${dep.texto.length} / 600 caracteres`;
                    
                    if (dep.avatar_path) {
                        document.getElementById('avatar-preview').innerHTML = `
                            <img src="../../../../../${dep.avatar_path}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-primary);">
                            <small style="display: block; margin-top: 0.5rem; color: var(--color-dark-variant);">Avatar atual</small>
                        `;
                    }
                }
            } else {
                title.textContent = 'Novo Depoimento';
                document.getElementById('edit-id').value = '';
            }
            
            modal.style.display = 'flex';
        }
        
        function fecharModal() {
            document.getElementById('modal-depoimento').style.display = 'none';
        }
        
        // Ações CRUD
        async function salvarDepoimento(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const editId = document.getElementById('edit-id').value;
            
            formData.append('action', editId ? 'update_testimonial' : 'add_testimonial');
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    fecharModal();
                    await carregarDepoimentos();
                } else {
                    showMessage(data.message || 'Erro ao salvar depoimento', 'error');
                }
            } catch (error) {
                console.error('Erro ao salvar:', error);
                showMessage('Erro ao salvar depoimento', 'error');
            }
        }
        
        function editarDepoimento(id) {
            abrirModal(id);
        }
        
        async function toggleDepoimento(id) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_testimonial');
                formData.append('id', id);
                
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    await carregarDepoimentos();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Erro ao alternar status:', error);
                showMessage('Erro ao alternar status', 'error');
            }
        }
        
        async function excluirDepoimento(id, nome) {
            if (!confirm(`Tem certeza que deseja excluir o depoimento de "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_testimonial');
                formData.append('id', id);
                
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    await carregarDepoimentos();
                } else {
                    showMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Erro ao excluir:', error);
                showMessage('Erro ao excluir depoimento', 'error');
            }
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            carregarDepoimentos();
            
            // Botão novo
            document.getElementById('btn-novo').addEventListener('click', () => abrirModal());
            
            // Fechar modal
            document.getElementById('btn-modal-close').addEventListener('click', fecharModal);
            document.getElementById('btn-cancelar').addEventListener('click', fecharModal);
            
            // Clicar fora do modal
            document.getElementById('modal-depoimento').addEventListener('click', function(e) {
                if (e.target === this) fecharModal();
            });
            
            // Form submit
            document.getElementById('form-depoimento').addEventListener('submit', salvarDepoimento);
            
            // Contador de caracteres
            document.getElementById('input-texto').addEventListener('input', function() {
                document.getElementById('char-count').textContent = `${this.value.length} / 600 caracteres`;
            });
            
            // Preview de avatar
            document.getElementById('input-avatar').addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('avatar-preview').innerHTML = `
                            <img src="${e.target.result}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-primary);">
                            <small style="display: block; margin-top: 0.5rem; color: var(--color-success);">Novo avatar</small>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Theme toggler
            const savedTheme = localStorage.getItem('darkTheme');
            if (savedTheme === 'true') {
                document.body.classList.add('dark-theme-variables');
                const themeToggler = document.querySelector('.theme-toggler');
                themeToggler.querySelector('span:nth-child(1)').classList.remove('active');
                themeToggler.querySelector('span:nth-child(2)').classList.add('active');
            }
            
            const themeToggler = document.querySelector('.theme-toggler');
            themeToggler.addEventListener('click', () => {
                document.body.classList.toggle('dark-theme-variables');
                themeToggler.querySelector('span:nth-child(1)').classList.toggle('active');
                themeToggler.querySelector('span:nth-child(2)').classList.toggle('active');
                
                if (document.body.classList.contains('dark-theme-variables')) {
                    localStorage.setItem('darkTheme', 'true');
                } else {
                    localStorage.setItem('darkTheme', 'false');
                }
            });
        });
    </script>
</body>
</html>


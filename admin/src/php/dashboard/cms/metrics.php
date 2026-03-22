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
- Tabela: cms_metrics (id, tipo, icone, titulo, valor, unidade, ordem, ativo, created_at, updated_at)
  * tipo: 'numero', 'percentual', 'texto'
  * icone: nome do ícone Material Symbols
- Interface para gerenciar:
  * Métricas da empresa (ex: "+10.000 clientes", "50+ categorias", "98% satisfação")
  * Título da métrica
  * Valor (número ou texto)
  * Unidade (clientes, categorias, etc)
  * Ícone personalizado
  * Ordenação
  * Ativar/desativar
- Preview da seção de métricas
- Sugestão de ícones (biblioteca Material Symbols)
*/
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS - Métricas da Empresa | Rare7 Admin</title>    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>admin/favicon.ico">    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <link rel="stylesheet" href="../../../css/dashboard-sections.css">
    <link rel="stylesheet" href="../../../css/dashboard-cards.css">
    <style>
        /* Override mínimo: mudar cor do item CMS para rosa */
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
        
        /* FIX CRÍTICO: Resetar posicionamento do item ativo no submenu */
        aside .sidebar .menu-item-container .submenu a {
            position: static !important;
            top: auto !important;
            bottom: auto !important;
            left: auto !important;
            right: auto !important;
            transform: none !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
        
        /* Estilo para item ativo dentro do submenu */
        aside .sidebar .menu-item-container .submenu a.active {
            background: rgba(198, 167, 94, 0.1) !important;
            color: #C6A75E !important;
            border-left: 4px solid #C6A75E !important;
            border-radius: 0 6px 6px 0 !important;
        }
        
        aside .sidebar .menu-item-container .submenu a.active span,
        aside .sidebar .menu-item-container .submenu a.active h3 {
            color: #C6A75E !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body class="page-metrics">
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
                    <a href="testimonials.php">
                      <span class="material-symbols-sharp">format_quote</span>
                      <h3>Depoimentos</h3>
                    </a>
                    <a href="metrics.php" class="active">
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
            <h1>CMS > Métricas da Empresa</h1>

            <div class="insights">
                <div class="sales" style="cursor: default;">
                    <span class="material-symbols-sharp">speed</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Métricas Ativas</h3>
                            <h1 id="count-active">0</h1>
                        </div>
                    </div>
                </div>
                <div class="expenses" style="cursor: default;">
                    <span class="material-symbols-sharp">trending_up</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total Cadastradas</h3>
                            <h1 id="count-total">0</h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Principal -->
            <div class="recent-orders" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>Gerenciar Métricas Exibidas</h2>
                    <button class="btn" id="btn-add-metric" style="padding: 0.8rem 1.5rem; background: var(--color-primary); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer;">
                        <span class="material-symbols-sharp" style="vertical-align: middle;">add</span>
                        Adicionar Métrica
                    </button>
                </div>
                
                <!-- Tabela de Métricas -->
                <div id="metrics-container">
                    <table id="metrics-table">
                        <thead>
                            <tr>
                                <th>Valor</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th>Ordem</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="metrics-tbody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">
                                    <div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                                    <p style="margin-top: 1rem; color: var(--color-dark-variant);">Carregando...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Estado Vazio -->
                <div id="empty-state" style="display: none; text-align: center; padding: 3rem 2rem;">
                    <span class="material-symbols-sharp" style="font-size: 4rem; color: var(--color-primary); display: block; margin-bottom: 1rem;">bar_chart</span>
                    <h3 style="margin-bottom: 0.8rem; color: var(--color-dark);">Nenhuma métrica cadastrada</h3>
                    <p style="color: var(--color-dark-variant); margin-bottom: 1.5rem;">
                        Clique em "Adicionar Métrica" para criar sua primeira métrica.
                    </p>
                </div>
                
                <!-- Botão de Setup (caso tabela não exista) -->
                <div id="setup-needed" style="display: none; text-align: center; padding: 3rem 2rem;">
                    <span class="material-symbols-sharp" style="font-size: 4rem; color: #ef4444; display: block; margin-bottom: 1rem;">warning</span>
                    <h3 style="margin-bottom: 0.8rem; color: var(--color-dark);">Tabela não encontrada</h3>
                    <p style="color: var(--color-dark-variant); margin-bottom: 1.5rem;">
                        A tabela <code>cms_home_metrics</code> precisa ser criada primeiro.
                    </p>
                    <a href="setup_metrics.php" class="btn" style="display: inline-block; padding: 1rem 2rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: var(--border-radius-1);">
                        <span class="material-symbols-sharp" style="vertical-align: middle;">build</span>
                        Executar Setup Agora
                    </a>
                </div>
            </div>
        </main>

        <!-- Modal de Adicionar/Editar Métrica -->
        <div id="modal-metric" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow: auto;">
            <div class="modal-content" style="background-color: var(--color-white); margin: 5% auto; padding: 2rem; border-radius: var(--border-radius-2); max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 id="modal-title" style="margin: 0;">Adicionar Métrica</h2>
                    <span class="material-symbols-sharp" id="modal-close" style="cursor: pointer; font-size: 1.8rem; color: var(--color-dark-variant);">close</span>
                </div>
                
                <form id="form-metric">
                    <input type="hidden" id="metric-id" name="id">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                            Valor <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="metric-valor" name="valor" maxlength="20" required
                            placeholder="Ex: 98%, 50k+, 4.9, 24h"
                            style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1); font-size: 1rem;">
                        <small style="color: var(--color-dark-variant); font-size: 0.85rem;">Máximo 20 caracteres</small>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                            Descrição (Label) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="metric-label" name="label" maxlength="60" required
                            placeholder="Ex: Clientes satisfeitas"
                            style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1); font-size: 1rem;">
                        <small style="color: var(--color-dark-variant); font-size: 0.85rem;">Máximo 60 caracteres</small>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                            Tipo
                        </label>
                        <select id="metric-tipo" name="tipo"
                            style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1); font-size: 1rem;">
                            <option value="texto">Texto</option>
                            <option value="numero">Número</option>
                            <option value="percentual">Percentual</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--color-dark);">
                            Ordem de Exibição
                        </label>
                        <input type="number" id="metric-ordem" name="ordem" value="0" min="0"
                            style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1); font-size: 1rem;">
                        <small style="color: var(--color-dark-variant); font-size: 0.85rem;">Menor número aparece primeiro</small>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="metric-ativo" name="ativo" checked
                                style="width: 20px; height: 20px; margin-right: 0.5rem; cursor: pointer;">
                            <span style="font-weight: 600; color: var(--color-dark);">Métrica Ativa</span>
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn" style="flex: 1; padding: 1rem; background: var(--color-primary); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-weight: 600;">
                            <span class="material-symbols-sharp" style="vertical-align: middle;">save</span>
                            Salvar
                        </button>
                        <button type="button" id="btn-cancel" class="btn" style="flex: 1; padding: 1rem; background: var(--color-dark-variant); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-weight: 600;">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

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
            
            // ========== MÉTRICAS - CRUD ==========
            
            let metrics = [];
            let editingId = null;
            
            const modal = document.getElementById('modal-metric');
            const modalTitle = document.getElementById('modal-title');
            const modalClose = document.getElementById('modal-close');
            const btnAddMetric = document.getElementById('btn-add-metric');
            const btnCancel = document.getElementById('btn-cancel');
            const form = document.getElementById('form-metric');
            
            const metricsTable = document.getElementById('metrics-table');
            const metricsTbody = document.getElementById('metrics-tbody');
            const emptyState = document.getElementById('empty-state');
            const setupNeeded = document.getElementById('setup-needed');
            
            const countActive = document.getElementById('count-active');
            const countTotal = document.getElementById('count-total');
            
            // Carregar métricas
            async function carregarMetricas() {
                try {
                    const response = await fetch('cms_api.php?action=list_metrics');
                    const data = await response.json();
                    
                    if (data.success) {
                        metrics = data.items;
                        
                        // Atualizar contadores
                        countActive.textContent = data.counts.active;
                        countTotal.textContent = data.counts.total;
                        
                        renderizarTabela();
                    } else {
                        if (data.setup_needed) {
                            setupNeeded.style.display = 'block';
                            metricsTable.style.display = 'none';
                            emptyState.style.display = 'none';
                        } else {
                            alert('Erro ao carregar métricas: ' + data.message);
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Erro ao conectar com o servidor');
                }
            }
            
            // Renderizar tabela
            function renderizarTabela() {
                if (metrics.length === 0) {
                    metricsTable.style.display = 'none';
                    emptyState.style.display = 'block';
                    setupNeeded.style.display = 'none';
                    return;
                }
                
                metricsTable.style.display = 'table';
                emptyState.style.display = 'none';
                setupNeeded.style.display = 'none';
                
                metricsTbody.innerHTML = '';
                
                metrics.forEach(metric => {
                    const tr = document.createElement('tr');
                    
                    // Valor
                    const tdValor = document.createElement('td');
                    tdValor.innerHTML = `<strong style="font-size: 1.2rem; color: var(--color-primary);">${escapeHtml(metric.valor)}</strong>`;
                    tr.appendChild(tdValor);
                    
                    // Label
                    const tdLabel = document.createElement('td');
                    tdLabel.textContent = metric.label;
                    tr.appendChild(tdLabel);
                    
                    // Tipo
                    const tdTipo = document.createElement('td');
                    const tipoMap = { texto: 'Texto', numero: 'Número', percentual: 'Percentual' };
                    tdTipo.innerHTML = `<span style="padding: 0.3rem 0.8rem; background: var(--color-info-light); border-radius: var(--border-radius-1); font-size: 0.85rem;">${tipoMap[metric.tipo] || metric.tipo}</span>`;
                    tr.appendChild(tdTipo);
                    
                    // Ordem
                    const tdOrdem = document.createElement('td');
                    tdOrdem.textContent = metric.ordem;
                    tr.appendChild(tdOrdem);
                    
                    // Status
                    const tdStatus = document.createElement('td');
                    const statusColor = metric.ativo == 1 ? '#10b981' : '#ef4444';
                    const statusText = metric.ativo == 1 ? 'Ativa' : 'Inativa';
                    tdStatus.innerHTML = `<span style="color: ${statusColor}; font-weight: 600;">●</span> ${statusText}`;
                    tr.appendChild(tdStatus);
                    
                    // Ações
                    const tdActions = document.createElement('td');
                    tdActions.innerHTML = `
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn-edit" data-id="${metric.id}" title="Editar"
                                style="padding: 0.5rem; background: #3b82f6; color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer;">
                                <span class="material-symbols-sharp" style="font-size: 1.2rem;">edit</span>
                            </button>
                            <button class="btn-toggle" data-id="${metric.id}" title="${metric.ativo == 1 ? 'Desativar' : 'Ativar'}"
                                style="padding: 0.5rem; background: ${metric.ativo == 1 ? '#f59e0b' : '#10b981'}; color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer;">
                                <span class="material-symbols-sharp" style="font-size: 1.2rem;">${metric.ativo == 1 ? 'toggle_on' : 'toggle_off'}</span>
                            </button>
                            <button class="btn-delete" data-id="${metric.id}" title="Excluir"
                                style="padding: 0.5rem; background: #ef4444; color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer;">
                                <span class="material-symbols-sharp" style="font-size: 1.2rem;">delete</span>
                            </button>
                        </div>
                    `;
                    tr.appendChild(tdActions);
                    
                    metricsTbody.appendChild(tr);
                });
                
                // Event listeners para botões de ação
                document.querySelectorAll('.btn-edit').forEach(btn => {
                    btn.addEventListener('click', () => editarMetrica(parseInt(btn.dataset.id)));
                });
                
                document.querySelectorAll('.btn-toggle').forEach(btn => {
                    btn.addEventListener('click', () => toggleMetrica(parseInt(btn.dataset.id)));
                });
                
                document.querySelectorAll('.btn-delete').forEach(btn => {
                    btn.addEventListener('click', () => excluirMetrica(parseInt(btn.dataset.id)));
                });
            }
            
            // Abrir modal para adicionar
            btnAddMetric.addEventListener('click', () => {
                editingId = null;
                modalTitle.textContent = 'Adicionar Métrica';
                form.reset();
                document.getElementById('metric-ativo').checked = true;
                modal.style.display = 'block';
            });
            
            // Editar métrica
            function editarMetrica(id) {
                const metric = metrics.find(m => m.id == id);
                if (!metric) return;
                
                editingId = id;
                modalTitle.textContent = 'Editar Métrica';
                
                document.getElementById('metric-id').value = metric.id;
                document.getElementById('metric-valor').value = metric.valor;
                document.getElementById('metric-label').value = metric.label;
                document.getElementById('metric-tipo').value = metric.tipo;
                document.getElementById('metric-ordem').value = metric.ordem;
                document.getElementById('metric-ativo').checked = metric.ativo == 1;
                
                modal.style.display = 'block';
            }
            
            // Salvar métrica
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(form);
                formData.append('action', editingId ? 'update_metric' : 'add_metric');
                
                try {
                    const response = await fetch('cms_api.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        modal.style.display = 'none';
                        carregarMetricas();
                        alert(data.message);
                    } else {
                        if (data.setup_needed) {
                            modal.style.display = 'none';
                            setupNeeded.style.display = 'block';
                            metricsTable.style.display = 'none';
                            emptyState.style.display = 'none';
                        } else {
                            alert('Erro: ' + data.message);
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Erro ao salvar métrica');
                }
            });
            
            // Toggle ativo/inativo
            async function toggleMetrica(id) {
                const formData = new FormData();
                formData.append('action', 'toggle_metric');
                formData.append('id', id);
                
                try {
                    const response = await fetch('cms_api.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        carregarMetricas();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Erro ao atualizar status');
                }
            }
            
            // Excluir métrica
            async function excluirMetrica(id) {
                const metric = metrics.find(m => m.id == id);
                if (!metric) return;
                
                if (!confirm(`Tem certeza que deseja excluir a métrica "${metric.valor} - ${metric.label}"?`)) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'delete_metric');
                formData.append('id', id);
                
                try {
                    const response = await fetch('cms_api.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        carregarMetricas();
                        alert(data.message);
                    } else {
                        alert('Erro: ' + data.message);
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    alert('Erro ao excluir métrica');
                }
            }
            
            // Fechar modal
            modalClose.addEventListener('click', () => {
                modal.style.display = 'none';
            });
            
            btnCancel.addEventListener('click', () => {
                modal.style.display = 'none';
            });
            
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Utility function
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }
            
            // Carregar métricas ao iniciar
            carregarMetricas();
        });
    </script>
    
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html>


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
$stats = [
    'ativas' => 0,
    'total' => 0
];

try {
    $result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM cms_home_promotions WHERE ativo = 1");
    if ($result) {
        $stats['ativas'] = mysqli_fetch_assoc($result)['total'];
    }
    
    $result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM cms_home_promotions");
    if ($result) {
        $stats['total'] = mysqli_fetch_assoc($result)['total'];
    }
} catch (Exception $e) {
    // Tabela ainda não existe
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS - Promoções e Ofertas | Rare7 Admin</title>    <link rel="icon" type="image/png" href="../../../../assets/images/logo_png.png" sizes="any">
    <link rel="apple-touch-icon" href="../../../../assets/images/logo_png.png">    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
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
                    <a href="promos.php" class="active">
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

        <!-- CONTEÚDO PRINCIPAL -->
        <main>
            <h1>CMS > Promoções e Ofertas</h1>

            <div class="insights">
                <div class="sales" style="cursor: default;">
                    <span class="material-symbols-sharp">campaign</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Promoções Ativas</h3>
                            <h1 id="promoAtivas"><?php echo $stats['ativas']; ?></h1>
                        </div>
                    </div>
                </div>
                <div class="expenses" style="cursor: default;">
                    <span class="material-symbols-sharp">sell</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total de Ofertas</h3>
                            <h1 id="promoTotal"><?php echo $stats['total']; ?></h1>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Principal -->
            <div class="recent-orders" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>Gerenciar Promoções na Home</h2>
                    <button id="btnNovaPromocao" class="btn" style="padding: 0.8rem 1.5rem; background: var(--color-primary); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer;">
                        <span class="material-symbols-sharp" style="vertical-align: middle;">add</span>
                        Nova Promoção
                    </button>
                </div>
                
                <div id="promoTable" style="background: var(--color-white); border-radius: var(--border-radius-2); overflow: hidden;">
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Cupom</th>
                                <th>Período</th>
                                <th>Ordem</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="promoTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">
                                    Carregando promoções...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Nova/Editar Promoção -->
            <div id="modalPromocao" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: var(--color-white); border-radius: var(--border-radius-2); width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2 id="modalTitle">Nova Promoção</h2>
                        <button onclick="fecharModal()" style="background: none; border: none; cursor: pointer; font-size: 1.5rem;">&times;</button>
                    </div>
                    
                    <form id="formPromocao">
                        <input type="hidden" id="promo_id" name="id">
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Título *</label>
                            <input type="text" id="promo_titulo" name="titulo" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Subtítulo</label>
                            <input type="text" id="promo_subtitulo" name="subtitulo" style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Badge (Ex: 15% OFF)</label>
                            <input type="text" id="promo_badge" name="badge_text" placeholder="15% OFF" style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Cupom Vinculado</label>
                            <select id="promo_cupom" name="cupom_id" style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                                <option value="">Nenhum cupom vinculado</option>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Texto do Botão</label>
                                <input type="text" id="promo_button_text" name="button_text" placeholder="Aproveitar Oferta" style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Link do Botão</label>
                                <input type="text" id="promo_button_link" name="button_link" placeholder="#" style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Data Início *</label>
                                <input type="date" id="promo_data_inicio" name="data_inicio" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Data Fim *</label>
                                <input type="date" id="promo_data_fim" name="data_fim" required style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Ordem de Exibição</label>
                                <input type="number" id="promo_ordem" name="ordem" value="0" min="0" style="width: 100%; padding: 0.8rem; border: 1px solid var(--color-info-dark); border-radius: var(--border-radius-1);">
                            </div>
                            <div style="display: flex; align-items: flex-end;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" id="promo_ativo" name="ativo" checked style="width: 20px; height: 20px; cursor: pointer;">
                                    <span style="font-weight: 600;">Promoção Ativa</span>
                                </label>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="submit" style="flex: 1; padding: 0.8rem; background: var(--color-primary); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-weight: 600;">
                                Salvar Promoção
                            </button>
                            <button type="button" onclick="fecharModal()" style="flex: 1; padding: 0.8rem; background: var(--color-info-dark); color: white; border: none; border-radius: var(--border-radius-1); cursor: pointer; font-weight: 600;">
                                Cancelar
                            </button>
                        </div>
                    </form>
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
        // ========================================
        // FUNÇÕES DE PROMOÇÕES
        // ========================================
        
        let promocoes = [];
        let cupons = [];
        let editandoId = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            carregarPromocoes();
            carregarCupons();
            
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
                localStorage.setItem('darkTheme', document.body.classList.contains('dark-theme-variables') ? 'true' : 'false');
            });
            
            // Botão nova promoção
            document.getElementById('btnNovaPromocao').addEventListener('click', abrirModalNovo);
            
            // Form submit
            document.getElementById('formPromocao').addEventListener('submit', salvarPromocao);
        });
        
        async function carregarPromocoes() {
            try {
                const response = await fetch('cms_api.php?action=list_promotions');
                
                if (!response.ok) {
                    throw new Error('Erro na resposta da API');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    promocoes = data.data;
                    renderizarTabela();
                    atualizarCards();
                } else {
                    // Verificar se precisa de setup
                    if (data.setup_needed) {
                        document.getElementById('promoTableBody').innerHTML = `
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem;">
                                    <span class="material-symbols-sharp" style="font-size: 4rem; color: var(--color-warning); display: block; margin-bottom: 1rem;">error</span>
                                    <h3 style="color: var(--color-dark); margin-bottom: 1rem;">Tabela não encontrada</h3>
                                    <p style="color: var(--color-dark-variant); margin-bottom: 1.5rem;">${data.message}</p>
                                    <a href="setup_promotions.php" style="display: inline-block; padding: 0.8rem 2rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: var(--border-radius-1); font-weight: 600;">
                                        <span class="material-symbols-sharp" style="vertical-align: middle; margin-right: 0.5rem;">build</span>
                                        Executar Setup Agora
                                    </a>
                                </td>
                            </tr>
                        `;
                    } else {
                        mostrarMensagem('Erro: ' + data.message, 'error');
                    }
                }
            } catch (error) {
                console.error('Erro:', error);
                document.getElementById('promoTableBody').innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem;">
                            <span class="material-symbols-sharp" style="font-size: 4rem; color: var(--color-danger); display: block; margin-bottom: 1rem;">error</span>
                            <h3 style="color: var(--color-dark); margin-bottom: 1rem;">Erro ao carregar promoções</h3>
                            <p style="color: var(--color-dark-variant); margin-bottom: 1.5rem;">A tabela cms_home_promotions pode não existir ainda.</p>
                            <a href="setup_promotions.php" style="display: inline-block; padding: 0.8rem 2rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: var(--border-radius-1); font-weight: 600;">
                                <span class="material-symbols-sharp" style="vertical-align: middle; margin-right: 0.5rem;">build</span>
                                Criar Estrutura do Banco
                            </a>
                        </td>
                    </tr>
                `;
            }
        }
        
        async function carregarCupons() {
            try {
                const response = await fetch('cms_api.php?action=list_coupons_simple');
                const data = await response.json();
                
                if (data.success) {
                    cupons = data.data;
                    atualizarSelectCupons();
                }
            } catch (error) {
                console.error('Erro ao carregar cupons:', error);
            }
        }
        
        function atualizarSelectCupons() {
            const select = document.getElementById('promo_cupom');
            select.innerHTML = '<option value="">Nenhum cupom vinculado</option>';
            
            cupons.forEach(cupom => {
                const option = document.createElement('option');
                option.value = cupom.id;
                option.textContent = cupom.codigo;
                select.appendChild(option);
            });
        }
        
        function renderizarTabela() {
            const tbody = document.getElementById('promoTableBody');
            
            if (promocoes.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--color-dark-variant);">
                            <span class="material-symbols-sharp" style="font-size: 3rem; display: block; margin-bottom: 1rem;">campaign</span>
                            Você ainda não criou nenhuma promoção.
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = promocoes.map(promo => `
                <tr>
                    <td>
                        <strong>${promo.titulo}</strong>
                        ${promo.subtitulo ? '<br><small style="color: var(--color-dark-variant);">' + promo.subtitulo + '</small>' : ''}
                        ${promo.badge_text ? '<br><span style="background: var(--color-primary); color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">' + promo.badge_text + '</span>' : ''}
                    </td>
                    <td>${promo.cupom_codigo || '<span style="color: var(--color-dark-variant);">Sem cupom</span>'}</td>
                    <td>
                        ${formatarData(promo.data_inicio)}<br>
                        <small style="color: var(--color-dark-variant);">até ${formatarData(promo.data_fim)}</small>
                    </td>
                    <td>${promo.ordem}</td>
                    <td>
                        <span style="padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; ${promo.ativo == 1 ? 'background: var(--color-success); color: white;' : 'background: var(--color-danger); color: white;'}">
                            ${promo.ativo == 1 ? '✓ Ativa' : '✗ Inativa'}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <button onclick="editarPromocao(${promo.id})" style="padding: 0.5rem; background: var(--color-primary); color: white; border: none; border-radius: 4px; cursor: pointer;" title="Editar">
                                <span class="material-symbols-sharp" style="font-size: 1.2rem;">edit</span>
                            </button>
                            <button onclick="togglePromocao(${promo.id})" style="padding: 0.5rem; background: var(--color-info-dark); color: white; border: none; border-radius: 4px; cursor: pointer;" title="${promo.ativo == 1 ? 'Desativar' : 'Ativar'}">
                                <span class="material-symbols-sharp" style="font-size: 1.2rem;">${promo.ativo == 1 ? 'visibility_off' : 'visibility'}</span>
                            </button>
                            <button onclick="excluirPromocao(${promo.id}, '${promo.titulo}')" style="padding: 0.5rem; background: var(--color-danger); color: white; border: none; border-radius: 4px; cursor: pointer;" title="Excluir">
                                <span class="material-symbols-sharp" style="font-size: 1.2rem;">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function atualizarCards() {
            const ativas = promocoes.filter(p => p.ativo == 1).length;
            document.getElementById('promoAtivas').textContent = ativas;
            document.getElementById('promoTotal').textContent = promocoes.length;
        }
        
        function formatarData(data) {
            if (!data) return '';
            const partes = data.split('-');
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }

        function formatDateLocal(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function abrirModalNovo() {
            editandoId = null;
            document.getElementById('modalTitle').textContent = 'Nova Promoção';
            document.getElementById('formPromocao').reset();
            document.getElementById('promo_id').value = '';
            document.getElementById('promo_ativo').checked = true;
            
            // Definir datas padrão
            const hoje = new Date();
            const daqui30dias = new Date(hoje.getTime() + (30 * 24 * 60 * 60 * 1000));
            document.getElementById('promo_data_inicio').value = formatDateLocal(hoje);
            document.getElementById('promo_data_fim').value = formatDateLocal(daqui30dias);
            
            document.getElementById('modalPromocao').style.display = 'flex';
        }
        
        function editarPromocao(id) {
            const promo = promocoes.find(p => p.id == id);
            if (!promo) return;
            
            editandoId = id;
            document.getElementById('modalTitle').textContent = 'Editar Promoção';
            document.getElementById('promo_id').value = promo.id;
            document.getElementById('promo_titulo').value = promo.titulo || '';
            document.getElementById('promo_subtitulo').value = promo.subtitulo || '';
            document.getElementById('promo_badge').value = promo.badge_text || '';
            document.getElementById('promo_cupom').value = promo.cupom_id || '';
            document.getElementById('promo_button_text').value = promo.button_text || '';
            document.getElementById('promo_button_link').value = promo.button_link || '';
            document.getElementById('promo_data_inicio').value = promo.data_inicio || '';
            document.getElementById('promo_data_fim').value = promo.data_fim || '';
            document.getElementById('promo_ordem').value = promo.ordem || 0;
            document.getElementById('promo_ativo').checked = promo.ativo == 1;
            
            document.getElementById('modalPromocao').style.display = 'flex';
        }
        
        function fecharModal() {
            document.getElementById('modalPromocao').style.display = 'none';
            editandoId = null;
        }
        
        async function salvarPromocao(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', editandoId ? 'update_promotion' : 'add_promotion');
            
            // Adicionar checkbox ativo se marcado
            if (document.getElementById('promo_ativo').checked) {
                formData.set('ativo', '1');
            } else {
                formData.delete('ativo');
            }
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensagem(data.message, 'success');
                    fecharModal();
                    carregarPromocoes();
                } else {
                    mostrarMensagem('Erro: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarMensagem('Erro ao salvar promoção', 'error');
            }
        }
        
        async function togglePromocao(id) {
            const formData = new FormData();
            formData.append('action', 'toggle_promotion');
            formData.append('id', id);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensagem(data.message, 'success');
                    carregarPromocoes();
                } else {
                    mostrarMensagem('Erro: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarMensagem('Erro ao atualizar status', 'error');
            }
        }
        
        async function excluirPromocao(id, titulo) {
            if (!confirm(`Tem certeza que deseja excluir a promoção "${titulo}"?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_promotion');
            formData.append('id', id);
            
            try {
                const response = await fetch('cms_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensagem(data.message, 'success');
                    carregarPromocoes();
                } else {
                    mostrarMensagem('Erro: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarMensagem('Erro ao excluir promoção', 'error');
            }
        }
        
        function mostrarMensagem(mensagem, tipo) {
            // Criar elemento de notificação
            const notif = document.createElement('div');
            notif.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                padding: 1rem 1.5rem;
                background: ${tipo === 'success' ? 'var(--color-success)' : 'var(--color-danger)'};
                color: white;
                border-radius: var(--border-radius-1);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
            `;
            notif.textContent = mensagem;
            
            document.body.appendChild(notif);
            
            setTimeout(() => {
                notif.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }
    </script>
    
    <style>
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</body>
</html>


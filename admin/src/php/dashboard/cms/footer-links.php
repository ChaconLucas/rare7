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

// Buscar TODOS os links do footer (incluindo inativos)
$links_sql = "SELECT * FROM cms_footer_links ORDER BY coluna, ordem ASC, id ASC";
$links_result = mysqli_query($conexao, $links_sql);
$links = [];
while ($row = mysqli_fetch_assoc($links_result)) {
    $links[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Links do Footer | Rare7 Admin</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="../../../css/dashboard.css">
    <link rel="stylesheet" href="../../../css/dashboard-sections.css">
    <link rel="stylesheet" href="../../../css/dashboard-cards.css">
    <style>
        .links-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .column-section {
            background: var(--color-white);
            border-radius: var(--border-radius-2);
            padding: 1.5rem;
        }
        
        .column-section h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--color-dark);
        }
        
        .link-item {
            background: var(--color-light);
            border-radius: var(--border-radius-1);
            padding: 1rem;
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .link-item:hover {
            background: var(--color-primary-light);
        }
        
        .link-item.inactive {
            opacity: 0.5;
            background: #f5f5f5;
        }
        
        .link-info {
            flex: 1;
        }
        
        .link-info strong {
            display: block;
            color: var(--color-dark);
            margin-bottom: 0.3rem;
        }
        
        .link-info small {
            color: var(--color-dark-variant);
            font-size: 0.85rem;
        }
        
        .link-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.4rem;
            border-radius: var(--border-radius-1);
            transition: all 0.3s;
        }
        
        .btn-icon:hover {
            background: var(--color-primary);
            color: white;
        }
        
        .btn-icon.delete:hover {
            background: var(--color-danger);
        }
        
        .btn-add {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            background: var(--color-primary-dark);
            transform: translateY(-2px);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--color-white);
            border-radius: var(--border-radius-2);
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--color-dark);
        }
        
        .close-modal {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--color-dark-variant);
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--color-dark);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--color-info-light);
            border-radius: var(--border-radius-1);
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn-cancel {
            background: var(--color-light);
            color: var(--color-dark);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius-1);
            cursor: pointer;
        }
        
        .btn-save {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius-1);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-save:hover {
            background: var(--color-primary-dark);
        }
        
        .success-message {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--color-success);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius-1);
            display: none;
            align-items: center;
            gap: 0.5rem;
            z-index: 2000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .success-message.show {
            display: flex;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .order-badge {
            background: var(--color-primary);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- SIDEBAR -->
        <?php include '../sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <main>
            <h1>
                <span class="material-symbols-sharp">link</span>
                Gerenciar Links do Footer
            </h1>
            
            <div class="insights">
                <div class="sales">
                    <span class="material-symbols-sharp">list</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Total de Links</h3>
                            <h1><?php echo count($links); ?></h1>
                        </div>
                    </div>
                    <small class="text-muted">Links cadastrados</small>
                </div>
                
                <div class="expenses">
                    <span class="material-symbols-sharp">shopping_bag</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Produtos</h3>
                            <h1><?php echo count(array_filter($links, fn($l) => $l['coluna'] == 'produtos')); ?></h1>
                        </div>
                    </div>
                    <small class="text-muted">Links de produtos</small>
                </div>
                
                <div class="income">
                    <span class="material-symbols-sharp">support_agent</span>
                    <div class="middle">
                        <div class="left">
                            <h3>Atendimento</h3>
                            <h1><?php echo count(array_filter($links, fn($l) => $l['coluna'] == 'atendimento')); ?></h1>
                        </div>
                    </div>
                    <small class="text-muted">Links de atendimento</small>
                </div>
            </div>

            <div class="links-grid">
                <!-- Coluna Produtos -->
                <div class="column-section">
                    <h3>
                        <span class="material-symbols-sharp">shopping_bag</span>
                        Coluna: Produtos
                    </h3>
                    <button class="btn-add" onclick="abrirModalNovo('produtos')">
                        <span class="material-symbols-sharp">add</span>
                        Adicionar Link
                    </button>
                    
                    <div style="margin-top: 1.5rem;">
                        <?php 
                        $produtos_links = array_filter($links, fn($l) => $l['coluna'] == 'produtos');
                        if (empty($produtos_links)): 
                        ?>
                            <p style="color: var(--color-dark-variant); text-align: center; padding: 2rem;">
                                Nenhum link cadastrado. Clique em "Adicionar Link" para começar.
                            </p>
                        <?php else: ?>
                            <?php foreach ($produtos_links as $link): ?>
                                <div class="link-item <?php echo !$link['ativo'] ? 'inactive' : ''; ?>" data-id="<?php echo $link['id']; ?>">
                                    <div class="link-info">
                                        <strong>
                                            <span class="order-badge">#<?php echo $link['ordem']; ?></span>
                                            <?php echo htmlspecialchars($link['texto']); ?>
                                            <?php if (!$link['ativo']): ?>
                                                <small style="color: var(--color-danger);">(Inativo)</small>
                                            <?php endif; ?>
                                        </strong>
                                        <small><?php echo htmlspecialchars($link['link']); ?></small>
                                    </div>
                                    <div class="link-actions">
                                        <button class="btn-icon" onclick="editarLink(<?php echo $link['id']; ?>)" title="Editar">
                                            <span class="material-symbols-sharp">edit</span>
                                        </button>
                                        <button class="btn-icon" onclick="toggleAtivo(<?php echo $link['id']; ?>, <?php echo $link['ativo'] ? 0 : 1; ?>)" title="<?php echo $link['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                            <span class="material-symbols-sharp"><?php echo $link['ativo'] ? 'visibility_off' : 'visibility'; ?></span>
                                        </button>
                                        <button class="btn-icon delete" onclick="excluirLink(<?php echo $link['id']; ?>)" title="Excluir">
                                            <span class="material-symbols-sharp">delete</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coluna Atendimento -->
                <div class="column-section">
                    <h3>
                        <span class="material-symbols-sharp">support_agent</span>
                        Coluna: Atendimento
                    </h3>
                    <button class="btn-add" onclick="abrirModalNovo('atendimento')">
                        <span class="material-symbols-sharp">add</span>
                        Adicionar Link
                    </button>
                    
                    <div style="margin-top: 1.5rem;">
                        <?php 
                        $atendimento_links = array_filter($links, fn($l) => $l['coluna'] == 'atendimento');
                        if (empty($atendimento_links)): 
                        ?>
                            <p style="color: var(--color-dark-variant); text-align: center; padding: 2rem;">
                                Nenhum link cadastrado. Clique em "Adicionar Link" para começar.
                            </p>
                        <?php else: ?>
                            <?php foreach ($atendimento_links as $link): ?>
                                <div class="link-item <?php echo !$link['ativo'] ? 'inactive' : ''; ?>" data-id="<?php echo $link['id']; ?>">
                                    <div class="link-info">
                                        <strong>
                                            <span class="order-badge">#<?php echo $link['ordem']; ?></span>
                                            <?php echo htmlspecialchars($link['texto']); ?>
                                            <?php if (!$link['ativo']): ?>
                                                <small style="color: var(--color-danger);">(Inativo)</small>
                                            <?php endif; ?>
                                        </strong>
                                        <small><?php echo htmlspecialchars($link['link']); ?></small>
                                    </div>
                                    <div class="link-actions">
                                        <button class="btn-icon" onclick="editarLink(<?php echo $link['id']; ?>)" title="Editar">
                                            <span class="material-symbols-sharp">edit</span>
                                        </button>
                                        <button class="btn-icon" onclick="toggleAtivo(<?php echo $link['id']; ?>, <?php echo $link['ativo'] ? 0 : 1; ?>)" title="<?php echo $link['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                            <span class="material-symbols-sharp"><?php echo $link['ativo'] ? 'visibility_off' : 'visibility'; ?></span>
                                        </button>
                                        <button class="btn-icon delete" onclick="excluirLink(<?php echo $link['id']; ?>)" title="Excluir">
                                            <span class="material-symbols-sharp">delete</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

    <!-- Modal Adicionar/Editar -->
    <div class="modal" id="linkModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Adicionar Link</h3>
                <button class="close-modal" onclick="fecharModal()">�-</button>
            </div>
            <form id="linkForm">
                <input type="hidden" id="linkId" name="id">
                <input type="hidden" id="linkAction" name="action" value="create">
                
                <div class="form-group">
                    <label>Coluna</label>
                    <select id="linkColuna" name="coluna" required>
                        <option value="produtos">Produtos</option>
                        <option value="atendimento">Atendimento</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Texto do Link</label>
                    <input type="text" id="linkTexto" name="texto" placeholder="Ex: Política de Troca" required>
                </div>
                
                <div class="form-group">
                    <label>URL/Link</label>
                    <input type="text" id="linkUrl" name="link" placeholder="Ex: #politica-troca ou /pagina" required>
                </div>
                
                <div class="form-group">
                    <label>Ordem de Exibição</label>
                    <input type="number" id="linkOrdem" name="ordem" min="0" value="0" required>
                    <small style="color: var(--color-dark-variant);">Número menor aparece primeiro</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="linkAtivo" name="ativo" value="1" checked>
                        Link ativo (visível no site)
                    </label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-save">
                        <span class="material-symbols-sharp">save</span>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Message -->
    <div class="success-message" id="successMessage">
        <span class="material-symbols-sharp">check_circle</span>
        <span id="successText">Operação realizada com sucesso!</span>
    </div>

    <script>
        // Dados dos links em JSON
        const linksData = <?php echo json_encode($links); ?>;
        
        // Abrir modal para novo link
        function abrirModalNovo(coluna) {
            document.getElementById('modalTitle').textContent = 'Adicionar Link';
            document.getElementById('linkForm').reset();
            document.getElementById('linkId').value = '';
            document.getElementById('linkAction').value = 'create';
            document.getElementById('linkColuna').value = coluna;
            document.getElementById('linkAtivo').checked = true;
            document.getElementById('linkModal').classList.add('show');
        }
        
        // Editar link
        function editarLink(id) {
            const link = linksData.find(l => l.id == id);
            if (!link) return;
            
            document.getElementById('modalTitle').textContent = 'Editar Link';
            document.getElementById('linkId').value = link.id;
            document.getElementById('linkAction').value = 'update';
            document.getElementById('linkColuna').value = link.coluna;
            document.getElementById('linkTexto').value = link.texto;
            document.getElementById('linkUrl').value = link.link;
            document.getElementById('linkOrdem').value = link.ordem;
            document.getElementById('linkAtivo').checked = link.ativo == 1;
            document.getElementById('linkModal').classList.add('show');
        }
        
        // Fechar modal
        function fecharModal() {
            document.getElementById('linkModal').classList.remove('show');
        }
        
        // Toggle ativo/inativo
        async function toggleAtivo(id, novoStatus) {
            if (!confirm(`Tem certeza que deseja ${novoStatus ? 'ativar' : 'desativar'} este link?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_active');
                formData.append('id', id);
                formData.append('ativo', novoStatus);
                
                const response = await fetch('footer-links-api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    mostrarSucesso(novoStatus ? 'Link ativado com sucesso!' : 'Link desativado com sucesso!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao atualizar link');
            }
        }
        
        // Excluir link
        async function excluirLink(id) {
            if (!confirm('Tem certeza que deseja EXCLUIR este link permanentemente?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                const response = await fetch('footer-links-api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    mostrarSucesso('Link excluído com sucesso!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir link');
            }
        }
        
        // Submit do formulário
        document.getElementById('linkForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('footer-links-api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const action = formData.get('action');
                    mostrarSucesso(action === 'create' ? 'Link adicionado com sucesso!' : 'Link atualizado com sucesso!');
                    fecharModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar link');
            }
        });
        
        // Mostrar mensagem de sucesso
        function mostrarSucesso(mensagem) {
            const successMsg = document.getElementById('successMessage');
            document.getElementById('successText').textContent = mensagem;
            successMsg.classList.add('show');
            setTimeout(() => {
                successMsg.classList.remove('show');
            }, 3000);
        }
        
        // Theme toggler
        document.addEventListener('DOMContentLoaded', function() {
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
        
        // Fechar modal ao clicar fora
        document.getElementById('linkModal').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
    </script>
</body>
</html>

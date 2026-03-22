<?php
session_start();
require_once '../sistema.php';
// Verificar se estÃ¡ logado
if (!isset($_SESSION['usuario_logado'])) {
    header('Location: ../../../PHP/login.php');
    exit();
}

// Incluir contador de mensagens
require_once 'helper-contador.php';

// Incluir sistema de logs automÃ¡tico
require_once '../auto_log.php';

// Declarar conexÃ£o global
global $conexao;

// Processar aÃ§Ãµes CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_usuario'])) {
        $nome = mysqli_real_escape_string($conexao, trim($_POST['nome']));
        $email = mysqli_real_escape_string($conexao, trim($_POST['email']));
        $senha = password_hash(trim($_POST['senha']), PASSWORD_DEFAULT);
        $data_nascimento = mysqli_real_escape_string($conexao, trim($_POST['data_nascimento']));
        
        $sql = "INSERT INTO adm_rare (nome, email, senha, data_nascimento) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $nome, $email, $senha, $data_nascimento);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['mensagem'] = 'UsuÃ¡rio criado com sucesso!';
        } else {
            $_SESSION['mensagem'] = 'Erro ao criar usuÃ¡rio.';
        }
        
        // Redirect para evitar resubmissÃ£o
        header('Location: settings.php');
        exit();}

// Incluir contador de mensagens
require_once 'helper-contador.php';
    
    if (isset($_POST['edit_usuario'])) {
        $id = (int)$_POST['id'];
        $nome = mysqli_real_escape_string($conexao, trim($_POST['nome']));
        $email = mysqli_real_escape_string($conexao, trim($_POST['email']));
        $data_nascimento = mysqli_real_escape_string($conexao, trim($_POST['data_nascimento']));
        
        if (!empty($_POST['senha'])) {
            $senha = password_hash(trim($_POST['senha']), PASSWORD_DEFAULT);
            $sql = "UPDATE adm_rare SET nome = ?, email = ?, senha = ?, data_nascimento = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "ssssi", $nome, $email, $senha, $data_nascimento, $id);
        } else {
            $sql = "UPDATE adm_rare SET nome = ?, email = ?, data_nascimento = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $nome, $email, $data_nascimento, $id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['mensagem'] = 'UsuÃ¡rio atualizado com sucesso!';
        } else {
            $_SESSION['mensagem'] = 'Erro ao atualizar usuÃ¡rio.';
        }
        
        // Redirect para evitar resubmissÃ£o
        header('Location: settings.php');
        exit();}

// Incluir contador de mensagens
require_once 'helper-contador.php';
    
    if (isset($_POST['delete_usuario'])) {
        $id = (int)$_POST['id'];
        
        // NÃ£o permitir que o usuÃ¡rio delete a si mesmo
        if ($id != $_SESSION['usuario_id']) {
            $sql = "DELETE FROM adm_rare WHERE id = ?";
            $stmt = mysqli_prepare($conexao, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['mensagem'] = 'UsuÃ¡rio removido com sucesso!';
            } else {
                $_SESSION['mensagem'] = 'Erro ao remover usuÃ¡rio.';
            }
        } else {
            $_SESSION['mensagem'] = 'VocÃª nÃ£o pode excluir sua prÃ³pria conta!';
        }
        
        // Redirect para evitar resubmissÃ£o
        header('Location: settings.php');
        exit();}

// Incluir contador de mensagens
require_once 'helper-contador.php';
}

// Buscar usuÃ¡rios
$sql = 'SELECT * FROM adm_rare ORDER BY id DESC';
$usuarios = mysqli_query($conexao, $sql);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp" rel="stylesheet" />
    <title>ConfiguraÃ§Ãµes - Admin Panel</title>
    <style>
      /* Estilos para o sistema CRUD */
      .config-section {
        background: var(--color-white);
        padding: var(--card-padding);
        border-radius: var(--card-border-radius);
        margin-top: 1rem;
        box-shadow: var(--box-shadow);
        transition: all 300ms ease;
      }
      
      .btn {
        padding: 0.6rem 1.2rem;
        border-radius: var(--border-radius-1);
        cursor: pointer;
        font-size: 0.8rem;
        text-align: center;
        display: inline-block;
        margin: 0.2rem;
        transition: all 300ms ease;
        text-decoration: none;
      }
      
      .btn-success {
        background: linear-gradient(135deg, var(--color-success), #2dd4bf);
        color: var(--color-white);
        border: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(65, 241, 182, 0.3);
        transform: translateY(0);
      }
      
      .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(65, 241, 182, 0.4);
        opacity: 1;
      }
      
      .btn-success .material-symbols-sharp {
        font-size: 1.2rem;
        font-weight: bold;
      }
      
      .btn-primary {
        background: var(--color-primary);
        color: var(--color-white);
      }
      
      .btn-danger {
        background: var(--color-danger);
        color: var(--color-white);
      }
      
      .btn-outline-primary {
        border: 1px solid var(--color-primary);
        background: transparent;
        color: var(--color-primary);
      }
      
      .btn-outline-danger {
        border: 1px solid var(--color-danger);
        background: transparent;
        color: var(--color-danger);
      }
      
      .btn:hover {
        opacity: 0.8;
      }
      
      .table {
        width: 100%;
        margin-top: 1rem;
        border-collapse: collapse;
      }
      
      .table th,
      .table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--color-light);
      }
      
      .table th {
        background: var(--color-dark);
        color: var(--color-white);
        font-weight: 500;
      }
      
      .table tbody tr:hover {
        background: var(--color-light);
      }
      
      .badge {
        background: var(--color-primary);
        color: var(--color-white);
        padding: 0.2rem 0.5rem;
        border-radius: var(--border-radius-1);
        font-size: 0.7rem;
      }
      
      .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: var(--border-radius-1);
        background: var(--color-success);
        color: var(--color-white);
      }
      
      /* Modal styles */
      .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
      }
      
      .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        max-width: 500px;
      }
      
      .modal-content {
        background: var(--color-white);
        border-radius: var(--card-border-radius);
        padding: 2rem;
      }
      
      .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
      }
      
      .modal-title {
        color: var(--color-dark);
        font-size: 1.2rem;
      }
      
      .btn-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--color-dark);
      }
      
      .form-label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--color-dark);
        font-weight: 500;
      }
      
      .form-control {
        width: 100%;
        padding: 0.7rem;
        border: 1px solid var(--color-light);
        border-radius: var(--border-radius-1);
        background: var(--color-white);
        color: var(--color-dark);
        margin-bottom: 1rem;
      }
      
      .form-control:focus {
        outline: none;
        border-color: var(--color-primary);
      }
      
      .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 1rem;
      }
      
      .btn-secondary {
        background: var(--color-info-dark);
        color: var(--color-white);
      }
      
      .text-center {
        text-align: center;
      }
      
      .text-muted {
        color: var(--color-info-dark);
      }
      
      .d-inline {
        display: inline;
      }
      
      .me-1 {
        margin-right: 0.25rem;
      }
      
      .ms-2 {
        margin-left: 0.5rem;
      }
      
      .mb-3 {
        margin-bottom: 1rem;
      }
      
      /* CSS simples para botÃµes */
      .btn-sm {
        padding: 8px 12px;
        font-size: 14px;
        border-radius: 4px;
        border: 1px solid;
        cursor: pointer;
        display: inline-block;
        margin: 2px;
      }
      
      .btn-outline-primary {
        border-color: var(--color-primary);
        color: var(--color-primary);
        background: transparent;
      }
      
      .btn-outline-danger {
        border-color: var(--color-danger);
        color: var(--color-danger);
        background: transparent;
      }
      
      /* Responsivo bÃ¡sico */
      @media (max-width: 768px) {
        .table th:first-child,
        .table td:first-child {
          display: none;
        }
        
        .btn-sm {
          padding: 10px;
          font-size: 16px;
        }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <aside>
        <div class="top">
          <div class="logo">
            <img src="../../../assets/images/logo_png.png" />
            <a href="index.php"><h2 class="danger">Rare7</h2></a>
          </div>
          <div class="close" id="close-btn">
            <span class="material-symbols-sharp">close</span>
          </div>
        </div>

        <div class="sidebar">
          <a href="index.php">
            <span class="material-symbols-sharp">grid_view</span>
            <h3>Painel</h3>
          </a>

          <a href="customers.php">
            <span class="material-symbols-sharp">group</span>
            <h3>Clientes</h3>
          </a>

          <a href="orders.php">
            <span class="material-symbols-sharp">Orders</span>
            <h3>Pedidos</h3>
          </a>



          <a href="analytics.php">
            <span class="material-symbols-sharp">Insights</span>
            <h3>GrÃ¡ficos</h3>
          </a>

          <a href="menssage.php">
            <span class="material-symbols-sharp">Mail</span>
            <h3>Mensagens</h3>
            <span class="message-count"><?= $nao_lidas; ?></span>
          </a>

          <a href="products.php">
            <span class="material-symbols-sharp">Inventory</span>
            <h3>Produtos</h3>
          </a>

          <a href="cupons.php">
            <span class="material-symbols-sharp">sell</span>
            <h3>Cupons</h3>
          </a>

          <a href="gestao-fluxo.php">
            <span class="material-symbols-sharp">account_tree</span>
            <h3>GestÃ£o de Fluxo</h3>
          </a>

          <div class="menu-item-container">
            <a href="cms/home.php" class="menu-item-with-submenu">
              <span class="material-symbols-sharp">web</span>
              <h3>CMS</h3>
            </a>
            
            <div class="submenu">
              <a href="cms/home.php">
                <span class="material-symbols-sharp">home</span>
                <h3>Home (Textos)</h3>
              </a>
              <a href="cms/banners.php">
                <span class="material-symbols-sharp">view_carousel</span>
                <h3>Banners</h3>
              </a>
              <a href="cms/featured.php">
                <span class="material-symbols-sharp">star</span>
                <h3>LanÃ§amentos</h3>
              </a>
              <a href="cms/promos.php">
                <span class="material-symbols-sharp">local_offer</span>
                <h3>PromoÃ§Ãµes</h3>
              </a>
              <a href="cms/testimonials.php">
                <span class="material-symbols-sharp">format_quote</span>
                <h3>Depoimentos</h3>
              </a>
              <a href="cms/metrics.php">
                <span class="material-symbols-sharp">speed</span>
                <h3>MÃ©tricas</h3>
              </a>
            </div>
          </div>

          <div class="menu-item-container">
            <a href="geral.php" class="panel menu-item-with-submenu">
              <span class="material-symbols-sharp">Settings</span>
              <h3>ConfiguraÃ§Ãµes</h3>
            </a>
            
            <div class="submenu">
              <a href="geral.php">
                <span class="material-symbols-sharp">tune</span>
                <h3>Geral</h3>
              </a>
              <a href="pagamentos.php">
                <span class="material-symbols-sharp">payments</span>
                <h3>Pagamentos</h3>
              </a>
              <a href="frete.php">
                <span class="material-symbols-sharp">local_shipping</span>
                <h3>Frete</h3>
              </a>
              <a href="automacao.php">
                <span class="material-symbols-sharp">automation</span>
                <h3>AutomaÃ§Ã£o</h3>
              </a>
              <a href="metricas.php">
                <span class="material-symbols-sharp">analytics</span>
                <h3>MÃ©tricas</h3>
              </a>
              <a href="settings.php">
                <span class="material-symbols-sharp">group</span>
                <h3>UsuÃ¡rios</h3>
              </a>
            </div>
          </div>

          <a href="revendedores.php">
            <span class="material-symbols-sharp">handshake</span>
            <h3>Revendedores</h3>
          </a>

          <a href="../../../PHP/logout.php">
            <span class="material-symbols-sharp">Logout</span>
            <h3>Sair</h3>
          </a>
        </div>
      </aside>

      <main>
        <h1>âš™ï¸ ConfiguraÃ§Ãµes do Sistema</h1>
        
        <!-- Mensagens de feedback -->
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="alert">
                <?= $_SESSION['mensagem']; ?>
            </div>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>

        <!-- SeÃ§Ã£o de Gerenciamento de UsuÃ¡rios -->
        <div class="config-section">
            <h2>ðŸ‘¥ Gerenciamento de UsuÃ¡rios Admin</h2>
            
            <!-- BotÃ£o para adicionar novo usuÃ¡rio -->
            <button class="btn btn-success mb-3" onclick="openModal('addUserModal')">
                <span class="material-symbols-sharp">person_add</span>
                <span>Novo UsuÃ¡rio</span>
            </button>

            <!-- Tabela de usuÃ¡rios -->
            <div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Data Nasc.</th>
                            <th>AÃ§Ãµes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($usuarios) > 0): ?>
                            <?php while($usuario = mysqli_fetch_assoc($usuarios)): ?>
                                <tr>
                                    <td><?= $usuario['id']; ?></td>
                                    <td>
                                        <?= $usuario['nome']; ?>
                                        <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                            <span class="badge bg-primary ms-2">VocÃª</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $usuario['email']; ?></td>
                                    <td><?= date('d/m/Y', strtotime($usuario['data_nascimento'] ?? '1970-01-01')); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                onclick="editUser(<?= $usuario['id']; ?>, '<?= addslashes($usuario['nome']); ?>', '<?= $usuario['email']; ?>', '<?= $usuario['data_nascimento']; ?>')">
                                            <span class="material-symbols-sharp">edit</span>
                                        </button>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este usuÃ¡rio?')">
                                                <input type="hidden" name="id" value="<?= $usuario['id']; ?>">
                                                <button type="submit" name="delete_usuario" class="btn btn-sm btn-outline-danger">
                                                    <span class="material-symbols-sharp">delete</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">NÃ£o Ã© possÃ­vel excluir</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Nenhum usuÃ¡rio encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
      </main>

      <div class="right">
        <div class="top">
          <button id="menu-btn">
            <span class="material-symbols-sharp">menu</span>
          </button>
          <div class="theme-toggler">
            <span class="material-symbols-sharp active">wb_sunny</span>
            <span class="material-symbols-sharp">bedtime</span>
          </div>
          <div class="profile">
            <div class="info">
              <p>OlÃ¡, <b><?= isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'UsuÃ¡rio'; ?></b></p>
              <small class="text-muted">Admin</small>
            </div>
            <div class="profile-photo">
              <img src="../../../assets/images/logo_png.png" alt="" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para Adicionar UsuÃ¡rio -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo UsuÃ¡rio</h5>
                    <button type="button" class="btn-close" onclick="closeModal('addUserModal')">Ã—</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" name="data_nascimento" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancelar</button>
                        <button type="submit" name="create_usuario" class="btn btn-success">Criar UsuÃ¡rio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar UsuÃ¡rio -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar UsuÃ¡rio</h5>
                    <button type="button" class="btn-close" onclick="closeModal('editUserModal')">Ã—</button>
                </div>
                <form method="POST" id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" id="editUserNome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="editUserEmail" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nova Senha (deixe vazio para manter atual)</label>
                            <input type="password" name="senha" class="form-control" minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" name="data_nascimento" id="editUserData" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancelar</button>
                        <button type="submit" name="edit_usuario" class="btn btn-primary">Salvar AlteraÃ§Ãµes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../js/dashboard.js"></script>
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editUser(id, nome, email, data) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUserNome').value = nome;
            document.getElementById('editUserEmail').value = email;
            document.getElementById('editUserData').value = data || '';
            
            openModal('editUserModal');
        }
        
        // Fechar modal clicando fora dele
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
  </body>
</html>










<?php
session_start();
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

// Verificar se está logado
if (!isset($_SESSION['cliente'])) {
    header('Location: login.php');
    exit;
}

$nomeUsuario = htmlspecialchars($_SESSION['cliente']['nome']);
$emailUsuario = htmlspecialchars($_SESSION['cliente']['email']);
$clienteId = $_SESSION['cliente']['id'];
$usuarioLogado = true;

// Buscar configuração de frete grátis
$freteGratisValor = getFreteGratisThreshold($pdo);

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

$sucesso = '';
$erro = '';

function normalizeTab(?string $tab): string {
    $validTabs = ['pedidos', 'conta', 'enderecos', 'seguranca'];
    return in_array($tab, $validTabs, true) ? $tab : 'pedidos';
}

$activeTab = normalizeTab($_GET['tab'] ?? 'pedidos');

if (isset($_SESSION['flash_sucesso'])) {
    $sucesso = $_SESSION['flash_sucesso'];
    unset($_SESSION['flash_sucesso']);
}

if (isset($_SESSION['flash_erro'])) {
    $erro = $_SESSION['flash_erro'];
    unset($_SESSION['flash_erro']);
}

function redirectWithFlash(string $tipo, string $mensagem, string $tab = 'pedidos'): void {
    $tab = normalizeTab($tab);
    $_SESSION[$tipo] = $mensagem;
    header('Location: minha-conta.php?tab=' . urlencode($tab));
    exit;
}

function ensureEnderecoTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS cliente_enderecos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        tipo VARCHAR(50) NOT NULL DEFAULT 'Casa',
        rua VARCHAR(255) NOT NULL,
        numero VARCHAR(20) NOT NULL,
        complemento VARCHAR(255) NULL,
        bairro VARCHAR(100) NOT NULL,
        cidade VARCHAR(100) NOT NULL,
        uf VARCHAR(2) NOT NULL,
        cep VARCHAR(10) NOT NULL,
        principal TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cliente_enderecos_cliente_id (cliente_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Erro ao verificar coluna ' . $table . '.' . $column . ': ' . $e->getMessage());
        return false;
    }
}

ensureEnderecoTable($pdo);

// Processar atualização de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_dados'])) {
    $formTab = normalizeTab($_POST['active_tab'] ?? 'conta');
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    
    // Validações básicas
    if (empty($nome)) {
        redirectWithFlash('flash_erro', 'O nome é obrigatório.', $formTab);
    } elseif (empty($telefone)) {
        redirectWithFlash('flash_erro', 'O telefone é obrigatório.', $formTab);
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE clientes 
                SET nome = ?, telefone = ?, whatsapp = ?,
                    data_ultima_atualizacao = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $nome, $telefone, $whatsapp,
                $clienteId
            ]);
            
            // Atualizar session
            $_SESSION['cliente']['nome'] = $nome;
            $nomeUsuario = htmlspecialchars($nome);
            
            redirectWithFlash('flash_sucesso', 'Dados atualizados com sucesso!', $formTab);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar cliente: " . $e->getMessage());
            redirectWithFlash('flash_erro', 'Erro ao atualizar dados. Tente novamente.', $formTab);
        }
    }
}

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    $formTab = normalizeTab($_POST['active_tab'] ?? 'seguranca');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $senha_nova = $_POST['senha_nova'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';
    
    if (empty($senha_atual) || empty($senha_nova) || empty($senha_confirmar)) {
        redirectWithFlash('flash_erro', 'Todos os campos de senha são obrigatórios.', $formTab);
    } elseif ($senha_nova !== $senha_confirmar) {
        redirectWithFlash('flash_erro', 'A nova senha e a confirmação não coincidem.', $formTab);
    } elseif (strlen($senha_nova) < 6) {
        redirectWithFlash('flash_erro', 'A nova senha deve ter pelo menos 6 caracteres.', $formTab);
    } else {
        try {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT senha FROM clientes WHERE id = ?");
            $stmt->execute([$clienteId]);
            $cliente_senha = $stmt->fetch();
            
            if ($cliente_senha && password_verify($senha_atual, $cliente_senha['senha'])) {
                // Atualizar senha
                $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE clientes SET senha = ? WHERE id = ?");
                $stmt->execute([$senha_hash, $clienteId]);
                
                redirectWithFlash('flash_sucesso', 'Senha alterada com sucesso!', $formTab);
            } else {
                redirectWithFlash('flash_erro', 'Senha atual incorreta.', $formTab);
            }
        } catch (PDOException $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            redirectWithFlash('flash_erro', 'Erro ao alterar senha. Tente novamente.', $formTab);
        }
    }
}

// Processar ações de endereço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address_action'])) {
    $formTab = normalizeTab($_POST['active_tab'] ?? 'enderecos');
    $action = trim((string) ($_POST['address_action'] ?? ''));

    if ($action === 'remove') {
        $enderecoId = (int) ($_POST['endereco_id'] ?? 0);

        if ($enderecoId <= 0) {
            redirectWithFlash('flash_erro', 'Endereco invalido para remocao.', $formTab);
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM cliente_enderecos WHERE id = ? AND cliente_id = ?");
            $stmt->execute([$enderecoId, $clienteId]);
            redirectWithFlash('flash_sucesso', 'Endereco removido com sucesso!', $formTab);
        } catch (PDOException $e) {
            error_log('Erro ao remover endereco: ' . $e->getMessage());
            redirectWithFlash('flash_erro', 'Erro ao remover endereco. Tente novamente.', $formTab);
        }
    }

    if ($action === 'save') {
        $enderecoId = (int) ($_POST['endereco_id'] ?? 0);
        $tipo = trim((string) ($_POST['tipo'] ?? 'Casa'));
        $rua = trim((string) ($_POST['rua'] ?? ''));
        $numero = trim((string) ($_POST['numero'] ?? ''));
        $complemento = trim((string) ($_POST['complemento'] ?? ''));
        $bairro = trim((string) ($_POST['bairro'] ?? ''));
        $cidade = trim((string) ($_POST['cidade'] ?? ''));
        $uf = strtoupper(trim((string) ($_POST['uf'] ?? '')));
        $cep = trim((string) ($_POST['cep'] ?? ''));

        if ($tipo === '' || $rua === '' || $numero === '' || $bairro === '' || $cidade === '' || $uf === '' || $cep === '') {
            redirectWithFlash('flash_erro', 'Preencha todos os campos obrigatorios do endereco.', $formTab);
        }

        try {
            if ($enderecoId > 0) {
                $stmt = $pdo->prepare("UPDATE cliente_enderecos
                    SET tipo = ?, rua = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, uf = ?, cep = ?
                    WHERE id = ? AND cliente_id = ?");
                $stmt->execute([$tipo, $rua, $numero, $complemento, $bairro, $cidade, $uf, $cep, $enderecoId, $clienteId]);
                redirectWithFlash('flash_sucesso', 'Endereco atualizado com sucesso!', $formTab);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cliente_enderecos
                    (cliente_id, tipo, rua, numero, complemento, bairro, cidade, uf, cep)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$clienteId, $tipo, $rua, $numero, $complemento, $bairro, $cidade, $uf, $cep]);
                redirectWithFlash('flash_sucesso', 'Endereco adicionado com sucesso!', $formTab);
            }
        } catch (PDOException $e) {
            error_log('Erro ao salvar endereco: ' . $e->getMessage());
            redirectWithFlash('flash_erro', 'Erro ao salvar endereco. Tente novamente.', $formTab);
        }
    }
}

// Buscar dados do cliente
try {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? LIMIT 1");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar cliente: " . $e->getMessage());
    $erro = "Erro ao carregar dados.";
}

// Buscar enderecos do cliente
$enderecos = [];
try {
    $stmt = $pdo->prepare("SELECT id, tipo, rua, numero, complemento, bairro, cidade, uf, cep
        FROM cliente_enderecos
        WHERE cliente_id = ?
        ORDER BY principal DESC, id DESC");
    $stmt->execute([$clienteId]);
    $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Migra o endereco principal do cadastro para a tabela de enderecos caso ainda nao exista nenhum.
    if (count($enderecos) === 0 && !empty($cliente['rua']) && !empty($cliente['numero']) && !empty($cliente['bairro']) && !empty($cliente['cidade']) && (!empty($cliente['uf']) || !empty($cliente['estado'])) && !empty($cliente['cep'])) {
        $stmt = $pdo->prepare("INSERT INTO cliente_enderecos
            (cliente_id, tipo, rua, numero, complemento, bairro, cidade, uf, cep, principal)
            VALUES (?, 'Casa', ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $clienteId,
            (string) $cliente['rua'],
            (string) $cliente['numero'],
            (string) ($cliente['complemento'] ?? ''),
            (string) $cliente['bairro'],
            (string) $cliente['cidade'],
            (string) (!empty($cliente['uf']) ? $cliente['uf'] : ($cliente['estado'] ?? '')),
            (string) $cliente['cep']
        ]);

        $stmt = $pdo->prepare("SELECT id, tipo, rua, numero, complemento, bairro, cidade, uf, cep
            FROM cliente_enderecos
            WHERE cliente_id = ?
            ORDER BY principal DESC, id DESC");
        $stmt->execute([$clienteId]);
        $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (PDOException $e) {
    error_log('Erro ao buscar enderecos: ' . $e->getMessage());
}

// Buscar pedidos do cliente para o resumo e cards
$pedidos = [];
$pedidos_info = ['total_pedidos' => 0, 'pedidos_entregues' => 0, 'pedidos_transporte' => 0];
try {
    $temCodigoRastreio = tableHasColumn($pdo, 'pedidos', 'codigo_rastreio');
    $temLinkRastreio = tableHasColumn($pdo, 'pedidos', 'link_rastreio');
    $temObservacoes = tableHasColumn($pdo, 'pedidos', 'observacoes');
    $temFormaPagamento = tableHasColumn($pdo, 'pedidos', 'forma_pagamento');

    $colunasPedido = [
        'id',
        'valor_total',
        'status',
        'data_pedido',
        $temObservacoes ? 'observacoes' : "'' AS observacoes",
        $temCodigoRastreio ? 'codigo_rastreio' : "'' AS codigo_rastreio",
        $temLinkRastreio ? 'link_rastreio' : "'' AS link_rastreio",
        $temFormaPagamento ? 'forma_pagamento' : "'' AS forma_pagamento"
    ];

    $queryPedidos = 'SELECT ' . implode(', ', $colunasPedido) . ' FROM pedidos WHERE cliente_id = ? ORDER BY data_pedido DESC LIMIT 20';
    $stmt = $pdo->prepare($queryPedidos);
    $stmt->execute([$clienteId]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (!empty($pedidos)) {
        $idsPedidos = array_map(static function (array $pedido): int {
            return (int) $pedido['id'];
        }, $pedidos);

        $placeholders = implode(',', array_fill(0, count($idsPedidos), '?'));
        $stmtItens = $pdo->prepare("
            SELECT
                ip.pedido_id,
                ip.quantidade,
                    ip.preco_unitario,
                    COALESCE(ip.nome_produto, pr.nome, 'Produto') AS nome_produto,
                    COALESCE(pr.imagem_principal, '') AS imagem_produto
            FROM itens_pedido ip
            LEFT JOIN produtos pr ON pr.id = ip.produto_id
            WHERE ip.pedido_id IN ($placeholders)
            ORDER BY ip.id ASC
        ");
        $stmtItens->execute($idsPedidos);
        $itensPedidos = $stmtItens->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $itensPorPedido = [];
        foreach ($itensPedidos as $item) {
            $pedidoIdItem = (int) ($item['pedido_id'] ?? 0);
            if (!isset($itensPorPedido[$pedidoIdItem])) {
                $itensPorPedido[$pedidoIdItem] = [];
            }

            $nomeItem = trim((string) ($item['nome_produto'] ?? 'Produto'));
            $quantidadeItem = max(1, (int) ($item['quantidade'] ?? 1));
            $precoUnitarioItem = (float) ($item['preco_unitario'] ?? 0);
            $imagemItem = trim((string) ($item['imagem_produto'] ?? ''));

            $itensPorPedido[$pedidoIdItem][] = [
                'nome' => $nomeItem,
                'quantidade' => $quantidadeItem,
                'preco_unitario' => $precoUnitarioItem,
                'imagem' => $imagemItem
            ];
        }

        foreach ($pedidos as &$pedido) {
            $pedidoIdAtual = (int) ($pedido['id'] ?? 0);
            $pedido['itens'] = $itensPorPedido[$pedidoIdAtual] ?? [];

            $statusAtual = strtolower(trim((string) ($pedido['status'] ?? '')));
            if ($statusAtual === 'entregue') {
                $pedidos_info['pedidos_entregues']++;
            }
            if (in_array($statusAtual, ['enviado', 'em transporte', 'em_transporte'], true)) {
                $pedidos_info['pedidos_transporte']++;
            }
        }
        unset($pedido);
    }

    $pedidos_info['total_pedidos'] = count($pedidos);
} catch (PDOException $e) {
    error_log('Erro ao buscar pedidos: ' . $e->getMessage());
    $pedidos = [];
    $pedidos_info = ['total_pedidos' => 0, 'pedidos_entregues' => 0, 'pedidos_transporte' => 0];
}

$pageTitle = 'Minha Conta - RARE7';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Cliente Premium</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Cinzel:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Material Symbols para ícones -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <!-- Link para css/loja.css que contém a base -->
    <link rel="stylesheet" href="../css/loja.css">
    
    <style>
        :root {
            --bg: #0e0e0e;
            --blue: #0f1c2e;
            --gold: #c6a75e;
            --white: #ffffff;
            --gray: #bfc5cc;
            --card: rgba(255, 255, 255, 0.04);
            --line: rgba(255, 255, 255, 0.12);
            --radius-xl: 2rem;
            --radius-lg: 1.5rem;
        }

        body {
            background: var(--bg);
            color: var(--white);
            font-family: "Space Grotesk", -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* NAVBAR - Usando a navbar existente do cliente */
        .header-loja {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(198, 167, 94, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 12px 0;
        }

        .header-loja .container-header {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
            flex-shrink: 0;
        }

        .logo-dz-oficial {
            width: auto;
            height: 40px;
            object-fit: contain;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            color: #E6007E;
            letter-spacing: 0.04em;
        }

        .nav-loja {
            flex: 1;
            display: flex;
            justify-content: center;
            min-width: 0;
        }

        .nav-loja > ul {
            display: flex;
            align-items: center;
            gap: 10px;
            list-style: none;
            padding: 0;
            margin: 0;
            white-space: nowrap;
        }

        .nav-loja > ul > li {
            position: relative;
        }

        .nav-loja a {
            color: #2d3748;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.82rem;
            padding: 8px 10px;
            border-radius: 999px;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .nav-loja a:hover {
            background: rgba(230, 0, 126, 0.08);
            color: #E6007E;
        }

        .dropdown-icon {
            font-size: 0.68rem;
            transition: transform 0.25s ease;
        }

        .has-dropdown:hover .dropdown-icon {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 220px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.25s ease;
            z-index: 1005;
            padding: 10px 0;
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

        .dropdown-menu a {
            display: block;
            border-radius: 0;
            padding: 10px 14px;
            color: #2d3748;
            font-weight: 500;
            font-size: 0.88rem;
        }

        .dropdown-menu a:hover {
            background: rgba(230, 0, 126, 0.08);
            color: #E6007E;
            transform: translateX(3px);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .search-panel {
            width: 0;
            max-width: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .search-panel.active {
            width: 220px;
            max-width: 220px;
            opacity: 1;
        }

        .search-panel input {
            width: 100%;
            border: 1px solid rgba(230, 0, 126, 0.2);
            border-radius: 999px;
            padding: 10px 36px 10px 12px;
            color: #1f2937;
            background: #fff;
            font-size: 0.9rem;
            outline: none;
        }

        .search-panel input:focus {
            border-color: #E6007E;
            box-shadow: 0 0 0 3px rgba(230, 0, 126, 0.12);
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            position: relative;
        }

        /* Equalizar todos os 3 ícones (search, person, carrinho) */
        .nav-icon-link,
        .user-dropdown-btn,
        .nav-search-toggle {
            width: 2rem;
            height: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: transparent;
            color: #bfc5cc;
            padding: 0;
            text-decoration: none;
        }

        .btn-cart {
            border: none;
            border-radius: 999px;
            padding: 10px 14px;
            background: linear-gradient(135deg, #E6007E, #C4006A);
            color: #fff;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            position: relative;
            transition: all 0.25s ease;
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(230, 0, 126, 0.32);
        }

        .cart-count {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            color: #E6007E;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            min-width: 220px;
            background: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10000;
            overflow: visible;
        }

        .user-dropdown.active .user-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-greeting {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            color: #c6a75e;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-dropdown-menu a {
            display: block;
            padding: 10px 16px;
            color: #bfc5cc;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .user-dropdown-menu a:last-child {
            border-bottom: none;
            color: #ef4444;
        }

        .user-dropdown-menu a:hover {
            background: rgba(198, 167, 94, 0.1);
            color: #c6a75e;
        }

        .user-dropdown-menu a:last-child:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ff6b6b;
        }

        .mobile-menu-toggle {
            display: none;
        }

        .mobile-menu,
        .mobile-menu-overlay {
            display: none;
        }

        /* HERO / TOPO DA ÁREA DO CLIENTE */
        .client-area-hero {
            margin-top: 0;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, 
                rgba(198, 167, 94, 0.08) 0%, 
                rgba(15, 28, 46, 0.05) 50%,
                rgba(255, 255, 255, 0.02) 100%);
            border-bottom: 1px solid var(--line);
            position: relative;
            overflow: hidden;
        }

        .client-area-hero::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(198, 167, 94, 0.15), transparent 70%);
            border-radius: 50%;
            filter: blur(40px);
            pointer-events: none;
        }

        .hero-content-wrap {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-header {
            margin-bottom: 2.5rem;
        }

        .hero-kicker {
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--gold);
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            color: var(--white);
            max-width: 600px;
        }

        .hero-subtitle {
            color: var(--gray);
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 600px;
        }

        /* GRID DE RESUMOS */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .summary-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }

        .summary-card:hover {
            border-color: rgba(198, 167, 94, 0.4);
            background: rgba(255, 255, 255, 0.06);
            transform: translateY(-4px);
        }

        .summary-card-label {
            font-size: 0.85rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .summary-card-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: -0.01em;
        }

        .summary-card-desc {
            font-size: 0.8rem;
            color: rgba(191, 197, 204, 0.7);
            margin-top: 0.5rem;
        }

        /* LAYOUT PRINCIPAL - 2 COLUNAS */
        .account-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }

        /* SIDEBAR - PERFIL E MENU */
        .account-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 110px;
        }

        .profile-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            border-color: rgba(198, 167, 94, 0.4);
            background: rgba(255, 255, 255, 0.06);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), rgba(198, 167, 94, 0.6));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 1rem;
            box-shadow: 0 0 20px rgba(198, 167, 94, 0.3);
        }

        .profile-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 0.3rem;
        }

        .profile-email {
            font-size: 0.85rem;
            color: var(--gray);
            word-break: break-word;
        }

        .menu-tabs {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: var(--radius-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }

        .menu-tab {
            flex: 1;
            padding: 1rem 1.1rem;
            background: transparent;
            border: none;
            border-left: 3px solid transparent;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.98rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            text-align: left;
            letter-spacing: 0.01em;
        }

        .menu-tab + .menu-tab {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .menu-tab span.material-symbols-sharp {
            font-size: 1.05rem;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: rgba(191, 197, 204, 0.95);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.25s ease;
        }

        .menu-tab:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--white);
            transform: translateX(2px);
        }

        .menu-tab:hover span.material-symbols-sharp {
            border-color: rgba(198, 167, 94, 0.35);
            color: var(--gold);
            background: rgba(198, 167, 94, 0.1);
        }

        .menu-tab.active {
            background: linear-gradient(90deg, rgba(198, 167, 94, 0.2), rgba(198, 167, 94, 0.06));
            border-left-color: var(--gold);
            color: var(--gold);
            box-shadow: inset 0 1px 0 rgba(198, 167, 94, 0.18);
        }

        .menu-tab.active span.material-symbols-sharp {
            color: var(--gold);
            background: rgba(198, 167, 94, 0.18);
            border-color: rgba(198, 167, 94, 0.45);
        }

        .menu-tab.active::before {
            content: '';
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 5px;
            background: var(--gold);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(198, 167, 94, 0.8);
        }

        /* CONTEÚDO PRINCIPAL */
        .account-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .tab-content {
            opacity: 0;
            visibility: hidden;
            position: absolute;
            pointer-events: none;
            transform: translateY(10px);
            transition: all 0.35s ease;
        }

        .tab-content.active {
            opacity: 1;
            visibility: visible;
            position: relative;
            pointer-events: auto;
            transform: translateY(0);
        }

        .content-section {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            padding: 2rem;
        }

        .section-header {
            margin-bottom: 2rem;
        }

        .section-kicker {
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--gold);
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
            letter-spacing: -0.01em;
        }

        /* FORMULÁRIOS */
        .form-grid {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            display: grid;
            gap: 0.5rem;
        }

        .form-group label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            min-height: 58px;
            color: var(--white);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(191, 197, 204, 0.4);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(198, 167, 94, 0.15);
        }

        .form-group input:disabled,
        .form-group select:disabled {
            background: rgba(255, 255, 255, 0.02);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .form-group small {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.3rem;
        }

        /* GRID DO FORMULÁRIO */
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        /* ALERTS */
        .alert {
            padding: 1rem 1.2rem;
            border-radius: 0.75rem;
            border-left: 4px solid;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            animation: slideDown 0.35s ease;
        }

        .alert.is-hiding {
            opacity: 0;
            transform: translateY(-8px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-left-color: #10b981;
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
            color: #ef4444;
        }

        .alert span.material-symbols-sharp {
            font-size: 1.3rem;
        }

        /* BUTTONS */
        .btn-primary {
            background: linear-gradient(135deg, var(--gold), rgba(198, 167, 94, 0.8));
            color: #161616;
            border: none;
            border-radius: 0.75rem;
            padding: 0.95rem 1.8rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            letter-spacing: 0.05em;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(198, 167, 94, 0.35);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--line);
            color: var(--gray);
            border-radius: 0.75rem;
            padding: 0.95rem 1.8rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            border-color: rgba(198, 167, 94, 0.5);
            color: var(--white);
            background: rgba(198, 167, 94, 0.05);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        /* LISTA DE PEDIDOS */
        .orders-list {
            display: grid;
            gap: 1.5rem;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }

        .order-card:hover {
            border-color: rgba(198, 167, 94, 0.4);
            background: rgba(255, 255, 255, 0.06);
            transform: translateY(-2px);
        }

        .order-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: start;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .order-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gold);
        }

        .order-date {
            font-size: 0.9rem;
            color: var(--gray);
        }

        .order-status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-delivered {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .status-in-transit {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .status-processing {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }

        .order-items {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin: 1rem 0;
        }

        .item-pill {
            background: rgba(198, 167, 94, 0.15);
            border: 1px solid rgba(198, 167, 94, 0.3);
            color: var(--gold);
            padding: 0.4rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.85rem;
        }

        .order-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--gold);
            margin-top: 1rem;
        }

        .order-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .btn-sm {
            padding: 0.6rem 1.2rem;
            font-size: 0.85rem;
            border-radius: 0.5rem;
        }

        .order-modal {
            position: fixed;
            inset: 0;
            z-index: 1190;
            display: grid;
            place-items: center;
            background: rgba(7, 8, 10, 0.72);
            backdrop-filter: blur(6px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            padding: 1.2rem;
        }

        .order-modal.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .order-modal-dialog {
            width: min(620px, 96vw);
            max-height: 90vh;
            overflow-y: auto;
            background: linear-gradient(180deg, rgba(23, 23, 25, 0.98), rgba(15, 15, 16, 0.98));
            border: 1px solid rgba(198, 167, 94, 0.35);
            border-radius: 1.1rem;
            padding: 1.5rem;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55);
            transform: translateY(12px) scale(0.98);
            transition: transform 0.25s ease;
        }

        .order-modal.active .order-modal-dialog {
            transform: translateY(0) scale(1);
        }

        .order-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .order-modal-title {
            margin: 0;
            font-size: 1.15rem;
            color: var(--white);
        }

        .order-modal-close {
            border: 1px solid var(--line);
            background: transparent;
            color: var(--gray);
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .order-modal-close:hover {
            border-color: rgba(198, 167, 94, 0.55);
            color: var(--white);
            background: rgba(198, 167, 94, 0.1);
        }

        .order-modal-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .order-modal-chip {
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.03);
            padding: 0.8rem 0.9rem;
        }

        .order-modal-chip-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }

        .order-modal-chip-value {
            color: var(--white);
            font-weight: 600;
        }

        .order-modal-products {
            margin: 1rem 0;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 1rem 0;
        }

        .order-modal-extra {
            display: grid;
            gap: 0.75rem;
            margin: 0.9rem 0 1rem;
        }

        .order-modal-extra-item {
            border: 1px solid rgba(198, 167, 94, 0.2);
            border-radius: 0.7rem;
            background: rgba(255, 255, 255, 0.04);
            padding: 0.7rem 0.85rem;
        }

        .order-modal-extra-label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--gray);
            margin-bottom: 0.2rem;
        }

        .order-modal-extra-value {
            color: var(--white);
            line-height: 1.45;
            word-break: break-word;
        }

        .order-modal-track-link {
            margin-top: 0.45rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .order-modal-track-link:hover {
            text-decoration: underline;
        }

        .order-modal-products h4 {
            margin: 0 0 0.7rem;
            font-size: 0.92rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .order-modal-products-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.55rem;
        }

        .order-modal-products-list li {
            display: grid;
            grid-template-columns: 62px 1fr auto;
            align-items: center;
            gap: 0.7rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(198, 167, 94, 0.2);
            border-radius: 0.65rem;
            padding: 0.5rem 0.65rem;
        }

        .order-item-thumb {
            width: 62px;
            height: 62px;
            border-radius: 0.55rem;
            border: 1px solid rgba(198, 167, 94, 0.35);
            background: rgba(255, 255, 255, 0.04);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
        }

        .order-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .order-item-info {
            min-width: 0;
        }

        .order-item-name {
            color: var(--white);
            font-weight: 600;
            line-height: 1.35;
            margin-bottom: 0.2rem;
        }

        .order-item-meta {
            color: var(--gray);
            font-size: 0.82rem;
            letter-spacing: 0.02em;
        }

        .order-item-price {
            color: var(--gold);
            font-weight: 700;
            white-space: nowrap;
            font-size: 0.9rem;
        }

        .order-modal-footer {
            display: flex;
            justify-content: flex-end;
        }

        /* ENDEREÇOS */
        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .address-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .address-card:hover {
            border-color: rgba(198, 167, 94, 0.4);
            background: rgba(255, 255, 255, 0.06);
        }

        .address-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 0.8rem;
        }

        .address-text {
            font-size: 0.9rem;
            color: var(--gray);
            line-height: 1.6;
        }

        .address-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }

        .address-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            background: rgba(7, 8, 10, 0.72);
            backdrop-filter: blur(6px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            padding: 1.2rem;
        }

        .address-modal.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .address-modal-dialog {
            width: min(720px, 96vw);
            max-height: 90vh;
            overflow-y: auto;
            background: linear-gradient(180deg, rgba(23, 23, 25, 0.98), rgba(15, 15, 16, 0.98));
            border: 1px solid rgba(198, 167, 94, 0.35);
            border-radius: 1.1rem;
            padding: 1.5rem;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55);
            transform: translateY(12px) scale(0.98);
            transition: transform 0.25s ease;
        }

        .address-modal.active .address-modal-dialog {
            transform: translateY(0) scale(1);
        }

        .address-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }

        .address-modal-title {
            margin: 0;
            font-size: 1.15rem;
            color: var(--white);
            letter-spacing: 0.01em;
        }

        .address-modal-close {
            border: 1px solid var(--line);
            background: transparent;
            color: var(--gray);
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .address-modal-close:hover {
            border-color: rgba(198, 167, 94, 0.55);
            color: var(--white);
            background: rgba(198, 167, 94, 0.1);
        }

        .address-modal .form-grid {
            gap: 1rem;
        }

        .address-modal-error {
            display: none;
            border-left: 4px solid #ef4444;
            background: rgba(239, 68, 68, 0.12);
            color: #fecaca;
            border-radius: 0.65rem;
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
        }

        .address-modal-error.active {
            display: block;
        }

        .address-modal-actions {
            display: flex;
            gap: 0.8rem;
            justify-content: flex-end;
            margin-top: 1.2rem;
            flex-wrap: wrap;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 768px) {
            .header-loja .container-header {
                padding: 0 10px;
            }

            .logo-dz-oficial {
                height: 34px;
            }

            .logo-text {
                font-size: 1.2rem;
            }

            .nav-loja {
                display: none;
            }

            .mobile-menu-toggle {
                width: 42px;
                height: 42px;
                border: none;
                border-radius: 50%;
                background: rgba(230, 0, 126, 0.1);
                color: #E6007E;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }

            .mobile-menu-toggle .hamburger {
                width: 20px;
                height: 14px;
                display: grid;
                gap: 4px;
            }

            .mobile-menu-toggle .hamburger span {
                display: block;
                height: 2px;
                background: currentColor;
                border-radius: 2px;
            }

            .mobile-menu-overlay {
                position: fixed;
                inset: 0;
                z-index: 1000;
                background: rgba(0, 0, 0, 0.45);
                opacity: 0;
                visibility: hidden;
                transition: all 0.25s ease;
            }

            .mobile-menu-overlay.active {
                display: block;
                opacity: 1;
                visibility: visible;
            }

            .mobile-menu {
                position: fixed;
                top: 0;
                right: -100%;
                width: min(320px, 85vw);
                height: 100vh;
                padding: 88px 20px 20px;
                background: #fff;
                z-index: 1001;
                transition: right 0.3s ease;
            }

            .mobile-menu.active {
                display: block;
                right: 0;
            }

            .mobile-menu ul {
                list-style: none;
                display: grid;
                gap: 8px;
                margin: 0;
                padding: 0;
            }

            .mobile-menu a {
                display: block;
                padding: 12px 14px;
                border-radius: 10px;
                font-weight: 600;
                color: #2d3748;
                text-decoration: none;
            }

            .mobile-menu a:hover {
                background: rgba(230, 0, 126, 0.08);
                color: #E6007E;
            }

            .search-panel.active {
                width: 160px;
                max-width: 160px;
            }

            .btn-cart span:not(.cart-count) {
                display: none;
            }

            .account-wrapper {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .account-sidebar {
                position: static;
            }

            .form-row-2 {
                grid-template-columns: 1fr;
            }

            .order-modal-dialog {
                width: min(680px, 100%);
                max-height: 88vh;
                padding: 1.2rem;
            }

            .order-modal-summary {
                grid-template-columns: 1fr;
            }

            .order-modal-products-list li {
                grid-template-columns: 56px 1fr;
                gap: 0.6rem;
            }

            .order-item-thumb {
                width: 56px;
                height: 56px;
            }

            .order-item-price {
                grid-column: 1 / -1;
                font-size: 0.82rem;
                margin-top: 0.15rem;
            }

            .order-modal-footer .btn-secondary {
                width: 100%;
            }

            .address-modal-dialog {
                width: min(680px, 100%);
                max-height: 88vh;
                padding: 1.2rem;
            }

            .address-modal-actions {
                justify-content: stretch;
            }

            .address-modal-actions .btn-primary,
            .address-modal-actions .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .order-header {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .address-grid {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 1.5rem;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .client-area-hero {
                padding: 2rem 1rem;
                margin-top: 0;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR RARE7 -->
<header class="floating-navbar" id="floatingNavbar">
    <div class="nav-wrap container-shell">
        <a href="../index.php" class="nav-logo" aria-label="Rare7 - Inicio">
            <img src="../../image/logo_png.png" alt="Logo Rare7" class="nav-logo-mark" loading="lazy" onerror="if(!this.dataset.fallback){this.dataset.fallback='1';this.src='../assets/images/logo.png';}else{this.style.display='none';}">
            <span class="nav-logo-text">RARE7</span>
        </a>
        <nav>
            <ul class="nav-links">
                <li><a href="../produtos.php">Camisas</a></li>
                <li><a href="../produtos.php?menu=retro">Retro</a></li>
                <li><a href="../produtos.php?menu=clubes">Clubes</a></li>
                <li><a href="../produtos.php?menu=selecoes">Selecoes</a></li>
            </ul>
        </nav>
        <div class="nav-icons">
            <form class="nav-search" id="navSearchForm" action="../produtos.php" method="get" role="search">
                <input type="search" id="navSearchInput" name="busca" placeholder="Buscar camisa..." aria-label="Buscar produtos">
                <button type="button" class="nav-icon-link nav-search-toggle" id="navSearchToggle" aria-label="Abrir pesquisa">
                    <span class="material-symbols-sharp">search</span>
                </button>
            </form>
            <div class="user-dropdown">
                <button class="user-dropdown-btn" onclick="toggleUserDropdown(event)" aria-label="Menu de usuário" aria-expanded="false">
                    <span class="material-symbols-sharp">person</span>
                </button>
                <div class="user-dropdown-menu">
                    <div class="user-greeting">Olá, <?php echo isset($nomeUsuario) ? htmlspecialchars($nomeUsuario) : 'Cliente'; ?></div>
                    <a href="minha-conta.php">Minha conta</a>
                    <a href="minha-conta.php?tab=pedidos">Meus pedidos</a>
                    <a href="logout.php">Sair</a>
                </div>
            </div>
            <a href="carrinho.php" class="nav-icon-link" aria-label="Carrinho">
                <span class="material-symbols-sharp">shopping_bag</span>
            </a>
        </div>
    </div>
</header>

<!-- HERO / TOPO -->
<section class="client-area-hero">
    <div class="hero-content-wrap">
        <div class="hero-header">
            <div class="hero-kicker">Área do Cliente</div>
            <h1 class="hero-title">Gerencie seus pedidos com o padrão RARE7</h1>
            <p class="hero-subtitle">Sua central para acompanhar compras de camisas de times e seleções, atualizar seus dados e manter tudo organizado.</p>
        </div>

        <!-- RESUMOS -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-card-label">Pedidos Realizados</div>
                <div class="summary-card-value"><?php echo $pedidos_info['total_pedidos'] ?? 0; ?></div>
                <div class="summary-card-desc">no histórico</div>
            </div>

            <div class="summary-card">
                <div class="summary-card-label">Em Transporte</div>
                <div class="summary-card-value"><?php echo $pedidos_info['pedidos_transporte'] ?? 0; ?></div>
                <div class="summary-card-desc">a caminho do seu lar</div>
            </div>

            <div class="summary-card">
                <div class="summary-card-label">Entregues</div>
                <div class="summary-card-value"><?php echo $pedidos_info['pedidos_entregues'] ?? 0; ?></div>
                <div class="summary-card-desc">recebidos com sucesso</div>
            </div>
        </div>
    </div>
</section>

<!-- LAYOUT PRINCIPAL -->
<div class="account-wrapper">
    <!-- SIDEBAR -->
    <aside class="account-sidebar">
        <!-- PERFIL -->
        <div class="profile-card">
            <div class="profile-avatar">
                <?php echo substr($nomeUsuario, 0, 1); ?>
            </div>
            <div class="profile-name"><?php echo $nomeUsuario; ?></div>
            <div class="profile-email"><?php echo $emailUsuario; ?></div>
        </div>

        <!-- MENU DE ABAS -->
        <nav class="menu-tabs">
            <button class="menu-tab <?php echo $activeTab === 'pedidos' ? 'active' : ''; ?>" data-tab="pedidos">
                <span class="material-symbols-sharp">local_shipping</span>
                <span>Meus Pedidos</span>
            </button>
            <button class="menu-tab <?php echo $activeTab === 'conta' ? 'active' : ''; ?>" data-tab="conta">
                <span class="material-symbols-sharp">person</span>
                <span>Informações</span>
            </button>
            <button class="menu-tab <?php echo $activeTab === 'enderecos' ? 'active' : ''; ?>" data-tab="enderecos">
                <span class="material-symbols-sharp">location_on</span>
                <span>Endereços</span>
            </button>
            <button class="menu-tab <?php echo $activeTab === 'seguranca' ? 'active' : ''; ?>" data-tab="seguranca">
                <span class="material-symbols-sharp">lock</span>
                <span>Segurança</span>
            </button>
        </nav>
    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="account-content">
        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                <span class="material-symbols-sharp">check_circle</span>
                <span><?php echo $sucesso; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-error">
                <span class="material-symbols-sharp">error</span>
                <span><?php echo $erro; ?></span>
            </div>
        <?php endif; ?>

        <!-- ABA 1: MEUS PEDIDOS -->
        <div class="tab-content <?php echo $activeTab === 'pedidos' ? 'active' : ''; ?>" data-tab="pedidos">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-kicker">Compras</div>
                    <h2 class="section-title">Acompanhe suas Compras</h2>
                </div>

                <div class="orders-list">
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <?php
                                $pedidoId = (int) ($pedido['id'] ?? 0);
                                $numeroPedido = '#R7-' . str_pad((string) $pedidoId, 4, '0', STR_PAD_LEFT);
                                $statusPedido = trim((string) ($pedido['status'] ?? 'Pendente'));
                                $statusNormalizado = strtolower($statusPedido);
                                $statusClasse = 'status-processing';
                                if ($statusNormalizado === 'entregue') {
                                    $statusClasse = 'status-delivered';
                                } elseif (in_array($statusNormalizado, ['enviado', 'em transporte', 'em_transporte'], true)) {
                                    $statusClasse = 'status-in-transit';
                                }

                                $valorTotalPedido = (float) ($pedido['valor_total'] ?? 0);
                                $itensPedido = is_array($pedido['itens'] ?? null) ? $pedido['itens'] : [];
                                $observacoesPedido = trim((string) ($pedido['observacoes'] ?? ''));
                                $codigoRastreioPedido = trim((string) ($pedido['codigo_rastreio'] ?? ''));
                                $linkRastreioPedido = trim((string) ($pedido['link_rastreio'] ?? ''));
                                $formaPagamentoPedido = trim((string) ($pedido['forma_pagamento'] ?? ''));
                                $dataPedido = !empty($pedido['data_pedido']) ? date('d/m/Y H:i', strtotime((string) $pedido['data_pedido'])) : '-';
                                $itensJson = htmlspecialchars(json_encode(array_values($itensPedido), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]', ENT_QUOTES, 'UTF-8');
                            ?>
                            <div
                                class="order-card"
                                data-order-number="<?php echo htmlspecialchars($numeroPedido, ENT_QUOTES, 'UTF-8'); ?>"
                                data-order-date="<?php echo htmlspecialchars($dataPedido, ENT_QUOTES, 'UTF-8'); ?>"
                                data-order-status="<?php echo htmlspecialchars($statusPedido, ENT_QUOTES, 'UTF-8'); ?>"
                                data-order-total="<?php echo htmlspecialchars('R$ ' . number_format($valorTotalPedido, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?>"
                                data-order-items="<?php echo $itensJson; ?>"
                                data-order-observacoes="<?php echo htmlspecialchars($observacoesPedido, ENT_QUOTES, 'UTF-8'); ?>"
                                data-order-rastreio="<?php echo htmlspecialchars($codigoRastreioPedido, ENT_QUOTES, 'UTF-8'); ?>"
                                data-order-rastreio-link="<?php echo htmlspecialchars($linkRastreioPedido, ENT_QUOTES, 'UTF-8'); ?>"
                                data-order-forma-pagamento="<?php echo htmlspecialchars($formaPagamentoPedido, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <div class="order-header">
                                    <div>
                                        <div class="order-number"><?php echo htmlspecialchars($numeroPedido); ?></div>
                                        <div class="order-date"><?php echo htmlspecialchars($dataPedido); ?></div>
                                    </div>
                                    <div class="order-status-badge <?php echo $statusClasse; ?>"><?php echo htmlspecialchars($statusPedido); ?></div>
                                </div>
                                <div class="order-items">
                                    <?php if (!empty($itensPedido)): ?>
                                        <?php foreach ($itensPedido as $itemPedido): ?>
                                            <?php
                                                $nomePreview = trim((string) ($itemPedido['nome'] ?? 'Produto'));
                                                $quantidadePreview = max(1, (int) ($itemPedido['quantidade'] ?? 1));
                                                $labelPreview = $quantidadePreview > 1 ? ($quantidadePreview . 'x ' . $nomePreview) : $nomePreview;
                                            ?>
                                            <div class="item-pill"><?php echo htmlspecialchars($labelPreview); ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="item-pill">Itens nao informados</div>
                                    <?php endif; ?>
                                </div>
                                <div class="order-total">R$ <?php echo number_format($valorTotalPedido, 2, ',', '.'); ?></div>
                                <div class="order-actions">
                                    <button class="btn-secondary btn-sm" data-order-action="details" type="button">Ver Pedido</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="order-card" style="grid-column: 1 / -1;">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">Nenhum pedido encontrado</div>
                                    <div class="order-date">Quando voce realizar sua primeira compra, ela aparecera aqui.</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="order-modal" id="orderDetailsModal" aria-hidden="true">
                    <div class="order-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
                        <div class="order-modal-header">
                            <h3 class="order-modal-title" id="orderModalTitle">Detalhes do Pedido</h3>
                            <button class="order-modal-close" id="orderModalClose" type="button" aria-label="Fechar detalhes do pedido">
                                <span class="material-symbols-sharp">close</span>
                            </button>
                        </div>

                        <div class="order-modal-summary">
                            <div class="order-modal-chip">
                                <div class="order-modal-chip-label">Pedido</div>
                                <div class="order-modal-chip-value" id="orderModalNumber">-</div>
                            </div>
                            <div class="order-modal-chip">
                                <div class="order-modal-chip-label">Data</div>
                                <div class="order-modal-chip-value" id="orderModalDate">-</div>
                            </div>
                            <div class="order-modal-chip">
                                <div class="order-modal-chip-label">Status</div>
                                <div class="order-modal-chip-value" id="orderModalStatus">-</div>
                            </div>
                            <div class="order-modal-chip">
                                <div class="order-modal-chip-label">Total</div>
                                <div class="order-modal-chip-value" id="orderModalTotal">-</div>
                            </div>
                        </div>

                        <div class="order-modal-products">
                            <h4>Itens do pedido</h4>
                            <ul class="order-modal-products-list" id="orderModalItems"></ul>
                        </div>

                        <div class="order-modal-extra">
                            <div class="order-modal-extra-item" id="orderModalTrackingWrap" style="display:none;">
                                <span class="order-modal-extra-label">Codigo de rastreio</span>
                                <div class="order-modal-extra-value" id="orderModalTrackingCode">-</div>
                                <a class="order-modal-track-link" id="orderModalTrackingLink" href="#" target="_blank" rel="noopener noreferrer" style="display:none;">
                                    <span class="material-symbols-sharp" style="font-size:18px;">open_in_new</span>
                                    Acompanhar entrega
                                </a>
                            </div>
                            <div class="order-modal-extra-item" id="orderModalObsWrap" style="display:none;">
                                <span class="order-modal-extra-label">Observacoes da equipe</span>
                                <div class="order-modal-extra-value" id="orderModalObs">-</div>
                            </div>
                            <div class="order-modal-extra-item" id="orderModalPaymentWrap" style="display:none;">
                                <span class="order-modal-extra-label">Forma de pagamento</span>
                                <div class="order-modal-extra-value" id="orderModalPayment">-</div>
                            </div>
                        </div>

                        <div class="order-modal-footer">
                            <button type="button" class="btn-secondary" id="orderModalCloseFooter">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 2: INFORMAÇÕES DA CONTA -->
        <div class="tab-content <?php echo $activeTab === 'conta' ? 'active' : ''; ?>" data-tab="conta">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-kicker">Perfil</div>
                    <h2 class="section-title">Suas Informações Pessoais</h2>
                </div>

                <form method="POST" action="minha-conta.php" class="form-grid">
                    <input type="hidden" name="active_tab" value="conta">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Nome Completo</label>
                            <input type="text" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>E-mail</label>
                            <input type="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" disabled>
                            <small>O e-mail não pode ser alterado</small>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000" required>
                        </div>
                        <div class="form-group">
                            <label>WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" value="<?php echo htmlspecialchars($cliente['whatsapp'] ?? ''); ?>" placeholder="(00) 00000-0000">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>CPF/CNPJ</label>
                        <input type="text" value="<?php echo htmlspecialchars($cliente['cpf_cnpj'] ?? ''); ?>" disabled>
                        <small>Não pode ser alterado</small>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="atualizar_dados" class="btn-primary">
                            <span class="material-symbols-sharp">save</span>
                            Salvar Alterações
                        </button>
                        <button type="reset" class="btn-secondary">
                            <span class="material-symbols-sharp">restart_alt</span>
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ABA 3: ENDEREÇOS -->
        <div class="tab-content <?php echo $activeTab === 'enderecos' ? 'active' : ''; ?>" data-tab="enderecos">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-kicker">Localização</div>
                    <h2 class="section-title">Seus Endereços</h2>
                </div>

                <div class="address-grid" id="addressGrid">
                    <?php if (!empty($enderecos)): ?>
                        <?php foreach ($enderecos as $endereco): ?>
                            <?php
                                $tipo = trim((string) ($endereco['tipo'] ?? 'Casa'));
                                $emoji = stripos($tipo, 'trab') !== false ? '🏢' : '🏠';
                                $rua = trim((string) ($endereco['rua'] ?? ''));
                                $numero = trim((string) ($endereco['numero'] ?? ''));
                                $complemento = trim((string) ($endereco['complemento'] ?? ''));
                                $bairro = trim((string) ($endereco['bairro'] ?? ''));
                                $cidade = trim((string) ($endereco['cidade'] ?? ''));
                                $uf = strtoupper(trim((string) ($endereco['uf'] ?? '')));
                                $cep = trim((string) ($endereco['cep'] ?? ''));

                                $linha1 = $rua . ', ' . $numero . ($complemento !== '' ? ' · ' . $complemento : '');
                                $linha2 = $cidade . ' - ' . $uf;
                            ?>
                            <div
                                class="address-card"
                                data-id="<?php echo (int) $endereco['id']; ?>"
                                data-tipo="<?php echo htmlspecialchars($tipo); ?>"
                                data-rua="<?php echo htmlspecialchars($rua); ?>"
                                data-numero="<?php echo htmlspecialchars($numero); ?>"
                                data-complemento="<?php echo htmlspecialchars($complemento); ?>"
                                data-bairro="<?php echo htmlspecialchars($bairro); ?>"
                                data-cidade="<?php echo htmlspecialchars($cidade); ?>"
                                data-uf="<?php echo htmlspecialchars($uf); ?>"
                                data-cep="<?php echo htmlspecialchars($cep); ?>"
                            >
                                <div class="address-title"><?php echo $emoji . ' ' . htmlspecialchars($tipo); ?></div>
                                <div class="address-text">
                                    <?php echo htmlspecialchars($linha1); ?><br>
                                    <?php echo htmlspecialchars($linha2); ?><br>
                                    CEP <?php echo htmlspecialchars($cep); ?>
                                </div>
                                <div class="address-actions">
                                    <button class="btn-secondary btn-sm" data-address-action="edit" type="button">Editar</button>
                                    <button class="btn-secondary btn-sm" data-address-action="remove" type="button">Remover</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="address-card" style="grid-column: 1 / -1;">
                            <div class="address-title">📍 Nenhum endereço cadastrado</div>
                            <div class="address-text">
                                Adicione um novo endereço para facilitar seu checkout.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 2rem;">
                    <button class="btn-primary" id="btnAddAddress" type="button">
                        <span class="material-symbols-sharp">add</span>
                        Adicionar Novo Endereço
                    </button>
                </div>

                <form id="addressForm" method="POST" action="minha-conta.php" style="display:none;">
                    <input type="hidden" name="active_tab" value="enderecos">
                    <input type="hidden" name="address_action" id="addressAction" value="save">
                    <input type="hidden" name="endereco_id" id="enderecoId" value="">
                    <input type="hidden" name="tipo" id="enderecoTipo" value="">
                    <input type="hidden" name="rua" id="enderecoRua" value="">
                    <input type="hidden" name="numero" id="enderecoNumero" value="">
                    <input type="hidden" name="complemento" id="enderecoComplemento" value="">
                    <input type="hidden" name="bairro" id="enderecoBairro" value="">
                    <input type="hidden" name="cidade" id="enderecoCidade" value="">
                    <input type="hidden" name="uf" id="enderecoUf" value="">
                    <input type="hidden" name="cep" id="enderecoCep" value="">
                </form>

                <div class="address-modal" id="addressModal" aria-hidden="true">
                    <div class="address-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addressModalTitle">
                        <div class="address-modal-header">
                            <h3 class="address-modal-title" id="addressModalTitle">Novo Endereco</h3>
                            <button class="address-modal-close" id="addressModalClose" type="button" aria-label="Fechar formulario de endereco">
                                <span class="material-symbols-sharp">close</span>
                            </button>
                        </div>

                        <div class="address-modal-error" id="addressModalError"></div>

                        <form id="addressModalForm" class="form-grid" novalidate>
                            <input type="hidden" id="modalEnderecoId" value="">

                            <div class="form-group">
                                <label for="modalEnderecoTipo">Tipo do endereco</label>
                                <input type="text" id="modalEnderecoTipo" maxlength="40" placeholder="Ex: Casa, Trabalho" required>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="modalEnderecoRua">Rua</label>
                                    <input type="text" id="modalEnderecoRua" maxlength="150" required>
                                </div>
                                <div class="form-group">
                                    <label for="modalEnderecoNumero">Numero</label>
                                    <input type="text" id="modalEnderecoNumero" maxlength="20" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="modalEnderecoComplemento">Complemento</label>
                                <input type="text" id="modalEnderecoComplemento" maxlength="120" placeholder="Apto, bloco, referencia (opcional)">
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="modalEnderecoBairro">Bairro</label>
                                    <input type="text" id="modalEnderecoBairro" maxlength="80" required>
                                </div>
                                <div class="form-group">
                                    <label for="modalEnderecoCidade">Cidade</label>
                                    <input type="text" id="modalEnderecoCidade" maxlength="80" required>
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="modalEnderecoUf">UF</label>
                                    <input type="text" id="modalEnderecoUf" maxlength="2" placeholder="SP" required>
                                </div>
                                <div class="form-group">
                                    <label for="modalEnderecoCep">CEP</label>
                                    <input type="text" id="modalEnderecoCep" maxlength="9" placeholder="00000-000" required>
                                </div>
                            </div>

                            <div class="address-modal-actions">
                                <button type="button" class="btn-secondary" id="addressModalCancel">Cancelar</button>
                                <button type="submit" class="btn-primary" id="addressModalSubmit">
                                    <span class="material-symbols-sharp">save</span>
                                    Salvar Endereco
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 4: SEGURANÇA -->
        <div class="tab-content <?php echo $activeTab === 'seguranca' ? 'active' : ''; ?>" data-tab="seguranca">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-kicker">Proteção</div>
                    <h2 class="section-title">Altere Sua Senha</h2>
                </div>

                <form method="POST" action="minha-conta.php" class="form-grid">
                    <input type="hidden" name="active_tab" value="seguranca">
                    <div class="form-group">
                        <label>Senha Atual</label>
                        <input type="password" name="senha_atual" placeholder="Digite sua senha atual" required>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Nova Senha</label>
                            <input type="password" name="senha_nova" placeholder="Mínimo 6 caracteres" required>
                        </div>
                        <div class="form-group">
                            <label>Confirme a Senha</label>
                            <input type="password" name="senha_confirmar" placeholder="Digite novamente" required>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="alterar_senha" class="btn-primary">
                            <span class="material-symbols-sharp">check_circle</span>
                            Atualizar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script>
function toggleMobileMenu(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const menu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.mobile-menu-overlay');

    if (!menu || !overlay) return;

    menu.classList.toggle('active');
    overlay.classList.toggle('active');
    document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
}

function closeMobileMenu(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const menu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.mobile-menu-overlay');

    if (!menu || !overlay) return;

    menu.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

window.toggleUserDropdown = function(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const dropdown = document.querySelector('.user-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
        const btn = dropdown.querySelector('.user-dropdown-btn');
        if (btn) {
            btn.setAttribute('aria-expanded', dropdown.classList.contains('active'));
        }
    }
}

// Fechar dropdown ao clicar fora
document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.user-dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
        const btn = dropdown.querySelector('.user-dropdown-btn');
        if (btn) {
            btn.setAttribute('aria-expanded', 'false');
        }
    }
}, true);

// Sistema de Abas
document.addEventListener('DOMContentLoaded', function() {
    const floatingNavbar = document.getElementById('floatingNavbar');

    if (floatingNavbar) {
        const toggleNavbar = () => {
            if (window.scrollY > 120) {
                floatingNavbar.classList.add('visible');
            } else {
                floatingNavbar.classList.remove('visible');
            }
        };

        window.addEventListener('scroll', toggleNavbar, { passive: true });
        toggleNavbar();
    }

    const navSearchForm = document.getElementById('navSearchForm');
    const navSearchInput = document.getElementById('navSearchInput');
    const navSearchToggle = document.getElementById('navSearchToggle');

    if (navSearchForm && navSearchInput && navSearchToggle) {
        const closeSearch = () => {
            navSearchForm.classList.remove('active');
            navSearchToggle.setAttribute('aria-label', 'Abrir pesquisa');
        };

        navSearchToggle.addEventListener('click', function() {
            if (!navSearchForm.classList.contains('active')) {
                navSearchForm.classList.add('active');
                navSearchToggle.setAttribute('aria-label', 'Pesquisar agora');
                requestAnimationFrame(() => navSearchInput.focus());
                return;
            }

            if (navSearchInput.value.trim() !== '') {
                navSearchForm.submit();
                return;
            }

            closeSearch();
        });

        navSearchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeSearch();
                navSearchInput.blur();
            }
        });
    }

    const menuTabs = document.querySelectorAll('.menu-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    const validTabs = ['pedidos', 'conta', 'enderecos', 'seguranca'];

    function activateTab(tabName) {
        if (!validTabs.includes(tabName)) {
            tabName = 'pedidos';
        }

        menuTabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        const selectedTabButton = document.querySelector(`.menu-tab[data-tab="${tabName}"]`);
        const selectedTabContent = document.querySelector(`.tab-content[data-tab="${tabName}"]`);

        if (selectedTabButton) {
            selectedTabButton.classList.add('active');
        }
        if (selectedTabContent) {
            selectedTabContent.classList.add('active');
        }

        localStorage.setItem('minha_conta_active_tab', tabName);

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);

        document.querySelectorAll('input[name="active_tab"]').forEach(input => {
            input.value = tabName;
        });
    }

    const initialTabFromUrl = new URLSearchParams(window.location.search).get('tab');
    const initialTabFromStorage = localStorage.getItem('minha_conta_active_tab');
    const initialTab = validTabs.includes(initialTabFromUrl)
        ? initialTabFromUrl
        : (validTabs.includes(initialTabFromStorage) ? initialTabFromStorage : 'pedidos');

    activateTab(initialTab);

    menuTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            activateTab(tabName);
        });
    });

    setupOrderDetailsModal();

    // Enderecos - acoes locais para testar UX da aba
    setupAddressActions();

    // Máscaras de entrada
    maskPhoneInputs();
    maskCepInput();

    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }

        if (navSearchForm && !navSearchForm.contains(e.target)) {
            navSearchForm.classList.remove('active');
        }
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileMenu();
        }
    });

    const alerts = document.querySelectorAll('.alert');
    if (alerts.length) {
        setTimeout(function() {
            alerts.forEach(function(alert) {
                alert.classList.add('is-hiding');
                setTimeout(function() {
                    alert.remove();
                }, 320);
            });
        }, 4500);
    }
});

function maskPhoneInputs() {
    const phoneInputs = document.querySelectorAll('#telefone, #whatsapp');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
            e.target.value = value;
        });
    });
}

function setupOrderDetailsModal() {
    const orderModal = document.getElementById('orderDetailsModal');
    if (!orderModal) return;

    const orderModalClose = document.getElementById('orderModalClose');
    const orderModalCloseFooter = document.getElementById('orderModalCloseFooter');
    const orderModalNumber = document.getElementById('orderModalNumber');
    const orderModalDate = document.getElementById('orderModalDate');
    const orderModalStatus = document.getElementById('orderModalStatus');
    const orderModalTotal = document.getElementById('orderModalTotal');
    const orderModalItems = document.getElementById('orderModalItems');
    const orderModalTrackingWrap = document.getElementById('orderModalTrackingWrap');
    const orderModalTrackingCode = document.getElementById('orderModalTrackingCode');
    const orderModalTrackingLink = document.getElementById('orderModalTrackingLink');
    const orderModalObsWrap = document.getElementById('orderModalObsWrap');
    const orderModalObs = document.getElementById('orderModalObs');
    const orderModalPaymentWrap = document.getElementById('orderModalPaymentWrap');
    const orderModalPayment = document.getElementById('orderModalPayment');
    let lastFocusedElement = null;

    const formatCurrency = function(value) {
        const amount = Number(value || 0);
        return amount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const resolveItemImage = function(imagePath) {
        const raw = String(imagePath || '').trim();
        if (!raw) {
            return '';
        }

        if (/^(https?:|data:|blob:)/i.test(raw)) {
            return raw;
        }

        if (raw.startsWith('/') || raw.startsWith('../') || raw.startsWith('./')) {
            return raw;
        }

        return '../../uploads/produtos/' + raw;
    };

    const closeOrderModal = function() {
        orderModal.classList.remove('active');
        orderModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    };

    const openOrderModal = function(card, triggerButton) {
        if (!card) return;

        const number = (card.dataset.orderNumber || card.querySelector('.order-number')?.textContent || '-').trim();
        const date = (card.dataset.orderDate || card.querySelector('.order-date')?.textContent || '-').trim();
        const status = (card.dataset.orderStatus || card.querySelector('.order-status-badge')?.textContent || '-').trim();
        const total = (card.dataset.orderTotal || card.querySelector('.order-total')?.textContent || '-').trim();
        const observacoes = (card.dataset.orderObservacoes || '').trim();
        const codigoRastreio = (card.dataset.orderRastreio || '').trim();
        const linkRastreio = (card.dataset.orderRastreioLink || '').trim();
        const formaPagamento = (card.dataset.orderFormaPagamento || '').trim();

        let items = [];
        try {
            const rawItems = card.dataset.orderItems || '[]';
            const parsedItems = JSON.parse(rawItems);
            if (Array.isArray(parsedItems)) {
                items = parsedItems.map(function(item) {
                    if (typeof item === 'string') {
                        return {
                            nome: item.trim(),
                            quantidade: 1,
                            preco_unitario: 0,
                            imagem: ''
                        };
                    }

                    return {
                        nome: String(item?.nome || 'Produto').trim(),
                        quantidade: Math.max(1, Number(item?.quantidade || 1)),
                        preco_unitario: Number(item?.preco_unitario || 0),
                        imagem: String(item?.imagem || '').trim()
                    };
                }).filter(function(item) {
                    return item.nome;
                });
            }
        } catch (e) {
            items = [];
        }

        if (items.length === 0) {
            items = Array.from(card.querySelectorAll('.item-pill')).map(function(item) {
                return {
                    nome: item.textContent.trim(),
                    quantidade: 1,
                    preco_unitario: 0,
                    imagem: ''
                };
            }).filter(Boolean);
        }

        orderModalNumber.textContent = number;
        orderModalDate.textContent = date;
        orderModalStatus.textContent = status;
        orderModalTotal.textContent = total;

        orderModalItems.innerHTML = '';
        if (items.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'Nenhum item identificado para este pedido.';
            orderModalItems.appendChild(li);
        } else {
            items.forEach(function(item) {
                const li = document.createElement('li');

                const imageUrl = resolveItemImage(item.imagem);
                const thumbContent = imageUrl
                    ? '<img src="' + imageUrl + '" alt="' + item.nome.replace(/"/g, '&quot;') + '" loading="lazy" onerror="this.remove(); this.parentElement.innerHTML=\'<span class=\"material-symbols-sharp\">inventory_2</span>\';">'
                    : '<span class="material-symbols-sharp">inventory_2</span>';

                const precoTexto = item.preco_unitario > 0 ? (formatCurrency(item.preco_unitario) + ' cada') : 'Valor indisponivel';
                const quantidadeTexto = 'Qtd: ' + item.quantidade;

                li.innerHTML = ''
                    + '<div class="order-item-thumb">' + thumbContent + '</div>'
                    + '<div class="order-item-info">'
                    + '  <div class="order-item-name"></div>'
                    + '  <div class="order-item-meta">' + quantidadeTexto + '</div>'
                    + '</div>'
                    + '<div class="order-item-price">' + precoTexto + '</div>';

                const nomeEl = li.querySelector('.order-item-name');
                if (nomeEl) {
                    nomeEl.textContent = item.nome;
                }

                orderModalItems.appendChild(li);
            });
        }

        if (codigoRastreio && orderModalTrackingWrap && orderModalTrackingCode) {
            orderModalTrackingCode.textContent = codigoRastreio;
            orderModalTrackingWrap.style.display = 'block';
        } else if (orderModalTrackingWrap && orderModalTrackingCode) {
            orderModalTrackingCode.textContent = '';
            orderModalTrackingWrap.style.display = 'none';
        }

        if (linkRastreio && orderModalTrackingLink) {
            orderModalTrackingLink.href = linkRastreio;
            orderModalTrackingLink.style.display = 'inline-flex';
        } else if (orderModalTrackingLink) {
            orderModalTrackingLink.removeAttribute('href');
            orderModalTrackingLink.style.display = 'none';
        }

        if (observacoes && orderModalObsWrap && orderModalObs) {
            orderModalObs.textContent = observacoes;
            orderModalObsWrap.style.display = 'block';
        } else if (orderModalObsWrap && orderModalObs) {
            orderModalObs.textContent = '';
            orderModalObsWrap.style.display = 'none';
        }

        if (formaPagamento && orderModalPaymentWrap && orderModalPayment) {
            orderModalPayment.textContent = formaPagamento;
            orderModalPaymentWrap.style.display = 'block';
        } else if (orderModalPaymentWrap && orderModalPayment) {
            orderModalPayment.textContent = '';
            orderModalPaymentWrap.style.display = 'none';
        }

        lastFocusedElement = triggerButton || document.activeElement;
        orderModal.classList.add('active');
        orderModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    document.addEventListener('click', function(event) {
        const detailsButton = event.target.closest('button[data-order-action="details"]');
        if (detailsButton) {
            const card = detailsButton.closest('.order-card');
            openOrderModal(card, detailsButton);
            return;
        }

        if (event.target === orderModal) {
            closeOrderModal();
        }
    });

    if (orderModalClose) {
        orderModalClose.addEventListener('click', closeOrderModal);
    }

    if (orderModalCloseFooter) {
        orderModalCloseFooter.addEventListener('click', closeOrderModal);
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && orderModal.classList.contains('active')) {
            closeOrderModal();
        }
    });
}

function setupAddressActions() {
    const addressGrid = document.getElementById('addressGrid');
    const btnAddAddress = document.getElementById('btnAddAddress');
    const addressForm = document.getElementById('addressForm');
    const addressModal = document.getElementById('addressModal');
    const addressModalForm = document.getElementById('addressModalForm');
    const addressModalTitle = document.getElementById('addressModalTitle');
    const addressModalError = document.getElementById('addressModalError');
    const addressModalClose = document.getElementById('addressModalClose');
    const addressModalCancel = document.getElementById('addressModalCancel');

    const modalEnderecoId = document.getElementById('modalEnderecoId');
    const modalEnderecoTipo = document.getElementById('modalEnderecoTipo');
    const modalEnderecoRua = document.getElementById('modalEnderecoRua');
    const modalEnderecoNumero = document.getElementById('modalEnderecoNumero');
    const modalEnderecoComplemento = document.getElementById('modalEnderecoComplemento');
    const modalEnderecoBairro = document.getElementById('modalEnderecoBairro');
    const modalEnderecoCidade = document.getElementById('modalEnderecoCidade');
    const modalEnderecoUf = document.getElementById('modalEnderecoUf');
    const modalEnderecoCep = document.getElementById('modalEnderecoCep');

    if (!addressGrid || !btnAddAddress || !addressForm || !addressModal || !addressModalForm) return;

    const addressAction = document.getElementById('addressAction');
    const enderecoId = document.getElementById('enderecoId');
    const enderecoTipo = document.getElementById('enderecoTipo');
    const enderecoRua = document.getElementById('enderecoRua');
    const enderecoNumero = document.getElementById('enderecoNumero');
    const enderecoComplemento = document.getElementById('enderecoComplemento');
    const enderecoBairro = document.getElementById('enderecoBairro');
    const enderecoCidade = document.getElementById('enderecoCidade');
    const enderecoUf = document.getElementById('enderecoUf');
    const enderecoCep = document.getElementById('enderecoCep');

    let lastFocusedElement = null;

    const setModalError = function(message) {
        if (!addressModalError) return;
        if (!message) {
            addressModalError.textContent = '';
            addressModalError.classList.remove('active');
            return;
        }

        addressModalError.textContent = message;
        addressModalError.classList.add('active');
    };

    const resetModalFields = function() {
        modalEnderecoId.value = '';
        modalEnderecoTipo.value = '';
        modalEnderecoRua.value = '';
        modalEnderecoNumero.value = '';
        modalEnderecoComplemento.value = '';
        modalEnderecoBairro.value = '';
        modalEnderecoCidade.value = '';
        modalEnderecoUf.value = '';
        modalEnderecoCep.value = '';
        setModalError('');
    };

    const openAddressModal = function(mode, card) {
        resetModalFields();
        lastFocusedElement = document.activeElement;

        const isEdit = mode === 'edit';
        addressModalTitle.textContent = isEdit ? 'Editar Endereco' : 'Novo Endereco';

        if (isEdit && card) {
            modalEnderecoId.value = (card.dataset.id || '').trim();
            modalEnderecoTipo.value = (card.dataset.tipo || '').trim();
            modalEnderecoRua.value = (card.dataset.rua || '').trim();
            modalEnderecoNumero.value = (card.dataset.numero || '').trim();
            modalEnderecoComplemento.value = (card.dataset.complemento || '').trim();
            modalEnderecoBairro.value = (card.dataset.bairro || '').trim();
            modalEnderecoCidade.value = (card.dataset.cidade || '').trim();
            modalEnderecoUf.value = (card.dataset.uf || '').trim().toUpperCase();
            modalEnderecoCep.value = (card.dataset.cep || '').trim();
        }

        addressModal.classList.add('active');
        addressModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => modalEnderecoTipo.focus());
    };

    const closeAddressModal = function() {
        addressModal.classList.remove('active');
        addressModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        setModalError('');
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    };

    const submitAddress = function(payload) {
        addressAction.value = payload.action || 'save';
        enderecoId.value = payload.id || '';
        enderecoTipo.value = payload.tipo || '';
        enderecoRua.value = payload.rua || '';
        enderecoNumero.value = payload.numero || '';
        enderecoComplemento.value = payload.complemento || '';
        enderecoBairro.value = payload.bairro || '';
        enderecoCidade.value = payload.cidade || '';
        enderecoUf.value = payload.uf || '';
        enderecoCep.value = payload.cep || '';
        addressForm.submit();
    };

    if (modalEnderecoCep) {
        modalEnderecoCep.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.slice(0, 8);
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }

    if (modalEnderecoUf) {
        modalEnderecoUf.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 2);
        });
    }

    btnAddAddress.addEventListener('click', function() {
        openAddressModal('create');
    });

    if (addressModalClose) {
        addressModalClose.addEventListener('click', closeAddressModal);
    }

    if (addressModalCancel) {
        addressModalCancel.addEventListener('click', closeAddressModal);
    }

    addressModal.addEventListener('click', function(event) {
        if (event.target === addressModal) {
            closeAddressModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && addressModal.classList.contains('active')) {
            closeAddressModal();
        }
    });

    addressModalForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const payload = {
            action: 'save',
            id: (modalEnderecoId.value || '').trim(),
            tipo: (modalEnderecoTipo.value || '').trim(),
            rua: (modalEnderecoRua.value || '').trim(),
            numero: (modalEnderecoNumero.value || '').trim(),
            complemento: (modalEnderecoComplemento.value || '').trim(),
            bairro: (modalEnderecoBairro.value || '').trim(),
            cidade: (modalEnderecoCidade.value || '').trim(),
            uf: (modalEnderecoUf.value || '').trim().toUpperCase(),
            cep: (modalEnderecoCep.value || '').trim()
        };

        if (!payload.tipo || !payload.rua || !payload.numero || !payload.bairro || !payload.cidade || !payload.uf || !payload.cep) {
            setModalError('Preencha os campos obrigatorios para salvar o endereco.');
            return;
        }

        if (payload.uf.length !== 2) {
            setModalError('A UF deve conter 2 letras. Exemplo: SP.');
            return;
        }

        if (!/^\d{5}-?\d{3}$/.test(payload.cep)) {
            setModalError('Informe um CEP valido no formato 00000-000.');
            return;
        }

        payload.cep = payload.cep.replace(/(\d{5})(\d{3})/, '$1-$2');
        closeAddressModal();
        submitAddress(payload);
    });

    addressGrid.addEventListener('click', function(event) {
        const button = event.target.closest('button[data-address-action]');
        if (!button) return;

        const card = button.closest('.address-card');
        if (!card) return;

        const action = button.getAttribute('data-address-action');

        if (action === 'remove') {
            const confirmRemove = window.confirm('Deseja remover este endereco?');
            if (confirmRemove) {
                submitAddress({ action: 'remove', id: card.dataset.id || '' });
            }
            return;
        }

        if (action === 'edit') {
            openAddressModal('edit', card);
        }
    });
}

function maskCepInput() {
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

</body>
</html>

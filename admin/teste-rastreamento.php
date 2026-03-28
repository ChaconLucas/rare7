<?php
/**
 * Formulário de Teste - Atualizar Rastreamento
 * Use este formulário para testar a funcionalidade
 */

require_once './cliente/config.php';

$message = '';
$messageType = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numeroPedido = $_POST['numero_pedido'] ?? '';
    $email = $_POST['email'] ?? '';
    $codigoRastreio = $_POST['codigo_rastreio'] ?? '';
    $transportadora = $_POST['transportadora'] ?? '';
    $statusEntrega = $_POST['status_entrega'] ?? '';
    $linkRastreio = $_POST['link_rastreio'] ?? '';
    
    if (empty($numeroPedido) || empty($email)) {
        $message = 'Número do pedido e e-mail são obrigatórios';
        $messageType = 'error';
    } else {
        try {
            // Buscar pedido
            // Remover # se houver
            $numeroPedidoLimpo = str_replace('#', '', $numeroPedido);
            
            // Buscar pedido por ID
            $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND cliente_email = ?");
            $stmt->execute([(int)$numeroPedidoLimpo, $email]);
            $pedido = $stmt->fetch();
            
            if (!$pedido) {
                $message = 'Pedido não encontrado';
                $messageType = 'error';
            } else {
                $updates = [];
                $params = [];
                
                if (!empty($codigoRastreio)) {
                    $updates[] = 'codigo_rastreio = ?';
                    $params[] = $codigoRastreio;
                }
                
                if (!empty($transportadora)) {
                    $updates[] = 'transportadora = ?';
                    $params[] = $transportadora;
                }
                
                if (!empty($statusEntrega)) {
                    $updates[] = 'status_entrega = ?';
                    $params[] = $statusEntrega;
                }
                
                if (!empty($linkRastreio)) {
                    $updates[] = 'link_rastreio = ?';
                    $params[] = $linkRastreio;
                }
                
                if (empty($updates)) {
                    $message = 'Nenhum dado de rastreamento foi informado';
                    $messageType = 'warning';
                } else {
                    $updates[] = 'data_atualizacao = NOW()';
                    $updates[] = 'ultima_atualizacao_rastreio = NOW()';
                    $params[] = $pedido['id'];
                    
                    $sql = "UPDATE pedidos SET " . implode(', ', $updates) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $message = '✓ Rastreamento atualizado com sucesso!';
                    $messageType = 'success';
                }
            }
        } catch (Exception $e) {
            $message = 'Erro: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Buscar pedidos para exemplo
$pedidos = [];
try {
    $stmt = $pdo->query("SELECT id, cliente_email, status, status_entrega FROM pedidos LIMIT 10");
    $pedidos = $stmt->fetchAll();
} catch (Exception $e) {
    // Ignorar erro
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Atualizar Rastreamento</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message.show { display: block; }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .examples {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 30px;
            border-radius: 5px;
        }
        
        .examples h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .example-item {
            background: white;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 3px;
            font-size: 13px;
            color: #666;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .example-item:hover {
            background: #e8eef7;
        }
        
        .example-item code {
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #c92a2a;
        }
        
        .hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚚 Teste de Rastreamento</h1>
        <p class="subtitle">Atualize os dados de rastreamento dos seus pedidos</p>
        
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?> show"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="numero_pedido">Número do Pedido *</label>
                    <input type="text" id="numero_pedido" name="numero_pedido" placeholder="#000005" required value="<?php echo htmlspecialchars($_POST['numero_pedido'] ?? ''); ?>">
                    <p class="hint">Formato: #XXXXXX</p>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail do Cliente *</label>
                    <input type="email" id="email" name="email" placeholder="cliente@email.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="codigo_rastreio">Código de Rastreio</label>
                    <input type="text" id="codigo_rastreio" name="codigo_rastreio" placeholder="XXBR123456789XX" value="<?php echo htmlspecialchars($_POST['codigo_rastreio'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="transportadora">Transportadora</label>
                    <select id="transportadora" name="transportadora">
                        <option value="">-- Selecione --</option>
                        <option value="Correios" <?php echo ($_POST['transportadora'] ?? '') === 'Correios' ? 'selected' : ''; ?>>Correios</option>
                        <option value="Sedex" <?php echo ($_POST['transportadora'] ?? '') === 'Sedex' ? 'selected' : ''; ?>>Sedex</option>
                        <option value="PAC" <?php echo ($_POST['transportadora'] ?? '') === 'PAC' ? 'selected' : ''; ?>>PAC</option>
                        <option value="Mercado Envios" <?php echo ($_POST['transportadora'] ?? '') === 'Mercado Envios' ? 'selected' : ''; ?>>Mercado Envios</option>
                        <option value="Loggi" <?php echo ($_POST['transportadora'] ?? '') === 'Loggi' ? 'selected' : ''; ?>>Loggi</option>
                        <option value="JadLog" <?php echo ($_POST['transportadora'] ?? '') === 'JadLog' ? 'selected' : ''; ?>>JadLog</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status_entrega">Status de Entrega</label>
                    <select id="status_entrega" name="status_entrega">
                        <option value="">-- Selecione --</option>
                        <option value="Aguardando postagem" <?php echo ($_POST['status_entrega'] ?? '') === 'Aguardando postagem' ? 'selected' : ''; ?>>Aguardando postagem</option>
                        <option value="Em transporte" <?php echo ($_POST['status_entrega'] ?? '') === 'Em transporte' ? 'selected' : ''; ?>>Em transporte</option>
                        <option value="Entregue" <?php echo ($_POST['status_entrega'] ?? '') === 'Entregue' ? 'selected' : ''; ?>>Entregue</option>
                        <option value="Envio cancelado" <?php echo ($_POST['status_entrega'] ?? '') === 'Envio cancelado' ? 'selected' : ''; ?>>Envio cancelado</option>
                        <option value="Processando envio" <?php echo ($_POST['status_entrega'] ?? '') === 'Processando envio' ? 'selected' : ''; ?>>Processando envio</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="link_rastreio">Link de Rastreio Externo</label>
                    <input type="url" id="link_rastreio" name="link_rastreio" placeholder="https://track.meuamigo.com" value="<?php echo htmlspecialchars($_POST['link_rastreio'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit">Atualizar Rastreamento</button>
        </form>
        
        <?php if (!empty($pedidos)): ?>
        <div class="examples">
            <h3>📦 Pedidos Disponíveis para Teste:</h3>
            <?php foreach ($pedidos as $pedido): ?>
            <div class="example-item" onclick="document.getElementById('numero_pedido').value='#<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?>'; document.getElementById('email').value='<?php echo htmlspecialchars($pedido['cliente_email']); ?>';">
                <code>#<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></code> - <?php echo htmlspecialchars($pedido['cliente_email']); ?> <br>
                <small>Status: <?php echo htmlspecialchars($pedido['status']); ?> | Entrega: <?php echo htmlspecialchars($pedido['status_entrega']); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
/**
 * Teste de Rastreamento - Versão Simplificada
 */

require_once './config.php';

$resultado = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numeroPedido = trim($_POST['numero_pedido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($numeroPedido) || empty($email)) {
        $erro = "❌ Preencha número do pedido e e-mail";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    numero_pedido,
                    cliente_email,
                    status,
                    status_entrega,
                    codigo_rastreio,
                    transportadora,
                    link_rastreio,
                    data_status_mudanca,
                    ultima_atualizacao_rastreio
                FROM pedidos 
                WHERE numero_pedido = ? AND cliente_email = ?
                LIMIT 1
            ");
            $stmt->execute([$numeroPedido, mb_strtolower($email)]);
            $resultado = $stmt->fetch();
            
            if (!$resultado) {
                $erro = "❌ Pedido não encontrado. Verifique número e e-mail.";
            }
        } catch (Exception $e) {
            $erro = "❌ Erro ao buscar: " . $e->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreador Simples - RARE7</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #1e1e2e;
            margin-bottom: 10px;
            font-size: 32px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 20px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .error {
            background: #ffe6e6;
            color: #c00;
            border: 1px solid #ffcccc;
        }

        .success {
            background: #e6ffe6;
            color: #060;
            border: 1px solid #ccffcc;
        }

        .resultado {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }

        .resultado h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .item {
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e0e0e0;
        }

        .item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .value {
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-emtransito {
            background: #ffd966;
            color: #334;
        }

        .status-entregue {
            background: #92d050;
            color: #333;
        }

        .status-aguardando {
            background: #70ad47;
            color: white;
        }

        .status-cancelado {
            background: #f4b084;
            color: #333;
        }

        .link-rastreio {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .link-rastreio:hover {
            background: #764ba2;
        }

        .Examples {
            background: #f0f0f0;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 30px;
            border-radius: 5px;
            font-size: 13px;
        }

        .Examples h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .example-code {
            background: white;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #c92a2a;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚚 Rastrear Pedido</h1>
        <p class="subtitle">Digite seu número de pedido e e-mail</p>

        <?php if ($erro): ?>
            <div class="mensaje error"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="numero">Número do Pedido</label>
                <input 
                    type="text" 
                    id="numero" 
                    name="numero_pedido" 
                    placeholder="Ex: #000005"
                    value="<?php echo htmlspecialchars($_POST['numero_pedido'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="seu@email.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>

            <button type="submit">🔍 Buscar Pedido</button>
        </form>

        <?php if ($resultado): ?>
            <div class="resultado">
                <h2>✅ Pedido Encontrado</h2>

                <div class="item">
                    <div class="label">Número do Pedido</div>
                    <div class="value">#<?php echo str_pad($resultado['id'], 6, '0', STR_PAD_LEFT); ?></div>
                </div>

                <div class="item">
                    <div class="label">Status do Pedido</div>
                    <div class="value"><?php echo htmlspecialchars($resultado['status']); ?></div>
                </div>

                <div class="item">
                    <div class="label">Status de Entrega</div>
                    <div class="value">
                        <?php
                        $status = strtolower($resultado['status_entrega'] ?? '');
                        $classe = 'status-aguardando';
                        if (strpos($status, 'transito') !== false) $classe = 'status-emtransito';
                        elseif (strpos($status, 'entregue') !== false) $classe = 'status-entregue';
                        elseif (strpos($status, 'cancelad') !== false) $classe = 'status-cancelado';
                        ?>
                        <span class="status-badge <?php echo $classe; ?>">
                            <?php echo htmlspecialchars($resultado['status_entrega']); ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($resultado['codigo_rastreio'])): ?>
                <div class="item">
                    <div class="label">Código de Rastreio</div>
                    <div class="value"><?php echo htmlspecialchars($resultado['codigo_rastreio']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($resultado['transportadora'])): ?>
                <div class="item">
                    <div class="label">Transportadora</div>
                    <div class="value"><?php echo htmlspecialchars($resultado['transportadora']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($resultado['link_rastreio'])): ?>
                <div class="item">
                    <div class="label">Rastrear Externamente</div>
                    <a href="<?php echo htmlspecialchars($resultado['link_rastreio']); ?>" target="_blank" class="link-rastreio">
                        Ir para 17track →
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($resultado['ultima_atualizacao_rastreio'])): ?>
                <div class="item">
                    <div class="label">Última Atualização</div>
                    <div class="value">
                        <?php 
                        $data = new DateTime($resultado['ultima_atualizacao_rastreio']);
                        echo $data->format('d/m/Y H:i'); 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="Examples">
            <h3>📝 Use esses dados para testar:</h3>
            <div class="example-code">Número: R7-260327-068863</div>
            <div class="example-code">Email: teste1774656290@teste.com</div>
        </div>
    </div>
</body>
</html>

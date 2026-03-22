<?php
/**
 * Setup da Tabela cms_home_metrics
 * Execute este arquivo uma vez para criar a tabela no banco
 */
session_start();
if (!isset($_SESSION['usuario_logado'])) {
    die('Acesso negado. Fa√ßa login como administrador.');
}

require_once '../../../../PHP/conexao.php';

// Ler e executar SQL
$sql = file_get_contents(__DIR__ . '/create_metrics_table.sql');

// Executar cada statement separadamente
$statements = array_filter(
    array_map('trim', explode(';', $sql)), 
    fn($s) => !empty($s) && strpos($s, '--') !== 0
);

$success = true;
$messages = [];

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    if (mysqli_query($conexao, $statement)) {
        $messages[] = "‚o" Statement executado com sucesso";
    } else {
        $success = false;
        $messages[] = "‚o- Erro: " . mysqli_error($conexao);
    }
}

mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Setup M√©tricas - CMS Rare7</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: <?php echo $success ? '#10b981' : '#ef4444'; ?>;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
        }
        .success {
            background: #f0fdf4;
            border-left-color: #10b981;
        }
        .error {
            background: #fef2f2;
            border-left-color: #ef4444;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #C6A75E;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn:hover {
            background: #d400b0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $success ? '‚o" Setup Conclu√≠do!' : '‚o- Erro no Setup'; ?></h1>
        
        <?php foreach ($messages as $msg): ?>
        <div class="message <?php echo strpos($msg, '‚o"') !== false ? 'success' : 'error'; ?>">
            <?php echo $msg; ?>
        </div>
        <?php endforeach; ?>
        
        <?php if ($success): ?>
        <p style="margin-top: 20px; color: #6b7280;">
            A tabela <strong>cms_home_metrics</strong> foi criada com sucesso e as m√©tricas padr√£o foram inseridas.
        </p>
        <?php endif; ?>
        
        <a href="metrics.php" class="btn">Ir para Gerenciamento de M√©tricas</a>
    </div>
</body>
</html>

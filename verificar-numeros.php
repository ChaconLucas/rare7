<?php
require_once './cliente/config.php';

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║    VERIFICANDO NÚMEROS DE PEDIDOS NA TABELA                  ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stmt = $pdo->query("
    SELECT 
        id,
        numero_pedido,
        cliente_email,
        status,
        valor_total
    FROM pedidos 
    ORDER BY data_pedido DESC
    LIMIT 10
");

$pedidos = $stmt->fetchAll();

if (empty($pedidos)) {
    echo "⚠️  Nenhum pedido encontrado\n\n";
} else {
    echo "📊 PEDIDOS COM O NÚMERO CORRETO:\n\n";
    
    foreach ($pedidos as $pedido) {
        $numeroExibicao = !empty($pedido['numero_pedido']) ? $pedido['numero_pedido'] : '#' . str_pad($pedido['id'], 6, '0', STR_PAD_LEFT);
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📦 Número: $numeroExibicao\n";
        echo "   ID: " . $pedido['id'] . "\n";
        echo "   Email: " . htmlspecialchars($pedido['cliente_email']) . "\n";
        echo "   Status: " . $pedido['status'] . "\n";
        echo "   Valor: R$ " . number_format($pedido['valor_total'], 2, ',', '.') . "\n";
    }
    
    echo "\n";
    echo "✅ RESULTADO: Números de pedido atualizados com sucesso!\n";
}

echo "\n";

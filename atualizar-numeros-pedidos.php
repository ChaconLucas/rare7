<?php
require_once './cliente/config.php';

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   ATUALIZANDO NÚMEROS EM PEDIDOS ANTIGOS                     ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Buscar pedidos sem número
$stmt = $pdo->query("
    SELECT id, data_pedido
    FROM pedidos 
    WHERE numero_pedido IS NULL OR numero_pedido = ''
    ORDER BY data_pedido ASC
");

$pedidosSemNumero = $stmt->fetchAll();

if (empty($pedidosSemNumero)) {
    echo "✅ Todos os pedidos já têm números atribuídos\n\n";
} else {
    echo "📝 Encontrados " . count($pedidosSemNumero) . " pedido(s) sem número\n\n";
    
    foreach ($pedidosSemNumero as $pedido) {
        $data = str_replace(['-', ' ', ':'], '', $pedido['data_pedido']);
        $data = substr($data, 2, 6); // YYMMDD
        $numero = 'R7-' . $data . '-' . str_pad((string)$pedido['id'], 6, '0', STR_PAD_LEFT);
        
        $updateStmt = $pdo->prepare("UPDATE pedidos SET numero_pedido = ? WHERE id = ?");
        $updateStmt->execute([$numero, $pedido['id']]);
        
        echo "✓ Pedido #" . $pedido['id'] . " → $numero\n";
    }
    
    echo "\n✅ Todos os pedidos agora têm números!\n\n";
}

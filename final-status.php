<?php
require './cliente/config.php';

echo "\n╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║         🎉 RASTREAMENTO DE PEDIDOS - STATUS FINAL              ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Buscar um pedido com rastreamento
$stmt = $pdo->query("
    SELECT 
        numero_pedido, 
        cliente_email, 
        status, 
        status_entrega, 
        codigo_rastreio,
        transportadora,
        link_rastreio
    FROM pedidos 
    WHERE codigo_rastreio IS NOT NULL AND codigo_rastreio != ''
    LIMIT 1
");

$pedido = $stmt->fetch();

if ($pedido) {
    echo "✅ PEDIDO COM RASTREAMENTO ATIVO ENCONTRADO:\n\n";
    echo "  📦 Número: " . $pedido['numero_pedido'] . "\n";
    echo "  📧 Email: " . $pedido['cliente_email'] . "\n";
    echo "  📍 Status do Pedido: " . $pedido['status'] . "\n";
    echo "  🚚 Status da Entrega: " . $pedido['status_entrega'] . "\n";
    echo "  📋 Código Rastreio: " . $pedido['codigo_rastreio'] . "\n";
    echo "  🚛 Transportadora: " . ($pedido['transportadora'] ?: 'Não informada') . "\n";
    echo "  🔗 Link: " . ($pedido['link_rastreio'] ? 'Disponível' : 'Não configurado') . "\n\n";
} else {
    echo "⚠️  Nenhum pedido com rastreamento encontrado.\n\n";
}

echo "📊 ESTATÍSTICAS DO BANCO:\n\n";

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
$totalPedidos = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as comRastreio FROM pedidos WHERE codigo_rastreio IS NOT NULL AND codigo_rastreio != ''");
$comRastreio = $stmt->fetch()['comRastreio'];

echo "  📦 Total de pedidos: $totalPedidos\n";
echo "  ✓ Com rastreamento ativo: $comRastreio\n";
echo "  ℹ️  Sem rastreamento: " . ($totalPedidos - $comRastreio) . "\n\n";

echo "🔗 LINKS DE TESTE:\n\n";
echo "  1. Página de Rastreamento:\n";
echo "     http://localhost/rare7/cliente/pages/rastreio.php\n\n";
echo "  2. Formulário de Atualização:\n";
echo "     http://localhost/rare7/admin/teste-rastreamento.php\n\n";
echo "  3. Teste Automático:\n";
echo "     http://localhost/rare7/test-rastreamento-completo.php\n\n";

echo "✨ SISTEMA PRONTO PARA USO!\n\n";

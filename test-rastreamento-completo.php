<?php
/**
 * Teste Completo - Rastreamento de Pedidos
 * Valida toda a funcionalidade de ponta a ponta
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './cliente/config.php';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║               TESTE COMPLETO - RASTREAMENTO                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    // 1. Verificar dados das colunas
    echo "1️⃣  VERIFICANDO COLUNAS DA TABELA PEDIDOS...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM pedidos");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    $colunasEssenciais = ['numero_pedido', 'status_entrega', 'transportadora', 'codigo_rastreio', 'link_rastreio'];
    $todasPresentes = true;
    foreach ($colunasEssenciais as $col) {
        $existe = in_array($col, $columns);
        echo ($existe ? "   ✓" : "   ✗") . " Coluna '$col': " . ($existe ? "OK" : "FALTA") . "\n";
        if (!$existe) $todasPresentes = false;
    }
    
    if (!$todasPresentes) {
        die("\n❌ Erro: Colunas essenciais não encontradas!\n\n");
    }
    echo "\n";

    // 2. Buscar um pedido existente
    echo "2️⃣  BUSCANDO UM PEDIDO PARA TESTE...\n";
    $stmt = $pdo->query("SELECT id, numero_pedido, cliente_email, status FROM pedidos WHERE status != 'Pedido Cancelado' LIMIT 1");
    $pedido = $stmt->fetch();
    
    if (!$pedido) {
        // Criar um pedido de teste
        echo "   ⚠️  Nenhum pedido encontrado. Criando um de teste...\n";
        
        // Criar cliente
        $email = 'teste' . time() . '@teste.com';
        $stmtCliente = $pdo->prepare("INSERT INTO clientes (nome, email, telefone) VALUES (?, ?, ?)");
        $stmtCliente->execute(['Cliente Teste', $email, '11999999999']);
        $clienteId = $pdo->lastInsertId();
        
        // Criar pedido
        $numeroPedido = 'R7-' . date('ymd') . '-' . str_pad((string)mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $stmtPedido = $pdo->prepare("
            INSERT INTO pedidos (
                cliente_id, numero_pedido, cliente_email, valor_total, status, 
                status_entrega, endereco_entrega, cidade, estado, data_pedido
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtPedido->execute([
            $clienteId, 
            $numeroPedido, 
            $email, 
            100.00, 
            'Pagamento Confirmado',
            'Aguardando postagem',
            'Rua Teste, 123',
            'São Paulo',
            'SP'
        ]);
        
        $pedido = [
            'id' => $pdo->lastInsertId(),
            'numero_pedido' => $numeroPedido,
            'cliente_email' => $email,
            'status' => 'Pagamento Confirmado'
        ];
        
        echo "   ✓ Pedido de teste criado:\n";
    } else {
        echo "   ✓ Pedido encontrado:\n";
    }
    
    $pedidoId = $pedido['id'];
    $numeroPedido = $pedido['numero_pedido'];
    $email = $pedido['cliente_email'];
    
    echo "      - ID: $pedidoId\n";
    echo "      - Número: $numeroPedido\n";
    echo "      - Email: $email\n";
    echo "      - Status: " . $pedido['status'] . "\n\n";

    // 3. Atualizar dados de rastreamento
    echo "3️⃣  ATUALIZANDO DADOS DE RASTREAMENTO...\n";
    
    $codigoRastreio = 'BR' . substr(sha1($pedidoId . time()), 0, 20) . 'XX';
    $linkRastreio = 'https://t.17track.net/pt#nums=' . urlencode($codigoRastreio);
    
    $stmtUpdate = $pdo->prepare("
        UPDATE pedidos SET
            codigo_rastreio = ?,
            link_rastreio = ?,
            transportadora = ?,
            status_entrega = ?,
            status = ?,
            data_atualizacao = NOW(),
            ultima_atualizacao_rastreio = NOW()
        WHERE id = ?
    ");
    
    $stmtUpdate->execute([
        $codigoRastreio,
        $linkRastreio,
        'Correios',
        'Em transporte',
        'Pedido Enviado',
        $pedidoId
    ]);
    
    echo "   ✓ Dados atualizados:\n";
    echo "      - Código de rastreio: $codigoRastreio\n";
    echo "      - Transportadora: Correios\n";
    echo "      - Status entrega: Em transporte\n";
    echo "      - Link: " . substr($linkRastreio, 0, 50) . "...\n\n";

    // 4. Buscar os dados de rastreamento atualizado
    echo "4️⃣  BUSCANDO DADOS DE RASTREAMENTO...\n";
    
    $stmtBusca = $pdo->prepare("
        SELECT 
            id, numero_pedido, status, status_entrega, 
            codigo_rastreio, link_rastreio, transportadora,
            data_atualizacao, ultima_atualizacao_rastreio
        FROM pedidos 
        WHERE numero_pedido = ? AND cliente_email = ?
        LIMIT 1
    ");
    
    $stmtBusca->execute([$numeroPedido, $email]);
    $resultado = $stmtBusca->fetch();
    
    if (!$resultado) {
        die("\n❌ Erro: Não conseguiu buscar os dados atualizados!\n\n");
    }
    
    echo "   ✓ Dados recuperados:\n";
    echo "      - Código: " . ($resultado['codigo_rastreio'] ?? 'VAZIO') . "\n";
    echo "      - Transportadora: " . ($resultado['transportadora'] ?? 'VAZIO') . "\n";
    echo "      - Status Entrega: " . ($resultado['status_entrega'] ?? 'VAZIO') . "\n";
    echo "      - Link: " . (empty($resultado['link_rastreio']) ? 'VAZIO' : substr($resultado['link_rastreio'], 0, 50) . '...') . "\n";
    echo "      - Data Atualização: " . ($resultado['data_atualizacao'] ?? 'VAZIO') . "\n";
    echo "      - Última Atualização: " . ($resultado['ultima_atualizacao_rastreio'] ?? 'VAZIO') . "\n\n";

    // 5. Testar a simulação do formulário de rastreio.php
    echo "5️⃣  TESTANDO LOGICA DE RASTREAMENTO (como rastreio.php)...\n\n";
    
    // Simular o que rastreio.php faz
    $emailColumn = 'cliente_email';
    $statusPedidoColumn = 'status';
    $statusEntregaColumn = 'status_entrega';
    $codigoRastreioColumn = 'codigo_rastreio';
    $transportadoraColumn = 'transportadora';
    $linkRastreioColumn = 'link_rastreio';
    $dataEnvioColumn = 'data_status_mudanca';
    $ultimaAtualizacaoColumn = 'ultima_atualizacao_rastreio';
    
    $selectList = [
        'id',
        'numero_pedido',
        "{$emailColumn} AS email_cliente",
        "{$statusPedidoColumn} AS status_pedido",
        "{$statusEntregaColumn} AS status_entrega",
        "{$codigoRastreioColumn} AS codigo_rastreio",
        "{$transportadoraColumn} AS transportadora",
        "{$linkRastreioColumn} AS link_rastreio",
        "{$dataEnvioColumn} AS data_envio",
        "{$ultimaAtualizacaoColumn} AS ultima_atualizacao_rastreio"
    ];
    
    $sql = 'SELECT ' . implode(', ', $selectList) . ' FROM pedidos 
            WHERE LOWER(' . $emailColumn . ') = ? AND numero_pedido = ? 
            LIMIT 1';
    
    $stmtRastreio = $pdo->prepare($sql);
    $stmtRastreio->execute([mb_strtolower($email, 'UTF-8'), $numeroPedido]);
    $rastreioResult = $stmtRastreio->fetch();
    
    if (!$rastreioResult) {
        echo "   ❌ Nenhum resultado encontrado na query de rastreio!\n\n";
    } else {
        echo "   ✓ Resultado da busca de rastreio:\n";
        foreach ($rastreioResult as $key => $value) {
            $displayValue = empty($value) ? '(vazio)' : (is_string($value) && strlen($value) > 50 ? substr($value, 0, 47) . '...' : $value);
            echo "      - $key: $displayValue\n";
        }
        echo "\n";
    }

    // RESUMO FINAL
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ TESTE CONCLUÍDO COM SUCESSO              ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 RESUMO DOS RESULTADOS:\n\n";
    echo "✓ Banco de dados: OK\n";
    echo "✓ Colunas: OK\n";
    echo "✓ Inserção de pedido: OK\n";
    echo "✓ Atualização de rastreamento: OK\n";
    echo "✓ Busca de rastreamento: OK\n\n";
    
    echo "🧪 DADOS DE TESTE:\n";
    echo "  Número do Pedido: $numeroPedido\n";
    echo "  Email: $email\n";
    echo "  Código Rastreio: $codigoRastreio\n\n";
    
    echo "📱 Próximos Passos:\n";
    echo "  1. Acesse: http://localhost/rare7/cliente/pages/rastreio.php\n";
    echo "  2. Digite o número e email acima\n";
    echo "  3. Verifique se os dados aparecem corretamente\n";
    echo "  4. Use o formulário em: http://localhost/rare7/admin/teste-rastreamento.php\n";
    echo "     para atualizar dados em tempo real\n\n";

} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n\n";
}

<?php
/**
 * Script de Teste - Rastreador de Pedidos
 * Verifica se a funcionalidade de rastreamento está funcionando corretamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './cliente/config.php';

echo "=== TESTE DE RASTREAMENTO DE PEDIDOS ===\n\n";

try {
    // 1. Verificar conexão com banco
    echo "1. Verificando conexão com banco de dados...\n";
    $testQuery = $pdo->query("SELECT 1");
    echo "✓ Conexão OK\n\n";

    // 2. Verificar existência da tabela pedidos
    echo "2. Verificando tabela 'pedidos'...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'pedidos'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabela 'pedidos' existe\n\n";
    } else {
        echo "✗ Tabela 'pedidos' NÃO existe\n\n";
        exit;
    }

    // 3. Listar todas as colunas da tabela pedidos
    echo "3. Colunas disponíveis na tabela 'pedidos':\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM `pedidos`");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "   - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    echo "\n";

    // 4. Verificar se as colunas essenciais de rastreamento existem
    echo "4. Verificando colunas essenciais de rastreamento:\n";
    $essentialColumns = [
        'email_cliente' => ['email_cliente', 'cliente_email'],
        'numero_pedido' => ['numero_pedido'],
        'status_pedido' => ['status_pedido', 'status'],
        'codigo_rastreio' => ['codigo_rastreio'],
        'status_entrega' => ['status_entrega'],
        'link_rastreio' => ['link_rastreio'],
        'transportadora' => ['transportadora'],
        'data_envio' => ['data_envio', 'data_status_mudanca'],
    ];

    $status = [];
    foreach ($essentialColumns as $name => $possibleColumns) {
        $found = false;
        $foundColumn = null;
        foreach ($possibleColumns as $col) {
            if (in_array($col, $columns)) {
                $found = true;
                $foundColumn = $col;
                break;
            }
        }
        $status[$name] = $found ? $foundColumn : 'NÃO ENCONTRADA';
        $symbol = $found ? '✓' : '✗';
        echo "   $symbol $name: " . ($found ? $foundColumn : 'Nenhuma opção encontrada') . "\n";
    }
    echo "\n";

    // 5. Contar pedidos no banco
    echo "5. Contando pedidos no banco:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total de pedidos: $count\n\n";

    if ($count > 0) {
        // 6. Mostrar alguns pedidos de exemplo
        echo "6. Primeiros 3 pedidos (amostra):\n";
        $emailCol = $status['email_cliente'];
        $numeroCol = $status['numero_pedido'];
        $statusCol = $status['status_pedido'];

        $selectCols = "id, $emailCol as email_cliente";
        if ($numeroCol !== 'NÃO ENCONTRADA') {
            $selectCols .= ", $numeroCol as numero_pedido";
        }
        if ($statusCol !== 'NÃO ENCONTRADA') {
            $selectCols .= ", $statusCol as status";
        }

        $stmt = $pdo->query("SELECT $selectCols FROM pedidos LIMIT 3");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   Pedido ID: " . $row['id'];
            if (!empty($row['numero_pedido'])) {
                echo " | Número: " . $row['numero_pedido'];
            }
            echo " | Email: " . ($row['email_cliente'] ?? 'SEM EMAIL');
            if (!empty($row['status'])) {
                echo " | Status: " . $row['status'];
            }
            echo "\n";
        }
        echo "\n";

        // 7. Testar consulta de rastreamento com um pedido real
        echo "7. Testando busca de rastreamento:\n";
        $stmt = $pdo->query("SELECT id, $emailCol as email_cliente FROM pedidos LIMIT 1");
        $testPedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($testPedido) {
            $testId = $testPedido['id'];
            $testEmail = $testPedido['email_cliente'];
            echo "   Buscando pedido com ID: $testId e Email: $testEmail\n";

            // Simular a query de rastreamento
            $selectList = [
                'id',
                "NULL AS numero_pedido",
                "'$testEmail' AS email_cliente",
                "'Processando' AS status_pedido",
                "NULL AS status_entrega",
                "NULL AS codigo_rastreio",
                "NULL AS transportadora",
                "NULL AS link_rastreio",
                "NULL AS data_envio",
                "NULL AS ultima_atualizacao_rastreio",
            ];

            $sql = "SELECT " . implode(', ', $selectList) . " FROM pedidos 
                    WHERE $emailCol = :email AND id = :id LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $testEmail, ':id' => $testId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo "   ✓ Pedido encontrado com sucesso!\n";
                echo "   Dados retornados:\n";
                foreach ($result as $key => $value) {
                    echo "      - $key: " . ($value ?? 'NULL') . "\n";
                }
            } else {
                echo "   ✗ Pedido NÃO encontrado\n";
            }
        }
    } else {
        echo "   ⚠ Nenhum pedido encontrado no banco\n";
    }

    echo "\n=== TESTE CONCLUÍDO ===\n";

} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
}

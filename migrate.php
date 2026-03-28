<?php
/**
 * Scripts de Migração de Banco de Dados
 * Adiciona colunas faltantes para rastreamento
 */

require_once './cliente/config.php';

echo "=== MIGRANDO BANCO DE DADOS ===\n\n";

try {
    // Ler arquivo SQL
    $sqlFile = './admin/sql/adicionar_colunas_rastreamento.sql';
    if (!file_exists($sqlFile)) {
        die("Arquivo SQL não encontrado: $sqlFile\n");
    }

    $sqlContent = file_get_contents($sqlFile);
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        fn($stmt) => !empty($stmt) && strpos($stmt, '--') !== 0
    );

    $executedCount = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            echo "✓ Comando executado:\n   " . substr($statement, 0, 80) . "...\n\n";
            $executedCount++;
        } catch (PDOException $e) {
            echo "✗ Erro ao executar:\n   " . substr($statement, 0, 80) . "...\n";
            echo "   Motivo: " . $e->getMessage() . "\n\n";
        }
    }

    echo "=== RESUMO ===\n";
    echo "✓ $executedCount comando(s) executado(s) com sucesso\n\n";

    // Verificar se as colunas foram adicionadas
    echo "=== VERIFICANDO RESULTADO ===\n\n";
    echo "Colunas atuais da tabela pedidos:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM pedidos");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "  - " . $row['Field'] . "\n";
    }

    echo "\n=== VERIFICAÇÃO FINAL ===\n";
    $checks = [
        'numero_pedido' => in_array('numero_pedido', $columns),
        'status_entrega' => in_array('status_entrega', $columns),
        'transportadora' => in_array('transportadora', $columns),
    ];

    foreach ($checks as $col => $exists) {
        echo ($exists ? '✓' : '✗') . " Coluna '$col': " . ($exists ? 'OK' : 'FALTA') . "\n";
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

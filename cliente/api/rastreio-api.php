<?php
/**
 * API de Rastreamento de Pedidos
 * Permite atualizar dados de rastreamento
 */

ob_start();
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json; charset=utf-8');
ob_clean();

require_once '../config.php';

function jsonResponse($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método não permitido');
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$input = json_decode(file_get_contents('php://input'), true);

if (!$input && $_POST) {
    $input = $_POST;
}

if (!$input) {
    jsonResponse(false, 'Dados inválidos');
}

try {
    // ===== ATUALIZAR RASTREAMENTO =====
    if ($action === 'atualizar_rastreio') {
        $numeroPedido = trim((string) ($input['numero_pedido'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')), 'UTF-8');
        
        if (empty($numeroPedido) || empty($email)) {
            jsonResponse(false, 'Número do pedido e e-mail são obrigatórios');
        }
        
        // Remover # se houver
        $numeroPedido = str_replace('#', '', $numeroPedido);
        
        // Buscar pedido por ID
        $stmt = $pdo->prepare("
            SELECT id FROM pedidos 
            WHERE id = ? AND cliente_email = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$numeroPedido, $email]);
        $pedido = $stmt->fetch();
        
        if (!$pedido) {
            jsonResponse(false, 'Pedido não encontrado');
        }
        
        $pedidoId = $pedido['id'];
        
        // Preparar campos a atualizar
        $updates = [];
        $params = [];
        
        if (!empty($input['codigo_rastreio'])) {
            $updates[] = 'codigo_rastreio = ?';
            $params[] = trim((string) $input['codigo_rastreio']);
        }
        
        if (!empty($input['link_rastreio'])) {
            $updates[] = 'link_rastreio = ?';
            $params[] = trim((string) $input['link_rastreio']);
        }
        
        if (!empty($input['transportadora'])) {
            $updates[] = 'transportadora = ?';
            $params[] = trim((string) $input['transportadora']);
        }
        
        if (!empty($input['status_entrega'])) {
            $updates[] = 'status_entrega = ?';
            $params[] = trim((string) $input['status_entrega']);
        }
        
        if (!empty($input['status_pedido'])) {
            $updates[] = 'status = ?';
            $params[] = trim((string) $input['status_pedido']);
        }
        
        // Adicionar data de atualização
        $updates[] = 'data_atualizacao = NOW()';
        $updates[] = 'ultima_atualizacao_rastreio = NOW()';
        
        if (empty($updates) || count($updates) <= 2) {
            jsonResponse(false, 'Nenhum campo de rastreamento fornecido');
        }
        
        $params[] = $pedidoId;
        
        $sql = "UPDATE pedidos SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        jsonResponse(true, 'Rastreamento atualizado com sucesso', [
            'pedido_id' => $pedidoId,
            'numero_pedido' => '#' . str_pad($pedidoId, 6, '0', STR_PAD_LEFT)
        ]);
    }
    
    // ===== OBTER RASTREAMENTO =====
    elseif ($action === 'obter_rastreio') {
        $numeroPedido = trim((string) ($input['numero_pedido'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')), 'UTF-8');
        
        if (empty($numeroPedido) || empty($email)) {
            jsonResponse(false, 'Número do pedido e e-mail são obrigatórios');
        }
        
        // Remover # se houver
        $numeroPedido = str_replace('#', '', $numeroPedido);
        
        $stmt = $pdo->prepare("
            SELECT 
                id,
                status,
                status_entrega,
                codigo_rastreio,
                link_rastreio,
                transportadora,
                data_envio,
                data_status_mudanca,
                data_atualizacao,
                ultima_atualizacao_rastreio
            FROM pedidos 
            WHERE id = ? AND cliente_email = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$numeroPedido, $email]);
        $rastreio = $stmt->fetch();
        
        if (!$rastreio) {
            jsonResponse(false, 'Pedido não encontrado');
        }
        
        jsonResponse(true, 'Rastreamento recuperado', $rastreio);
    }
    
    else {
        jsonResponse(false, 'Ação inválida');
    }
    
} catch (Exception $e) {
    error_log('Erro na API de rastreamento: ' . $e->getMessage());
    jsonResponse(false, 'Erro ao processar requisição: ' . $e->getMessage());
}

<?php
/**
 * Webhook de Notificações do Mercado Pago
 * Recebe updates de status de pagamento e atualiza pedidos
 */

// ===== RESPOSTA IMEDIATA (CRÍTICO PARA MERCADO PAGO) =====
// Técnica para desconectar cliente ANTES de processar (funciona em mod_php e PHP-FPM)
ignore_user_abort(true);
set_time_limit(0);

$response = '{"status":"ok"}';
$responseLength = strlen($response);

http_response_code(200);
header('Content-Type: application/json');
header('Content-Length: ' . $responseLength);
header('Connection: close');

echo $response;

// Forçar envio imediato ao cliente
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // PHP-FPM
}
ob_flush();
flush();

// Cliente desconectado - script continua em background
// Configurações
date_default_timezone_set('America/Sao_Paulo');
error_reporting(0);
ini_set('display_errors', 0);

// ===== LEITURA BRUTA DO PAYLOAD =====
$json_raw = file_get_contents('php://input');
$debugLogPath = __DIR__ . '/debug_webhook.txt';

// ===== LOG DE DEBUG TOTAL =====
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$tamanho = strlen($json_raw);
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? 'não especificado';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'não especificado';

// Headers recebidos
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        $headers[] = $key . ': ' . $value;
    }
}

$logEntry = "\n" . str_repeat("=", 80) . "\n";
$logEntry .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
$logEntry .= "Método: {$metodo}\n";
$logEntry .= "Content-Type: {$contentType}\n";
$logEntry .= "User-Agent: {$userAgent}\n";
$logEntry .= "Query String: " . ($queryString ?: '(vazio)') . "\n";
$logEntry .= "Tamanho Payload: {$tamanho} bytes\n";
$logEntry .= "Payload Bruto RAW: " . ($json_raw ?: '(COMPLETAMENTE VAZIO)') . "\n";
$logEntry .= "Headers HTTP:\n";
foreach ($headers as $header) {
    $logEntry .= "  - {$header}\n";
}
$logEntry .= str_repeat("-", 80) . "\n";

file_put_contents($debugLogPath, $logEntry, FILE_APPEND);

// ===== CONEXÃO COM BANCO DE DADOS =====
try {
    $dsn = "mysql:host=127.0.0.1;dbname=adm_rare;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '-03:00'"
    ]);
} catch (PDOException $e) {
    file_put_contents($debugLogPath, "ERRO DB: " . $e->getMessage() . "\n", FILE_APPEND);
    exit;
}

// ===== BUSCAR ACCESS TOKEN DO BANCO =====
try {
    $stmt = $pdo->query("SELECT secret_key FROM payment_settings WHERE gateway_active = 1 LIMIT 1");
    $settings = $stmt->fetch();
    
    if (!$settings || empty($settings['secret_key'])) {
        file_put_contents($debugLogPath, "ERRO: Access token não encontrado no banco\n", FILE_APPEND);
        exit;
    }
    
    $access_token = $settings['secret_key'];
    file_put_contents($debugLogPath, "✅ Access token carregado\n", FILE_APPEND);
    
} catch (PDOException $e) {
    file_put_contents($debugLogPath, "ERRO ao buscar token: " . $e->getMessage() . "\n", FILE_APPEND);
    exit;
}

// ===== RESPOSTA A TESTES DO PAINEL (GET REQUEST) =====
if ($metodo === 'GET') {
    file_put_contents($debugLogPath, "ℹ️ Requisição GET detectada (teste do painel MP)\n", FILE_APPEND);
    file_put_contents($debugLogPath, "✅ Respondendo 200 OK ao teste\n", FILE_APPEND);
    file_put_contents($debugLogPath, str_repeat("=", 80) . "\n\n", FILE_APPEND);
    exit;
}

// ===== PROCESSAR NOTIFICAÇÃO =====
try {
    file_put_contents($debugLogPath, "🔄 Iniciando processamento...\n", FILE_APPEND);
    
    // ===== TENTATIVA 1: DECODIFICAR JSON DO BODY =====
    $data = null;
    $payment_id = null;
    
    if (!empty($json_raw) && trim($json_raw) !== '') {
        file_put_contents($debugLogPath, "📥 Tentando decodificar JSON do body...\n", FILE_APPEND);
        $data = json_decode($json_raw, true);
        
        if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
            file_put_contents($debugLogPath, "✅ JSON decodificado com sucesso\n", FILE_APPEND);
            file_put_contents($debugLogPath, "📦 Estrutura JSON: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            // Verificar tipo de notificação
            $tipo = $data['type'] ?? $data['topic'] ?? $data['action'] ?? null;
            file_put_contents($debugLogPath, "🔔 Tipo: " . ($tipo ?: 'não especificado') . "\n", FILE_APPEND);
            
            // Extrair payment_id do JSON
            if (isset($data['data']['id'])) {
                $payment_id = $data['data']['id'];
                file_put_contents($debugLogPath, "💳 Payment ID via data.id: {$payment_id}\n", FILE_APPEND);
            } elseif (isset($data['id'])) {
                $payment_id = $data['id'];
                file_put_contents($debugLogPath, "💳 Payment ID via id: {$payment_id}\n", FILE_APPEND);
            } elseif (isset($data['resource'])) {
                // Formato: {"resource": "/v1/payments/123456"}
                if (preg_match('/\/payments\/(\d+)/', $data['resource'], $matches)) {
                    $payment_id = $matches[1];
                    file_put_contents($debugLogPath, "💳 Payment ID via resource: {$payment_id}\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents($debugLogPath, "❌ JSON inválido ou vazio: " . json_last_error_msg() . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents($debugLogPath, "⚠️ Body completamente vazio (0 bytes)\n", FILE_APPEND);
    }
    
    // ===== TENTATIVA 2: FALLBACK PARA $_GET (MERCADO PAGO PODE ENVIAR ID NA URL) =====
    if (empty($payment_id)) {
        file_put_contents($debugLogPath, "🔄 Tentando extrair ID da URL (\$_GET)...\n", FILE_APPEND);
        
        // Opções comuns de parâmetros GET do MP
        if (isset($_GET['id'])) {
            $payment_id = $_GET['id'];
            file_put_contents($debugLogPath, "💳 Payment ID via \$_GET['id']: {$payment_id}\n", FILE_APPEND);
        } elseif (isset($_GET['data_id'])) {
            $payment_id = $_GET['data_id'];
            file_put_contents($debugLogPath, "💳 Payment ID via \$_GET['data_id']: {$payment_id}\n", FILE_APPEND);
        } elseif (isset($_GET['payment_id'])) {
            $payment_id = $_GET['payment_id'];
            file_put_contents($debugLogPath, "💳 Payment ID via \$_GET['payment_id']: {$payment_id}\n", FILE_APPEND);
        } else {
            file_put_contents($debugLogPath, "❌ Nenhum ID encontrado em \$_GET\n", FILE_APPEND);
            file_put_contents($debugLogPath, "   \$_GET completo: " . json_encode($_GET) . "\n", FILE_APPEND);
        }
    }
    
    // ===== VALIDAR SE CONSEGUIU EXTRAIR PAYMENT_ID =====
    if (empty($payment_id)) {
        file_put_contents($debugLogPath, "❌ FALHA TOTAL: Não foi possível extrair Payment ID\n", FILE_APPEND);
        file_put_contents($debugLogPath, "   JSON body: " . ($json_raw ?: '(vazio)') . "\n", FILE_APPEND);
        file_put_contents($debugLogPath, "   Query string: " . ($queryString ?: '(vazio)') . "\n", FILE_APPEND);
        file_put_contents($debugLogPath, "   Requisição rejeitada.\n", FILE_APPEND);
        file_put_contents($debugLogPath, str_repeat("=", 80) . "\n\n", FILE_APPEND);
        exit;
    }
    
    file_put_contents($debugLogPath, "✅ Payment ID confirmado: {$payment_id}\n", FILE_APPEND);
    
    // ===== VERIFICAR SE É TESTE DO PAINEL MP (ID GENÉRICO 123456) =====
    if ($payment_id === '123456' || $payment_id === 123456) {
        file_put_contents($debugLogPath, "⚠️ Teste de pulsação do painel detectado (ID genérico). Ignorando consulta à API.\n", FILE_APPEND);
        file_put_contents($debugLogPath, str_repeat("=", 80) . "\n\n", FILE_APPEND);
        exit;
    }
    
    // ===== CONSULTAR STATUS DO PAGAMENTO NA API DO MP =====
    file_put_contents($debugLogPath, "🔍 Consultando API do Mercado Pago...\n", FILE_APPEND);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.mercadopago.com/v1/payments/{$payment_id}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false, // ⚠️ Remover em produção
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        file_put_contents($debugLogPath, "❌ Erro cURL: {$curl_error}\n", FILE_APPEND);
        exit;
    }
    
    file_put_contents($debugLogPath, "📡 Resposta MP API (HTTP {$http_code})\n", FILE_APPEND);
    file_put_contents($debugLogPath, "   Tamanho: " . strlen($response) . " bytes\n", FILE_APPEND);
    
    if ($http_code !== 200) {
        file_put_contents($debugLogPath, "❌ Erro na API do MP: HTTP {$http_code}\n", FILE_APPEND);
        file_put_contents($debugLogPath, "   Resposta: {$response}\n", FILE_APPEND);
        exit;
    }
    
    $payment_data = json_decode($response, true);
    
    if (empty($payment_data)) {
        file_put_contents($debugLogPath, "❌ Resposta da API vazia ou inválida\n", FILE_APPEND);
        exit;
    }
    
    file_put_contents($debugLogPath, "✅ Pagamento recuperado da API com sucesso\n", FILE_APPEND);
    
    // ===== EXTRAIR DADOS DO PAGAMENTO =====
    $mp_status = $payment_data['status'] ?? 'unknown';
    $status_detail = $payment_data['status_detail'] ?? '';
    $valor_total = $payment_data['transaction_amount'] ?? 0;
    $parcelas = $payment_data['installments'] ?? 1;
    $valor_parcela = isset($payment_data['transaction_details']['installment_amount']) 
        ? $payment_data['transaction_details']['installment_amount'] 
        : round($valor_total / $parcelas, 2);
    
    file_put_contents($debugLogPath, "💰 Dados do pagamento:\n", FILE_APPEND);
    file_put_contents($debugLogPath, "   Status MP: {$mp_status}\n", FILE_APPEND);
    file_put_contents($debugLogPath, "   Status Detail: {$status_detail}\n", FILE_APPEND);
    file_put_contents($debugLogPath, "   Valor Total: R$ {$valor_total}\n", FILE_APPEND);
    file_put_contents($debugLogPath, "   Parcelas: {$parcelas}x de R$ {$valor_parcela}\n", FILE_APPEND);
    
    // ===== MAPEAR STATUS DO MP PARA STATUS DO SISTEMA =====
    $status_pedido = 'Pagamento Pendente';
    
    if ($mp_status === 'approved') {
        $status_pedido = 'Pedido Recebido';
    } elseif ($mp_status === 'rejected' || $mp_status === 'cancelled') {
        $status_pedido = 'Cancelado';
    } elseif ($mp_status === 'pending' || $mp_status === 'in_process' || $mp_status === 'in_mediation') {
        $status_pedido = 'Pagamento Pendente';
    } elseif ($mp_status === 'refunded' || $mp_status === 'charged_back') {
        $status_pedido = 'Reembolsado';
    }
    
    file_put_contents($debugLogPath, "🔄 Mapeamento de status: {$mp_status} → {$status_pedido}\n", FILE_APPEND);
    
    // ===== ATUALIZAR PEDIDO NO BANCO =====
    try {
        // Buscar pedido pelo payment_id
        $stmtPedido = $pdo->prepare("
            SELECT id, status 
            FROM pedidos 
            WHERE mercadopago_payment_id = ? 
            LIMIT 1
        ");
        $stmtPedido->execute([$payment_id]);
        $pedido = $stmtPedido->fetch();
        
        if (!$pedido) {
            file_put_contents($debugLogPath, "⚠️ Pedido não encontrado para payment_id: {$payment_id}\n", FILE_APPEND);
            file_put_contents($debugLogPath, "   Isso pode significar que o pagamento foi feito fora do sistema ou em outro ambiente\n", FILE_APPEND);
            exit;
        }
        
        file_put_contents($debugLogPath, "📦 Pedido encontrado: ID #{$pedido['id']} | Status atual: {$pedido['status']}\n", FILE_APPEND);
        
        // Atualizar pedido
        $stmtUpdate = $pdo->prepare("
            UPDATE pedidos 
            SET mercadopago_status = ?,
                status = ?,
                parcelas = ?,
                valor_total = ?,
                valor_parcela = ?
            WHERE id = ?
        ");
        
        $resultado = $stmtUpdate->execute([
            $mp_status,
            $status_pedido,
            $parcelas,
            $valor_total,
            $valor_parcela,
            $pedido['id']
        ]);
        
        if ($resultado) {
            file_put_contents($debugLogPath, "✅ Pedido #{$pedido['id']} atualizado com sucesso!\n", FILE_APPEND);
            file_put_contents($debugLogPath, "   Status: {$pedido['status']} → {$status_pedido}\n", FILE_APPEND);
            file_put_contents($debugLogPath, "   Valor: R$ {$valor_total} em {$parcelas}x de R$ {$valor_parcela}\n", FILE_APPEND);
        } else {
            file_put_contents($debugLogPath, "❌ Falha ao atualizar pedido\n", FILE_APPEND);
        }
        
    } catch (PDOException $e) {
        file_put_contents($debugLogPath, "❌ Erro ao atualizar pedido: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    file_put_contents($debugLogPath, "\n✅ Webhook processado com sucesso!\n", FILE_APPEND);
    file_put_contents($debugLogPath, str_repeat("=", 80) . "\n\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents($debugLogPath, "❌ Erro geral: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($debugLogPath, str_repeat("=", 80) . "\n\n", FILE_APPEND);
}

exit;

<?php
/**
 * API de Processamento de Pedidos
 * Recebe dados do checkout e cria o pedido no banco
 */

// Capturar qualquer output indesejado
ob_start();

// Suprimir warnings que podem quebrar JSON
error_reporting(E_ERROR | E_PARSE);

// session_start(); // REMOVIDO: não necessário em API JSON
header('Content-Type: application/json; charset=utf-8');

// Limpar buffer de output antes de enviar JSON
ob_clean();

require_once '../config.php';

// Função para resposta JSON
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

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, 'Dados inválidos');
}

// Validar dados obrigatórios
$cliente = $input['cliente'] ?? [];
$endereco = $input['endereco'] ?? [];
$carrinho = $input['carrinho'] ?? [];
$pagamento = $input['pagamento'] ?? [];

if (empty($cliente['nome']) || empty($cliente['email']) || empty($cliente['telefone'])) {
    jsonResponse(false, 'Dados do cliente incompletos');
}

if (empty($endereco['cep']) || empty($endereco['rua']) || empty($endereco['cidade'])) {
    jsonResponse(false, 'Endereço incompleto');
}

if (empty($carrinho['items']) || count($carrinho['items']) === 0) {
    jsonResponse(false, 'Carrinho vazio');
}

if (empty($pagamento['forma'])) {
    jsonResponse(false, 'Forma de pagamento não selecionada');
}

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // ===== CRIAR OU ATUALIZAR CLIENTE =====
    $clienteId = null;
    
    if (isset($cliente['id']) && $cliente['id'] > 0) {
        // Cliente logado - usar ID existente
        $clienteId = $cliente['id'];
        
        // Atualizar dados se necessário (usando apenas campos garantidos)
        try {
            $stmtUpdate = $pdo->prepare("
                UPDATE clientes SET 
                    nome = ?, 
                    telefone = ?,
                    endereco = ?,
                    cidade = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $cliente['nome'],
                $cliente['telefone'],
                $endereco['rua'] . ', ' . $endereco['numero'] . ($endereco['complemento'] ? ' - ' . $endereco['complemento'] : ''),
                $endereco['cidade'],
                $clienteId
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar cliente: " . $e->getMessage());
        }
        
    } else {
        // Cliente não logado - verificar se já existe por email
        $stmtCheck = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmtCheck->execute([$cliente['email']]);
        $clienteExistente = $stmtCheck->fetch();
        
        if ($clienteExistente) {
            $clienteId = $clienteExistente['id'];
        } else {
            // Criar novo cliente (usando apenas campos garantidos)
            $stmtCliente = $pdo->prepare("
                INSERT INTO clientes (nome, email, telefone, endereco, cidade)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtCliente->execute([
                $cliente['nome'],
                $cliente['email'],
                $cliente['telefone'],
                $endereco['rua'] . ', ' . $endereco['numero'] . ($endereco['complemento'] ? ' - ' . $endereco['complemento'] : ''),
                $endereco['cidade']
            ]);
            $clienteId = $pdo->lastInsertId();
        }
    }
    
    // ===== CALCULAR VALORES =====
    $subtotal = 0;
    foreach ($carrinho['items'] as $item) {
        $subtotal += ($item['price'] * $item['qty']);
    }
    
    $desconto = $carrinho['desconto'] ?? 0;
    $valorFrete = 0;
    $freteGratis = false;
    
    if (isset($carrinho['frete'])) {
        $freteGratis = $carrinho['frete']['gratis'] ?? false;
        $valorFrete = $freteGratis ? 0 : ($carrinho['frete']['valor'] ??  0);
    }
    
    $valorTotal = $subtotal - $desconto + $valorFrete;
    
    // ===== DEFINIR MODO DE PAGAMENTO =====
    $isTransparente = isset($pagamento['transparente']) && $pagamento['transparente'] === true;
    
    // ===== CRIAR PEDIDO =====
    // Obter número de parcelas (se for pagamento transparente)
    $parcelas = 1; // Padrão: à vista
    if ($isTransparente && isset($pagamento['payment_data']['installments'])) {
        $parcelas = (int)$pagamento['payment_data']['installments'];
    }
    
    // Calcular valor de cada parcela
    $valorParcela = round($valorTotal / $parcelas, 2);
    
    $stmtPedido = $pdo->prepare("
        INSERT INTO pedidos (
            cliente_id, 
            valor_subtotal,
            valor_desconto,
            valor_frete,
            valor_total,
            forma_pagamento,
            parcelas,
            valor_parcela,
            status,
            endereco_entrega,
            cep,
            cidade,
            estado,
            cupom_codigo,
            observacoes,
            data_pedido
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $enderecoCompleto = sprintf(
        "%s, %s%s - %s",
        $endereco['rua'],
        $endereco['numero'],
        $endereco['complemento'] ? ' (' . $endereco['complemento'] . ')' : '',
        $endereco['bairro']
    );
    
    $stmtPedido->execute([
        $clienteId,
        $subtotal,
        $desconto,
        $valorFrete,
        $valorTotal,
        $pagamento['forma'],
        $parcelas,
        $valorParcela,
        'Pagamento Pendente', // Status inicial com nome do status do fluxo
        $enderecoCompleto,
        $endereco['cep'],
        $endereco['cidade'],
        $endereco['estado'],
        $carrinho['cupom']['codigo'] ?? null,
        'Pedido realizado via checkout online'
    ]);
    
    $pedidoId = $pdo->lastInsertId();
    
    // ===== CRIAR ITENS DO PEDIDO =====
    $stmtItem = $pdo->prepare("
        INSERT INTO itens_pedido (
            pedido_id,
            produto_id,
            variacao_id,
            quantidade,
            preco_unitario,
            nome_produto
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($carrinho['items'] as $item) {
        $stmtItem->execute([
            $pedidoId,
            $item['id'],
            $item['variacao_id'] ?? null,
            $item['qty'],
            $item['price'],
            $item['name']
        ]);
        
        // ===== BAIXAR ESTOQUE =====
        if (isset($item['variacao_id']) && $item['variacao_id']) {
            // Baixar estoque da variação
            $stmtEstoque = $pdo->prepare("
                UPDATE produto_variacoes 
                SET estoque = estoque - ? 
                WHERE id = ?
            ");
            $stmtEstoque->execute([$item['qty'], $item['variacao_id']]);
        } else {
            // Baixar estoque do produto principal
            $stmtEstoque = $pdo->prepare("
                UPDATE produtos 
                SET estoque = estoque - ? 
                WHERE id = ?
            ");
            $stmtEstoque->execute([$item['qty'], $item['id']]);
        }
    }
    
    // ===== REGISTRAR USO DO CUPOM (se houver) =====
    if (isset($carrinho['cupom']['codigo'])) {
        try {
            $stmtCupom = $pdo->prepare("
                UPDATE cupons 
                SET quantidade_usada = quantidade_usada + 1 
                WHERE codigo = ?
            ");
            $stmtCupom->execute([$carrinho['cupom']['codigo']]);
        } catch (Exception $e) {
            // Ignorar erro de cupom
            error_log("Erro ao atualizar cupom: " . $e->getMessage());
        }
    }
    
    // Commit da transação
    $pdo->commit();
    
    // ===== INTEGRAÇÃO COM MERCADO PAGO =====
    $init_point = null;
    $payment_id = null;
    $payment_status = null;
    $payment_message = null;
    // $isTransparente já definido acima (linha ~142)
    
    // ===== DETECTAR PROTOCOLO (HTTPS/HTTP) =====
    // Considerar proxies reversos (ngrok, Cloudflare Tunnel)
    $protocol = 'http';
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        // Proxy/Tunnel envia o protocolo original
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocol = 'https';
    } elseif ($_SERVER['SERVER_PORT'] == 443) {
        $protocol = 'https';
    } elseif (strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false || 
              strpos($_SERVER['HTTP_HOST'], 'trycloudflare.com') !== false) {
        // Túneis sempre usam HTTPS externamente
        $protocol = 'https';
    }
    
    try {
        // Buscar credenciais do gateway de pagamento
        $stmtGateway = $pdo->prepare("
            SELECT 
                gateway_provider,
                gateway_active,
                secret_key,
                environment
            FROM payment_settings 
            WHERE id = 1 AND gateway_active = 1
        ");
        $stmtGateway->execute();
        $gateway = $stmtGateway->fetch(PDO::FETCH_ASSOC);
        
        if ($gateway && $gateway['gateway_provider'] === 'mercadopago' && !empty($gateway['secret_key'])) {
            
            // ===== PIX NATIVO (CHECKOUT TRANSPARENTE) =====
            if ($isTransparente && isset($pagamento['payment_method_id']) && $pagamento['payment_method_id'] === 'pix') {
                error_log("Processando pagamento Pix nativo...");
                
                // Preparar dados do pagamento Pix
                $payment_data = [
                    'transaction_amount' => round(floatval($valorTotal), 2),
                    'description' => 'Pedido #' . $pedidoId,
                    'payment_method_id' => 'pix',
                    'payer' => [
                        'email' => $cliente['email'],
                        'first_name' => explode(' ', $cliente['nome'])[0],
                        'last_name' => implode(' ', array_slice(explode(' ', $cliente['nome']), 1)) ?: explode(' ', $cliente['nome'])[0],
                        'identification' => [
                            'type' => 'CPF',
                            'number' => preg_replace('/[^0-9]/', '', $cliente['cpf_cnpj'] ?? '')
                        ]
                    ],
                    'external_reference' => (string)$pedidoId,
                    'statement_descriptor' => 'PEDIDO' . $pedidoId,
                    'notification_url' => $protocol . '://' . $_SERVER['HTTP_HOST'] . '/admin-teste/webhooks/notificacao.php'
                ];
                
                // Log do JSON sendo enviado
                error_log("Mercado Pago Pix Data: " . json_encode($payment_data, JSON_PRETTY_PRINT));
                
                // Endpoint da API de payments
                $api_url = 'https://api.mercadopago.com/v1/payments';
                
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $gateway['secret_key'],
                    'Content-Type: application/json',
                    'X-Idempotency-Key: ' . uniqid('pix_' . $pedidoId . '_', true)
                ]);
                
                // SSL para localhost/Tunnel
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                // Log da resposta
                error_log("Mercado Pago Pix API Response - HTTP $http_code: " . $response);
                
                if ($curl_error) {
                    error_log("Mercado Pago cURL Error: " . $curl_error);
                    throw new Exception("Erro de conexão com Mercado Pago: " . $curl_error);
                }
                
                $resultado = json_decode($response, true);
                
                if ($http_code === 201 && isset($resultado['id'])) {
                    $payment_id = $resultado['id'];
                    $payment_status = $resultado['status'];
                    
                    // Extrair QR Code do Pix
                    $qr_code = null;
                    $qr_code_base64 = null;
                    
                    if (isset($resultado['point_of_interaction']['transaction_data'])) {
                        $qr_code = $resultado['point_of_interaction']['transaction_data']['qr_code'] ?? null;
                        $qr_code_base64 = $resultado['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;
                    }
                    
                    error_log("✅ Pix gerado - Payment ID: $payment_id | Status: $payment_status");
                    
                    if (!$qr_code || !$qr_code_base64) {
                        error_log("⚠️ QR Code não retornado pelo Mercado Pago");
                        throw new Exception("QR Code do Pix não foi gerado. Tente novamente.");
                    }
                    
                    // Atualizar pedido
                    $stmtUpdate = $pdo->prepare("
                        UPDATE pedidos 
                        SET mercadopago_payment_id = ?,
                            mercadopago_status = ?,
                            status = 'Pagamento Pendente'
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$payment_id, $payment_status, $pedidoId]);
                    
                    // Retornar QR Code para o frontend
                    jsonResponse(true, 'Pix gerado com sucesso', [
                        'pedido_id' => $pedidoId,
                        'payment_id' => $payment_id,
                        'pix_qr_code' => $qr_code,
                        'pix_qr_code_base64' => $qr_code_base64
                    ]);
                } else {
                    $error_message = $resultado['message'] ?? 'Erro desconhecido';
                    error_log("Mercado Pago Pix Error (HTTP $http_code): $error_message");
                    throw new Exception("Erro ao gerar Pix: " . $error_message);
                }
            
            // ===== BOLETO NATIVO (CHECKOUT TRANSPARENTE) =====
            } elseif ($isTransparente && isset($pagamento['payment_method_id']) && $pagamento['payment_method_id'] === 'bolbancario') {
                error_log("Processando pagamento Boleto nativo...");
                
                // Preparar dados do pagamento Boleto
                $payment_data = [
                    'transaction_amount' => round(floatval($valorTotal), 2),
                    'description' => 'Pedido #' . $pedidoId,
                    'payment_method_id' => 'bolbancario',
                    'payer' => [
                        'email' => $cliente['email'],
                        'first_name' => explode(' ', $cliente['nome'])[0],
                        'last_name' => implode(' ', array_slice(explode(' ', $cliente['nome']), 1)) ?: explode(' ', $cliente['nome'])[0],
                        'identification' => [
                            'type' => 'CPF',
                            'number' => preg_replace('/[^0-9]/', '', $cliente['cpf_cnpj'] ?? '')
                        ],
                        'address' => [
                            'zip_code' => preg_replace('/[^0-9]/', '', $endereco['cep']),
                            'street_name' => $endereco['rua'],
                            'street_number' => intval($endereco['numero']),
                            'neighborhood' => $endereco['bairro'],
                            'city' => $endereco['cidade'],
                            'federal_unit' => $endereco['estado']
                        ]
                    ],
                    'external_reference' => (string)$pedidoId,
                    'notification_url' => $protocol . '://' . $_SERVER['HTTP_HOST'] . '/admin-teste/webhooks/notificacao.php'
                ];
                
                // Log do JSON sendo enviado
                error_log("Mercado Pago Boleto Data: " . json_encode($payment_data, JSON_PRETTY_PRINT));
                
                // Endpoint da API de payments
                $api_url = 'https://api.mercadopago.com/v1/payments';
                
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $gateway['secret_key'],
                    'Content-Type: application/json',
                    'X-Idempotency-Key: ' . uniqid('boleto_' . $pedidoId . '_', true)
                ]);
                
                // SSL para localhost/Tunnel
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                // Log da resposta
                error_log("Mercado Pago Boleto API Response - HTTP $http_code: " . $response);
                
                if ($curl_error) {
                    error_log("Mercado Pago cURL Error: " . $curl_error);
                    throw new Exception("Erro de conexão com Mercado Pago: " . $curl_error);
                }
                
                $resultado = json_decode($response, true);
                
                if ($http_code === 201 && isset($resultado['id'])) {
                    $payment_id = $resultado['id'];
                    $payment_status = $resultado['status'];
                    
                    // Extrair dados do boleto
                    $boleto_url = null;
                    $boleto_barcode = null;
                    $boleto_digitable_line = null;
                    $boleto_due_date = null;
                    
                    if (isset($resultado['transaction_details'])) {
                        $boleto_url = $resultado['transaction_details']['external_resource_url'] ?? null;
                        $boleto_barcode = $resultado['barcode']['content'] ?? null;
                        $boleto_digitable_line = $resultado['transaction_details']['digitable_line'] ?? null;
                        
                        // Data de vencimento
                        if (isset($resultado['date_of_expiration'])) {
                            $boleto_due_date = date('d/m/Y', strtotime($resultado['date_of_expiration']));
                        }
                    }
                    
                    error_log("✅ Boleto gerado - Payment ID: $payment_id | Status: $payment_status");
                    
                    if (!$boleto_url) {
                        error_log("⚠️ URL do boleto não retornada pelo Mercado Pago");
                        throw new Exception("Boleto não foi gerado. Tente novamente.");
                    }
                    
                    // Atualizar pedido
                    $stmtUpdate = $pdo->prepare("
                        UPDATE pedidos 
                        SET mercadopago_payment_id = ?,
                            mercadopago_status = ?,
                            status = 'Pagamento Pendente'
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$payment_id, $payment_status, $pedidoId]);
                    
                    // Retornar dados do boleto para o frontend
                    jsonResponse(true, 'Boleto gerado com sucesso', [
                        'pedido_id' => $pedidoId,
                        'payment_id' => $payment_id,
                        'boleto_url' => $boleto_url,
                        'boleto_barcode' => $boleto_barcode,
                        'boleto_digitable_line' => $boleto_digitable_line,
                        'boleto_due_date' => $boleto_due_date
                    ]);
                } else {
                    $error_message = $resultado['message'] ?? 'Erro desconhecido';
                    error_log("Mercado Pago Boleto Error (HTTP $http_code): $error_message");
                    throw new Exception("Erro ao gerar Boleto: " . $error_message);
                }
            
            // ===== CARTÃO (CHECKOUT TRANSPARENTE) =====
            } elseif ($isTransparente && isset($pagamento['payment_data'])) {
                error_log("Processando pagamento transparente...");
                
                $payment_form_data = $pagamento['payment_data'];
                
                // Preparar dados do pagamento transparente
                $payment_data = [
                    'transaction_amount' => round(floatval($valorTotal), 2),
                    'token' => $payment_form_data['token'],
                    'description' => 'Pedido #' . $pedidoId,
                    'installments' => (int)$payment_form_data['installments'],
                    'payment_method_id' => $payment_form_data['payment_method_id'],
                    'issuer_id' => $payment_form_data['issuer_id'] ?? null,
                    'payer' => [
                        'email' => $cliente['email'],
                        'identification' => [
                            'type' => 'CPF',
                            'number' => preg_replace('/[^0-9]/', '', $cliente['cpf_cnpj'] ?? '')
                        ]
                    ],
                    'external_reference' => (string)$pedidoId,
                    'statement_descriptor' => 'PEDIDO' . $pedidoId,
                    'notification_url' => $protocol . '://' . $_SERVER['HTTP_HOST'] . '/admin-teste/webhooks/notificacao.php'
                ];
                
                // Log da URL de notificação
                error_log("Notification URL: " . $payment_data['notification_url']);
                
                // Log do JSON sendo enviado
                error_log("Mercado Pago Payment Data: " . json_encode($payment_data, JSON_PRETTY_PRINT));
                
                // Endpoint da API de payments
                $api_url = 'https://api.mercadopago.com/v1/payments';
                
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $gateway['secret_key'],
                    'Content-Type: application/json',
                    'X-Idempotency-Key: ' . uniqid('payment_' . $pedidoId . '_', true)
                ]);
                
                // SSL para localhost/Cloudflare Tunnel
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                // Log da resposta
                error_log("Mercado Pago Payment API Response - HTTP $http_code: " . $response);
                
                if ($curl_error) {
                    error_log("Mercado Pago cURL Error: " . $curl_error);
                    throw new Exception("Erro de conexão com Mercado Pago: " . $curl_error);
                }
                
                $resultado = json_decode($response, true);
                
                // Verificar resposta
                if ($http_code === 200 || $http_code === 201) {
                    $payment_id = $resultado['id'] ?? null;
                    $payment_status = $resultado['status'] ?? 'unknown';
                    $status_detail = $resultado['status_detail'] ?? '';
                    
                    // Mapa de mensagens específicas do Mercado Pago (status_detail)
                    $mpErrorMessages = [
                        // Cartão rejeitado
                        'cc_rejected_insufficient_amount' => 'Cartão sem saldo suficiente',
                        'cc_rejected_bad_filled_security_code' => 'Código de segurança inválido',
                        'cc_rejected_bad_filled_date' => 'Data de validade inválida',
                        'cc_rejected_bad_filled_card_number' => 'Número do cartão inválido',
                        'cc_rejected_bad_filled_other' => 'Dados do cartão incorretos',
                        'cc_rejected_blacklist' => 'Cartão bloqueado',
                        'cc_rejected_call_for_authorize' => 'Entre em contato com seu banco para autorizar',
                        'cc_rejected_card_disabled' => 'Cartão desabilitado',
                        'cc_rejected_card_error' => 'Erro no processamento do cartão',
                        'cc_rejected_duplicated_payment' => 'Pagamento duplicado',
                        'cc_rejected_high_risk' => 'Pagamento recusado por segurança',
                        'cc_rejected_invalid_installments' => 'Número de parcelas inválido',
                        'cc_rejected_max_attempts' => 'Limite de tentativas excedido',
                        'cc_rejected_other_reason' => 'Pagamento recusado pelo banco',
                        
                        // Aprovados
                        'accredited' => 'Pagamento aprovado e creditado',
                        
                        // Pendentes
                        'pending_contingency' => 'Aguardando confirmação do banco',
                        'pending_review_manual' => 'Em análise de segurança',
                    ];
                    
                    // Traduzir mensagem de erro
                    $payment_message = isset($mpErrorMessages[$status_detail]) 
                        ? $mpErrorMessages[$status_detail] 
                        : ($status_detail ?: '');
                    
                    // Adicionar mensagem genérica dependendo do status
                    if ($payment_status === 'rejected' && empty($payment_message)) {
                        $payment_message = 'Pagamento recusado. Verifique os dados do cartão ou tente outro meio de pagamento.';
                    } elseif ($payment_status === 'pending' && empty($payment_message)) {
                        $payment_message = 'Pagamento pendente de confirmação.';
                    } elseif ($payment_status === 'approved' && empty($payment_message)) {
                        $payment_message = 'Pagamento aprovado com sucesso!';
                    }
                    
                    // Obter valores REAIS cobrados (com juros se houver)
                    $valor_total_cobrado = isset($resultado['transaction_amount']) ? floatval($resultado['transaction_amount']) : $valorTotal;
                    $parcelas_cobradas = isset($resultado['installments']) ? intval($resultado['installments']) : 1;
                    $valor_parcela_cobrada = isset($resultado['transaction_details']['installment_amount']) 
                        ? floatval($resultado['transaction_details']['installment_amount'])
                        : round($valor_total_cobrado / $parcelas_cobradas, 2);
                    
                    error_log("💰 Valores cobrados - Total: $valor_total_cobrado | Parcelas: $parcelas_cobradas | Valor parcela: $valor_parcela_cobrada");
                    
                    // Atualizar pedido com informações do pagamento
                    $stmtUpdate = $pdo->prepare("
                        UPDATE pedidos 
                        SET mercadopago_payment_id = ?,
                            mercadopago_status = ?,
                            status = ?,
                            parcelas = ?,
                            valor_total = ?,
                            valor_parcela = ?
                        WHERE id = ?
                    ");
                    
                    // Mapear status do MP para status do sistema de fluxo
                    $status_pedido = 'Pagamento Pendente';
                    if ($payment_status === 'approved') {
                        $status_pedido = 'Pedido Recebido';
                    } elseif ($payment_status === 'rejected') {
                        $status_pedido = 'Cancelado';
                    } elseif ($payment_status === 'pending' || $payment_status === 'in_process') {
                        $status_pedido = 'Pagamento Pendente';
                    }
                    
                    $stmtUpdate->execute([
                        $payment_id, 
                        $payment_status, 
                        $status_pedido, 
                        $parcelas_cobradas,
                        $valor_total_cobrado,
                        $valor_parcela_cobrada,
                        $pedidoId
                    ]);
                    
                    error_log("✅ Pagamento processado - Status: $payment_status | Payment ID: $payment_id | Total: $valor_total_cobrado");
                    
                } else {
                    // Erro na API
                    $error_message = $resultado['message'] ?? 'Erro desconhecido';
                    $error_details = isset($resultado['cause']) ? json_encode($resultado['cause']) : '';
                    
                    error_log("Mercado Pago Payment API Error (HTTP $http_code): $error_message | Details: $error_details");
                    
                    throw new Exception("Erro do Mercado Pago (HTTP $http_code): " . $error_message);
                }
                
            // ===== CHECKOUT PRO (REDIRECIONAMENTO) =====
            } else {
                error_log("Processando checkout com redirecionamento (Checkout Pro)...");
                
                // Código antigo de preference (mantém para Pix/Boleto/outros)
                $items = [];
                foreach ($carrinho['items'] as $item) {
                    $items[] = [
                        'title' => $item['name'],
                        'quantity' => (int)$item['qty'],
                        'unit_price' => (float)$item['price'],
                        'currency_id' => 'BRL'
                    ];
                }
                
                if ($valorFrete > 0) {
                    $items[] = [
                        'title' => 'Frete',
                        'quantity' => 1,
                        'unit_price' => (float)$valorFrete,
                        'currency_id' => 'BRL'
                    ];
                }
                
                if ($desconto > 0) {
                    $items[] = [
                        'title' => 'Desconto',
                        'quantity' => 1,
                        'unit_price' => -(float)$desconto,
                        'currency_id' => 'BRL'
                    ];
                }
                
                // ===== DETECTAR PROTOCOLO (HTTPS/HTTP) para URLs de retorno =====
                $protocol = 'http';
                
                if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                    $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
                } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                    $protocol = 'https';
                } elseif ($_SERVER['SERVER_PORT'] == 443) {
                    $protocol = 'https';
                } elseif (strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false || 
                          strpos($_SERVER['HTTP_HOST'], 'trycloudflare.com') !== false) {
                    $protocol = 'https';
                }
                
                $host = $_SERVER['HTTP_HOST'];
                $script_path = dirname(dirname($_SERVER['PHP_SELF']));
                $base_url = $protocol . '://' . $host . $script_path;
                
                $success_url = $base_url . '/pages/pedidos.php?status=success&pedido=' . $pedidoId;
                $failure_url = $base_url . '/pages/checkout.php?status=failure';
                $pending_url = $base_url . '/pages/pedidos.php?status=pending&pedido=' . $pedidoId;
                
                $preference_data = [
                    'items' => $items,
                    'payer' => [
                        'name' => $cliente['nome'],
                        'email' => $cliente['email'],
                        'phone' => [
                            'number' => preg_replace('/[^0-9]/', '', $cliente['telefone'])
                        ],
                        'address' => [
                            'zip_code' => preg_replace('/[^0-9]/', '', $endereco['cep']),
                            'street_name' => $endereco['rua'],
                            'street_number' => (int)$endereco['numero']
                        ]
                    ],
                    'back_urls' => [
                        'success' => $success_url,
                        'failure' => $failure_url,
                        'pending' => $pending_url
                    ],
                    'external_reference' => (string)$pedidoId,
                    'statement_descriptor' => 'PEDIDO #' . $pedidoId
                ];
                
                $api_url = 'https://api.mercadopago.com/checkout/preferences';
                
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $gateway['secret_key'],
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                error_log("Mercado Pago Preference API Response - HTTP $http_code: " . $response);
                
                if ($curl_error) {
                    error_log("Mercado Pago cURL Error: " . $curl_error);
                    throw new Exception("Erro de conexão com Mercado Pago: " . $curl_error);
                }
                
                $resultado = json_decode($response, true);
                
                if ($http_code === 200 || $http_code === 201) {
                    if (isset($resultado['init_point'])) {
                        $init_point = $resultado['init_point'];
                        $payment_id = $resultado['id'] ?? null;
                        
                        if ($payment_id) {
                            $stmtUpdate = $pdo->prepare("
                                UPDATE pedidos 
                                SET mercadopago_preference_id = ? 
                                WHERE id = ?
                            ");
                            $stmtUpdate->execute([$payment_id, $pedidoId]);
                        }
                    } else {
                        error_log("Mercado Pago - init_point não retornado");
                        throw new Exception("Resposta inválida do Mercado Pago");
                    }
                } else {
                    $error_message = $resultado['message'] ?? 'Erro desconhecido';
                    error_log("Mercado Pago Preference API Error (HTTP $http_code): $error_message");
                    throw new Exception("Erro do Mercado Pago (HTTP $http_code): " . $error_message);
                }
            }
        } else {
            error_log("Gateway não configurado ou inativo");
        }
        
    } catch (Exception $e) {
        // Log do erro
        error_log("Erro ao processar pagamento Mercado Pago: " . $e->getMessage());
        
        // Extrair mensagem amigável
        $errorMsg = $e->getMessage();
        $friendlyMsg = 'Não foi possível processar o pagamento. Tente novamente ou escolha outra forma de pagamento.';
        
        // Verificar se é erro de conexão
        if (strpos($errorMsg, 'cURL') !== false || strpos($errorMsg, 'conexão') !== false) {
            $friendlyMsg = 'Erro de conexão com o sistema de pagamento. Verifique sua internet e tente novamente.';
        }
        // Erro HTTP do Mercado Pago
        elseif (strpos($errorMsg, 'HTTP 4') !== false) {
            $friendlyMsg = 'Dados de pagamento inválidos. Verifique as informações do cartão.';
        }
        elseif (strpos($errorMsg, 'HTTP 5') !== false) {
            $friendlyMsg = 'Sistema de pagamento temporariamente indisponível. Tente novamente em instantes.';
        }
        
        // Retornar erro específico
        jsonResponse(false, $friendlyMsg, [
            'pedido_id' => $pedidoId,
            'payment_status' => 'error',
            'payment_message' => $friendlyMsg,
            'error_details' => $errorMsg
        ]);
    }
    
    // ===== ENVIAR EMAIL DE CONFIRMAÇÃO (OPCIONAL) =====
    try {
        // Aqui você pode adicionar o envio de email
        // usando PHPMailer ou similar
    } catch (Exception $e) {
        error_log("Erro ao enviar email: " . $e->getMessage());
    }
    
    // Retornar sucesso
    $response_data = [
        'pedido_id' => $pedidoId,
        'cliente_id' => $clienteId,
        'valor_total' => $valorTotal
    ];
    
    // Adicionar dados específicos de checkout transparente
    if ($isTransparente && $payment_status) {
        $response_data['payment_status'] = $payment_status;
        $response_data['payment_id'] = $payment_id;
        $response_data['payment_message'] = $payment_message;
        
        // Adicionar detalhe extra para rejected (para exibir dicas ao usuário)
        if ($payment_status === 'rejected') {
            $response_data['payment_detail'] = 'Tente usar outro cartão ou forma de pagamento.';
        }
    }
    
    // Adicionar dados de checkout pro (redirecionamento)
    if ($init_point) {
        $response_data['init_point'] = $init_point;
        $response_data['payment_id'] = $payment_id;
    }
    
    jsonResponse(true, 'Pedido criado com sucesso!', $response_data);
    
} catch (PDOException $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro ao processar pedido (PDO): " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    
    // Retornar erro mais detalhado
    jsonResponse(false, 'Erro no banco de dados: ' . $e->getMessage(), [
        'error_type' => 'database',
        'error_code' => $e->getCode()
    ]);
    
} catch (Exception $e) {
    // Rollback em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro geral ao processar pedido: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    
    // Retornar erro específico
    jsonResponse(false, $e->getMessage(), [
        'error_type' => 'general',
        'error_details' => $e->getMessage()
    ]);
}

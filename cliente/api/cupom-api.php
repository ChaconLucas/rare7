<?php
/**
 * API de Cupom de Desconto - D&Z E-commerce
 * Endpoint para validação e aplicação de cupons
 */

// Suprimir warnings e notices para garantir JSON puro
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
header('Content-Type: application/json; charset=utf-8');

// Log de debug
$debug_log = [];
$debug_log[] = "🎟️ API Cupom Iniciada";

require_once '../config.php';

$debug_log[] = "✅ Config incluído";

// Função para resposta JSON
function jsonResponse($success, $data = [], $message = '', $debug = []) {
    // Limpar qualquer output anterior
    if (ob_get_level()) ob_clean();
    
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'debug' => $debug
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Método não permitido', $debug_log);
}

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$debug_log[] = "📥 Action: " . $action;

try {
    switch ($action) {
        
        // ===== VALIDAR CUPOM =====
        case 'validate':
            $codigo = strtoupper(trim($input['codigo'] ?? ''));
            $subtotal = (float)($input['subtotal'] ?? 0);

            $debug_log[] = "🎟️ Código: " . $codigo;
            $debug_log[] = "💰 Subtotal: R$ " . $subtotal;

            if (empty($codigo)) {
                jsonResponse(false, [], 'Código do cupom não informado', $debug_log);
            }

            if ($subtotal <= 0) {
                jsonResponse(false, [], 'Valor do pedido inválido', $debug_log);
            }

            // Verificar se tabela existe
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'cupons'")->fetch();
            if (!$tableCheck) {
                $debug_log[] = "❌ Tabela cupons não existe";
                jsonResponse(false, [], 'Sistema de cupons não está configurado. Execute: criar_sistema_carrinho.sql', $debug_log);
            }

            $debug_log[] = "✅ Tabela cupons existe";

            // Buscar cupom no banco
            $query = "SELECT * FROM cupons WHERE codigo = ? LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$codigo]);
            $cupom = $stmt->fetch();

            if (!$cupom) {
                $debug_log[] = "❌ Cupom não encontrado no banco";
                jsonResponse(false, [], 'Cupom não encontrado', $debug_log);
            }

            $debug_log[] = "✅ Cupom encontrado: " . ($cupom['descricao'] ?? '');

            // Validar se está ativo
            if ($cupom['ativo'] != 1) {
                $debug_log[] = "❌ Cupom inativo";
                jsonResponse(false, [], 'Cupom inativo', $debug_log);
            }

            // Validar datas - adaptar para ambas as estruturas de banco
            $hoje = date('Y-m-d');
            
            // Verificar qual estrutura de data o banco está usando
            if (isset($cupom['data_expiracao'])) {
                // Estrutura antiga: apenas data_expiracao
                $dataExpiracao = $cupom['data_expiracao'];
                $debug_log[] = "📅 Hoje: {$hoje}, Expiração: {$dataExpiracao}";
                
                if ($hoje > $dataExpiracao) {
                    $debug_log[] = "❌ Cupom expirado em {$dataExpiracao}";
                    jsonResponse(false, [], 'Cupom expirado', $debug_log);
                }
                
                $debug_log[] = "✅ Data válida (expira em {$dataExpiracao})";
                
            } elseif (isset($cupom['data_inicio']) && isset($cupom['data_fim'])) {
                // Estrutura nova: data_inicio e data_fim
                $dataInicio = $cupom['data_inicio'];
                $dataFim = $cupom['data_fim'];
                $debug_log[] = "📅 Hoje: {$hoje}, Início: {$dataInicio}, Fim: {$dataFim}";
                
                if ($hoje < $dataInicio) {
                    $debug_log[] = "❌ Cupom ainda não iniciou";
                    jsonResponse(false, [], 'Cupom ainda não está válido', $debug_log);
                }
                
                if ($hoje > $dataFim) {
                    $debug_log[] = "❌ Cupom expirado em {$dataFim}";
                    jsonResponse(false, [], 'Cupom expirado', $debug_log);
                }
                
                $debug_log[] = "✅ Data válida";
            } else {
                // Sem campos de data - considerar sempre válido
                $debug_log[] = "⚠️ Cupom sem campo de data - considerando válido";
            }

            // Validar valor mínimo
            if ($subtotal < $cupom['valor_minimo']) {
                $minimo = number_format($cupom['valor_minimo'], 2, ',', '.');
                $debug_log[] = "❌ Valor mínimo não atingido: R$ {$minimo}";
                jsonResponse(false, [], "Valor mínimo do pedido: R$ {$minimo}", $debug_log);
            }

            $debug_log[] = "✅ Valor mínimo OK";

            // Validar limite de usos
            if ($cupom['usos_maximos'] !== null && $cupom['usos_realizados'] >= $cupom['usos_maximos']) {
                $debug_log[] = "❌ Limite de usos atingido";
                jsonResponse(false, [], 'Cupom atingiu o limite de usos', $debug_log);
            }

            // Calcular desconto
            $valorDesconto = 0;
            
            // Buscar o tipo correto - pode ser 'tipo' ou 'tipo_desconto'
            $tipoCupom = $cupom['tipo'] ?? $cupom['tipo_desconto'] ?? 'fixo';
            $debug_log[] = "📊 Tipo do cupom: " . $tipoCupom;
            
            // Aceitar 'percentual' ou 'porcentagem'
            if ($tipoCupom === 'percentual' || $tipoCupom === 'porcentagem') {
                $valorDesconto = ($subtotal * $cupom['valor']) / 100;
                $descontoTexto = $cupom['valor'] . '%';
                $debug_log[] = "💯 Desconto percentual: {$cupom['valor']}% de R$ {$subtotal} = R$ " . number_format($valorDesconto, 2, ',', '.');
            } else {
                // Desconto fixo
                $valorDesconto = $cupom['valor'];
                $descontoTexto = 'R$ ' . number_format($cupom['valor'], 2, ',', '.');
                $debug_log[] = "💵 Desconto fixo: R$ " . number_format($valorDesconto, 2, ',', '.');
            }

            // Desconto não pode ser maior que o subtotal
            if ($valorDesconto > $subtotal) {
                $valorDesconto = $subtotal;
                $debug_log[] = "⚠️ Desconto limitado ao subtotal";
            }

            $debug_log[] = "✅ Valor final do desconto: R$ " . number_format($valorDesconto, 2, ',', '.');

            jsonResponse(true, [
                'cupom_id' => $cupom['id'],
                'codigo' => $cupom['codigo'],
                'descricao' => $cupom['descricao'] ?? '',
                'tipo' => $tipoCupom, // Tipo detectado corretamente
                'valor' => (float)$cupom['valor'],
                'desconto_aplicado' => round($valorDesconto, 2),
                'desconto_texto' => $descontoTexto,
                'novo_total' => round($subtotal - $valorDesconto, 2)
            ], 'Cupom aplicado com sucesso!', $debug_log);
            break;

        // ===== REMOVER CUPOM =====
        case 'remove':
            $debug_log[] = "🗑️ Removendo cupom";
            jsonResponse(true, [], 'Cupom removido', $debug_log);
            break;

        default:
            $debug_log[] = "❌ Ação desconhecida: " . $action;
            jsonResponse(false, [], 'Ação não reconhecida', $debug_log);
    }

} catch (Exception $e) {
    $debug_log[] = "❌ ERRO: " . $e->getMessage();
    $debug_log[] = "Linha: " . $e->getLine();
    $debug_log[] = "Arquivo: " . $e->getFile();
    
    error_log("Erro na API de Cupom: " . $e->getMessage());
    jsonResponse(false, [], 'Erro: ' . $e->getMessage(), $debug_log);
}

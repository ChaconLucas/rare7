<?php
/**
 * API de Cupom de Desconto - RARE7 E-commerce
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

function normalizarCodigoCupom($codigo) {
    $codigo = strtoupper(trim((string)$codigo));
    // Remove tudo que não for letra ou número para comparação tolerante
    return preg_replace('/[^A-Z0-9]/', '', $codigo);
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
            $valorFrete = (float)($input['frete'] ?? 0);
            $clienteId = intval($input['cliente_id'] ?? 0);
            $codigoNormalizado = normalizarCodigoCupom($codigo);

            $debug_log[] = "🎟️ Código: " . $codigo;
            $debug_log[] = "💰 Subtotal: R$ " . $subtotal;

            if (empty($codigo)) {
                jsonResponse(false, [], 'Código do cupom não informado', $debug_log);
            }

            if (empty($codigoNormalizado)) {
                jsonResponse(false, [], 'Código do cupom inválido', $debug_log);
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

            // Buscar cupom no banco por igualdade direta (case-insensitive)
            $query = "SELECT * FROM cupons WHERE UPPER(codigo) = ? LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute([strtoupper($codigo)]);
            $cupom = $stmt->fetch();

            // Fallback: buscar de forma tolerante (ignora espaços, hífens e símbolos)
            if (!$cupom) {
                $debug_log[] = "🔎 Cupom não encontrado por igualdade direta. Tentando busca tolerante...";

                $stmtTodos = $pdo->query("SELECT * FROM cupons");
                $todosCupons = $stmtTodos ? $stmtTodos->fetchAll() : [];

                foreach ($todosCupons as $cupomItem) {
                    $codigoBancoNormalizado = normalizarCodigoCupom($cupomItem['codigo'] ?? '');
                    if ($codigoBancoNormalizado !== '' && $codigoBancoNormalizado === $codigoNormalizado) {
                        $cupom = $cupomItem;
                        $debug_log[] = "✅ Cupom encontrado por busca tolerante: " . ($cupomItem['codigo'] ?? '');
                        break;
                    }
                }
            }

            if (!$cupom) {
                $debug_log[] = "❌ Cupom não encontrado no banco";

                $sugestao = null;
                $menorDistancia = PHP_INT_MAX;

                try {
                    $stmtSugestoes = $pdo->query("SELECT codigo FROM cupons WHERE ativo = 1");
                    $codigosAtivos = $stmtSugestoes ? $stmtSugestoes->fetchAll(PDO::FETCH_COLUMN) : [];

                    foreach ($codigosAtivos as $codigoAtivo) {
                        $codigoAtivoNorm = normalizarCodigoCupom($codigoAtivo);
                        if ($codigoAtivoNorm === '') {
                            continue;
                        }

                        $distancia = levenshtein($codigoNormalizado, $codigoAtivoNorm);
                        if ($distancia < $menorDistancia) {
                            $menorDistancia = $distancia;
                            $sugestao = $codigoAtivo;
                        }
                    }
                } catch (Exception $e) {
                    $debug_log[] = "⚠️ Erro ao calcular sugestão de cupom: " . $e->getMessage();
                }

                if ($sugestao !== null && $menorDistancia <= 3) {
                    jsonResponse(false, [
                        'sugestao' => $sugestao
                    ], "Cupom não encontrado. Você quis dizer {$sugestao}?", $debug_log);
                }

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

            // Validar limite por CPF/cliente
            if (!empty($cupom['limite_uso_cpf']) && $clienteId > 0) {
                $stmtUsoCliente = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ? AND cupom_codigo = ? AND status NOT IN ('Pedido Cancelado','Estornado')");
                $stmtUsoCliente->execute([$clienteId, $cupom['codigo']]);
                $usosCliente = (int)$stmtUsoCliente->fetchColumn();
                if ($usosCliente >= (int)$cupom['limite_uso_cpf']) {
                    $debug_log[] = "❌ Limite de uso por cliente atingido ({$usosCliente}/{$cupom['limite_uso_cpf']})";
                    jsonResponse(false, [], 'Você já utilizou este cupom o número máximo de vezes permitido', $debug_log);
                }
            }

            // Validar primeira compra
            if (!empty($cupom['primeira_compra']) && $cupom['primeira_compra'] == 1) {
                if ($clienteId > 0) {
                    $stmtPC = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ? AND status NOT IN ('Pedido Cancelado','Estornado')");
                    $stmtPC->execute([$clienteId]);
                    if ((int)$stmtPC->fetchColumn() > 0) {
                        $debug_log[] = "❌ Cupom de primeira compra - cliente já tem pedidos";
                        jsonResponse(false, [], 'Este cupom é válido apenas para a primeira compra', $debug_log);
                    }
                } else {
                    $debug_log[] = "⚠️ Cupom de primeira compra sem cliente logado - permitindo (validação final no checkout)";
                }
            }

            // Calcular desconto
            $valorDesconto = 0;
            $freteGratis = false;
            
            // Buscar o tipo correto - pode ser 'tipo' ou 'tipo_desconto'
            $tipoCupom = $cupom['tipo_desconto'] ?? $cupom['tipo'] ?? 'fixo';
            $debug_log[] = "📊 Tipo do cupom: " . $tipoCupom;
            
            if ($tipoCupom === 'frete_gratis') {
                $valorDesconto = $valorFrete;
                $freteGratis = true;
                $descontoTexto = 'Frete Grátis';
                $debug_log[] = "🚚 Frete grátis aplicado: R$ " . number_format($valorDesconto, 2, ',', '.');
            } elseif ($tipoCupom === 'percentual' || $tipoCupom === 'porcentagem') {
                $valorDesconto = round(($subtotal * $cupom['valor']) / 100, 2);
                $descontoTexto = $cupom['valor'] . '%';
                $debug_log[] = "💯 Desconto percentual: {$cupom['valor']}% de R$ {$subtotal} = R$ " . number_format($valorDesconto, 2, ',', '.');
            } else {
                // valor_fixo / fixo / qualquer outro
                $valorDesconto = min((float)$cupom['valor'], $subtotal);
                $descontoTexto = 'R$ ' . number_format($cupom['valor'], 2, ',', '.');
                $debug_log[] = "💵 Desconto fixo: R$ " . number_format($valorDesconto, 2, ',', '.');
            }

            // Desconto não pode ser maior que o subtotal (exceto frete_gratis)
            if (!$freteGratis && $valorDesconto > $subtotal) {
                $valorDesconto = $subtotal;
                $debug_log[] = "⚠️ Desconto limitado ao subtotal";
            }

            $debug_log[] = "✅ Valor final do desconto: R$ " . number_format($valorDesconto, 2, ',', '.');

            jsonResponse(true, [
                'cupom_id'        => $cupom['id'],
                'codigo'          => $cupom['codigo'],
                'descricao'       => $cupom['descricao'] ?? '',
                'tipo'            => $tipoCupom,
                'valor'           => (float)$cupom['valor'],
                'frete_gratis'    => $freteGratis,
                'desconto_aplicado' => round($valorDesconto, 2),
                'desconto_texto'  => $descontoTexto,
                'novo_total'      => round($subtotal - ($freteGratis ? 0 : $valorDesconto) + ($freteGratis ? 0 : 0), 2)
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

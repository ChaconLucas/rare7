<?php
/**
 * API de Frete - RARE7 E-commerce
 * Integração com Melhor Envio usando dados reais dos produtos
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
header('Content-Type: application/json; charset=utf-8');

// Log de debug
$debug_log = [];
$debug_log[] = "🚀 API Iniciada";

require_once '../config.php';
require_once '../conexao.php';

$debug_log[] = "✅ Arquivos incluídos";

// Função para resposta JSON
function jsonResponse($success, $data = [], $message = '', $debug = []) {
    if (ob_get_level()) ob_clean();
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'debug' => $debug
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Monta opções de frete de fallback quando a integração externa não estiver disponível
function montarOpcoesFallback($subtotal, $fallbackValue, $freteGratisLimite = 0) {
    $opcoes = [];

    if ($freteGratisLimite > 0 && $subtotal >= $freteGratisLimite) {
        $opcoes[] = [
            'id' => 0,
            'nome' => 'Frete Grátis',
            'empresa' => 'RARE7',
            'valor' => 0.00,
            'prazo_dias' => 10,
            'data_estimada' => date('d/m/Y', strtotime('+10 days')),
            'prazo_texto' => '10 dias úteis',
            'gratis' => true
        ];
    }

    $opcoes[] = [
        'id' => 'fallback-standard',
        'nome' => 'Entrega Padrão',
        'empresa' => 'RARE7',
        'valor' => max(0, (float)$fallbackValue),
        'prazo_dias' => 7,
        'data_estimada' => date('d/m/Y', strtotime('+7 days')),
        'prazo_texto' => '3 a 7 dias úteis',
        'gratis' => false,
        'fallback' => true
    ];

    return $opcoes;
}

function mapearRegiaoPorUf($uf) {
    $uf = strtoupper(trim((string) $uf));

    $mapa = [
        'NORTE' => ['AC', 'AP', 'AM', 'PA', 'RO', 'RR', 'TO'],
        'NORDESTE' => ['AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE'],
        'CENTRO_OESTE' => ['DF', 'GO', 'MT', 'MS'],
        'SUDESTE' => ['ES', 'MG', 'RJ', 'SP'],
        'SUL' => ['PR', 'RS', 'SC']
    ];

    foreach ($mapa as $regiao => $ufs) {
        if (in_array($uf, $ufs, true)) {
            return $regiao;
        }
    }

    return null;
}

function resolverFallbackRegional(array $settingsFrete, $uf, $fallbackPadrao) {
    $regiao = mapearRegiaoPorUf($uf);
    if (!$regiao) {
        return (float) $fallbackPadrao;
    }

    $mapaCampos = [
        'SUDESTE' => 'fallback_value_sudeste',
        'SUL' => 'fallback_value_sul',
        'CENTRO_OESTE' => 'fallback_value_centro_oeste',
        'NORDESTE' => 'fallback_value_nordeste',
        'NORTE' => 'fallback_value_norte'
    ];

    $campo = $mapaCampos[$regiao] ?? null;
    if ($campo && isset($settingsFrete[$campo]) && $settingsFrete[$campo] !== null && $settingsFrete[$campo] !== '') {
        return (float) $settingsFrete[$campo];
    }

    return (float) $fallbackPadrao;
}

// Validar CEP
function validarCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    return strlen($cep) === 8 ? $cep : false;
}

// Buscar endereço na API ViaCEP
function buscarEnderecoCEP($cep) {
    $url = "https://viacep.com.br/ws/{$cep}/json/";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['erro']) && $data['erro'] === true) {
        return null;
    }
    
    return $data;
}

// Calcular frete via Melhor Envio
function calcularMelhorEnvio($token, $cep_origem, $cep_destino, $peso, $altura, $largura, $comprimento, $valor_declarado) {
    $url = "https://www.melhorenvio.com.br/api/v2/me/shipment/calculate";
    
    $payload = [
        "from" => [
            "postal_code" => preg_replace('/\D/', '', $cep_origem)
        ],
        "to" => [
            "postal_code" => preg_replace('/\D/', '', $cep_destino)
        ],
        "package" => [
            "height" => (float)$altura,
            "width" => (float)$largura,
            "length" => (float)$comprimento,
            "weight" => (float)$peso
        ],
        "options" => [
            "insurance_value" => (float)$valor_declarado,
            "receipt" => false,
            "own_hand" => false
        ]
    ];
    
    $headers = [
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
        "Accept: application/json",
        "User-Agent: Sistema-RARE7-Ecommerce/1.0"
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return ['error' => 'Erro de conexão: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['message'] ?? 'API retornou código ' . $httpCode;
        
        // Retornar erro com detalhes completos
        return [
            'error' => $errorMsg,
            'http_code' => $httpCode,
            'response' => $response,
            'payload_sent' => $payload
        ];
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'Erro ao decodificar resposta da API',
            'json_error' => json_last_error_msg(),
            'response' => $response
        ];
    }
    
    return $data;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Método não permitido', $debug_log);
}

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$debug_log[] = "📥 Action: " . $action;
$debug_log[] = "📦 Input: " . json_encode($input);

try {
    switch ($action) {
        
        // ===== CALCULAR FRETE POR CEP =====
        case 'calculate':
            $debug_log[] = "🚚 Iniciando cálculo de frete";
            
            $cep = trim($input['cep'] ?? '');
            $subtotal = (float)($input['subtotal'] ?? 0);
            $items = $input['items'] ?? [];

            $debug_log[] = "CEP: " . $cep;
            $debug_log[] = "Subtotal: R$ " . $subtotal;
            $debug_log[] = "Items count: " . count($items);

            // Validar CEP
            $cepLimpo = validarCEP($cep);
            if (!$cepLimpo) {
                jsonResponse(false, [], 'CEP inválido. Use o formato: 12345-678', $debug_log);
            }

            $debug_log[] = "✅ CEP válido: " . $cepLimpo;

            // Buscar endereço
            $endereco = buscarEnderecoCEP($cepLimpo);
            if (!$endereco) {
                jsonResponse(false, [], 'CEP não encontrado', $debug_log);
            }

            $debug_log[] = "✅ Endereço encontrado: " . ($endereco['localidade'] ?? 'N/A');

            // Ler configurações de fallback e frete grátis
            $fallbackEnabled = 1;
            $fallbackValue = 15.00;
            $fallbackMessage = 'Frete estimado automaticamente. O valor final será confirmado no envio.';
            $freteGratisLimite = (float)getFreteGratisThreshold($pdo);

            try {
                $queryFreteSettings = "SELECT * FROM freight_settings WHERE id = 1 LIMIT 1";
                $resultFreteSettings = mysqli_query($conn, $queryFreteSettings);

                if ($resultFreteSettings && mysqli_num_rows($resultFreteSettings) > 0) {
                    $settingsFrete = mysqli_fetch_assoc($resultFreteSettings);
                    $fallbackEnabled = (int)($settingsFrete['fallback_enabled'] ?? 1);
                    $fallbackValue = resolverFallbackRegional($settingsFrete, $endereco['uf'] ?? '', (float)($settingsFrete['fallback_value'] ?? 15.00));
                    $fallbackMessageDb = trim((string)($settingsFrete['fallback_message'] ?? ''));

                    $debug_log[] = "🗺️ Região destino: " . (mapearRegiaoPorUf($endereco['uf'] ?? '') ?: 'não identificada');
                    $debug_log[] = "💵 Fallback regional aplicado: R$ " . number_format($fallbackValue, 2, ',', '.');

                    if ($fallbackMessageDb !== '') {
                        $fallbackMessage = $fallbackMessageDb;
                    }
                }
            } catch (Exception $e) {
                $debug_log[] = "⚠️ Erro ao carregar fallback do frete: " . $e->getMessage();
            }

            // ===== BUSCAR DIMENSÕES E PESO DOS PRODUTOS =====
            $pesoTotal = 0;
            $alturaMax = 5.0;
            $larguraMax = 12.0;
            $comprimentoMax = 16.0;
            $pesoDimensoesEncontrados = false;

            // Verificar estrutura de dimensões (pode ser colunas separadas OU campo único 'dimensoes')
            $usarColunasSeparadas = false;
            $usarColunaDimensoes = false;
            
            try {
                // Verificar se TODAS as 4 colunas existem
                $checkPeso = mysqli_query($conn, "SHOW COLUMNS FROM produtos LIKE 'peso'");
                $checkAltura = mysqli_query($conn, "SHOW COLUMNS FROM produtos LIKE 'altura'");
                $checkLargura = mysqli_query($conn, "SHOW COLUMNS FROM produtos LIKE 'largura'");
                $checkComprimento = mysqli_query($conn, "SHOW COLUMNS FROM produtos LIKE 'comprimento'");
                
                $usarColunasSeparadas = (
                    $checkPeso && mysqli_num_rows($checkPeso) > 0 &&
                    $checkAltura && mysqli_num_rows($checkAltura) > 0 &&
                    $checkLargura && mysqli_num_rows($checkLargura) > 0 &&
                    $checkComprimento && mysqli_num_rows($checkComprimento) > 0
                );
                
                // Verificar se existe coluna única 'dimensoes'
                $checkDimensoes = mysqli_query($conn, "SHOW COLUMNS FROM produtos LIKE 'dimensoes'");
                $usarColunaDimensoes = ($checkDimensoes && mysqli_num_rows($checkDimensoes) > 0);
                
                if ($usarColunasSeparadas) {
                    $debug_log[] = "✅ Usando colunas SEPARADAS (peso, altura, largura, comprimento)";
                } elseif ($usarColunaDimensoes) {
                    $debug_log[] = "✅ Usando coluna ÚNICA 'dimensoes' (formato texto)";
                } else {
                    $debug_log[] = "⚠️ Nenhuma estrutura de dimensões encontrada";
                    $debug_log[] = "💡 Usando valores padrão para todos os produtos";
                }
            } catch (Exception $e) {
                $debug_log[] = "⚠️ Erro ao verificar colunas: " . $e->getMessage();
            }

            if (!empty($items) && is_array($items)) {
                $debug_log[] = "🔍 Processando produtos do carrinho...";
                
                foreach ($items as $item) {
                    $produtoId = (int)($item['produto_id'] ?? $item['id'] ?? 0);
                    $quantidade = (int)($item['quantidade'] ?? $item['qty'] ?? 1);

                    if ($produtoId > 0) {
                        $debug_log[] = "📦 Produto ID: {$produtoId}, Qtd: {$quantidade}";
                        
                        // OPÇÃO 1: Colunas separadas (peso, altura, largura, comprimento)
                        if ($usarColunasSeparadas) {
                            try {
                                $queryProd = "SELECT peso, altura, largura, comprimento FROM produtos WHERE id = ?";
                                $stmtProd = mysqli_prepare($conn, $queryProd);
                                
                                if ($stmtProd) {
                                    mysqli_stmt_bind_param($stmtProd, 'i', $produtoId);
                                    mysqli_stmt_execute($stmtProd);
                                    $resultProd = mysqli_stmt_get_result($stmtProd);
                                    $produto = mysqli_fetch_assoc($resultProd);

                                    if ($produto) {
                                        $pesoDimensoesEncontrados = true;
                                        $peso = (float)($produto['peso'] ?: 0.3);
                                        $pesoTotal += $peso * $quantidade;
                                        
                                        $alturaMax = max($alturaMax, (float)($produto['altura'] ?: 5.0));
                                        $larguraMax = max($larguraMax, (float)($produto['largura'] ?: 12.0));
                                        $comprimentoMax = max($comprimentoMax, (float)($produto['comprimento'] ?: 16.0));
                                        
                                        $debug_log[] = "  └─ Peso: {$peso}kg x {$quantidade} = " . ($peso * $quantidade) . "kg";
                                        $debug_log[] = "  └─ Dimensões: {$produto['altura']}x{$produto['largura']}x{$produto['comprimento']} cm";
                                    }
                                    mysqli_stmt_close($stmtProd);
                                }
                            } catch (Exception $e) {
                                $debug_log[] = "  └─ Erro ao buscar produto: " . $e->getMessage();
                            }
                        }
                        // OPÇÃO 2: Coluna única 'dimensoes' (formato: "10x50x25")
                        elseif ($usarColunaDimensoes) {
                            try {
                                $queryProd = "SELECT peso, dimensoes FROM produtos WHERE id = ?";
                                $stmtProd = mysqli_prepare($conn, $queryProd);
                                
                                if ($stmtProd) {
                                    mysqli_stmt_bind_param($stmtProd, 'i', $produtoId);
                                    mysqli_stmt_execute($stmtProd);
                                    $resultProd = mysqli_stmt_get_result($stmtProd);
                                    $produto = mysqli_fetch_assoc($resultProd);

                                    if ($produto) {
                                        $pesoDimensoesEncontrados = true;
                                        $peso = (float)($produto['peso'] ?: 0.3);
                                        $pesoTotal += $peso * $quantidade;
                                        
                                        // Extrair dimensões do formato "10x50x25" ou "10 x 50 x 25"
                                        $dimensoesStr = $produto['dimensoes'] ?? '';
                                        $debug_log[] = "  └─ Dimensões brutas: '{$dimensoesStr}'";
                                        
                                        if (!empty($dimensoesStr)) {
                                            // Remove espaços e separa por 'x' ou 'X'
                                            $partes = preg_split('/[xX]/', str_replace(' ', '', $dimensoesStr));
                                            
                                            if (count($partes) >= 3) {
                                                $altura = (float)trim($partes[0]);
                                                $largura = (float)trim($partes[1]);
                                                $comprimento = (float)trim($partes[2]);
                                                
                                                $alturaMax = max($alturaMax, $altura ?: 5.0);
                                                $larguraMax = max($larguraMax, $largura ?: 12.0);
                                                $comprimentoMax = max($comprimentoMax, $comprimento ?: 16.0);
                                                
                                                $debug_log[] = "  └─ Peso: {$peso}kg x {$quantidade} = " . ($peso * $quantidade) . "kg";
                                                $debug_log[] = "  └─ Dimensões extraídas: {$altura}x{$largura}x{$comprimento} cm";
                                            } else {
                                                $debug_log[] = "  └─ ⚠️ Formato de dimensões inválido, usando padrão";
                                            }
                                        } else {
                                            $debug_log[] = "  └─ Peso: {$peso}kg x {$quantidade}, dimensões não informadas";
                                        }
                                    }
                                    mysqli_stmt_close($stmtProd);
                                }
                            } catch (Exception $e) {
                                $debug_log[] = "  └─ Erro ao buscar produto: " . $e->getMessage();
                            }
                        }
                        // OPÇÃO 3: Nenhuma estrutura encontrada - usar padrões
                        else {
                            $pesoTotal += 0.3 * $quantidade;
                            $debug_log[] = "  └─ Usando peso padrão: 0.3kg x {$quantidade}";
                        }
                    }
                }
            }

            if ($pesoTotal == 0) {
                $pesoTotal = 0.3;
                $debug_log[] = "⚙️ Usando peso padrão: 300g";
            } else {
                $debug_log[] = "✅ Peso total calculado: {$pesoTotal}kg";
            }
            
            $debug_log[] = "";
            $debug_log[] = "📐 DIMENSÕES PARA CÁLCULO:";
            $debug_log[] = "  • Peso: {$pesoTotal}kg";
            $debug_log[] = "  • Altura: {$alturaMax}cm";
            $debug_log[] = "  • Largura: {$larguraMax}cm";
            $debug_log[] = "  • Comprimento: {$comprimentoMax}cm";
            $debug_log[] = "  • Valor declarado: R$ " . number_format($subtotal, 2, ',', '.');
            
            if ($pesoDimensoesEncontrados) {
                $debug_log[] = "✅ Usando dimensões REAIS dos produtos no banco!";
            } else {
                $debug_log[] = "⚠️ Usando dimensões PADRÃO (colunas não existem)";
                $debug_log[] = "💡 Execute o SQL: admin/sql/adicionar_dimensoes_produtos.sql";
            }
            
            // ===== VALIDAR LIMITES DAS TRANSPORTADORAS =====
            $somaDimensoes = $alturaMax + $larguraMax + $comprimentoMax;
            $dimensaoMaxima = max($alturaMax, $larguraMax, $comprimentoMax);
            
            $debug_log[] = "";
            $debug_log[] = "🔍 VALIDAÇÕES DE LIMITE:";
            $debug_log[] = "  • Soma dimensões (A+L+C): {$somaDimensoes}cm (máx: 200cm)";
            $debug_log[] = "  • Maior dimensão: {$dimensaoMaxima}cm (máx: 105cm)";
            $debug_log[] = "  • Peso: {$pesoTotal}kg (máx: 30kg)";
            
            // Validar peso
            if ($pesoTotal > 30) {
                $debug_log[] = "❌ ERRO: Peso excede 30kg (limite das transportadoras)";
                $debug_log[] = "💡 Reduza a quantidade de produtos no carrinho";
                jsonResponse(false, [], 'Peso do pedido excede o limite de 30kg. Reduza a quantidade de produtos ou divida em pedidos menores.', $debug_log);
            }
            
            // Validar soma das dimensões
            if ($somaDimensoes > 200) {
                $debug_log[] = "❌ ERRO: Soma das dimensões excede 200cm";
                jsonResponse(false, [], 'Dimensões do pedido muito grandes. Entre em contato para orçamento personalizado.', $debug_log);
            }
            
            // Validar dimensão individual
            if ($dimensaoMaxima > 105) {
                $debug_log[] = "❌ ERRO: Dimensão máxima excede 105cm";
                jsonResponse(false, [], 'Dimensões do pedido muito grandes. Entre em contato para orçamento personalizado.', $debug_log);
            }
            
            $debug_log[] = "✅ Dimensões e peso dentro dos limites permitidos";
            
            // ===== BUSCAR TOKEN DO MELHOR ENVIO DO BANCO =====
            $usarMelhorEnvio = false;
            $tokenMelhorEnvio = null;
            
            try {
                // Buscar configurações de frete do admin
                $queryToken = "SELECT token, active, environment FROM freight_integrations 
                              WHERE provider_slug = 'melhor_envio' AND active = 1 LIMIT 1";
                $resultToken = mysqli_query($conn, $queryToken);
                
                if ($resultToken && mysqli_num_rows($resultToken) > 0) {
                    $integration = mysqli_fetch_assoc($resultToken);
                    $tokenMelhorEnvio = trim($integration['token']);
                    
                    if (!empty($tokenMelhorEnvio)) {
                        $usarMelhorEnvio = true;
                        $debug_log[] = "✅ Token Melhor Envio encontrado no banco (ambiente: {$integration['environment']})";
                    } else {
                        $debug_log[] = "⚠️ Integração Melhor Envio ativa, mas token vazio";
                    }
                } else {
                    $debug_log[] = "ℹ️ Melhor Envio não configurado ou inativo no admin";
                }
            } catch (Exception $e) {
                $debug_log[] = "⚠️ Erro ao buscar token: " . $e->getMessage();
            }
            
            // Buscar CEP de origem configurado
            $cepOrigem = '01310-100'; // Default
            try {
                $queryCepOrigem = "SELECT origin_zipcode FROM freight_settings WHERE id = 1 LIMIT 1";
                $resultCep = mysqli_query($conn, $queryCepOrigem);
                if ($resultCep && mysqli_num_rows($resultCep) > 0) {
                    $settings = mysqli_fetch_assoc($resultCep);
                    $cepOrigem = $settings['origin_zipcode'] ?? $cepOrigem;
                    $debug_log[] = "📍 CEP de origem: $cepOrigem";
                }
            } catch (Exception $e) {
                $debug_log[] = "⚠️ Usando CEP de origem padrão: $cepOrigem";
            }
            
            // ===== INTEGRAÇÃO COM MELHOR ENVIO =====
            if ($usarMelhorEnvio && $tokenMelhorEnvio) {
                $debug_log[] = "🌐 Consultando API do Melhor Envio...";
                
                $resultME = calcularMelhorEnvio(
                    $tokenMelhorEnvio,
                    $cepOrigem,
                    $cepLimpo,
                    $pesoTotal,
                    $alturaMax,
                    $larguraMax,
                    $comprimentoMax,
                    $subtotal
                );
                
                if (isset($resultME['error'])) {
                    $erroME = $resultME['error'];
                    $debug_log[] = "❌ Erro Melhor Envio: " . $erroME;
                    
                    // Adicionar logs detalhados do erro
                    if (isset($resultME['http_code'])) {
                        $debug_log[] = "📡 HTTP Code: " . $resultME['http_code'];
                    }
                    if (isset($resultME['response'])) {
                        $debug_log[] = "📄 Resposta da API: " . substr($resultME['response'], 0, 500);
                    }
                    if (isset($resultME['payload_sent'])) {
                        $debug_log[] = "📤 Payload enviado: " . json_encode($resultME['payload_sent']);
                    }
                    if (isset($resultME['json_error'])) {
                        $debug_log[] = "⚠️ Erro JSON: " . $resultME['json_error'];
                    }
                    
                    // Analisar tipo de erro e dar mensagem adequada
                    if (isset($resultME['http_code'])) {
                        $httpCode = $resultME['http_code'];
                        if ($httpCode == 422) {
                            $debug_log[] = "💡 Erro 422: Provavelmente peso/dimensões excedem limites";
                            jsonResponse(false, [], 'Quantidade de produtos muito alta. Reduza para calcular o frete ou entre em contato.', $debug_log);
                        } elseif ($httpCode == 400) {
                            $debug_log[] = "💡 Erro 400: Parâmetros inválidos";
                            jsonResponse(false, [], 'Erro ao calcular frete. Tente novamente ou entre em contato.', $debug_log);
                        }
                    }

                    if ($fallbackEnabled) {
                        $debug_log[] = "🛟 Aplicando fallback de frete após falha no Melhor Envio";
                        $opcoesFallback = montarOpcoesFallback($subtotal, $fallbackValue, $freteGratisLimite);

                        jsonResponse(true, [
                            'cep' => $cepLimpo,
                            'endereco' => [
                                'logradouro' => $endereco['logradouro'] ?? '',
                                'bairro' => $endereco['bairro'] ?? '',
                                'cidade' => $endereco['localidade'] ?? '',
                                'uf' => $endereco['uf'] ?? '',
                                'complemento' => $endereco['complemento'] ?? ''
                            ],
                            'opcoes' => $opcoesFallback,
                            'peso_total' => $pesoTotal,
                            'dimensoes' => [
                                'altura' => $alturaMax,
                                'largura' => $larguraMax,
                                'comprimento' => $comprimentoMax
                            ],
                            'fallback_used' => true
                        ], $fallbackMessage, $debug_log);
                    }
                    
                    // Mensagem genérica
                    jsonResponse(false, [], 'Frete incorreto. Verifique o CEP e tente novamente.', $debug_log);
                } else {
                    $debug_log[] = "✅ Melhor Envio respondeu com sucesso!";
                    
                    // Buscar serviços ativos do admin
                    $servicosAtivos = [];
                    try {
                        $queryServicos = "SELECT service_code, service_name FROM freight_services WHERE active = 1";
                        $resultServicos = mysqli_query($conn, $queryServicos);
                        if ($resultServicos) {
                            while ($servico = mysqli_fetch_assoc($resultServicos)) {
                                $servicosAtivos[] = $servico['service_code'];
                            }
                        }
                        $debug_log[] = "🔧 Serviços ativos: " . implode(', ', $servicosAtivos);
                    } catch (Exception $e) {
                        $debug_log[] = "⚠️ Erro ao buscar serviços: " . $e->getMessage();
                    }
                    
                    // Processar resposta do Melhor Envio
                    $opcoesFrete = [];
                    $totalOpcoes = 0;
                    $opcoesInvalidas = 0;
                    
                    foreach ($resultME as $opcao) {
                        // Filtrar apenas serviços ativos (se houver configuração)
                        $serviceId = (string)($opcao['id'] ?? '');
                        if (!empty($servicosAtivos) && !in_array($serviceId, $servicosAtivos)) {
                            continue; // Pular se não estiver ativo
                        }
                        
                        $preco = (float)($opcao['price'] ?? 0);
                        $prazo = (int)($opcao['delivery_time'] ?? 0);
                        $nome = $opcao['name'] ?? 'Sem nome';
                        $empresa = $opcao['company']['name'] ?? 'N/A';
                        
                        // Validar se a opção é válida (preço > 0 e prazo > 0)
                        if ($preco <= 0 || $prazo <= 0) {
                            $opcoesInvalidas++;
                            $debug_log[] = "  ⚠️ {$nome} ({$empresa}) - INVÁLIDO (R$ {$preco}, {$prazo} dias) - Ignorado";
                            continue;
                        }
                        
                        $totalOpcoes++;
                        $opcoesFrete[] = [
                            'id' => $opcao['id'] ?? 0,
                            'nome' => $nome,
                            'empresa' => $empresa,
                            'valor' => $preco,
                            'prazo_dias' => $prazo,
                            'data_estimada' => date('d/m/Y', strtotime("+{$prazo} days")),
                            'prazo_texto' => "{$prazo} dias úteis",
                            'gratis' => false
                        ];
                        
                        $debug_log[] = "  • {$nome} ({$empresa}) - R$ {$preco} ({$prazo} dias)";
                    }
                    
                    if ($opcoesInvalidas > 0) {
                        $debug_log[] = "⚠️ {$opcoesInvalidas} opção(ões) inválida(s) removida(s)";
                    }
                    $debug_log[] = "✅ Total de opções válidas: $totalOpcoes";
                    
                    // Se nenhuma opção válida, retornar erro
                    if ($totalOpcoes === 0) {
                        $debug_log[] = "❌ Nenhuma opção válida retornada pelo Melhor Envio";
                        $debug_log[] = "💡 Possível causa: Peso/dimensões acumuladas excedem limites das transportadoras";
                        $debug_log[] = "💡 Produtos no carrinho: " . count($items);
                        $debug_log[] = "💡 Peso total: {$pesoTotal}kg";

                        if ($fallbackEnabled) {
                            $debug_log[] = "🛟 Aplicando fallback de frete por ausência de opções válidas";
                            $opcoesFallback = montarOpcoesFallback($subtotal, $fallbackValue, $freteGratisLimite);

                            jsonResponse(true, [
                                'cep' => $cepLimpo,
                                'endereco' => [
                                    'logradouro' => $endereco['logradouro'] ?? '',
                                    'bairro' => $endereco['bairro'] ?? '',
                                    'cidade' => $endereco['localidade'] ?? '',
                                    'uf' => $endereco['uf'] ?? '',
                                    'complemento' => $endereco['complemento'] ?? ''
                                ],
                                'opcoes' => $opcoesFallback,
                                'peso_total' => $pesoTotal,
                                'dimensoes' => [
                                    'altura' => $alturaMax,
                                    'largura' => $larguraMax,
                                    'comprimento' => $comprimentoMax
                                ],
                                'fallback_used' => true
                            ], $fallbackMessage, $debug_log);
                        }

                        jsonResponse(false, [], 'Quantidade de produtos muito alta para frete automático. Reduza a quantidade ou entre em contato.', $debug_log);
                    }
                }
            }
            
            // ===== FALLBACK: Melhor Envio NÃO configurado =====
            if (!$usarMelhorEnvio) {
                $debug_log[] = "❌ Melhor Envio não configurado ou inativo";

                if ($fallbackEnabled) {
                    $debug_log[] = "🛟 Aplicando fallback de frete sem integração ativa";
                    $opcoesFallback = montarOpcoesFallback($subtotal, $fallbackValue, $freteGratisLimite);

                    jsonResponse(true, [
                        'cep' => $cepLimpo,
                        'endereco' => [
                            'logradouro' => $endereco['logradouro'] ?? '',
                            'bairro' => $endereco['bairro'] ?? '',
                            'cidade' => $endereco['localidade'] ?? '',
                            'uf' => $endereco['uf'] ?? '',
                            'complemento' => $endereco['complemento'] ?? ''
                        ],
                        'opcoes' => $opcoesFallback,
                        'peso_total' => $pesoTotal,
                        'dimensoes' => [
                            'altura' => $alturaMax,
                            'largura' => $larguraMax,
                            'comprimento' => $comprimentoMax
                        ],
                        'fallback_used' => true
                    ], $fallbackMessage, $debug_log);
                }

                jsonResponse(false, [], 'Frete indisponível no momento. Configure a integração no painel admin.', $debug_log);
            }

            // ===== FRETE GRÁTIS =====
            $debug_log[] = "🎯 Frete grátis configurado para: R$ " . $freteGratisLimite;
            
            if ($subtotal >= $freteGratisLimite) {
                array_unshift($opcoesFrete, [
                    'id' => 0,
                    'nome' => 'Frete Grátis',
                    'empresa' => 'RARE7',
                    'valor' => 0.00,
                    'prazo_dias' => 10,
                    'data_estimada' => date('d/m/Y', strtotime('+10 days')),
                    'prazo_texto' => '10 dias úteis',
                    'gratis' => true
                ]);
                
                $debug_log[] = "🎉 Frete grátis adicionado!";
            }

            // Ordenar
            usort($opcoesFrete, function($a, $b) {
                if ($a['gratis'] && !$b['gratis']) return -1;
                if (!$a['gratis'] && $b['gratis']) return 1;
                return $a['valor'] <=> $b['valor'];
            });

            $debug_log[] = "✅ Cálculo concluído com sucesso!";

            jsonResponse(true, [
                'cep' => $cepLimpo,
                'endereco' => [
                    'logradouro' => $endereco['logradouro'] ?? '',
                    'bairro' => $endereco['bairro'] ?? '',
                    'cidade' => $endereco['localidade'] ?? '',
                    'uf' => $endereco['uf'] ?? '',
                    'complemento' => $endereco['complemento'] ?? ''
                ],
                'opcoes' => $opcoesFrete,
                'peso_total' => $pesoTotal,
                'dimensoes' => [
                    'altura' => $alturaMax,
                    'largura' => $larguraMax,
                    'comprimento' => $comprimentoMax
                ]
            ], 'Frete calculado com sucesso', $debug_log);
            break;

        default:
            jsonResponse(false, [], 'Ação não reconhecida', $debug_log);
    }

} catch (Exception $e) {
    $debug_log[] = "❌ ERRO: " . $e->getMessage();
    $debug_log[] = "Linha: " . $e->getLine();
    $debug_log[] = "Arquivo: " . $e->getFile();
    
    error_log("Erro na API de Frete: " . $e->getMessage());
    jsonResponse(false, [], 'Erro: ' . $e->getMessage(), $debug_log);
}

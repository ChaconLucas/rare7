<?php
/**
 * API de Carrinho - RARE7 E-commerce
 * Endpoint para operações de carrinho:
 * - sync: Sincronizar carrinho do localStorage
 * - get: Buscar carrinho salvo
 * - add: Adicionar item
 * - update: Atualizar quantidade
 * - remove: Remover item
 * - clear: Limpar carrinho
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';
require_once '../conexao.php';

// Função para resposta JSON
function jsonResponse($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Método não permitido');
}

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Identificar cliente (logado ou sessão)
$clienteId = isset($_SESSION['cliente']['id']) ? $_SESSION['cliente']['id'] : null;
$sessionId = session_id();

try {
    switch ($action) {
        
        // ===== BUSCAR DADOS COMPLETOS DO PRODUTO =====
        case 'getProductData':
            $produtoId = (int)($input['produto_id'] ?? 0);
            $variacaoId = isset($input['variacao_id']) ? (int)$input['variacao_id'] : null;

            if ($produtoId <= 0) {
                jsonResponse(false, [], 'ID do produto inválido');
            }

            // Buscar produto
            $query = "SELECT p.*, c.nome AS categoria_nome 
                      FROM produtos p 
                      LEFT JOIN categorias c ON p.categoria_id = c.id 
                      WHERE p.id = ? AND p.status = 'ativo'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'i', $produtoId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $produto = mysqli_fetch_assoc($result);

            if (!$produto) {
                jsonResponse(false, [], 'Produto não encontrado');
            }

            // Se tem variação, buscar dados da variação
            $variacao = null;
            if ($variacaoId) {
                $queryVar = "SELECT * FROM produto_variacoes WHERE id = ? AND produto_id = ? AND ativo = 1";
                $stmtVar = mysqli_prepare($conn, $queryVar);
                mysqli_stmt_bind_param($stmtVar, 'ii', $variacaoId, $produtoId);
                mysqli_stmt_execute($stmtVar);
                $resultVar = mysqli_stmt_get_result($stmtVar);
                $variacao = mysqli_fetch_assoc($resultVar);
            }

            // Calcular estoque e preço com herança do produto pai quando a variação não define preço próprio
            $estoque = $variacao ? (int)$variacao['estoque'] : (int)$produto['estoque'];
            $preco = (float)$produto['preco'];
            $precoPromocional = isset($produto['preco_promocional']) ? (float)$produto['preco_promocional'] : 0.0;

            if ($variacao) {
                $usaPrecoPai = !isset($variacao['preco']) || $variacao['preco'] === null || (float)$variacao['preco'] <= 0;
                $preco = $usaPrecoPai ? (float)$produto['preco'] : (float)$variacao['preco'];

                if (isset($variacao['preco_promocional']) && $variacao['preco_promocional'] !== null && (float)$variacao['preco_promocional'] > 0) {
                    $precoPromocional = (float)$variacao['preco_promocional'];
                } elseif (!$usaPrecoPai) {
                    $precoPromocional = 0.0;
                }
            }

            $precoFinal = ($precoPromocional > 0 && $precoPromocional < $preco) ? $precoPromocional : $preco;

            // Imagem
            $imagem = $variacao && !empty($variacao['imagem']) ? $variacao['imagem'] : $produto['imagem'];
            if (!empty($imagem) && strpos($imagem, 'http') === false) {
                $imagem = '../admin/assets/images/produtos/' . $imagem;
            }

            // Variação texto
            $variacaoTexto = null;
            if ($variacao) {
                $tipo = $variacao['tipo'] ?? 'Variação';
                $valor = $variacao['valor'] ?? '';
                $variacaoTexto = $tipo . ': ' . $valor;
            }

            jsonResponse(true, [
                'produto_id' => $produto['id'],
                'variacao_id' => $variacaoId,
                'nome' => $produto['nome'],
                'variacao_texto' => $variacaoTexto,
                'preco' => $precoFinal,
                'preco_original' => $preco,
                'preco_promocional' => $precoPromocional,
                'estoque' => $estoque,
                'imagem' => $imagem,
                'tem_promocao' => $precoPromocional > 0 && $precoPromocional < $preco
            ]);
            break;

        // ===== VALIDAR ESTOQUE =====
        case 'validateStock':
            $items = $input['items'] ?? [];
            $errors = [];

            foreach ($items as $item) {
                $produtoId = (int)$item['produto_id'];
                $variacaoId = isset($item['variacao_id']) ? (int)$item['variacao_id'] : null;
                $quantidade = (int)$item['quantidade'];

                // Buscar estoque
                if ($variacaoId) {
                    $query = "SELECT estoque FROM produto_variacoes WHERE id = ? AND produto_id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, 'ii', $variacaoId, $produtoId);
                } else {
                    $query = "SELECT estoque FROM produtos WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, 'i', $produtoId);
                }

                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);

                if (!$row) {
                    $errors[] = "Produto ID {$produtoId} não encontrado";
                    continue;
                }

                $estoqueDisponivel = (int)$row['estoque'];
                if ($quantidade > $estoqueDisponivel) {
                    $errors[] = "{$item['nome']}: estoque insuficiente (disponível: {$estoqueDisponivel})";
                }
            }

            if (!empty($errors)) {
                jsonResponse(false, ['errors' => $errors], implode(', ', $errors));
            }

            jsonResponse(true, [], 'Estoque validado');
            break;

        // ===== CASOS NÃO IMPLEMENTADOS =====
        default:
            jsonResponse(false, [], 'Ação não reconhecida');
    }

} catch (Exception $e) {
    error_log("Erro na API de Carrinho: " . $e->getMessage());
    jsonResponse(false, [], 'Erro no servidor. Tente novamente.');
}

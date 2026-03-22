<?php
/**
 * API para Gerenciar Links do Footer
 * Operações CRUD para cms_footer_links
 */

session_start();

// Verificar autenticação
if (!isset($_SESSION['usuario_logado'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

require_once '../../../../config/base.php';
require_once '../../../../PHP/conexao.php';

// Headers para JSON
header('Content-Type: application/json');

// Obter ação
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Criar novo link
            $coluna = mysqli_real_escape_string($conexao, $_POST['coluna']);
            $texto = mysqli_real_escape_string($conexao, $_POST['texto']);
            $link = mysqli_real_escape_string($conexao, $_POST['link']);
            $ordem = (int)($_POST['ordem'] ?? 0);
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            // Validações
            if (!in_array($coluna, ['produtos', 'atendimento'])) {
                echo json_encode(['success' => false, 'message' => 'Coluna inválida']);
                exit();
            }
            
            if (empty($texto) || empty($link)) {
                echo json_encode(['success' => false, 'message' => 'Texto e link são obrigatórios']);
                exit();
            }
            
            $sql = "INSERT INTO cms_footer_links (coluna, texto, link, ordem, ativo) 
                    VALUES ('$coluna', '$texto', '$link', $ordem, $ativo)";
            
            if (mysqli_query($conexao, $sql)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Link criado com sucesso',
                    'id' => mysqli_insert_id($conexao)
                ]);
            } else {
                throw new Exception('Erro ao criar link: ' . mysqli_error($conexao));
            }
            break;
            
        case 'update':
            // Atualizar link existente
            $id = (int)$_POST['id'];
            $coluna = mysqli_real_escape_string($conexao, $_POST['coluna']);
            $texto = mysqli_real_escape_string($conexao, $_POST['texto']);
            $link = mysqli_real_escape_string($conexao, $_POST['link']);
            $ordem = (int)($_POST['ordem'] ?? 0);
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            // Validações
            if (!in_array($coluna, ['produtos', 'atendimento'])) {
                echo json_encode(['success' => false, 'message' => 'Coluna inválida']);
                exit();
            }
            
            if (empty($texto) || empty($link)) {
                echo json_encode(['success' => false, 'message' => 'Texto e link são obrigatórios']);
                exit();
            }
            
            $sql = "UPDATE cms_footer_links 
                    SET coluna = '$coluna',
                        texto = '$texto',
                        link = '$link',
                        ordem = $ordem,
                        ativo = $ativo,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = $id";
            
            if (mysqli_query($conexao, $sql)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Link atualizado com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao atualizar link: ' . mysqli_error($conexao));
            }
            break;
            
        case 'delete':
            // Excluir link permanentemente
            $id = (int)$_POST['id'];
            
            $sql = "DELETE FROM cms_footer_links WHERE id = $id";
            
            if (mysqli_query($conexao, $sql)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Link excluído com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao excluir link: ' . mysqli_error($conexao));
            }
            break;
            
        case 'toggle_active':
            // Ativar/desativar link
            $id = (int)$_POST['id'];
            $ativo = (int)$_POST['ativo'];
            
            $sql = "UPDATE cms_footer_links 
                    SET ativo = $ativo,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = $id";
            
            if (mysqli_query($conexao, $sql)) {
                echo json_encode([
                    'success' => true, 
                    'message' => $ativo ? 'Link ativado' : 'Link desativado'
                ]);
            } else {
                throw new Exception('Erro ao atualizar status: ' . mysqli_error($conexao));
            }
            break;
            
        case 'reorder':
            // Reordenar links (futuro)
            $links = json_decode($_POST['links'], true);
            
            if (!is_array($links)) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
                exit();
            }
            
            mysqli_begin_transaction($conexao);
            
            try {
                foreach ($links as $ordem => $id) {
                    $id = (int)$id;
                    $ordem = (int)$ordem;
                    $sql = "UPDATE cms_footer_links SET ordem = $ordem WHERE id = $id";
                    mysqli_query($conexao, $sql);
                }
                
                mysqli_commit($conexao);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Links reordenados com sucesso'
                ]);
            } catch (Exception $e) {
                mysqli_rollback($conexao);
                throw $e;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

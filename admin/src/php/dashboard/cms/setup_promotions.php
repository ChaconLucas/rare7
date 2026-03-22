<?php
/**
 * Script para criar a tabela cms_home_promotions
 * Execute este arquivo uma vez acessando via navegador
 */

require_once '../../../../PHP/conexao.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Criar Tabela Promoções</title></head><body>";
echo "<h1>Criando Tabela cms_home_promotions</h1>";
echo "<pre>";

try {
    // Criar tabela
    $sql = "CREATE TABLE IF NOT EXISTS cms_home_promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        subtitulo VARCHAR(255) DEFAULT NULL,
        badge_text VARCHAR(50) DEFAULT NULL COMMENT 'Ex: 15% OFF',
        button_text VARCHAR(100) DEFAULT 'Aproveitar Oferta',
        button_link VARCHAR(255) DEFAULT '#',
        cupom_id INT DEFAULT NULL COMMENT 'FK para tabela cupons',
        data_inicio DATE NOT NULL,
        data_fim DATE NOT NULL,
        ordem INT DEFAULT 0,
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ativo (ativo),
        INDEX idx_ordem (ordem),
        INDEX idx_datas (data_inicio, data_fim),
        FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (mysqli_query($conexao, $sql)) {
        echo "✓ Tabela cms_home_promotions criada com sucesso!\n\n";
        
        // Mostrar estrutura
        echo "Estrutura da tabela:\n";
        $result = mysqli_query($conexao, "DESCRIBE cms_home_promotions");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
        
        echo "\n✓ Sistema de promoções pronto para uso!";
        echo "\n\n<a href='promos.php'>Ir para Gerenciar Promoções</a>";
    } else {
        echo "✗ Erro ao criar tabela: " . mysqli_error($conexao) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "</body></html>";

mysqli_close($conexao);
?>

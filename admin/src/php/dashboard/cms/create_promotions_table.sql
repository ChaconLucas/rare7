-- ====================================================================
-- CRIAÇÃO DA TABELA cms_home_promotions
-- Sistema de Promoções e Ofertas da Home
-- ====================================================================

USE teste_dz;

CREATE TABLE IF NOT EXISTS cms_home_promotions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- VERIFICAÇÃO
-- ====================================================================

SELECT 'Tabela cms_home_promotions criada com sucesso!' AS status;
DESCRIBE cms_home_promotions;

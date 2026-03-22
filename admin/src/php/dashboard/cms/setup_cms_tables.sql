-- ====================================================================
-- SCRIPT SQL - CMS D&Z ADMIN
-- Execute este script no phpMyAdmin ou MySQL Workbench
-- Database: teste_dz
-- ====================================================================

USE teste_dz;

-- ====================================================================
-- TABELA 1: home_settings
-- Armazena configurações e textos da página inicial
-- ====================================================================

CREATE TABLE IF NOT EXISTS home_settings (
    id INT PRIMARY KEY DEFAULT 1,
    hero_title VARCHAR(255) DEFAULT 'Bem-vindo à D&Z',
    hero_subtitle VARCHAR(255) DEFAULT 'Moda com estilo e qualidade',
    hero_description TEXT DEFAULT 'Descubra nossa coleção exclusiva com os melhores produtos para você.',
    hero_button_text VARCHAR(100) DEFAULT 'Ver Coleção',
    hero_button_link VARCHAR(255) DEFAULT '/produtos',
    launch_title VARCHAR(255) DEFAULT 'Lançamentos',
    launch_subtitle VARCHAR(255) DEFAULT 'Confira nossas novidades',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir registro padrão
INSERT INTO home_settings (id) VALUES (1) 
ON DUPLICATE KEY UPDATE id = id;

-- ====================================================================
-- TABELA 2: home_banners
-- Gerencia banners do carrossel da home
-- ====================================================================

CREATE TABLE IF NOT EXISTS home_banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(500) NOT NULL,
    button_text VARCHAR(100) DEFAULT NULL,
    button_link VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- TABELA 3: home_featured_products
-- Vincula produtos às seções da home (Lançamentos, Destaques, etc)
-- ====================================================================

CREATE TABLE IF NOT EXISTS home_featured_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL,
    product_id INT NOT NULL,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_product (section_key, product_id),
    INDEX idx_section (section_key),
    INDEX idx_position (position),
    FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- VIEWS AUXILIARES (OPCIONAL - FACILITA CONSULTAS)
-- ====================================================================

-- View para listar banners ativos ordenados
CREATE OR REPLACE VIEW v_banners_ativos AS
SELECT 
    id, title, subtitle, description, 
    image_path, button_text, button_link, position
FROM home_banners
WHERE is_active = 1
ORDER BY position ASC;

-- View para listar produtos em destaque na home
CREATE OR REPLACE VIEW v_produtos_lancamentos AS
SELECT 
    hfp.id as featured_id,
    hfp.position,
    p.id,
    p.nome,
    p.preco,
    p.preco_promocional,
    p.imagem,
    p.estoque,
    p.status
FROM home_featured_products hfp
INNER JOIN produtos p ON hfp.product_id = p.id
WHERE hfp.section_key = 'launches'
AND p.status = 'ativo'
ORDER BY hfp.position ASC;

-- ====================================================================
-- DADOS DE TESTE (OPCIONAL - REMOVER EM PRODUÇÃO)
-- ====================================================================

-- Banner de exemplo 1
INSERT INTO home_banners (title, subtitle, description, image_path, button_text, button_link, is_active, position) VALUES
('Coleção Verão 2026', 'Moda praia com até 50% OFF', 'Aproveite os melhores preços em nossa coleção de verão exclusiva', 'uploads/banners/banner-verao.jpg', 'Comprar Agora', '/categoria/verao', 1, 1);

-- Banner de exemplo 2
INSERT INTO home_banners (title, subtitle, description, image_path, button_text, button_link, is_active, position) VALUES
('Novidades Toda Semana', 'Fique por dentro das tendências', 'Cadastre-se e receba ofertas exclusivas no seu e-mail', 'uploads/banners/banner-novidades.jpg', 'Ver Novidades', '/novidades', 1, 2);

-- ====================================================================
-- VERIFICAÇÃO
-- ====================================================================

-- Verificar criação das tabelas
SHOW TABLES LIKE 'home_%';

-- Verificar estrutura
DESCRIBE home_settings;
DESCRIBE home_banners;
DESCRIBE home_featured_products;

-- Verificar dados de teste
SELECT * FROM home_settings;
SELECT * FROM home_banners;
SELECT * FROM home_featured_products;

-- ====================================================================
-- FIM DO SCRIPT
-- ====================================================================

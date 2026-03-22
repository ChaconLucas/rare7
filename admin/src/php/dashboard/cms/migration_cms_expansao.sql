-- ====================================================================
-- MIGRAÇÃO CMS - EXPANSÃO HOME E FOOTER
-- Database: teste_dz
-- Data: 2026-03-03
-- ====================================================================
-- IMPORTANTE: Este script verifica a existência de tabelas/colunas
-- antes de criar, garantindo que nada será sobrescrito.
-- ====================================================================

USE teste_dz;

-- ====================================================================
-- PARTE 1: ADICIONAR COLUNAS NA TABELA home_settings (se não existirem)
-- ====================================================================

-- Verificar e adicionar campos para seção "Lançamentos" (botão)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'teste_dz' 
     AND TABLE_NAME = 'home_settings' 
     AND COLUMN_NAME = 'launch_button_text') = 0,
    'ALTER TABLE home_settings ADD COLUMN launch_button_text VARCHAR(100) DEFAULT "Ver Todos os Lançamentos" AFTER launch_subtitle',
    'SELECT "Coluna launch_button_text já existe" AS msg'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'teste_dz' 
     AND TABLE_NAME = 'home_settings' 
     AND COLUMN_NAME = 'launch_button_link') = 0,
    'ALTER TABLE home_settings ADD COLUMN launch_button_link VARCHAR(255) DEFAULT "#catalogo" AFTER launch_button_text',
    'SELECT "Coluna launch_button_link já existe" AS msg'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar campos para seção "Todos os Produtos"
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'teste_dz' 
     AND TABLE_NAME = 'home_settings' 
     AND COLUMN_NAME = 'products_title') = 0,
    'ALTER TABLE home_settings ADD COLUMN products_title VARCHAR(255) DEFAULT "Todos os Produtos" AFTER launch_button_link',
    'SELECT "Coluna products_title já existe" AS msg'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'teste_dz' 
     AND TABLE_NAME = 'home_settings' 
     AND COLUMN_NAME = 'products_subtitle') = 0,
    'ALTER TABLE home_settings ADD COLUMN products_subtitle VARCHAR(255) DEFAULT "Toda a nossa coleção premium em um só lugar" AFTER products_title',
    'SELECT "Coluna products_subtitle já existe" AS msg'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'teste_dz' 
     AND TABLE_NAME = 'home_settings' 
     AND COLUMN_NAME = 'products_button_text') = 0,
    'ALTER TABLE home_settings ADD COLUMN products_button_text VARCHAR(100) DEFAULT "Ver Depoimentos" AFTER products_subtitle',
    'SELECT "Coluna products_button_text já existe" AS msg'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'teste_dz' 
     AND TABLE_NAME = 'home_settings' 
     AND COLUMN_NAME = 'products_button_link') = 0,
    'ALTER TABLE home_settings ADD COLUMN products_button_link VARCHAR(255) DEFAULT "#depoimentos" AFTER products_button_text',
    'SELECT "Coluna products_button_link já existe" AS msg'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualizar registro padrão (apenas se os campos ainda estiverem com valores NULL)
UPDATE home_settings 
SET 
    launch_button_text = COALESCE(launch_button_text, 'Ver Todos os Lançamentos'),
    launch_button_link = COALESCE(launch_button_link, '#catalogo'),
    products_title = COALESCE(products_title, 'Todos os Produtos'),
    products_subtitle = COALESCE(products_subtitle, 'Toda a nossa coleção premium em um só lugar'),
    products_button_text = COALESCE(products_button_text, 'Ver Depoimentos'),
    products_button_link = COALESCE(products_button_link, '#depoimentos')
WHERE id = 1;

-- ====================================================================
-- PARTE 2: CRIAR TABELA cms_home_beneficios (se não existir)
-- ====================================================================

CREATE TABLE IF NOT EXISTS cms_home_beneficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    subtitulo VARCHAR(255) NOT NULL,
    icone VARCHAR(50) NOT NULL COMMENT 'Nome do ícone SVG: truck, shield, refresh, support',
    cor VARCHAR(7) DEFAULT '#10b981' COMMENT 'Cor hex do ícone',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir registros padrão apenas se a tabela estiver vazia
INSERT INTO cms_home_beneficios (titulo, subtitulo, icone, cor, ordem, ativo)
SELECT * FROM (
    SELECT 'Entrega Grátis' as titulo, 'Acima de R$ 99 para todo o Brasil' as subtitulo, 'truck' as icone, '#10b981' as cor, 1 as ordem, 1 as ativo UNION ALL
    SELECT 'Qualidade Premium', 'Produtos testados e aprovados', 'shield', '#E6007E', 2, 1 UNION ALL
    SELECT 'Troca Fácil', '7 dias para trocar ou devolver', 'refresh', '#3b82f6', 3, 1 UNION ALL
    SELECT 'Suporte 24h', 'Atendimento especializado sempre', 'support', '#f59e0b', 4, 1
) AS defaults
WHERE NOT EXISTS (SELECT 1 FROM cms_home_beneficios LIMIT 1);

-- ====================================================================
-- PARTE 3: CRIAR TABELA cms_footer (se não existir)
-- ====================================================================

CREATE TABLE IF NOT EXISTS cms_footer (
    id INT PRIMARY KEY DEFAULT 1,
    marca_titulo VARCHAR(100) DEFAULT 'D&Z',
    marca_subtitulo VARCHAR(100) DEFAULT 'Beauty & Style',
    marca_descricao TEXT DEFAULT 'Transformando a beleza das mulheres brasileiras com produtos premium e atendimento excepcional.',
    telefone VARCHAR(20) DEFAULT '(11) 9999-9999',
    whatsapp VARCHAR(20) DEFAULT '(11) 9999-9999',
    email VARCHAR(100) DEFAULT 'contato@dzecommerce.com',
    instagram VARCHAR(255) DEFAULT '#',
    tiktok VARCHAR(255) DEFAULT '#',
    facebook VARCHAR(255) DEFAULT '#',
    copyright_texto VARCHAR(255) DEFAULT '© 2026 D&Z Beauty • Todos os direitos reservados',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir registro padrão (apenas se não existir)
INSERT INTO cms_footer (id) VALUES (1) 
ON DUPLICATE KEY UPDATE id = id;

-- ====================================================================
-- PARTE 4: CRIAR TABELA cms_footer_links (se não existir)
-- ====================================================================

CREATE TABLE IF NOT EXISTS cms_footer_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coluna ENUM('produtos', 'atendimento') NOT NULL,
    texto VARCHAR(100) NOT NULL,
    link VARCHAR(255) NOT NULL,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_coluna (coluna),
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir links padrão apenas se a tabela estiver vazia
INSERT INTO cms_footer_links (coluna, texto, link, ordem, ativo)
SELECT * FROM (
    SELECT 'produtos' as coluna, 'Unhas Profissionais' as texto, '#' as link, 1 as ordem, 1 as ativo UNION ALL
    SELECT 'produtos', 'Cílios Premium', '#', 2, 1 UNION ALL
    SELECT 'produtos', 'Kits Completos', '#', 3, 1 UNION ALL
    SELECT 'produtos', 'Novidades', '#', 4, 1 UNION ALL
    SELECT 'atendimento', 'Central de Ajuda', '#', 1, 1 UNION ALL
    SELECT 'atendimento', 'Política de Troca', '#', 2, 1 UNION ALL
    SELECT 'atendimento', 'Garantia', '#', 3, 1 UNION ALL
    SELECT 'atendimento', 'Rastreamento', '#', 4, 1 UNION ALL
    SELECT 'atendimento', 'Suporte Premium', '#', 5, 1
) AS defaults
WHERE NOT EXISTS (SELECT 1 FROM cms_footer_links LIMIT 1);

-- ====================================================================
-- VERIFICAÇÃO FINAL
-- ====================================================================

-- Mostrar estrutura atualizada da home_settings
SELECT 'Estrutura home_settings:' AS info;
DESCRIBE home_settings;

-- Mostrar dados atuais
SELECT 'Dados atuais home_settings:' AS info;
SELECT * FROM home_settings WHERE id = 1;

-- Mostrar benefícios cadastrados
SELECT 'Benefícios cadastrados:' AS info;
SELECT id, titulo, subtitulo, icone, cor, ordem, ativo FROM cms_home_beneficios ORDER BY ordem;

-- Mostrar dados do footer
SELECT 'Dados do footer:' AS info;
SELECT * FROM cms_footer WHERE id = 1;

-- Mostrar links do footer
SELECT 'Links do footer:' AS info;
SELECT id, coluna, texto, link, ordem, ativo FROM cms_footer_links WHERE ativo = 1 ORDER BY coluna, ordem;

-- ====================================================================
-- FIM DA MIGRAÇÃO
-- ====================================================================

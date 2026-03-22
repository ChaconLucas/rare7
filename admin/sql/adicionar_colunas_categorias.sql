-- ============================================
-- Adicionar colunas para sistema de menu dinâmico
-- Executar uma única vez no banco teste_dz
-- ============================================

USE teste_dz;

-- Adicionar coluna menu_group (define em qual menu a categoria aparece)
ALTER TABLE categorias 
ADD COLUMN menu_group VARCHAR(30) NOT NULL DEFAULT 'outros' 
AFTER descricao;

-- Adicionar coluna parent_id (permite criar subcategorias)
ALTER TABLE categorias 
ADD COLUMN parent_id INT NULL 
AFTER menu_group;

-- Adicionar índice para melhorar performance nas buscas
ALTER TABLE categorias 
ADD INDEX idx_menu_group (menu_group);

ALTER TABLE categorias 
ADD INDEX idx_parent_id (parent_id);

-- Adicionar chave estrangeira (opcional, mas recomendado)
ALTER TABLE categorias 
ADD CONSTRAINT fk_categoria_parent 
FOREIGN KEY (parent_id) REFERENCES categorias(id) ON DELETE SET NULL;

-- ============================================
-- Atualizar categorias existentes (opcional)
-- ============================================

-- Se já existem categorias, você pode classificá-las manualmente:
-- UPDATE categorias SET menu_group = 'unhas' WHERE nome LIKE '%unha%';
-- UPDATE categorias SET menu_group = 'cilios' WHERE nome LIKE '%cílio%' OR nome LIKE '%cilio%';
-- UPDATE categorias SET menu_group = 'eletronicos' WHERE nome LIKE '%eletrônico%' OR nome LIKE '%eletronico%';

-- ============================================
-- Inserir categorias de exemplo (opcional)
-- ============================================

-- Categorias principais UNHAS
INSERT INTO categorias (nome, descricao, menu_group, parent_id, ativo) VALUES
('Preparadores', 'Produtos para preparação das unhas', 'unhas', NULL, 1),
('Construtores', 'Produtos para construção e alongamento', 'unhas', NULL, 1),
('Acabamentos', 'Produtos de finalização', 'unhas', NULL, 1);

-- Subcategorias de Preparadores
SET @preparadores_id = (SELECT id FROM categorias WHERE nome = 'Preparadores' AND menu_group = 'unhas' LIMIT 1);
INSERT INTO categorias (nome, descricao, menu_group, parent_id, ativo) VALUES
('Primer', 'Primers para unhas', 'unhas', @preparadores_id, 1),
('Base Coat', 'Bases para unhas', 'unhas', @preparadores_id, 1),
('Ultra Bond', 'Produtos de adesão', 'unhas', @preparadores_id, 1),
('Top Coat', 'Finalizadores', 'unhas', @preparadores_id, 1);

-- Subcategorias de Construtores
SET @construtores_id = (SELECT id FROM categorias WHERE nome = 'Construtores' AND menu_group = 'unhas' LIMIT 1);
INSERT INTO categorias (nome, descricao, menu_group, parent_id, ativo) VALUES
('Gel', 'Géis para construção', 'unhas', @construtores_id, 1),
('Polygel', 'Polygel para alongamento', 'unhas', @construtores_id, 1),
('Fibra', 'Fibra de vidro', 'unhas', @construtores_id, 1),
('Pó Acrílico', 'Pós acrílicos', 'unhas', @construtores_id, 1);

-- Categorias CÍLIOS
INSERT INTO categorias (nome, descricao, menu_group, parent_id, ativo) VALUES
('Postiços', 'Cílios postiços completos', 'cilios', NULL, 1),
('Tufos', 'Cílios em tufos', 'cilios', NULL, 1),
('Fio a fio', 'Extensão fio a fio', 'cilios', NULL, 1),
('Colas para Cílios', 'Adesivos para cílios', 'cilios', NULL, 1),
('Removedores de Cílios', 'Removedores de cola', 'cilios', NULL, 1);

-- Categorias ELETRÔNICOS
INSERT INTO categorias (nome, descricao, menu_group, parent_id, ativo) VALUES
('Cabines UV/LED', 'Cabines para secagem', 'eletronicos', NULL, 1),
('Coletores de Pó', 'Coletores e aspiradores', 'eletronicos', NULL, 1),
('Motores para Unha', 'Lixadeiras elétricas', 'eletronicos', NULL, 1),
('Iluminação', 'Luminárias e ring lights', 'eletronicos', NULL, 1);

-- Categorias FERRAMENTAS
INSERT INTO categorias (nome, descricao, menu_group, parent_id, ativo) VALUES
('Alicates', 'Alicates profissionais', 'ferramentas', NULL, 1),
('Espátulas', 'Espátulas e empurradores', 'ferramentas', NULL, 1),
('Tesouras', 'Tesouras de precisão', 'ferramentas', NULL, 1),
('Lixas e Polimentos', 'Lixas e blocos', 'ferramentas', NULL, 1),
('Pincéis', 'Pincéis para nail art', 'ferramentas', NULL, 1),
('Pinças', 'Pinças de precisão', 'ferramentas', NULL, 1);

-- Categorias MARCAS (exemplos)
INSERT INTO categorias (nome, descricao, menu_group, parent_id, ativo) VALUES
('Premium Line', 'Produtos linha premium', 'marcas', NULL, 1),
('Professional', 'Linha profissional', 'marcas', NULL, 1),
('Importados', 'Produtos importados', 'marcas', NULL, 1);

-- ============================================
-- Verificar resultado
-- ============================================

-- Ver todas as categorias organizadas por menu_group
SELECT 
    id,
    nome,
    menu_group,
    parent_id,
    ativo,
    CASE 
        WHEN parent_id IS NULL THEN 'Categoria Principal'
        ELSE 'Subcategoria'
    END as tipo
FROM categorias
ORDER BY menu_group, parent_id IS NOT NULL, nome;

COMMIT;

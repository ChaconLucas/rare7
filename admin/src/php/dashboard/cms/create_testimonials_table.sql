-- Tabela para gerenciar depoimentos de clientes exibidos na home
-- Seção: "O que dizem nossas clientes"

CREATE TABLE IF NOT EXISTS cms_testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL COMMENT 'Nome do cliente',
    cargo_empresa VARCHAR(120) NULL COMMENT 'Cargo/função do cliente (ex: Cliente verificada)',
    texto VARCHAR(600) NOT NULL COMMENT 'Texto do depoimento',
    rating TINYINT NOT NULL DEFAULT 5 COMMENT 'Avaliação de 1 a 5 estrelas',
    avatar_path VARCHAR(255) NULL COMMENT 'Caminho da foto do cliente (opcional)',
    ordem INT DEFAULT 0 COMMENT 'Ordem de exibição',
    ativo TINYINT(1) DEFAULT 1 COMMENT 'Depoimento ativo/inativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir 3 depoimentos de exemplo (conforme layout atual)
INSERT INTO cms_testimonials (nome, cargo_empresa, texto, rating, ordem, ativo) VALUES
('Maria Silva', 'Cliente verificada', 'Simplesmente apaixonada pelos produtos! A qualidade é incrível e o atendimento é excepcional. Minha pele nunca esteve tão bonita!', 5, 1, 1),
('Ana Costa', 'Cliente verificada', 'O kit de unhas é perfeito! Resultado de salão em casa. Economizei muito e o resultado é profissional. Super recomendo!', 5, 2, 1),
('Carla Mendes', 'Cliente verificada', 'Entrega rápida e produtos originais! Já fiz várias compras e sempre fico satisfeita. Virei cliente fiel da D&Z!', 5, 3, 1);

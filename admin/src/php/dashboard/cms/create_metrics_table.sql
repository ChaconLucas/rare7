-- Tabela para gerenciar métricas exibidas na home
-- Exemplo: "98%" - "Clientes satisfeitas", "50k+" - "Produtos vendidos"

CREATE TABLE IF NOT EXISTS cms_home_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor VARCHAR(20) NOT NULL COMMENT 'Valor display (ex: 98%, 50k+, 4.9, 24h)',
    label VARCHAR(60) NOT NULL COMMENT 'Descrição da métrica',
    tipo ENUM('texto', 'numero', 'percentual') DEFAULT 'texto' COMMENT 'Tipo de dado',
    ordem INT DEFAULT 0 COMMENT 'Ordem de exibição',
    ativo TINYINT(1) DEFAULT 1 COMMENT 'Métrica ativa/inativa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir métricas padrão (migrar dos valores hardcoded)
INSERT INTO cms_home_metrics (valor, label, tipo, ordem, ativo) VALUES
('98%', 'Clientes satisfeitas', 'percentual', 1, 1),
('50k+', 'Produtos vendidos', 'texto', 2, 1),
('4.9', 'Avaliação média', 'numero', 3, 1),
('24h', 'Entrega rápida', 'texto', 4, 1);

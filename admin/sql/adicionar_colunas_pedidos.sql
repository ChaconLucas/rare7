-- Adicionar colunas extras na tabela pedidos
-- Execute este SQL se a tabela pedidos já existir mas não tiver essas colunas

ALTER TABLE pedidos 
ADD COLUMN IF NOT EXISTS valor_subtotal DECIMAL(10,2) DEFAULT 0 AFTER valor_total,
ADD COLUMN IF NOT EXISTS  valor_desconto DECIMAL(10,2) DEFAULT 0 AFTER valor_subtotal,
ADD COLUMN IF NOT EXISTS valor_frete DECIMAL(10,2) DEFAULT 0 AFTER valor_desconto,
ADD COLUMN IF NOT EXISTS forma_pagamento VARCHAR(50) AFTER valor_frete,
ADD COLUMN IF NOT EXISTS endereco_entrega TEXT AFTER forma_pagamento,
ADD COLUMN IF NOT EXISTS cep VARCHAR(10) AFTER endereco_entrega,
ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) AFTER cep,
ADD COLUMN IF NOT EXISTS estado VARCHAR(2) AFTER cidade,
ADD COLUMN IF NOT EXISTS cupom_codigo VARCHAR(50) AFTER estado,
ADD COLUMN IF NOT EXISTS mercadopago_preference_id VARCHAR(100) AFTER cupom_codigo,
ADD COLUMN IF NOT EXISTS mercadopago_payment_id VARCHAR(100) AFTER mercadopago_preference_id;

-- Adicionar colunas extras na tabela itens_pedido
ALTER TABLE itens_pedido
ADD COLUMN IF NOT EXISTS variacao_id INT NULL AFTER produto_id,
ADD COLUMN IF NOT EXISTS nome_produto VARCHAR(255) AFTER preco_unitario;

-- Index para melhorar performance
CREATE INDEX IF NOT EXISTS idx_pedidos_cliente ON pedidos(cliente_id);
CREATE INDEX IF NOT EXISTS idx_pedidos_status ON pedidos(status);
CREATE INDEX IF NOT EXISTS idx_itens_pedido ON itens_pedido(pedido_id);
CREATE INDEX IF NOT EXISTS idx_itens_produto ON itens_pedido(produto_id);

ALTER TABLE pedidos 
ADD COLUMN IF NOT EXISTS numero_pedido VARCHAR(50) AFTER id,
ADD COLUMN IF NOT EXISTS status_entrega VARCHAR(100) DEFAULT 'Aguardando postagem' AFTER status,
ADD COLUMN IF NOT EXISTS transportadora VARCHAR(100) AFTER status_entrega,
ADD COLUMN IF NOT EXISTS ultima_atualizacao_rastreio TIMESTAMP NULL AFTER data_atualizacao;

CREATE INDEX IF NOT EXISTS idx_numero_pedido ON pedidos(numero_pedido);
CREATE INDEX IF NOT EXISTS idx_codigo_rastreio ON pedidos(codigo_rastreio);

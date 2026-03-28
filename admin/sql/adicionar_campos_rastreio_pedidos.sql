-- SQL opcional para reforcar suporte de rastreamento no e-commerce
-- Execute conforme necessidade do seu ambiente.

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS numero_pedido VARCHAR(50) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS email_cliente VARCHAR(255) NULL AFTER numero_pedido,
  ADD COLUMN IF NOT EXISTS status_pedido VARCHAR(100) NULL AFTER status,
  ADD COLUMN IF NOT EXISTS status_entrega VARCHAR(100) NULL AFTER status_pedido,
  ADD COLUMN IF NOT EXISTS transportadora VARCHAR(120) NULL AFTER codigo_rastreio,
  ADD COLUMN IF NOT EXISTS data_envio DATETIME NULL AFTER transportadora,
  ADD COLUMN IF NOT EXISTS ultima_atualizacao_rastreio DATETIME NULL AFTER data_envio;

-- Opcional: garantir indice para buscas por codigo
ALTER TABLE pedidos
  ADD INDEX idx_pedidos_codigo_rastreio (codigo_rastreio);

-- Opcional: preencher colunas com dados existentes
UPDATE pedidos
SET
  email_cliente = COALESCE(email_cliente, cliente_email),
  status_pedido = COALESCE(status_pedido, status),
  data_envio = COALESCE(data_envio, data_status_mudanca),
  ultima_atualizacao_rastreio = COALESCE(ultima_atualizacao_rastreio, data_atualizacao)
WHERE 1 = 1;

-- Adicionar colunas para integração com Mercado Pago
-- Execute este SQL antes de testar a integração

-- Adicionar colunas na tabela pedidos para armazenar dados do Mercado Pago
ALTER TABLE pedidos 
ADD COLUMN IF NOT EXISTS mercadopago_preference_id VARCHAR(100) NULL COMMENT 'ID da preferência criada no Mercado Pago',
ADD COLUMN IF NOT EXISTS mercadopago_payment_id VARCHAR(100) NULL COMMENT 'ID do pagamento no Mercado Pago',
ADD COLUMN IF NOT EXISTS mercadopago_status VARCHAR(50) NULL COMMENT 'Status do pagamento no Mercado Pago';

-- Criar índice para facilitar buscas por ID do Mercado Pago
CREATE INDEX IF NOT EXISTS idx_mercadopago_preference ON pedidos(mercadopago_preference_id);
CREATE INDEX IF NOT EXISTS idx_mercadopago_payment ON pedidos(mercadopago_payment_id);

-- Verificar se as colunas foram criadas
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'pedidos' 
AND COLUMN_NAME LIKE 'mercadopago%';

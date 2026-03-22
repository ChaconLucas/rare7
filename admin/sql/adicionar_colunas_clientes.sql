-- Adicionar colunas à tabela clientes se não existirem
-- Execute este SQL para garantir compatibilidade com o checkout

-- Verificar se a tabela existe
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
);

-- Adicionar colunas que podem estar faltando
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS estado VARCHAR(2) AFTER cidade,
ADD COLUMN IF NOT EXISTS cep VARCHAR(10) AFTER estado,
ADD COLUMN IF NOT EXISTS cpf_cnpj VARCHAR(20) AFTER telefone,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL AFTER data_cadastro;

-- Atualizar created_at com os valores de data_cadastro se estiverem vazios
UPDATE clientes 
SET created_at = data_cadastro 
WHERE created_at IS NULL AND data_cadastro IS NOT NULL;

-- Criar índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_clientes_email ON clientes(email);
CREATE INDEX IF NOT EXISTS idx_clientes_telefone ON clientes(telefone);
CREATE INDEX IF NOT EXISTS idx_clientes_status ON clientes(status);

-- Verificar estrutura final
SHOW COLUMNS FROM clientes;

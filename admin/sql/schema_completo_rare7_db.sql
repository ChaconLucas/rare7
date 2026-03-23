-- ============================================================
-- D&Z Admin / Cliente - Schema completo (rare7_db)
-- Idempotente: pode rodar mais de uma vez
-- ============================================================

CREATE DATABASE IF NOT EXISTS adm_rare
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE adm_rare;

SET NAMES utf8mb4;
SET time_zone = '-03:00';

-- ============================================================
-- NUCLEO ADMIN / CONFIG
-- ============================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    admin_nome VARCHAR(255) NOT NULL,
    acao TEXT NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_admin_data (admin_id, data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    UNIQUE KEY unique_user_session (user_id, session_id),
    INDEX idx_user_activity (user_id, last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes_gerais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campo VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host_smtp VARCHAR(255) DEFAULT 'smtp.gmail.com',
    porta_smtp INT DEFAULT 465,
    email_usuario VARCHAR(255),
    senha_smtp TEXT,
    email_remetente VARCHAR(255),
    nome_remetente VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_email_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    conteudo TEXT NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destinatario VARCHAR(255) NOT NULL,
    assunto VARCHAR(500) NOT NULL,
    status ENUM('enviado', 'erro') NOT NULL,
    erro TEXT,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_email_data (data_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CATALOGO
-- ============================================================

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT,
    menu_group VARCHAR(30) NOT NULL DEFAULT 'outros',
    parent_id INT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_menu_group (menu_group),
    INDEX idx_parent_id (parent_id),
    CONSTRAINT fk_categoria_parent FOREIGN KEY (parent_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    preco_promocional DECIMAL(10,2) NULL,
    estoque INT NOT NULL DEFAULT 0,
    categoria VARCHAR(100) NULL,
    categoria_id INT NULL,
    subcategoria VARCHAR(100) NULL,
    marca VARCHAR(100) NULL,
    sku VARCHAR(50) NULL UNIQUE,
    imagem VARCHAR(255) NULL,
    imagem_principal VARCHAR(255) NULL,
    imagens LONGTEXT NULL,
    peso DECIMAL(8,3) NULL,
    altura DECIMAL(8,2) NULL,
    largura DECIMAL(8,2) NULL,
    comprimento DECIMAL(8,2) NULL,
    dimensoes VARCHAR(100) NULL,
    video_url VARCHAR(500) NULL,
    garantia VARCHAR(100) NULL,
    origem VARCHAR(100) NULL,
    destaque TINYINT(1) DEFAULT 0,
    tags TEXT,
    seo_title VARCHAR(255),
    seo_description TEXT,
    status ENUM('ativo','inativo','rascunho') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_produto_status (status),
    INDEX idx_produto_categoria_id (categoria_id),
    INDEX idx_produto_slug (slug),
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produto_variacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    valor VARCHAR(100) NOT NULL,
    sku VARCHAR(100) NULL,
    sku_variacao VARCHAR(100) NULL,
    preco_adicional DECIMAL(10,2) DEFAULT 0,
    preco DECIMAL(10,2) NULL,
    preco_promocional DECIMAL(10,2) NULL,
    estoque INT DEFAULT 0,
    imagem VARCHAR(255) NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_var_produto (produto_id),
    INDEX idx_var_ativo (ativo),
    CONSTRAINT fk_variacao_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CLIENTES / PEDIDOS
-- ============================================================

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NULL,
    cpf VARCHAR(14) NULL UNIQUE,
    cpf_cnpj VARCHAR(20) NULL,
    data_nascimento DATE NULL,
    whatsapp VARCHAR(20) NULL,
    telefone VARCHAR(20) NULL,
    endereco TEXT NULL,
    rua VARCHAR(255) NULL,
    numero VARCHAR(20) NULL,
    complemento VARCHAR(255) NULL,
    bairro VARCHAR(100) NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    uf VARCHAR(2) NULL,
    cep VARCHAR(10) NULL,
    notas_internas TEXT,
    status VARCHAR(20) DEFAULT 'Ativo',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_atualizacao TIMESTAMP NULL,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    INDEX idx_cliente_status (status),
    INDEX idx_cliente_cpf_cnpj (cpf_cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_fluxo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cor_hex VARCHAR(7) NOT NULL DEFAULT '#ff00d4',
    baixa_estoque TINYINT(1) DEFAULT 0,
    bloquear_edicao TINYINT(1) DEFAULT 0,
    gerar_logistica TINYINT(1) DEFAULT 0,
    notificar TINYINT(1) DEFAULT 0,
    estornar_estoque TINYINT(1) DEFAULT 0,
    gerar_link_cobranca TINYINT(1) DEFAULT 0,
    sla_horas INT DEFAULT 0,
    mensagem_template TEXT,
    mensagem_email TEXT,
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fluxo_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    cliente_nome VARCHAR(255) NULL,
    cliente_email VARCHAR(255) NULL,
    valor_subtotal DECIMAL(10,2) DEFAULT 0,
    valor_desconto DECIMAL(10,2) DEFAULT 0,
    valor_frete DECIMAL(10,2) DEFAULT 0,
    valor_total DECIMAL(10,2) NOT NULL,
    forma_pagamento VARCHAR(50) NULL,
    status_pagamento VARCHAR(50) DEFAULT 'Pendente',
    parcelas INT DEFAULT 1,
    valor_parcela DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(100) DEFAULT 'Pedido Recebido',
    endereco_entrega TEXT NULL,
    cep VARCHAR(10) NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    cupom_codigo VARCHAR(50) NULL,
    codigo_rastreio VARCHAR(100) NULL,
    link_rastreio TEXT NULL,
    comprovante_pagamento VARCHAR(255) NULL,
    mercadopago_preference_id VARCHAR(100) NULL,
    mercadopago_payment_id VARCHAR(100) NULL,
    mercadopago_status VARCHAR(50) NULL,
    observacoes TEXT,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_entrega DATE NULL,
    data_status_mudanca TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pedido_cliente (cliente_id),
    INDEX idx_pedido_status (status),
    INDEX idx_pedido_mp_preference (mercadopago_preference_id),
    INDEX idx_pedido_mp_payment (mercadopago_payment_id),
    CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    variacao_id INT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    nome_produto VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_pedido (pedido_id),
    INDEX idx_item_produto (produto_id),
    INDEX idx_item_variacao (variacao_id),
    CONSTRAINT fk_item_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_variacao FOREIGN KEY (variacao_id) REFERENCES produto_variacoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos_historico_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    status_anterior VARCHAR(100),
    status_novo VARCHAR(100) NOT NULL,
    data_mudanca TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_alteracao VARCHAR(100),
    observacoes TEXT,
    email_enviado TINYINT(1) DEFAULT 0,
    INDEX idx_pedido_data (pedido_id, data_mudanca),
    CONSTRAINT fk_historico_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CUPONS / PAGAMENTOS / FRETE
-- ============================================================

CREATE TABLE IF NOT EXISTS cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT NULL,
    tipo VARCHAR(30) NULL,
    tipo_desconto ENUM('porcentagem','percentual','valor_fixo','frete_gratis','brinde','progressivo','fixo') NOT NULL DEFAULT 'valor_fixo',
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_minimo DECIMAL(10,2) DEFAULT 0,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    data_expiracao DATE NULL,
    usos_maximos INT NULL,
    usos_realizados INT DEFAULT 0,
    quantidade_usada INT DEFAULT 0,
    limite_uso_total INT DEFAULT 100,
    limite_uso_cpf INT DEFAULT 1,
    uso_diario INT DEFAULT 50,
    primeira_compra TINYINT(1) DEFAULT 0,
    categoria_especifica VARCHAR(50) NULL,
    brinde_item VARCHAR(100) NULL,
    progressivo_config JSON NULL,
    economia_gerada DECIMAL(12,2) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cupom_ativo (ativo),
    INDEX idx_cupom_data_exp (data_expiracao),
    INDEX idx_cupom_periodo (data_inicio, data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_provider ENUM('mercadopago','stripe','paypal','outros') DEFAULT 'mercadopago',
    custom_provider_name VARCHAR(100) NULL,
    gateway_active TINYINT(1) DEFAULT 0,
    public_key TEXT,
    secret_key TEXT,
    client_id TEXT,
    client_secret TEXT,
    environment ENUM('sandbox','production') DEFAULT 'sandbox',
    method_pix TINYINT(1) DEFAULT 0,
    method_credit_card TINYINT(1) DEFAULT 0,
    method_debit_card TINYINT(1) DEFAULT 0,
    method_boleto TINYINT(1) DEFAULT 0,
    payment_currency ENUM('BRL','USD','EUR') DEFAULT 'BRL',
    payment_gateway_id VARCHAR(100) NULL,
    webhook_url TEXT,
    min_value_pix DECIMAL(10,2) DEFAULT 5.00,
    min_value_card DECIMAL(10,2) DEFAULT 10.00,
    min_value_debit DECIMAL(10,2) DEFAULT 10.00,
    min_value_boleto DECIMAL(10,2) DEFAULT 20.00,
    max_installments INT DEFAULT 12,
    free_installments INT DEFAULT 1,
    interest_rate DECIMAL(5,2) DEFAULT 2.50,
    payment_maintenance TINYINT(1) DEFAULT 0,
    maintenance_message TEXT,
    logs_enabled TINYINT(1) DEFAULT 0,
    logs_level ENUM('basico','detalhado') DEFAULT 'basico',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS freight_integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_name VARCHAR(100) NOT NULL,
    provider_slug VARCHAR(50) NOT NULL UNIQUE,
    active TINYINT(1) DEFAULT 0,
    environment ENUM('sandbox','production') DEFAULT 'sandbox',
    api_key TEXT,
    token TEXT,
    secret_key TEXT,
    timeout INT DEFAULT 30,
    priority INT DEFAULT 1,
    webhook_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_freight_provider_active (provider_slug, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS freight_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NULL,
    service_code VARCHAR(50) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    company_name VARCHAR(100) NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_freight_service_active (active),
    CONSTRAINT fk_freight_service_integration FOREIGN KEY (integration_id) REFERENCES freight_integrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS freight_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin_zipcode VARCHAR(9) NOT NULL DEFAULT '00000-000',
    default_weight DECIMAL(8,3) DEFAULT 0.500,
    default_height DECIMAL(8,2) DEFAULT 20.00,
    default_width DECIMAL(8,2) DEFAULT 30.00,
    default_length DECIMAL(8,2) DEFAULT 40.00,
    default_product_value DECIMAL(10,2) DEFAULT 0.00,
    margin_type ENUM('percentage','fixed') DEFAULT 'percentage',
    margin_value DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'BRL',
    rounding_type ENUM('floor','ceil','round') DEFAULT 'round',
    calculation_mode ENUM('lowest_price','lowest_time','priority') DEFAULT 'lowest_price',
    free_shipping_threshold DECIMAL(10,2) DEFAULT 0.00,
    minimum_order_value DECIMAL(10,2) DEFAULT 0.00,
    maximum_freight_value DECIMAL(10,2) DEFAULT 999.99,
    fallback_enabled TINYINT(1) DEFAULT 1,
    fallback_value DECIMAL(10,2) DEFAULT 15.00,
    fallback_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CMS
-- ============================================================

CREATE TABLE IF NOT EXISTS home_settings (
    id INT PRIMARY KEY DEFAULT 1,
    hero_title VARCHAR(255) DEFAULT 'Bem-vindo à D&Z',
    hero_kicker VARCHAR(120) DEFAULT 'RARE EXPERIENCE',
    hero_logo_path VARCHAR(255) DEFAULT 'assets/images/logo.png',
    hero_subtitle VARCHAR(255) DEFAULT 'Moda com estilo e qualidade',
    hero_description TEXT,
    hero_button_text VARCHAR(100) DEFAULT 'Ver Coleção',
    hero_button_link VARCHAR(255) DEFAULT '/produtos',
    launch_title VARCHAR(255) DEFAULT 'Lançamentos',
    launch_subtitle VARCHAR(255) DEFAULT 'Confira nossas novidades',
    benefits_title VARCHAR(255) DEFAULT 'Beneficios Rare',
    benefits_subtitle VARCHAR(255) DEFAULT 'Acabamento premium e experiencia de compra refinada.',
    launch_button_text VARCHAR(100) DEFAULT 'Ver Todos os Lançamentos',
    launch_button_link VARCHAR(255) DEFAULT '#catalogo',
    products_title VARCHAR(255) DEFAULT 'Todos os Produtos',
    products_subtitle VARCHAR(255) DEFAULT 'Toda a nossa coleção premium em um só lugar',
    products_button_text VARCHAR(100) DEFAULT 'Ver Depoimentos',
    products_button_link VARCHAR(255) DEFAULT '#depoimentos',
    banner_interval INT DEFAULT 6,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    description TEXT,
    image_path VARCHAR(500) NOT NULL,
    button_text VARCHAR(100),
    button_link VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_home_banner_active (is_active),
    INDEX idx_home_banner_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_featured_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL,
    product_id INT NOT NULL,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_product (section_key, product_id),
    INDEX idx_section (section_key),
    INDEX idx_position (position),
    CONSTRAINT fk_home_featured_product FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_home_beneficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    subtitulo VARCHAR(255) NOT NULL,
    icone VARCHAR(50) NOT NULL,
    cor VARCHAR(7) DEFAULT '#10b981',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_benef_ativo (ativo),
    INDEX idx_benef_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_footer (
    id INT PRIMARY KEY DEFAULT 1,
    marca_titulo VARCHAR(100) DEFAULT 'D&Z',
    marca_subtitulo VARCHAR(100) DEFAULT 'Beauty & Style',
    marca_descricao TEXT,
    telefone VARCHAR(20),
    whatsapp VARCHAR(20),
    email VARCHAR(100),
    instagram VARCHAR(255),
    tiktok VARCHAR(255),
    facebook VARCHAR(255),
    copyright_texto VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_footer_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coluna ENUM('produtos','atendimento') NOT NULL,
    texto VARCHAR(100) NOT NULL,
    link VARCHAR(255) NOT NULL,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_footer_coluna (coluna),
    INDEX idx_footer_ativo (ativo),
    INDEX idx_footer_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_home_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    subtitulo VARCHAR(255),
    badge_text VARCHAR(50),
    button_text VARCHAR(100) DEFAULT 'Aproveitar Oferta',
    button_link VARCHAR(255) DEFAULT '#',
    cupom_id INT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_promo_ativo (ativo),
    INDEX idx_promo_ordem (ordem),
    INDEX idx_promo_datas (data_inicio, data_fim),
    CONSTRAINT fk_cms_promo_cupom FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_home_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor VARCHAR(20) NOT NULL,
    label VARCHAR(60) NOT NULL,
    tipo ENUM('texto','numero','percentual') DEFAULT 'texto',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_metrics_ativo (ativo),
    INDEX idx_metrics_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    cargo_empresa VARCHAR(120),
    texto VARCHAR(600) NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    avatar_path VARCHAR(255),
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_testimonials_ativo (ativo),
    INDEX idx_testimonials_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CHAT
-- ============================================================

CREATE TABLE IF NOT EXISTS conversas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_nome VARCHAR(255),
    usuario_email VARCHAR(255),
    status VARCHAR(30) NOT NULL DEFAULT 'ativa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_conversa_status (status),
    INDEX idx_conversa_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversa_id INT NOT NULL,
    remetente ENUM('usuario','ia','admin') NOT NULL,
    remetente_nome VARCHAR(255) NULL,
    conteudo TEXT NOT NULL,
    lida TINYINT(1) DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mensagem_conversa (conversa_id),
    INDEX idx_mensagem_lida (lida),
    INDEX idx_mensagem_timestamp (timestamp),
    CONSTRAINT fk_mensagem_conversa FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- REVENDA / LEADS
-- ============================================================

CREATE TABLE IF NOT EXISTS vendedoras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads_revendedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_responsavel VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    nome_loja VARCHAR(255) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,
    ramo_loja VARCHAR(100) NOT NULL,
    faturamento ENUM('ate_5000','5001_15000','15001_30000','acima_30000') NOT NULL,
    interesse SET('unha','cilios') NOT NULL,
    vendedora_id INT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vendedora (vendedora_id),
    INDEX idx_lead_whatsapp (whatsapp),
    CONSTRAINT fk_lead_vendedora FOREIGN KEY (vendedora_id) REFERENCES vendedoras(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS controle_ciclo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendedoras_usadas TEXT,
    ciclo_completo TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS revendedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    empresa VARCHAR(255),
    cnpj VARCHAR(20),
    cidade VARCHAR(100),
    estado VARCHAR(50),
    experiencia TEXT,
    mensagem TEXT,
    status ENUM('pendente','aprovado','rejeitado') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rev_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- LEGADO (TELAS ADMIN/PHP ANTIGAS)
-- ============================================================

CREATE TABLE IF NOT EXISTS adm_rare (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    data_nascimento DATE NULL,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEEDS MINIMOS
-- ============================================================

INSERT INTO home_settings (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO cms_footer (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO payment_settings (id, gateway_provider, environment)
VALUES (1, 'mercadopago', 'sandbox')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO freight_settings (id)
VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

INSERT IGNORE INTO status_fluxo (nome, cor_hex, baixa_estoque, bloquear_edicao, gerar_logistica, notificar, ordem)
VALUES
('Pedido Recebido', '#ff00d4', 0, 0, 0, 1, 1),
('Pagamento Confirmado', '#41f1b6', 0, 0, 0, 1, 2),
('Em Preparação', '#ffbb55', 1, 1, 0, 1, 3),
('Enviado', '#007bff', 0, 0, 1, 1, 4),
('Entregue', '#28a745', 0, 0, 0, 1, 5);

INSERT IGNORE INTO configuracoes_gerais (campo, valor)
VALUES
('smtp_host', 'smtp.gmail.com'),
('smtp_porta', '587'),
('smtp_email', ''),
('smtp_senha', ''),
('email_remetente', ''),
('nome_remetente', 'D&Z Nails');

INSERT IGNORE INTO configuracoes (chave, valor)
VALUES
('smtp_host', 'smtp.gmail.com'),
('smtp_porta', '587'),
('smtp_email', ''),
('smtp_senha', ''),
('email_remetente', ''),
('nome_remetente', 'D&Z Nails');

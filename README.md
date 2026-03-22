# 🎯 D&Z Admin - Sistema Completo de Gestão E-commerce

**Painel administrativo moderno com CMS integrado para gerenciamento completo de e-commerce de produtos de beleza profissional.**

---

## 📋 **Índice**

1. [Sobre o Projeto](#-sobre-o-projeto)
2. [Tecnologias Utilizadas](#-tecnologias-utilizadas)
3. [Estrutura do Projeto](#-estrutura-do-projeto)
4. [Instalação Rápida](#-instalação-rápida)
5. [Módulo CMS](#-módulo-cms)
   - [Sistema de Categorias](#sistema-de-categorias)
   - [Depoimentos](#depoimentos-testimonials)
   - [Promoções](#promoções-e-ofertas)
   - [Métricas](#métricas-da-empresa)
   - [Banners e Conteúdo](#banners-e-conteúdo)
6. [Gestão de Pedidos](#-gestão-de-pedidos)
7. [Sistema de Chat com IA](#-sistema-de-chat-com-ia)
8. [Área do Cliente](#-área-do-cliente)
9. [Configurações](#-configurações)
10. [Segurança](#-segurança)

---

## 🚀 **Sobre o Projeto**

O **D&Z Admin** é um sistema completo de administração desenvolvido para e-commerce de produtos profissionais de beleza (unhas, cílios) com:

- ✅ **Painel Admin Completo**: Produtos, pedidos, vendedores, métricas
- ✅ **CMS Integrado**: Gerenciamento de conteúdo do site público
- ✅ **Chat com IA**: Sistema de atendimento automatizado (Groq API)
- ✅ **Sistema de Logs**: Auditoria completa de ações
- ✅ **Site Público**: Loja virtual integrada ao admin
- ✅ **Dashboard Analytics**: Gráficos e métricas em tempo real

---

## 💻 **Tecnologias Utilizadas**

### **Backend:**

- PHP 8.0+
- MySQL 8.0 (utf8mb4)
- XAMPP Local Server

### **Frontend:**

- HTML5 + CSS3 (Grid/Flexbox)
- JavaScript ES6+ (Vanilla)
- Chart.js
- Material Symbols Sharp

### **APIs:**

- Groq API (llama-3.3-70b-versatile) - Chat IA
- PHPMailer - Envio de e-mails

---

## 📁 **Estrutura do Projeto**

```
admin-teste/
├── 📂 admin/                      # Painel administrativo
│   ├── config/
│   │   ├── base.php              # BASE_URL e caminhos globais
│   │   ├── config.php            # Credenciais banco
│   │   └── email-config.php      # Configurações e-mail
│   ├── src/
│   │   ├── css/                  # Estilos do dashboard
│   │   ├── js/                   # JavaScript
│   │   │   ├── dashboard.js      # Funções principais
│   │   │   └── contador-auto.js  # Contador mensagens
│   │   └── php/
│   │       ├── sistema.php       # API principal
│   │       └── dashboard/        # Páginas admin
│   │           ├── index.php     # Dashboard
│   │           ├── products.php  # Produtos
│   │           ├── addproducts.php # Adicionar/Editar produtos
│   │           ├── orders.php    # Pedidos
│   │           ├── customers.php # Clientes
│   │           ├── menssage.php  # Chat
│   │           ├── cupons.php    # Cupons de desconto
│   │           ├── revendedores.php # Revendedores
│   │           ├── gerenciar-vendedoras.php # Vendedores
│   │           ├── gestao-fluxo.php # Gestão de fluxo
│   │           ├── geral.php     # Configurações gerais
│   │           ├── pagamentos.php # Config pagamentos
│   │           ├── frete.php     # Config frete
│   │           ├── automacao.php # Automação
│   │           ├── metricas.php  # Métricas
│   │           └── cms/          # Sistema CMS ⭐
│   │               ├── home.php          # Textos da home
│   │               ├── banners.php       # Banners carrossel
│   │               ├── featured.php      # Produtos destaque
│   │               ├── promos.php        # Promoções
│   │               ├── testimonials.php  # Depoimentos
│   │               ├── metrics.php       # Métricas empresa
│   │               ├── cms_api.php       # API do CMS
│   │               └── setup_*.php       # Scripts de setup
│   └── PHP/                      # Core PHP
│       ├── conexao.php           # Conexão MySQL
│       ├── acoes.php             # CRUD usuários
│       ├── login.php             # Sistema login
│       └── logout.php            # Logout
├── 📂 cliente/                    # Site público (loja)
│   ├── index.php                 # Home da loja
│   ├── cms_data_provider.php     # Provider CMS ⭐
│   ├── conexao.php               # Conexão cliente
│   └── pages/                    # Páginas cliente
│       ├── carrinho.php
│       ├── login.php
│       ├── minha-conta.php
│       └── pedidos.php
├── 📂 uploads/                    # Uploads de arquivos
│   ├── banners/                  # Imagens de banners
│   ├── produtos/                 # Imagens de produtos
│   └── testimonials/             # Avatars de clientes
└── composer.json                  # Dependências PHP
```

---

## 🛠 **Instalação Rápida**

### **Passo 1: Requisitos**

- XAMPP instalado
- PHP 7.4+ com extensões: mysqli, curl, gd
- MySQL 8.0+

### **Passo 2: Banco de Dados**

1. Acesse phpMyAdmin: `http://localhost/phpmyadmin`
2. Crie o banco: `teste_dz` (Cotejamento: `utf8mb4_unicode_ci`)
3. Execute os scripts SQL na ordem:
   - `admin/sql/criar_tabelas_dashboard.sql` (tabelas principais)
   - `admin/src/php/dashboard/cms/setup_cms_tables.sql` (CMS básico)

### **Passo 3: Configuração**

**Arquivo:** `admin/config/config.php`

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'teste_dz');

// API Groq (Chat IA)
define('GROQ_API_KEY', 'sua-chave-aqui');
```

**Arquivo:** `admin/config/base.php` (já configurado)

```php
<?php
define('BASE_URL', '/admin-teste/');
define('API_SISTEMA_URL', BASE_URL . 'admin/src/php/sistema.php');
```

### **Passo 4: Acesso**

- **Admin:** `http://localhost/admin-teste/admin/PHP/login.php`
- **Site Público:** `http://localhost/admin-teste/cliente/`

---

## 🎨 **Módulo CMS**

### **O que é?**

Sistema de gerenciamento de conteúdo integrado ao painel admin para editar o site público sem alterar código.

### **Funcionalidades Principais:**

#### 1️⃣ **Home - Textos Principais**

- Seção Hero (título, subtítulo, descrição, botão)
- Seção Lançamentos (título, subtítulo)

#### 2️⃣ **Banners do Carrossel**

- Upload de imagens (JPG, PNG, WEBP - máx 2MB)
- Título, subtítulo, descrição
- Botão de ação (texto + link)
- Ativar/Desativar
- Ordenação (↑↓)
- CRUD completo

#### 3️⃣ **Produtos em Destaque**

- Selecionar produtos da base existente
- Busca/filtro em tempo real
- Ordenação personalizada
- Limite: 4-8 produtos

#### 4️⃣ **Links do Footer**

- ✅ CRUD completo de links do footer
- ✅ Organização em colunas (Produtos / Atendimento)
- ✅ Ativar/Desativar links individualmente
- ✅ Controlar ordem de exibição
- ✅ Edição de texto e URL
- ✅ Integração automática no site público

---

### **Sistema de Categorias**

Sistema reutilizável para gerenciar categorias de produtos.

#### **Tabela: `categorias`**

```sql
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL UNIQUE,
    descricao TEXT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

#### **Funcionalidades:**

- ✅ Seleção de categorias existentes via dropdown
- ✅ Criação de novas categorias automaticamente
- ✅ Prevenção de duplicatas (case-insensitive)
- ✅ Edição e exclusão de categorias
- ✅ Persistência automática no banco

#### **Uso no Formulário de Produtos:**

1. Select mostra categorias ativas ordenadas alfabeticamente
2. Última opção: **"+ Criar nova categoria"**
3. Ao criar nova: campo de input aparece
4. Backend verifica duplicatas (case-insensitive)
5. Produto salvo com `categoria_id`

#### **Prevenção de Duplicatas:**

```php
// Backend verifica: "Eletrônicos" = "ELETRÔNICOS" = "eletrônicos"
SELECT id FROM categorias WHERE LOWER(nome) = LOWER(?)
```

**Arquivo Principal:** `admin/src/php/dashboard/addproducts.php`

---

### **Depoimentos (Testimonials)**

Sistema de gerenciamento de depoimentos de clientes.

#### **Tabela: `cms_testimonials`**

```sql
CREATE TABLE cms_testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    cargo_empresa VARCHAR(120) NULL,
    texto VARCHAR(600) NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    avatar_path VARCHAR(255) NULL,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Funcionalidades:**

- ✅ CRUD completo de depoimentos
- ✅ Upload de avatar (JPG, PNG, WEBP)
- ✅ Avatar automático com inicial se sem foto
- ✅ Avaliação de 1 a 5 estrelas
- ✅ Ordenação personalizada
- ✅ Ativar/Desativar depoimentos
- ✅ Limite de 600 caracteres no texto

#### **Setup Inicial:**

```
Acesse: admin/src/php/dashboard/cms/setup_testimonials.php
```

#### **Integração Cliente:**

```php
// cliente/cms_data_provider.php
$testimonials = $cms->getTestimonials(3);

// Renderiza 3 depoimentos ativos mais recentes
```

#### **Recursos Visuais:**

- Cards com glassmorphism
- 5 gradientes de cores para avatars
- Estrelas dinâmicas conforme rating
- Preview de avatar ao fazer upload

**Arquivos:**

- Admin: `admin/src/php/dashboard/cms/testimonials.php`
- API: `cms_api.php` (endpoints: list, add, update, toggle, delete)

---

### **Promoções e Ofertas**

Sistema de gerenciamento de blocos promocionais.

#### **Tabela: `cms_home_promotions`**

```sql
CREATE TABLE cms_home_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    subtitulo VARCHAR(255),
    badge_text VARCHAR(50),        -- Ex: "15% OFF"
    button_text VARCHAR(100),
    button_link VARCHAR(255),
    cupom_id INT,                  -- FK para cupons
    data_inicio DATE,
    data_fim DATE,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cupom_id) REFERENCES cupons(id)
);
```

#### **Funcionalidades:**

- ✅ CRUD completo de promoções
- ✅ Vinculação com cupons do sistema
- ✅ Badge customizável (ex: "15% OFF")
- ✅ Período de validade (data início/fim)
- ✅ Ordenação e toggle de status
- ✅ Seleção de cupons ativos

#### **Setup Inicial:**

```
Acesse: admin/src/php/dashboard/cms/setup_promotions.php
```

#### **API Endpoints:**

- `list_promotions` - Lista todas
- `add_promotion` - Cria nova
- `update_promotion` - Atualiza
- `toggle_promotion` - Ativa/desativa
- `delete_promotion` - Exclui
- `list_coupons_simple` - Lista cupons para seleção

**Arquivo Principal:** `admin/src/php/dashboard/cms/promos.php`

---

### **Métricas da Empresa**

Sistema de métricas estatísticas exibidas na home.

#### **Tabela: `cms_home_metrics`**

```sql
CREATE TABLE cms_home_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor VARCHAR(20) NOT NULL,    -- Ex: "98%", "50k+", "4.9"
    label VARCHAR(60) NOT NULL,    -- Ex: "Clientes satisfeitas"
    tipo ENUM('texto','numero','percentual') DEFAULT 'texto',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Funcionalidades:**

- ✅ CRUD completo de métricas
- ✅ Tipos: texto, número, percentual
- ✅ Ordenação personalizada
- ✅ Ativar/Desativar
- ✅ Limite: 3-5 métricas ativas recomendado

#### **Dados Iniciais (Setup):**

- 98% - Clientes satisfeitas
- 50k+ - Produtos vendidos
- 4.9 - Avaliação média
- 24h - Entrega rápida

#### **Setup Inicial:**

```
Acesse: admin/src/php/dashboard/cms/setup_metrics.php
```

#### **Integração Cliente:**

```php
$metricas = $cms->getActiveMetrics();
// Renderiza métricas ativas ordenadas
```

**Arquivo Principal:** `admin/src/php/dashboard/cms/metrics.php`

---

### **Tabelas do CMS:**

```sql
-- Tabelas principais
home_settings              -- Textos da home (singleton)
home_banners               -- Banners do carrossel
home_featured_products     -- Produtos em destaque
cms_home_beneficios        -- Benefícios da home
cms_footer                 -- Dados do footer (singleton)
cms_footer_links           -- Links do footer
cms_testimonials           -- Depoimentos de clientes
cms_home_promotions        -- Promoções e ofertas
cms_home_metrics           -- Métricas da empresa
categorias                 -- Categorias de produtos
```

---

### **Integração Site Público:**

**Arquivo:** `cliente/cms_data_provider.php`

```php
<?php
// Provider centralizado para todos os dados do CMS
$cms = new CMSProvider($conexao);

// Métodos disponíveis:
$cmsData = $cms->getAllData();           // Todos os dados
$banners = $cms->getBanners();           // Banners ativos
$settings = $cms->getSettings();         // Textos da home
$featured = $cms->getFeaturedProducts(); // Produtos destaque
$testimonials = $cms->getTestimonials(); // Depoimentos
$metrics = $cms->getActiveMetrics();     // Métricas
$promotions = $cms->getPromotions();     // Promoções
```

**Uso no HTML:**

```php
<!-- Exemplo: Banners dinâmicos -->
<?php foreach ($banners as $banner): ?>
    <div class="banner-slide">
        <img src="<?= getBannerImageUrl($banner['image_path']) ?>"
             alt="<?= htmlspecialchars($banner['title']) ?>">
        <h2><?= htmlspecialchars($banner['title']) ?></h2>
    </div>
<?php endforeach; ?>
```

---

## 📦 **Gestão de Pedidos**

### **Visão Geral**

Sistema completo de gerenciamento de pedidos com filtros avançados e integração com Gestão de Fluxo.

### **Funcionalidades:**

#### 1️⃣ **Filtros e Navegação**

- ✅ Filtro por data (início e fim)
- ✅ Abas de navegação:
  - Todos os Pedidos
  - Aguardando Pagamento
  - Pagos
  - Enviados
  - Reembolsos
- ✅ Busca por Nome, CPF ou Nº do Pedido

#### 2️⃣ **Tabela Dinâmica**

- ✅ Listagem de pedidos com dados do banco
- ✅ Integração com Gestão de Fluxo (status e cores)
- ✅ Colunas: ID, Data, Cliente, Valor Total, Status, Ações
- ✅ Badges coloridos de status

#### 3️⃣ **Modal de Detalhes**

- Dados do Cliente (nome, e-mail, telefone)
- Endereço de Entrega completo
- Informações do Pedido (data, valor, status)
- Itens do Pedido (produtos, quantidades, valores)

#### 4️⃣ **Gestão de Reembolso**

- ✅ Aba específica para pedidos em reembolso
- ✅ Botão "Processar Reembolso"
- ✅ Atualização automática de status

### **Estrutura de Dados:**

```sql
pedidos                     -- Dados dos pedidos
clientes                    -- Informações dos clientes
itens_pedido                -- Produtos do pedido
status_fluxo                -- Status e configurações
pedidos_historico_status    -- Histórico de mudanças
```

**Arquivo Principal:** `admin/src/php/dashboard/orders.php`

---

## 🤖 **Sistema de Chat com IA**

### **Tecnologia:**

- **API:** Groq (llama-3.3-70b-versatile)
- **Personalidade:** DAIze - Consultora digital D&Z

### **Funcionalidades:**

- ✅ Atendimento automatizado 24/7
- ✅ Respostas sobre produtos (unhas, cílios)
- ✅ Escalonamento para humano quando necessário
- ✅ Histórico de conversas
- ✅ Interface moderna com status online/offline

### **Configuração:**

1. Obtenha API key em: https://console.groq.com
2. Configure em `admin/config/config.php`:

```php
define('GROQ_API_KEY', 'gsk_...sua_chave');
```

### **Endpoints da API:**

```javascript
// Cliente (site público)
POST /admin/src/php/sistema.php?api=1&endpoint=client&action=send_message

// Admin (painel)
GET /admin/src/php/sistema.php?api=1&endpoint=admin&action=get_stats
```

---

## 🛍️ **Área do Cliente**

### **Funcionalidades:**

- ✅ Vitrine de produtos com filtros
- ✅ Carrinho de compras
- ✅ Sistema de login/cadastro
- ✅ Minha conta
- ✅ Histórico de pedidos
- ✅ Chat com IA integrado
- ✅ Conteúdo dinâmico via CMS

### **Arquivos principais:**

```
cliente/
├── index.php              # Home da loja
├── conexao.php            # Conexão compartilhada
├── cms_data_provider.php  # Provider CMS
└── pages/
    ├── carrinho.php       # Carrinho
    ├── login.php          # Login
    ├── minha-conta.php    # Conta do cliente
    └── pedidos.php        # Histórico
```

---

## ⚙️ **Configurações**

### **BASE_URL Global:**

```php
// admin/config/base.php
define('BASE_URL', '/admin-teste/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('BANNERS_URL', UPLOADS_URL . 'banners/');
```

### **Upload de Imagens:**

- **Pasta:** Criada automaticamente em `uploads/`
- **Permissões:** 0755
- **Tamanho máx:** 2MB
- **Formatos:** JPG, PNG, WEBP

---

## 🔒 **Segurança**

### **Implementado:**

✅ **Prepared Statements** - Prevenção SQL Injection  
✅ **XSS Protection** - `htmlspecialchars()` em outputs  
✅ **Session Management** - Controle de sessões seguro  
✅ **CSRF Protection** - Tokens em formulários críticos  
✅ **File Upload Validation** - Verificação de tipo MIME  
✅ **Path Sanitization** - Prevenção directory traversal  
✅ **Password Hashing** - `password_hash()` bcrypt

### **Recomendações Produção:**

1. Alterar credenciais do banco
2. Ativar HTTPS (certificado SSL)
3. Configurar `.htaccess` robusto
4. Remover arquivos de teste/debug
5. Ativar `display_errors = Off`
6. Implementar rate limiting nas APIs
7. Configurar backup automático do banco

---

## 📊 **Sistema de Logs**

Todas as ações administrativas são registradas:

**Tabela:** `admin_logs`  
**Campos:** ID, admin_id, admin_nome, acao, ip_address, timestamp  
**Visualização:** `admin/src/php/dashboard/all-logs.php`

---

## 🚀 **Deploy / Mudança de Ambiente**

### **Local → Servidor:**

1. **Atualizar BASE_URL:**

```php
// admin/config/base.php
define('BASE_URL', '/');  // Se na raiz
```

2. **Atualizar conexão:**

```php
// config/config.php
define('DB_HOST', 'seu-servidor-mysql');
define('DB_USER', 'usuario-producao');
define('DB_PASS', 'senha-forte');
define('DB_NAME', 'banco-producao');
```

3. **Ajustar permissões:**

```bash
chmod 755 uploads/
chmod 755 uploads/banners/
chmod 755 uploads/produtos/
chmod 755 uploads/testimonials/
```

---

## 🆘 **Troubleshooting**

### **Imagens não aparecem**

```bash
# Verificar permissões
ls -la uploads/banners/
# Corrigir
chmod 755 uploads/banners/
```

### **Erro 404 nas APIs**

```php
// Verificar BASE_URL
echo BASE_URL;  // Deve corresponder ao caminho real
```

### **Chat IA não responde**

```php
// Verificar API key
var_dump(defined('GROQ_API_KEY'));
```

### **Encoding errado**

```sql
-- Verificar collation
SHOW TABLE STATUS WHERE Name='home_settings';
-- Deve ser utf8mb4_unicode_ci
```

---

## 📝 **Changelog**

### **Versão 2.2 (Março 2026)**

- ✅ README consolidado único
- ✅ Sistema de categorias reutilizável
- ✅ Depoimentos com avatars dinâmicos
- ✅ Promoções vinculadas a cupons
- ✅ Métricas da empresa
- ✅ Menu CMS padronizado em todas as páginas

### **Versão 2.1 (Março 2026)**

- ✅ Gerenciamento de Links do Footer
- ✅ Nova página CRUD para footer
- ✅ Integração automática no site

### **Versão 2.0 (Fevereiro 2026)**

- ✅ Sistema CMS completo
- ✅ Gestão de Pedidos com reembolso
- ✅ Chat com IA (Groq API)
- ✅ Dashboard analytics

---

**Desenvolvido com ❤️ para D&Z - Produtos Profissionais de Beleza**

**Versão Atual:** 2.2  
**Última Atualização:** 05 de Março de 2026

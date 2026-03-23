# 🎯 Rare7 - E-commerce Completo

**Sistema integrado com painel administrativo, CMS, loja virtual e gestão de pedidos.**

---

## 📋 **Índice Rápido**

- [Sobre](#-sobre) | [Instalação](#-instalação) | [Admin](#-admin) | [Loja](#-loja-pública) | [Checkout](#-checkout) | [CMS](#-cms) | [API](#-integração-de-apis)

---

## 🚀 **Sobre**

Sistema e-commerce profissional com:

- ✅ **Admin Completo**: Produtos, pedidos, clientes, estatísticas
- ✅ **CMS Integrado**: Gerenciar site sem código
- ✅ **Loja Virtual**: Catálogo, carrinho, checkout
- ✅ **Chat IA**: Atendimento automatizado (Groq)
- ✅ **Gestão de Pedidos**: Status, rastreamento, e-mail
- ✅ **Dashboard Analytics**: Gráficos em tempo real

---

## 💻 **Tecnologias**

**Backend:** PHP 8.0+ | MySQL 8.0 | MySQLi  
**Frontend:** HTML5 | CSS3 | JavaScript ES6+  
**Utilitários:** Chart.js | PHPMailer | Groq API

---

## 🛠 **Instalação**

### **Requisitos**

- XAMPP (PHP 7.4+, MySQL 8.0)
- Extensões: `mysqli`, `curl`, `gd`

### **Passo 1: Criar Banco**

1. Acesse: `http://localhost/phpmyadmin`
2. Crie banco: `teste_dz` (UTF-8: `utf8mb4_unicode_ci`)
3. Execute SQL (em ordem):
   ```
   admin/sql/criar_tabelas_dashboard.sql
   admin/sql/adicionar_colunas_pedidos.sql
   admin/sql/adicionar_mercadopago.sql
   ```

### **Passo 2: Configurar**

**Arquivo:** `admin/config/config.php`

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'teste_dz');
```

### **Passo 3: Acessar**

- **Admin:** `http://localhost/rare7/admin/PHP/login.php`
- **Loja:** `http://localhost/rare7/cliente/`

---

## 📁 **Estrutura**

```
rare7/
├── admin/
│   ├── config/              # Configurações
│   ├── PHP/                 # Login e core
│   ├── phpmailer/           # Biblioteca e-mail
│   ├── src/
│   │   ├── css/            # Estilos admin
│   │   ├── js/             # Scripts
│   │   └── php/dashboard/  # Páginas admin
│   │       ├── addproducts.php
│   │       ├── orders.php
│   │       ├── cms/        # Depoimentos, banners, etc
│   │       └── ...
│   └── sql/                 # Scripts de banco
├── cliente/
│   ├── index.php            # Home da loja
│   ├── css/                 # Estilos loja
│   ├── pages/
│   │   ├── carrinho.php
│   │   ├── checkout.php
│   │   ├── minha-conta.php
│   │   └── pedidos.php
│   └── api/
│       └── processar-pedido.php
├── uploads/
│   ├── banners/
│   ├── produtos/
│   └── testimonials/
└── webhooks/
    └── notificacao.php
```

---

## 🏢 **Painel Administrativo**

### **Acesso**

- URL: `admin/PHP/login.php`
- Sessão segura com autenticação
- Logout automático

### **Módulos Principais**

| Página           | Função                                  |
| ---------------- | --------------------------------------- |
| Dashboard        | Resumo vendas, pedidos, clientes        |
| Produtos         | Listar/buscar/filtrar                   |
| Adicionar/Editar | CRUD com variações (tamanhos P-XGG)     |
| Pedidos          | Gerenciar status, enviar e-mail         |
| Clientes         | Listar e visualizar dados               |
| Chat             | Mensagens com IA (Groq)                 |
| Cupons           | Criar/gerenciar descontos               |
| Revendedores     | Gerenciar parceiros                     |
| CMS              | Editar site (banners, depoimentos, etc) |
| Configurações    | Geral, pagamentos, frete                |
| Métricas         | Gráficos e análises                     |

### **Sistema de Variações (Produtos)**

**Modelo Simplificado:**

- Apenas **tamanhos** (P, M, G, GG, XG, XGG)
- Cada variação: SKU, estoque, preço (opcional)
- Preço promocional herdado do produto se não definido
- Estoque independente por tamanho

**Admin (Adicionar Produto):**

- 6 campos de estoque (um por tamanho)
- Tipo ficado em "tamanho" (oculto na UI)
- Checkbox "Usar preço do produto" (default: ativado)

---

## 🛍️ **Loja Pública**

### **Home (`index.php`)**

- Hero com promoção destaque
- Carrossel de banners (CMS)
- Vitrine de produtos
- Depoimentos de clientes

### **Catálogo**

- Listar todos os produtos
- Filtrar por categoria
- Busca por nome
- Ordenação (preço, novo)

### **Detalhe do Produto**

- Imagem e descrição
- Preço com desconto
- **Seletor de tamanho** (se tem variações)
- Stock disponível
- Adicionar ao carrinho

### **Carrinho**

- Listar itens com tamanho/qty
- Subtotal e totalizadores
- Aplicar cupom
- Calcular frete (ViaCEP)
- "Finalizar Compra"

### **Área do Cliente** (`minha-conta.php`)

**Login obrigatório**

#### **4 Abas:**

1. **Meus Pedidos:** Lista com status, total, itens
2. **Minha Conta:** Editar dados pessoais (nome, email, telefone, etc)
3. **Endereços:** Grid de endereços salvos com editar/remover
4. **Segurança:** Alterar senha com validação

---

## 🛒 **Checkout (Processo de Pedido)**

### **Arquivo:** `cliente/pages/checkout.php`

**Fluxo:**

1. Carrinho → Checkout
2. **Auto-preenchimento** (se logado): dados pessoais + último endereço
3. **Formulário completo:**
   - Nome, e-mail, telefone, CPF/CNPJ
   - CEP + busca ViaCEP automática
4. **Resumo:** subtotal + desconto + frete = total
5. **Forma de pagamento:** Pix / Cartão / Boleto
6. **Confirmar** → Criar pedido → Redirecion Meus Pedidos

### **Processamento:** `cliente/api/processar-pedido.php`

- Valida dados
- Cria cliente se novo
- Insere pedido + itens
- Baixa estoque
- Registra cupom
- Transação com rollback

### **Tabelas Necessárias**

```sql
clientes (id, nome, email, telefone, cpf_cnpj, endereco, etc)
pedidos (id, cliente_id, valor_total, status, forma_pagamento, etc)
itens_pedido (id, pedido_id, produto_id, variacao_id, quantidade, tamanho)
produtos (id, nome, preco, preco_promocional, estoque, categoria_id)
produto_variacoes (id, produto_id, tipo, tamanho, sku, estoque, preco)
```

---

## 🎨 **CMS - Gerenciador de Conteúdo**

Editar site público sem código (textos, imagens, depoimentos, links).

### **Módulos**

| Módulo          | Funcionalidade                               |
| --------------- | -------------------------------------------- |
| **Home**        | Textos: Hero, Lançamentos                    |
| **Banners**     | Upload imagens, título, CTA, ordenação       |
| **Destaque**    | Selecionar produtos em destaque              |
| **Promoções**   | Gerenciar promoções ativas, datas, descontos |
| **Depoimentos** | CRUD de testimonials com avatar, rating 1-5  |
| **Métricas**    | Números: clientes, regiões, anos, projetos   |
| **Footer**      | Gerenciar links em colunas                   |

### **Depoimentos (Testimonials)**

**Tabela:** `cms_testimonials`

```sql
id, nome, cargo_empresa, texto (max 600 char),
rating (1-5), avatar_path, ordem, ativo, created_at
```

- Upload avatar (JPG, PNG, WEBP)
- Avatar automático com inicial se sem foto
- Avaliação 1-5 estrelas
- Ordenação personalizada
- Ativar/Desativar

---

## 🤖 **Integração de APIs**

### **Chat IA (Groq)**

- **Modelo:** llama-3.3-70b-versatile
- **Configuração:** `admin/config/email-config.php`
- **Acesso:** `admin/src/php/dashboard/mensage.php`
- **Uso:** Atendimento automático

### **E-mail (PHPMailer)**

- **Arquivo:** `admin/phpmailer/`
- **Uso:** Notificações, confirmações
- **Usado em:**
  - `admin/src/php/dashboard/email_automatico.php`
  - `admin/src/php/dashboard/orders.php`

### **Frete (ViaCEP)**

- **Endpoint:** `https://viacep.com.br/ws/{cep}/json/`
- **Uso:** Busca automática no checkout
- **Autenticação:** Não requerida (pública)

### **Webhooks**

- **Recebimento:** `webhooks/notificacao.php`
- **Integração:** MercadoPago, PayPal
- **Ação:** Atualizar status pedido

---

## 🔒 **Segurança**

- ✅ Prepared statements (MySQLi) vs SQL injection
- ✅ Validação de entrada em todos formulários
- ✅ Sessão PHP com autenticação
- ✅ Escape de output (XSS protection)
- ✅ Transações de banco para integridade dados

---

## 📊 **Sistema de Variações - Resumo**

**Padrão Único e Fixo:**

- Tipo: Apenas `tamanho` (bloqueado em UI + backend)
- Valores: P, M, G, GG, XG, XGG (6 tamanhos fixos)
- Preço: Herda do produto principal se não definido
- Estoque: Independente por tamanho

---

## 🔄 **Fluxo Completo de Vendas**

```
Cliente navega
   ↓
Seleciona produto + tamanho
   ↓
Adiciona ao carrinho
   ↓
Calcula frete + cupom
   ↓
Checkout (preenche dados)
   ↓
Confirma pagamento
   ↓
Pedido criado no banco
   ↓
Admin notificado (e-mail)
   ↓
Admin atualiza status
   ↓
Cliente recebe atualizações
   ↓
Pedido entregue
```

---

## 📝 **Notas Importantes**

- **Banco:** Sempre `utf8mb4` para caracteres especiais
- **Imagens:** Armazenar em `uploads/`, referência no BD
- **Logs:** Apache error.log se houver erro 500
- **Debug:** Adicione `error_reporting(E_ALL);` em `config.php`
- **Futuro:** Considerar API REST completa + JWT auth

---

**Última atualização:** 22/03/2026 | Total Produtos: 56 | Schema: Tamanhos P-XGG

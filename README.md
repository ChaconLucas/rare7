# Rare7 - E-commerce Completo

Sistema integrado com painel administrativo, CMS, loja virtual e gestão de pedidos.

**Backend:** PHP 8.0+ | MySQL 8.0 | MySQLi  
**Frontend:** HTML5 | CSS3 | JavaScript ES6+  
**Utilitários:** Chart.js | PHPMailer | Groq API

---

## Índice

- [Instalação](#instalação)
- [Estrutura de Pastas](#estrutura-de-pastas)
- [Banco de Dados](#banco-de-dados)
- [Sistema de Badges e Tags](#sistema-de-badges-e-tags)
- [Painel Administrativo](#painel-administrativo)
- [Loja Pública](#loja-pública)
- [SQL — Queries de Referência](#sql--queries-de-referência)
- [Tarefas Comuns](#tarefas-comuns)
- [Checklist de Verificação](#checklist-de-verificação)

---

## Instalação

### Requisitos

- XAMPP (PHP 7.4+, MySQL 8.0)
- Extensões: `mysqli`, `curl`, `gd`

### Passo a Passo

**1. Criar o banco**

1. Acesse `http://localhost/phpmyadmin`
2. Crie o banco `teste_dz` com charset `utf8mb4_unicode_ci`
3. Execute os SQLs nesta ordem:
   ```
   admin/sql/schema_completo_rare7_db.sql
   admin/sql/adicionar_colunas_pedidos.sql
   admin/sql/adicionar_mercadopago.sql
   ```

**2. Configurar conexão**

Edite `admin/config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'teste_dz');
```

**3. Acessar**

- Admin: `http://localhost/rare7/admin/PHP/login.php`
- Loja: `http://localhost/rare7/cliente/`

---

## Estrutura de Pastas

```
rare7/
├── admin/
│   ├── config/                  # Configurações (DB, e-mail)
│   ├── PHP/                     # Login, logout, core
│   ├── phpmailer/               # Biblioteca PHPMailer
│   ├── sql/                     # Scripts de banco
│   ├── assets/images/produtos/  # Imagens dos produtos
│   └── src/php/dashboard/       # Páginas do painel
│       ├── addproducts.php
│       ├── products.php
│       ├── orders.php
│       └── cms/                 # CMS (banners, destaques, etc.)
├── cliente/
│   ├── index.php                # Home da loja
│   ├── produtos.php             # Catálogo
│   ├── produto.php              # Detalhe do produto
│   ├── cms_data_provider.php    # Helper de dados CMS
│   ├── css/loja.css             # Estilos da loja
│   ├── pages/
│   │   ├── carrinho.php
│   │   ├── checkout.php
│   │   ├── minha-conta.php
│   │   ├── pedidos.php
│   │   ├── login.php
│   │   └── register.php
│   └── api/
│       ├── carrinho-api.php
│       ├── cupom-api.php
│       ├── frete-api.php
│       └── processar-pedido.php
├── uploads/
│   ├── banners/
│   ├── produtos/
│   └── testimonials/
└── webhooks/
    └── notificacao.php
```

---

## Banco de Dados

### Tabela `categorias`

```sql
CREATE TABLE categorias (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(255) NOT NULL UNIQUE,
    descricao   TEXT,
    menu_group  VARCHAR(30) NOT NULL DEFAULT 'outros',
    parent_id   INT NULL,        -- subcategorias
    ativo       TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**`menu_group` disponíveis:** `clubes`, `selecoes`, `retro`, `raras`, `unhas`, `cilios`, `eletronicos`, `outros`

### Tabela `produtos`

Campos relevantes para badges/categorias:

| Campo               | Tipo          | Uso                                              |
| ------------------- | ------------- | ------------------------------------------------ |
| `categoria_id`      | INT FK        | Vínculo com tabela categorias                    |
| `tags`              | TEXT          | Tags separadas por vírgula (ex: "futebol, nike") |
| `destaque`          | TINYINT(1)    | Flag de produto em destaque                      |
| `preco_promocional` | DECIMAL(10,2) | Se < preco → produto em promoção                 |
| `created_at`        | TIMESTAMP     | Usado para detectar "NOVO" (≤ 30 dias)           |
| `status`            | ENUM          | `ativo` / `inativo` / `rascunho`                 |

### Tabela `home_featured_products`

Gerencia produtos como lançamentos na home.

```sql
CREATE TABLE home_featured_products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL,  -- 'launches', 'featured'
    product_id  INT NOT NULL,
    position    INT DEFAULT 0,
    UNIQUE KEY unique_section_product (section_key, product_id)
);
```

### Tabela `produto_variacoes`

Variações de tamanho por produto (P, M, G, GG, XG, XGG).

```sql
CREATE TABLE produto_variacoes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo       VARCHAR(50) DEFAULT 'tamanho',
    valor      VARCHAR(50),   -- 'P', 'M', 'G', etc.
    estoque    INT DEFAULT 0,
    ativo      TINYINT(1) DEFAULT 1
);
```

---

## Sistema de Badges e Tags

### Prioridade de Badges nos Cards

| #   | Badge       | Condição                                                         |
| --- | ----------- | ---------------------------------------------------------------- |
| 1   | PROMOÇÃO    | `preco_promocional > 0` e `< preco`                              |
| 2   | LANÇAMENTO  | Produto em `home_featured_products` com `section_key='launches'` |
| 3   | NOVO        | `created_at` há ≤ 30 dias                                        |
| —   | _(nenhuma)_ | Não se enquadra nas regras acima                                 |

### Tags (Campo `tags`)

- Armazenadas na coluna `tags` da tabela `produtos`
- Formato: texto separado por vírgula — `"futebol, real madrid, premium"`
- Usadas para organização interna/buscas; renderizadas como chip visual somente na página de detalhe do produto (`produto.php`)
- Editáveis no admin em Produtos → Editar → campo "Tags"

### Função `getProductTags()` — `cliente/cms_data_provider.php`

```
Regra 1: Produto marcado como lançamento → tag LANCAMENTO
Regra 2: Tem tag manual no campo tags   → usa a primeira tag
Regra 3: Criado há ≤ 15 dias            → tag NOVO
```

### URLs de filtro

```
/cliente/produtos.php?menu=lancamentos
/cliente/produtos.php?menu=clubes
/cliente/produtos.php?menu=retro
/cliente/produtos.php?categoria=Esmaltes
/cliente/produtos.php?busca=real+madrid
/cliente/produtos.php?tag=novo
```

---

## Painel Administrativo

### Módulos

| Página                        | Função                                    |
| ----------------------------- | ----------------------------------------- |
| Dashboard                     | Resumo de vendas, pedidos, clientes       |
| Produtos (`products.php`)     | Listar, buscar, filtrar                   |
| Adicionar (`addproducts.php`) | CRUD com variações de tamanho             |
| Pedidos (`orders.php`)        | Gerenciar status, enviar e-mail           |
| Clientes                      | Listar e visualizar dados                 |
| Chat                          | Mensagens com IA (Groq)                   |
| Cupons                        | Criar/gerenciar descontos                 |
| Revendedores                  | Gerenciar parceiros                       |
| CMS (`cms/`)                  | Banners, depoimentos, clubes, lançamentos |
| Configurações                 | Geral, pagamentos, frete                  |

---

## Loja Pública

### Home (`cliente/index.php`)

- Vitrine de produtos com badge automática
- Carrossel de banners (CMS)
- Filtro por time/clube
- Depoimentos de clientes

### Catálogo (`cliente/produtos.php`)

- Produto em destaque (hero) na 1ª página
- Grid de 4 colunas (12 por página + 1 hero)
- Filtros: categoria, marca, busca, menu, tag, promoção, faixa de preço
- Ordenação: mais recentes, menor/maior preço

### Detalhe (`cliente/produto.php`)

- Galeria de imagens
- Seletor de tamanho
- Preço com % de desconto
- Tags visuais (`rare-tag`)
- Produtos relacionados

### Área do Cliente

| Aba         | Função                                  |
| ----------- | --------------------------------------- |
| Pedidos     | Lista com status, total e itens         |
| Minha Conta | Editar nome, e-mail, telefone, CPF/CNPJ |
| Endereços   | Grid de endereços salvos                |
| Segurança   | Alterar senha com validação             |

---

## SQL — Queries de Referência

### Buscar lançamentos (igual ao frontend)

```sql
SELECT p.*, c.nome AS categoria,
    CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento,
    (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) AS tem_variacoes
FROM home_featured_products fp
INNER JOIN produtos p ON fp.product_id = p.id
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE fp.section_key = 'launches' AND p.status = 'ativo'
ORDER BY fp.position ASC;
```

### Verificar badges de todos os produtos

```sql
SELECT id, nome, preco, preco_promocional,
    CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento,
    CASE WHEN DATEDIFF(NOW(), created_at) <= 30 THEN 1 ELSE 0 END AS is_novo,
    CASE WHEN preco_promocional > 0 AND preco_promocional < preco THEN 1 ELSE 0 END AS is_promocao
FROM produtos p
LEFT JOIN home_featured_products fp ON p.id = fp.product_id AND fp.section_key = 'launches'
WHERE p.status = 'ativo';
```

### Buscar por tag

```sql
SELECT id, nome, tags FROM produtos
WHERE status = 'ativo'
  AND FIND_IN_SET('futebol', REPLACE(tags, ' ', '')) > 0;
```

### Produtos em promoção

```sql
SELECT id, nome, preco, preco_promocional,
    ROUND((1 - preco_promocional/preco) * 100, 2) AS desconto_percentual
FROM produtos
WHERE status = 'ativo'
  AND preco_promocional IS NOT NULL
  AND preco_promocional > 0
  AND preco_promocional < preco
ORDER BY desconto_percentual DESC;
```

### Adicionar produto como lançamento

```sql
INSERT INTO home_featured_products (section_key, product_id, position)
SELECT 'launches', ?, COALESCE(MAX(position), 0) + 1
FROM home_featured_products WHERE section_key = 'launches';
```

### Remover produto dos lançamentos

```sql
DELETE FROM home_featured_products WHERE section_key = 'launches' AND product_id = ?;
```

---

## Tarefas Comuns

### Adicionar um produto como lançamento

1. Acesse `admin/src/php/dashboard/cms/featured.php`
2. Busque o produto pelo nome (campo esquerdo)
3. Clique para adicionar na coluna "Lançamentos selecionados"
4. Reordene com as setas, se necessário
5. Verifique em `/cliente/produtos.php?menu=lancamentos`

### Editar tags de um produto

1. Acesse `admin/src/php/dashboard/products.php`
2. Clique em Editar no produto desejado
3. Role até o campo "Tags" e edite no formato `tag1, tag2, tag3`
4. Salve com "Atualizar Produto"

### Criar nova categoria

1. Acesse o painel de categorias
2. Preencha Nome, Menu Group e Ativo
3. Para subcategoria: preencha também o campo Parent ID
4. Salve — a URL correspondente será `/cliente/produtos.php?menu=<menu_group>`

### Cadastrar produto completo

1. `addproducts.php` → preencha Nome, Descrição, SKU, Categoria, Marca
2. Informe Preço, Preço Promocional (opcional) e Estoque
3. Faça upload da imagem principal (salva em `admin/assets/images/produtos/`)
4. Preencha tags: `"futebol, real madrid, camisa, 2024"`
5. Salve, anote a ID
6. Vá ao CMS → Lançamentos e adicione o produto se necessário

---

## Checklist de Verificação

### Banco de Dados

- [ ] Tabela `categorias` com colunas: `id`, `nome`, `menu_group`, `parent_id`, `ativo`
- [ ] Tabela `produtos` com colunas: `id`, `tags`, `destaque`, `preco_promocional`, `categoria_id`, `created_at`
- [ ] Tabela `home_featured_products` com colunas: `section_key`, `product_id`, `position`
- [ ] Tabela `produto_variacoes` com colunas: `produto_id`, `tipo`, `valor`, `ativo`

### Frontend

- [ ] `/cliente/produtos.php?menu=lancamentos` carrega corretamente
- [ ] Badge "LANÇAMENTO" aparece nos produtos marcados
- [ ] Badge "PROMOÇÃO" aparece quando `preco_promo < preco`
- [ ] Badge "NOVO" aparece em produtos criados há ≤ 30 dias
- [ ] Cards mostram seletor de tamanhos (se houver variações)
- [ ] Imagens carregam de `/admin/assets/images/produtos/`

### Admin

- [ ] CMS em `admin/src/php/dashboard/cms/featured.php` abre
- [ ] Busca de produtos funciona
- [ ] Campo "Tags" visível ao editar produto
- [ ] API CMS (`cms/cms_api.php`) responde

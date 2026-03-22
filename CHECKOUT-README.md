# Sistema de Checkout - Instruções de Teste

## ✅ Arquivos Criados

1. **cliente/pages/checkout.php** (800+ linhas)
   - Página completa de checkout com formulários e validações
   - Integração com gateway de pagamento (opcional)
   - Auto-preenchimento para usuários logados
   - Máscaras e validações de CPF/CNPJ, telefone, CEP
   - Integração com ViaCEP para busca de endereço
   - Resumo do pedido com produtos, desconto e frete

2. **cliente/api/processar-pedido.php** (260 linhas)
   -API para processamento de pedidos
   - Validação de dados do cliente e endereço
   - Criação/atualização de cliente no banco
   - Inserção de pedido na tabela `pedidos`
   - Inserção de itens na tabela `itens_pedido`
   - Baixa automática de estoque
   - Registro de uso de cupom
   - Transações para garantir integridade

3. **admin/sql/adicionar_colunas_pedidos.sql**
   - Script SQL para adicionar colunas extras às tabelas
   - Campos para subtotal, desconto, frete
   - Campos para forma de pagamento
   - Campos para endereço completo
   - Índices para melhorar performance

4. **executar_sql_pedidos.php** (raiz do projeto)
   - Script auxiliar para executar o SQL de atualização
   - Interface web para facilitar a atualização do banco

## 🔧 Configuração do Banco de Dados

### Passo 1: Atualizar estrutura das tabelas

Acesse no navegador:

```
http://localhost/admin-teste/executar_sql_pedidos.php
```

Ou execute manualmente o SQL:

```
admin/sql/adicionar_colunas_pedidos.sql
```

### Passo 2: Verificar tabelas necessárias

As seguintes tabelas devem existir:

- `clientes` (id, nome, email, telefone, cpf_cnpj, endereco, cidade, estado)
- `pedidos` (id, cliente_id, valor_total, status, data_pedido, forma_pagamento, endereco_entrega, etc)
- `itens_pedido` (id, pedido_id, produto_id, variacao_id, quantidade, preco_unitario, nome_produto)
- `produtos` (id, nome, preco, estoque)
- `produto_variacoes` (id, produto_id, estoque)
- `cupons` (opcional)
- `freight_settings` (optional)
- `payment_gateway_config` (opcional)

## 🛒 Fluxo de Teste

### 1. Adicionar produtos ao carrinho

- Navegue em: `http://localhost/admin-teste/cliente/`
- Adicione produtos ao carrinho
- Para produtos com variação, selecione a variação antes

### 2. Calcular frete (no carrinho)

- Vá para: `http://localhost/admin-teste/cliente/pages/carrinho.php`
- Insira o CEP e calcule o frete
- Aplique cupom (se houver)
- Clique em "Finalizar Compra"

### 3. Preencher dados de checkout

- Será redirecionado para: `checkout.php`
- Se logado, dados serão preenchidos automaticamente
- Preencha ou confirme:
  - Dados pessoais (nome, email, telefone, CPF/CNPJ)
  - Endereço de entrega (use busca automática por CEP)
- Selecione forma de pagamento (Pix, Cartão ou Boleto)
- Revise o resumo do pedido

### 4. Finalizar pedido

- Clique em "Confirmar Pedido"
- Aguarde processamento (loading overlay)
- Após sucesso, será redirecionado para "Meus Pedidos"

### 5. Verificar pedido criado

- Na página "Meus Pedidos": `pedidos.php`
- ou no admin (se houver página de gestão de pedidos)
- Verifique o banco de dados:
  ```sql
  SELECT * FROM pedidos ORDER BY id DESC LIMIT 1;
  SELECT * FROM itens_pedido WHERE pedido_id = [ID_DO_PEDIDO];
  SELECT * FROM clientes WHERE id = [ID_DO_CLIENTE];
  ```

## 🔍 Validações Implementadas

### Cliente

- Nome obrigatório
- Email obrigatório e válido
- Telefone com máscara (11) 99999-9999
- CPF/CNPJ com validação de formato

### Endereço

- CEP obrigatório (busca automática via ViaCEP)
- Rua, número, bairro, cidade e estado obrigatórios
- Complemento opcional

### Carrinho

- Mínimo 1 produto
- Frete calculado obrigatoriamente
- Validação de estoque disponível

### Pagamento

- Forma de pagamento obrigatória
- Gateway opcional (sistema funciona sem)

## 📊 Status do Pedido

O pedido é criado com status inicial **"pendente"** (conforme ENUM do banco).

Os status disponíveis são:

- `pendente` - Aguardando processamento
- `processando` - Em processamento
- `enviado` - Enviado para transporte
- `entregue` - Entregue ao cliente
- `cancelado` - Cancelado

## ⚠️ Observações Importantes

1. **Gateway de Pagamento**: O sistema detecta se há um gateway configurado (tabela `payment_gateway_config`). Se não houver, mostra aviso mas permite concluir o pedido.

2. **Baixa de Estoque**: O estoque é baixado automaticamente ao criar o pedido. Se houver variação, baixa da variação, caso contrário, do produto principal.

3. **Transações**: Todo o processo usa transações do banco para garantir que, em caso de erro, nada seja gravado pela metade.

4. **localStorage**: O carrinho é armazenado no `localStorage` do navegador com as chaves:
   - `dz_cart` - Produtos
   - `dz_frete` - Dados de frete
   - `dz_cupom` - Cupom aplicado

5. **Limpeza**: Após sucesso, o localStorage é limpo automaticamente.

## 🐛 Troubleshooting

### Erro: "Dados inválidos"

- Verifique se o carrinho está preenchido
- Verifique se o frete foi calculado

### Erro: "Endereço incompleto"

- Preencha todos os campos obrigatórios do endereço
- Use a busca automática de CEP

### Erro: "Erro ao processar pedido"

- Verifique o console do navegador (F12)
- Verifique os logs do PHP em `error_log` ou console do XAMPP
- Verifique se todas as colunas foram adicionadas às tabelas

### Pedido não aparece em "Meus Pedidos"

- Verifique se você está logado
- Verifique se o `cliente_id` foi gravado corretamente
- Verifique a query no arquivo `pedidos.php`

## 📝 Próximas Melhorias (Sugestões)

1. ✉️ Envio de email de confirmação (estrutura pronta para PHPMailer)
2. 💳 Integração real com gateway de pagamento
3. 📱 Notificação por SMS
4. 🎨 Página de sucesso dedicada (atualmente usa alert)
5. 📦 Geração de etiqueta de envio
6. 🔔 Notificações push para status do pedido
7. 📄 Geração de nota fiscal

## ✨ Conclusão

O sistema de checkout está completo e funcional! Todos os arquivos foram criados e as validações implementadas. Basta atualizar o banco de dados e testar o fluxo completo.

---

**Desenvolvido para D&Z Store**

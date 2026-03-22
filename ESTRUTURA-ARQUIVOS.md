# 📁 Estrutura de Arquivos - Sistema de E-commerce

## 🎯 Arquivos Essenciais do Checkout Transparente

### **Cliente - Frontend e API**

```
cliente/
├── api/
│   └── processar-pedido.php      ✅ Processa checkout e cria pagamento no MP
├── pages/
│   ├── checkout.php               ✅ Página de checkout com Card Payment Brick
│   └── pedidos.php                ✅ Página de confirmação de pedidos
└── config.php                     ✅ Configurações do banco de dados
```

### **Webhooks - Notificações do Mercado Pago**

```
webhooks/
├── notificacao.php                ✅ Recebe notificações do MP (HTTP 200 imediato)
└── debug_webhook.txt              📝 Log de debug das notificações
```

### **Admin - Painel Administrativo**

```
admin/
├── index.php                      ✅ Dashboard principal
├── PHP/
│   ├── acoes.php                  ✅ CRUD de produtos/pedidos
│   └── login.php                  ✅ Sistema de autenticação
└── config/
    └── config.php                 ✅ Configurações gerais
```

---

## 🔄 Fluxo de Processamento de Pagamento

### **1. Checkout (cliente/pages/checkout.php)**

- Renderiza Card Payment Brick do Mercado Pago
- Coleta dados do cliente e endereço
- Envia para API de processamento

### **2. API de Processamento (cliente/api/processar-pedido.php)**

```php
Recebe dados do checkout
    ↓
Cria pedido no banco de dados
    ↓
Envia payment_data para MP API
    ↓
Retorna JSON com status
    ↓
Redireciona para página de sucesso
```

**Características:**

- Detecta protocolo HTTPS automaticamente (ngrok/Cloudflare)
- `notification_url` configurada: `https://seu-dominio.com/admin-teste/webhooks/notificacao.php`
- Precision fix: `round(floatval($valorTotal), 2)`
- Suporta checkout transparente (cartão) e redirect (pix/boleto)

### **3. Webhook (webhooks/notificacao.php)**

```php
Recebe notificação do MP
    ↓
Responde HTTP 200 imediatamente (< 5ms)
    ↓
Desconecta cliente (ignore_user_abort)
    ↓
Processa em background:
  - Consulta API do MP
  - Atualiza status do pedido
  - Registra log de debug
```

**Características:**

- Resposta instantânea para evitar timeout 502
- Triple-fallback para extrair payment_id
- Detecção de ID de teste (123456)
- Log detalhado em `debug_webhook.txt`

---

## 🗑️ Arquivos Removidos (Limpeza 20/03/2026)

### **Testes e Debug Temporários:**

- ❌ `viva.php` - Teste de infraestrutura (não mais necessário)
- ❌ `mp_webhook.php` - Webhook alternativo (não usado)
- ❌ `teste_viva.php` - Interface de teste
- ❌ `teste_webhook.php` - Interface de teste
- ❌ `webhooks/teste.php` - Script de teste
- ❌ `log_viva.txt` - Log de teste
- ❌ `webhook_debug.txt` - Log antigo

### **Documentação Temporária:**

- ❌ `CHECKOUT-TRANSPARENTE-DEBUG.md`
- ❌ `IMPORTANTE-JUROS-MERCADOPAGO.md`
- ❌ `MERCADOPAGO-INTEGRATION.md`

---

## ✅ Configuração Necessária

### **1. Banco de Dados**

Tabela `payment_settings`:

```sql
gateway_provider = 'mercadopago'
secret_key = 'APP_USR-...'  -- Access Token
gateway_active = 1
```

### **2. Webhook no Painel Mercado Pago**

```
URL: https://seu-dominio.com/admin-teste/webhooks/notificacao.php
Eventos: payment.created, payment.updated
```

### **3. Túnel (Desenvolvimento)**

```bash
# Opção 1: ngrok
ngrok http 80

# Opção 2: Cloudflare Tunnel
cloudflared tunnel --url http://localhost:80
```

**IMPORTANTE:** O sistema detecta automaticamente túneis e usa HTTPS na `notification_url`.

---

## 🔍 Debug e Monitoramento

### **Logs Importantes:**

```powershell
# Apache errors
Get-Content "C:\XAMPP-install\apache\logs\error.log" -Wait -Tail 10

# Webhook debug
Get-Content "C:\XAMPP-install\htdocs\admin-teste\webhooks\debug_webhook.txt" -Wait -Tail 20

# Apache access
Get-Content "C:\XAMPP-install\apache\logs\access.log" -Wait -Tail 10
```

### **Testar Webhook Manualmente:**

```powershell
$body = '{"action":"payment.updated","data":{"id":"1234567"}}'
Invoke-WebRequest -Uri "http://localhost/admin-teste/webhooks/notificacao.php" `
  -Method POST -Body $body -ContentType "application/json"
```

---

## 📦 Estrutura Completa Atualizada

```
admin-teste/
├── 📄 README.md                   Documentação principal
├── 📄 CHECKOUT-README.md          Guia do checkout
├── 📄 ESTRUTURA-ARQUIVOS.md       Este arquivo
│
├── 🗂️ admin/                       Painel administrativo
├── 🗂️ cliente/                     Frontend e-commerce
│   ├── api/processar-pedido.php   ✅ ESSENCIAL
│   ├── pages/checkout.php         ✅ ESSENCIAL
│   └── pages/pedidos.php          ✅ ESSENCIAL
│
├── 🗂️ webhooks/                    Notificações MP
│   ├── notificacao.php            ✅ ESSENCIAL
│   └── debug_webhook.txt          📝 LOG ATIVO
│
└── 🗂️ uploads/                     Imagens e arquivos
```

---

## 🚀 Status do Sistema

✅ **Checkout Transparente** - Funcionando  
✅ **Webhook** - HTTP 200 OK  
✅ **Detecção HTTPS** - Automática  
✅ **Timeout** - Resolvido  
✅ **Logs** - Ativos

**Última atualização:** 20/03/2026  
**Versão:** 1.0 (Produção)

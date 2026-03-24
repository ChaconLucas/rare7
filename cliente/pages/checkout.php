<?php
/**
 * Checkout - Finalizacao de Compra
 * Sistema de pagamento integrado com admin
 */

session_start();
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

$usuarioLogado = isset($_SESSION['cliente']);
$clienteData = $usuarioLogado ? $_SESSION['cliente'] : null;
$nomeUsuario = $usuarioLogado ? htmlspecialchars($clienteData['nome']) : '';

$freteGratisValor = getFreteGratisThreshold($pdo);

$clienteCompleto = null;
if ($usuarioLogado && isset($clienteData['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$clienteData['id']]);
    $clienteCompleto = $stmt->fetch();
}

$gatewayAtivo = false;
$gatewayConfigurado = false;
$formasPagamento = [];
$paymentConfig = null;

try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'payment_settings'");
    if ($checkTable->rowCount() > 0) {
        $stmt = $pdo->query("SELECT * FROM payment_settings WHERE id = 1 LIMIT 1");
        $paymentConfig = $stmt->fetch();

        if ($paymentConfig) {
            $gatewayAtivo = (bool)$paymentConfig['gateway_active'];

            $hasPublicKey = !empty($paymentConfig['public_key']);
            $hasSecretKey = !empty($paymentConfig['secret_key']);
            $hasClientId = !empty($paymentConfig['client_id']);
            $hasClientSecret = !empty($paymentConfig['client_secret']);

            $gatewayConfigurado = ($hasPublicKey && $hasSecretKey) || ($hasClientId && $hasClientSecret);

            if ($paymentConfig['method_pix']) {
                $formasPagamento[] = 'Pix';
            }
            if ($paymentConfig['method_credit_card']) {
                $formasPagamento[] = 'Cartao de Credito';
            }
            if ($paymentConfig['method_debit_card']) {
                $formasPagamento[] = 'Cartao de Debito';
            }
            if ($paymentConfig['method_boleto']) {
                $formasPagamento[] = 'Boleto';
            }
        }
    }

    if (empty($formasPagamento)) {
        $formasPagamento = ['Pix', 'Cartao de Credito', 'Cartao de Debito', 'Boleto'];
    }
} catch (PDOException $e) {
    error_log("Payment settings check error: " . $e->getMessage());
    $formasPagamento = ['Pix', 'Cartao de Credito', 'Cartao de Debito', 'Boleto'];
}

$pageTitle = 'Finalizar Compra - RARE7';
$pixDisponivel = in_array('Pix', $formasPagamento, true);
$cartaoDisponivel = in_array('Cartao de Credito', $formasPagamento, true) || in_array('Cartao de Debito', $formasPagamento, true);
$boletoDisponivel = in_array('Boleto', $formasPagamento, true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Cinzel:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="../css/loja.css">

    <style>
        :root {
            --rare-black: #0E0E0E;
            --rare-navy: #0F1C2E;
            --rare-gold: #C6A75E;
            --rare-gray: #BFC5CC;
            --rare-border: rgba(191, 197, 204, 0.2);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: #f2f2f2;
            font-family: "Space Grotesk", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 18% 14%, rgba(198, 167, 94, 0.18) 0%, rgba(198, 167, 94, 0.02) 42%, transparent 66%),
                radial-gradient(circle at 82% 30%, rgba(15, 28, 46, 0.55) 0%, rgba(15, 28, 46, 0.1) 45%, transparent 70%),
                #0E0E0E;
            padding-top: 96px;
        }

        .checkout-container {
            width: min(1240px, 95%);
            margin: 0 auto;
            padding: 34px 0 56px;
        }

        .checkout-header {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 22px;
            align-items: start;
        }

        .checkout-breadcrumb {
            display: inline-flex;
            gap: 10px;
            margin-bottom: 10px;
            color: var(--rare-gray);
            text-transform: uppercase;
            letter-spacing: .14em;
            font-size: .8rem;
        }

        .checkout-breadcrumb .current { color: var(--rare-gold); font-weight: 700; }

        .checkout-header h1 {
            margin: 0;
            max-width: 780px;
            font-size: clamp(1.6rem, 3.1vw, 2.45rem);
            line-height: 1.25;
            font-weight: 700;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 26px;
            align-items: start;
        }

        .checkout-main { display: grid; gap: 18px; min-width: 0; }

        .checkout-section {
            border-radius: 18px;
            border: 1px solid var(--rare-border);
            background: linear-gradient(160deg, rgba(16,16,16,.93), rgba(15,28,46,.3));
            padding: 24px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 16px;
            font-size: 1.15rem;
            font-weight: 700;
        }
        .section-title .material-symbols-sharp { color: var(--rare-gold); }

        .customer-wrap { display: grid; gap: 16px; }
        .inner-card {
            border-radius: 14px;
            border: 1px solid var(--rare-border);
            background: rgba(6, 6, 6, .52);
            padding: 16px;
        }
        .inner-card h3 {
            margin: 0 0 12px;
            color: var(--rare-gold);
            font-size: .95rem;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .form-row,
        .form-row-3,
        .form-row-address,
        .card-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        .form-row-3 { grid-template-columns: 150px minmax(0,1fr) minmax(0,1fr); }
        .form-row-address { grid-template-columns: 280px minmax(0, 1fr); }

        .form-group { display: grid; gap: 6px; }
        .form-group label {
            color: var(--rare-gray);
            font-size: .78rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .coupon-field input {
            width: 100%;
            border-radius: 11px;
            border: 1px solid rgba(191, 197, 204, .3);
            background: rgba(4, 4, 4, .75);
            color: #f5f5f5;
            padding: 12px 13px;
            min-width: 0;
        }

        .form-group input:focus,
        .form-group select:focus,
        .coupon-field input:focus {
            outline: none;
            border-color: rgba(198, 167, 94, .7);
            box-shadow: 0 0 0 3px rgba(198, 167, 94, .14);
        }

        .cep-input-wrap { display: flex; gap: 8px; }
        .cep-input-wrap input { flex: 1; min-width: 130px; }
        .btn-inline {
            border: 1px solid rgba(198, 167, 94, .65);
            background: transparent;
            color: var(--rare-gold);
            border-radius: 10px;
            font-size: .75rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
            cursor: pointer;
            padding: 0 14px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .payment-method {
            border: 1px solid rgba(191,197,204,.24);
            border-radius: 13px;
            background: rgba(8, 8, 8, .62);
            padding: 12px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            cursor: pointer;
            transition: all .22s ease;
        }

        .payment-method:hover { border-color: rgba(198, 167, 94, .58); }
        .payment-method input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .payment-method.selected {
            border-color: rgba(198, 167, 94, .75);
            background: linear-gradient(160deg, rgba(198, 167, 94, .2), rgba(15, 28, 46, .38));
            box-shadow: 0 10px 24px rgba(198, 167, 94, .12);
        }

        .payment-method-name {
            font-size: .81rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .payment-method-desc { color: var(--rare-gray); font-size: .8rem; line-height: 1.3; }
        .payment-method-icon { color: var(--rare-gold); font-size: 1.35rem; }

        .payment-extra {
            border: 1px solid var(--rare-border);
            border-radius: 12px;
            background: rgba(8, 8, 8, .58);
            padding: 15px;
            font-size: .9rem;
            color: var(--rare-gray);
            line-height: 1.45;
            margin-top: 12px;
        }
        .payment-extra strong { color: #f4f4f4; }
        .card-preview-fields { display: grid; gap: 10px; margin-top: 10px; }

        #cardPaymentBrick_container,
        #pixContainer,
        #boletoContainer {
            margin-top: 14px;
            border: 1px solid var(--rare-border);
            border-radius: 13px;
            background: rgba(5,5,5,.66);
            padding: 16px;
        }

        #pixCopyPaste, #boletoDigitableLine {
            color: #f5f5f5 !important;
            background: rgba(0,0,0,.7) !important;
            border: 1px solid rgba(191,197,204,.34) !important;
        }

        .checkout-sidebar { position: sticky; top: 116px; min-width: 0; }
        .order-summary {
            border-color: rgba(198, 167, 94, .24);
            background: linear-gradient(165deg, rgba(15,28,46,.58), rgba(14,14,14,.96));
            box-shadow: 0 18px 36px rgba(0,0,0,.4);
        }

        .summary-items-list {
            display: grid;
            gap: 10px;
            max-height: 290px;
            overflow: auto;
            padding-right: 4px;
            margin-bottom: 14px;
        }

        .summary-item {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
        }

        .summary-item-image {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid rgba(191,197,204,.3);
            background: rgba(255,255,255,.08);
        }

        .summary-item-image img { width: 100%; height: 100%; object-fit: cover; }
        .summary-item-name {
            font-size: .84rem;
            font-weight: 600;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .summary-item-qty { font-size: .75rem; color: #adb4bc; }
        .summary-item-price { color: var(--rare-gold); font-size: .82rem; font-weight: 700; white-space: nowrap; }

        .coupon-field { display: grid; gap: 7px; margin-bottom: 14px; }
        .coupon-field small { color: #9aa2aa; font-size: .76rem; }
        .coupon-badge-wrap {
            min-height: 46px;
            border: 1px solid rgba(191, 197, 204, .3);
            border-radius: 11px;
            background: rgba(4, 4, 4, .75);
            display: flex;
            align-items: center;
            padding: 8px 10px;
        }
        .coupon-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(198, 167, 94, .45);
            background: linear-gradient(130deg, rgba(198, 167, 94, .2), rgba(15, 28, 46, .42));
            color: #f3e2b6;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 8px 12px;
            line-height: 1;
        }
        .coupon-badge .material-symbols-sharp {
            font-size: 15px;
            color: #c6a75e;
        }
        .coupon-empty {
            color: #8f98a3;
            font-size: .82rem;
        }

        .summary-totals { display: grid; gap: 8px; margin-bottom: 12px; }
        .summary-row { display: flex; justify-content: space-between; gap: 8px; color: var(--rare-gray); font-size: .88rem; }
        .summary-row .value { color: #f8f8f8; font-weight: 600; text-align: right; }
        .summary-row.discount .value { color: #62c98a; }
        .summary-row.total {
            border-top: 1px solid rgba(198, 167, 94, .25);
            margin-top: 4px;
            padding-top: 12px;
            font-size: 1.16rem;
            color: #f7f7f7;
            font-weight: 700;
        }
        .summary-row.total .value { color: var(--rare-gold); font-size: 1.42rem; }

        .installment-info { color: #9ca4ad; font-size: .82rem; margin-bottom: 14px; }
        .installment-info strong { color: var(--rare-gold); }

        .btn {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 13px 14px;
            font-size: .89rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            text-decoration: none;
            transition: all .22s ease;
        }
        .btn-primary {
            background: linear-gradient(120deg, #a8873f, #c6a75e);
            color: #111;
        }
        .btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(198,167,94,.24); }
        .btn-primary:disabled { opacity: .55; cursor: not-allowed; }

        .btn-secondary {
            margin-top: 10px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(191,197,204,.34);
            color: #f4f4f4;
        }
        .btn-secondary:hover { border-color: rgba(198,167,94,.65); color: var(--rare-gold); }

        .alert {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid transparent;
            font-size: .92rem;
        }
        .alert-info { border-color: rgba(98,147,201,.45); background: rgba(15,28,46,.46); color: #cae0f6; }
        .alert-warning { border-color: rgba(217,163,95,.5); background: rgba(89,54,18,.3); color: #f2d2a9; }
        .alert-danger { border-color: rgba(227,108,108,.5); background: rgba(91,24,24,.35); color: #ffcece; }

        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6,6,6,.72);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-overlay.active { display: flex; }
        .loading-content {
            background: #111317;
            border: 1px solid var(--rare-border);
            border-radius: 16px;
            padding: 24px 28px;
            text-align: center;
            min-width: 220px;
        }
        .loading-content p { margin: 0; color: #d2d7dd; }
        .spinner {
            width: 44px;
            height: 44px;
            border: 3px solid rgba(255,255,255,.1);
            border-top-color: var(--rare-gold);
            border-radius: 50%;
            animation: spin .9s linear infinite;
            margin: 0 auto 14px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 1100px) {
            .checkout-grid { grid-template-columns: minmax(0, 1fr); }
            .checkout-sidebar { position: static; }
        }

        @media (max-width: 760px) {
            .checkout-header { grid-template-columns: minmax(0, 1fr); }
            .checkout-container { width: min(100%, 96%); padding: 24px 0 40px; }
            .checkout-section { padding: 18px; }
            .inner-card { padding: 14px; }
            .form-row, .form-row-3, .form-row-address, .payment-methods, .card-preview-grid { grid-template-columns: minmax(0, 1fr); }
            .cep-input-wrap { width: 100%; }
            .summary-item { grid-template-columns: 52px minmax(0,1fr); }
            .summary-item-price { grid-column: 2; justify-self: end; }
        }
    </style>

    <?php if ($gatewayConfigurado && $paymentConfig): ?>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const MP_PUBLIC_KEY = '<?php echo htmlspecialchars($paymentConfig['public_key']); ?>';
    </script>
    <?php endif; ?>
</head>
<body>

    <?php $currentPage = 'cart'; ?>
    <?php include '../includes/navbar.php'; ?>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Processando seu pedido...</p>
        </div>
    </div>

    <div class="checkout-container">
        <div class="checkout-header">
            <div>
                <div class="checkout-breadcrumb">
                    <span>Carrinho</span>
                    <span>/</span>
                    <span class="current">Checkout</span>
                </div>
                <h1>Conclua seu pedido com estilo e seguranca</h1>
            </div>
        </div>

        <div class="checkout-grid">
            <div class="checkout-main">
                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="material-symbols-sharp">person</span>
                        Informacoes do Cliente
                    </h2>

                    <?php if (!$usuarioLogado): ?>
                    <div class="alert alert-info">
                        <span class="material-symbols-sharp">info</span>
                        <div>Voce nao esta logado. Preencha os dados para continuar com o checkout.</div>
                    </div>
                    <?php endif; ?>

                    <div class="customer-wrap">
                        <div class="inner-card">
                            <h3>Contato</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nome">Nome completo</label>
                                    <input type="text" id="nome" placeholder="Seu nome completo" value="<?php echo $clienteCompleto['nome'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">E-mail</label>
                                    <input type="email" id="email" placeholder="seu@email.com" value="<?php echo $clienteCompleto['email'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="telefone">Telefone</label>
                                    <input type="tel" id="telefone" placeholder="(00) 00000-0000" value="<?php echo $clienteCompleto['telefone'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="cpf_cnpj">CPF</label>
                                    <input type="text" id="cpf_cnpj" placeholder="000.000.000-00" value="<?php echo $clienteCompleto['cpf_cnpj'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="inner-card">
                            <h3>Endereco</h3>

                            <div class="form-row-address">
                                <div class="form-group">
                                    <label for="cep">CEP</label>
                                    <div class="cep-input-wrap">
                                        <input type="text" id="cep" placeholder="00000-000" value="<?php echo $clienteCompleto['cep'] ?? $clienteCompleto['cep_entrega'] ?? ''; ?>" required>
                                        <button type="button" class="btn-inline" onclick="buscarCep()">Buscar CEP</button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="rua">Endereco</label>
                                    <input type="text" id="rua" placeholder="Rua, avenida, travessa" value="<?php echo $clienteCompleto['endereco'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <label for="numero">Numero</label>
                                    <input type="text" id="numero" placeholder="Nº" value="<?php echo $clienteCompleto['numero'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label for="complemento">Complemento</label>
                                    <input type="text" id="complemento" placeholder="Apartamento, bloco, referencia" value="<?php echo $clienteCompleto['complemento'] ?? ''; ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bairro">Bairro</label>
                                    <input type="text" id="bairro" placeholder="Bairro" value="<?php echo $clienteCompleto['bairro'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="cidade">Cidade</label>
                                    <input type="text" id="cidade" placeholder="Cidade" value="<?php echo $clienteCompleto['cidade'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" required>
                                    <option value="">Selecione o estado</option>
                                    <option value="AC">Acre</option>
                                    <option value="AL">Alagoas</option>
                                    <option value="AP">Amapa</option>
                                    <option value="AM">Amazonas</option>
                                    <option value="BA">Bahia</option>
                                    <option value="CE">Ceara</option>
                                    <option value="DF">Distrito Federal</option>
                                    <option value="ES">Espirito Santo</option>
                                    <option value="GO">Goias</option>
                                    <option value="MA">Maranhao</option>
                                    <option value="MT">Mato Grosso</option>
                                    <option value="MS">Mato Grosso do Sul</option>
                                    <option value="MG">Minas Gerais</option>
                                    <option value="PA">Para</option>
                                    <option value="PB">Paraiba</option>
                                    <option value="PR">Parana</option>
                                    <option value="PE">Pernambuco</option>
                                    <option value="PI">Piaui</option>
                                    <option value="RJ">Rio de Janeiro</option>
                                    <option value="RN">Rio Grande do Norte</option>
                                    <option value="RS">Rio Grande do Sul</option>
                                    <option value="RO">Rondonia</option>
                                    <option value="RR">Roraima</option>
                                    <option value="SC">Santa Catarina</option>
                                    <option value="SP" <?php echo ($clienteCompleto['estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>Sao Paulo</option>
                                    <option value="SE">Sergipe</option>
                                    <option value="TO">Tocantins</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="checkout-section">
                    <h2 class="section-title">
                        <span class="material-symbols-sharp">payments</span>
                        Escolha como pagar
                    </h2>

                    <?php if (!$gatewayConfigurado): ?>
                    <div class="alert alert-warning">
                        <span class="material-symbols-sharp">warning</span>
                        <div>
                            <strong>Gateway de pagamento nao configurado.</strong><br>
                            Configure as credenciais no painel administrativo para habilitar o checkout online.
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$pixDisponivel && !$cartaoDisponivel && !$boletoDisponivel): ?>
                    <div class="alert alert-warning">
                        <span class="material-symbols-sharp">info</span>
                        <div>
                            <strong>Nenhuma forma de pagamento ativa.</strong><br>
                            Ative pelo menos uma opcao no painel administrativo.
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($gatewayConfigurado && ($pixDisponivel || $cartaoDisponivel || $boletoDisponivel)): ?>
                    <div class="payment-methods">
                        <?php if ($pixDisponivel): ?>
                        <label class="payment-method" onclick="selectPayment('pix')">
                            <input type="radio" name="payment" value="pix" id="payment_pix">
                            <div class="payment-method-info">
                                <div class="payment-method-name">Pix</div>
                                <div class="payment-method-desc">Aprovacao rapida e fluxo direto</div>
                            </div>
                            <span class="material-symbols-sharp payment-method-icon">qr_code</span>
                        </label>
                        <?php endif; ?>

                        <?php if ($cartaoDisponivel): ?>
                        <label class="payment-method" onclick="selectPayment('cartao')">
                            <input type="radio" name="payment" value="cartao" id="payment_cartao">
                            <div class="payment-method-info">
                                <div class="payment-method-name">Cartao</div>
                                <div class="payment-method-desc">Credito em ambiente seguro com parcelamento</div>
                            </div>
                            <span class="material-symbols-sharp payment-method-icon">credit_card</span>
                        </label>
                        <?php endif; ?>

                        <?php if ($boletoDisponivel): ?>
                        <label class="payment-method" onclick="selectPayment('boleto')">
                            <input type="radio" name="payment" value="boleto" id="payment_boleto">
                            <div class="payment-method-info">
                                <div class="payment-method-name">Boleto</div>
                                <div class="payment-method-desc">Compensacao bancaria em ate 3 dias uteis</div>
                            </div>
                            <span class="material-symbols-sharp payment-method-icon">receipt_long</span>
                        </label>
                        <?php endif; ?>
                    </div>

                    <div class="payment-extra" id="paymentGuidance">
                        <strong>Selecione uma forma de pagamento</strong> para exibir os proximos passos. O checkout permanece protegido e integrado com o gateway configurado no painel.
                    </div>

                    <div class="payment-extra" id="cardPreviewFields" style="display: none;">
                        <strong>Dados do cartao</strong>
                        <div class="card-preview-fields">
                            <div class="form-group">
                                <label for="card_nome_preview">Nome no cartao</label>
                                <input type="text" id="card_nome_preview" placeholder="Como esta no cartao" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="card_numero_preview">Numero do cartao</label>
                                <input type="text" id="card_numero_preview" placeholder="0000 0000 0000 0000" autocomplete="off">
                            </div>
                            <div class="card-preview-grid">
                                <div class="form-group">
                                    <label for="card_validade_preview">Validade</label>
                                    <input type="text" id="card_validade_preview" placeholder="MM/AA" autocomplete="off">
                                </div>
                                <div class="form-group">
                                    <label for="card_cvv_preview">CVV</label>
                                    <input type="text" id="card_cvv_preview" placeholder="***" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="cardPaymentBrick_container" style="display: none;">
                        <div class="brick-loader">
                            <span class="material-symbols-sharp" style="font-size: 34px; display: block; margin-bottom: 8px; color: var(--rare-gold);">credit_card</span>
                            Carregando formulario seguro de cartao...
                        </div>
                    </div>

                    <div id="pixContainer" style="display: none; text-align: center;">
                        <div id="pixLoading" style="display: block;">
                            <span class="material-symbols-sharp" style="font-size: 42px; color: var(--rare-gold);">qr_code</span>
                            <h3 style="margin: 14px 0 8px;">Gerando codigo Pix...</h3>
                            <p style="color: #bfc5cc; margin: 0;">Pagamento instantaneo para confirmar seu pedido sem espera.</p>
                        </div>
                        <div id="pixContent" style="display: none;">
                            <h3 style="margin-bottom: 14px;">Pague com Pix</h3>
                            <div id="pixQRCode" style="margin: 18px auto; max-width: 300px;"></div>
                            <p style="margin: 10px 0 18px; color: #bfc5cc;">Escaneie o QR Code no app do seu banco.</p>
                            <div style="border-top: 1px solid rgba(191, 197, 204, 0.24); margin-top: 12px; padding-top: 14px;">
                                <p style="font-weight: 600; margin: 0 0 8px;">Ou copie o codigo:</p>
                                <div style="position: relative;">
                                    <input type="text" id="pixCopyPaste" readonly style="width: 100%; padding: 11px 88px 11px 10px; border-radius: 10px; font-family: monospace; font-size: 11px;">
                                    <button onclick="copiarCodigoPix()" style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); padding: 7px 12px; background: linear-gradient(120deg, #a8873f, #c6a75e); color: #121212; border: none; border-radius: 8px; cursor: pointer; font-weight: 700;">Copiar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="boletoContainer" style="display: none; text-align: center;">
                        <div id="boletoLoading" style="display: block;">
                            <span class="material-symbols-sharp" style="font-size: 42px; color: var(--rare-gold);">receipt_long</span>
                            <h3 style="margin: 14px 0 8px;">Gerando boleto...</h3>
                            <p style="color: #bfc5cc; margin: 0;">O documento sera liberado em instantes.</p>
                        </div>
                        <div id="boletoContent" style="display: none;">
                            <h3 style="margin: 0 0 14px;">Boleto Bancario Gerado</h3>
                            <div style="background: rgba(5, 5, 5, 0.45); border: 1px solid rgba(191, 197, 204, 0.2); padding: 16px; border-radius: 10px; margin: 14px 0;">
                                <p style="font-size: 13px; color: #bfc5cc; margin: 0 0 6px;">Vencimento</p>
                                <p id="boletoDueDate" style="font-size: 20px; font-weight: 700; color: var(--rare-gold); margin: 0;"></p>
                            </div>
                            <div style="border-top: 1px solid rgba(191, 197, 204, 0.22); margin-top: 12px; padding-top: 12px;">
                                <p style="font-weight: 600; margin: 0 0 8px;">Linha digitavel</p>
                                <div style="position: relative;">
                                    <input type="text" id="boletoDigitableLine" readonly style="width: 100%; padding: 11px 88px 11px 10px; border-radius: 10px; font-family: monospace; font-size: 11px;">
                                    <button onclick="copiarLinhaDigitavel()" style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); padding: 7px 12px; background: linear-gradient(120deg, #a8873f, #c6a75e); color: #121212; border: none; border-radius: 8px; cursor: pointer; font-weight: 700;">Copiar</button>
                                </div>
                            </div>
                            <div style="margin-top: 16px;">
                                <a id="boletoPdfLink" href="#" target="_blank" style="display: inline-flex; align-items: center; gap: 7px; padding: 11px 18px; background: linear-gradient(120deg, #a8873f, #c6a75e); color: #111; text-decoration: none; border-radius: 10px; font-weight: 700;">
                                    <span class="material-symbols-sharp" style="font-size: 18px;">download</span>
                                    Baixar Boleto
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="checkout-sidebar">
                <div id="freeShippingBarCheckout" class="free-shipping-bar" style="margin-bottom: 14px; border-radius: 10px;">
                    <!-- Conteúdo gerado dinamicamente pelo JavaScript -->
                </div>

                <div class="checkout-section order-summary">
                    <h2 class="section-title">
                        <span class="material-symbols-sharp">shopping_cart</span>
                        Resumo do Pedido
                    </h2>

                    <div class="summary-items-list" id="summaryItems"></div>

                    <div class="coupon-field">
                        <div class="coupon-badge-wrap" id="summaryCouponBadgeWrap">
                            <span class="coupon-empty">Nenhum cupom aplicado</span>
                        </div>
                        <small>Para inserir ou alterar cupom, volte para o carrinho.</small>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <span class="label">Subtotal</span>
                            <span class="value" id="summarySubtotal">R$ 0,00</span>
                        </div>
                        <div class="summary-row discount" id="summaryDiscountRow" style="display: none;">
                            <span class="label">Desconto</span>
                            <span class="value" id="summaryDiscount">- R$ 0,00</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Frete</span>
                            <span class="value" id="summaryFrete">A calcular</span>
                        </div>
                        <div class="summary-row total">
                            <span class="label">Total</span>
                            <span class="value" id="summaryTotal">R$ 0,00</span>
                        </div>
                    </div>

                    <div class="installment-info" id="summaryInstallments">Parcelamento disponível apenas no cartão de crédito.</div>

                    <button class="btn btn-primary" id="btnFinalizarCompra"
                            onclick="finalizarCompra()"
                            <?php echo (!$gatewayConfigurado ? 'disabled title="Configure o gateway de pagamento no painel admin"' : ''); ?>>
                        <span class="material-symbols-sharp">check_circle</span>
                        <?php echo ($gatewayConfigurado ? 'Finalizar Pedido' : 'Gateway nao configurado'); ?>
                    </button>

                    <a href="carrinho.php" class="btn btn-secondary">
                        <span class="material-symbols-sharp">arrow_back</span>
                        Voltar ao Carrinho
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
<script>
        const __noopLog = (...args) => {};
        // ConfiguraÃ§Ãµes do servidor
        const GATEWAY_ATIVO = <?php echo $gatewayAtivo ? 'true' : 'false'; ?>;
        const GATEWAY_CONFIGURADO = <?php echo $gatewayConfigurado ? 'true' : 'false'; ?>;
        const USUARIO_LOGADO = <?php echo $usuarioLogado ? 'true' : 'false'; ?>;
        const CLIENTE_ID = <?php echo $usuarioLogado && isset($clienteData['id']) ? $clienteData['id'] : 'null'; ?>;
        
        // Dados do carrinho
        let carrinho = {
            items: [],
            subtotal: 0,
            desconto: 0,
            frete: null,
            cupom: null,
            total: 0
        };
        
        // Forma de pagamento selecionada
        let formaPagamento = null;

        function calcularDescontoCupom(cupom, subtotal) {
            if (!cupom) return 0;

            const subtotalNumero = parseFloat(subtotal) || 0;
            if (subtotalNumero <= 0) return 0;

            const descontoAplicado = parseFloat(cupom.desconto_aplicado || cupom.desconto || 0);
            if (descontoAplicado > 0) {
                return Math.min(descontoAplicado, subtotalNumero);
            }

            const valorCupom = parseFloat(cupom.valor || 0);
            if (valorCupom <= 0) return 0;

            const tipoCupom = String(cupom.tipo || cupom.tipo_desconto || '').toLowerCase();
            let desconto = 0;

            if (tipoCupom === 'percentual' || tipoCupom === 'porcentagem') {
                desconto = (subtotalNumero * valorCupom) / 100;
            } else {
                desconto = valorCupom;
            }

            return Math.min(desconto, subtotalNumero);
        }

        function atualizarTextoParcelamento() {
            const installmentsEl = document.getElementById('summaryInstallments');
            if (!installmentsEl) return;

            if (formaPagamento === 'cartao') {
                installmentsEl.innerHTML = 'ou em <strong>3x</strong> sem juros no cartão.';
            } else {
                installmentsEl.textContent = 'Parcelamento disponível apenas no cartão de crédito.';
            }
        }
        
        /**
         * Carregar dados do carrinho do localStorage
         */
        function carregarCarrinho() {
            // Buscar do localStorage
            const cartData = localStorage.getItem('dz_cart');
            const freteData = localStorage.getItem('dz_frete');
            const cupomData = localStorage.getItem('dz_cupom');
            
            if (!cartData || cartData === '[]') {
                alert('Seu carrinho estÃ¡ vazio!');
                window.location.href = 'carrinho.php';
                return;
            }
            
            // Parse dos dados
            carrinho.items = JSON.parse(cartData);
            
            // Calcular subtotal
            carrinho.subtotal = carrinho.items.reduce((sum, item) => {
                return sum + (item.price * item.qty);
            }, 0);
            
            // Processar cupom
            if (cupomData) {
                const cupom = JSON.parse(cupomData);
                carrinho.cupom = cupom;
                carrinho.desconto = calcularDescontoCupom(cupom, carrinho.subtotal);
            }
            
            // Processar frete
            if (freteData) {
                const frete = JSON.parse(freteData);
                carrinho.frete = frete;
                
                // Preencher CEP automaticamente se disponÃ­vel
                if (frete.cep) {
                    const cepInput = document.getElementById('cep');
                    if (cepInput) {
                        cepInput.value = frete.cep;
                    }
                }
            }
            
            // Calcular total
            calcularTotal();
            
            // Renderizar resumo
            renderizarResumo();
               atualizarProgressoFreteGratis();
            atualizarTextoParcelamento();
            
            // Verificar e alertar se frete nÃ£o foi calculado
            if (!carrinho.frete) {
                setTimeout(() => {
                    const freteElement = document.getElementById('summaryFrete');
                    if (freteElement) {
                        freteElement.innerHTML = '<span style="color: #f59e0b;">âš ï¸ A calcular no carrinho</span>';
                    }
                }, 100);
            }
        }
        
        /**
         * Calcular total do pedido
         */
        function calcularTotal() {
            let total = carrinho.subtotal;
            
            // Aplicar desconto
            if (carrinho.desconto > 0) {
                total -= carrinho.desconto;
            }
            
            // Adicionar frete
            if (carrinho.frete && !carrinho.frete.gratis) {
                total += carrinho.frete.valor;
            }
            
            carrinho.total = total;
        }

        /**
         * Atualizar barra de progresso de frete gratis
         */
        function atualizarProgressoFreteGratis() {
            const limiteBruto = <?php echo (float) $freteGratisValor; ?>;
            const limite = Number(limiteBruto) > 0 ? Number(limiteBruto) : 500;
            const bar = document.getElementById('freeShippingBarCheckout');

            if (!bar) return;

            if (carrinho.subtotal >= limite) {
                bar.innerHTML = '<div style="background:linear-gradient(135deg,#1a4d2e 0%,#0f2818 100%);border:1px solid rgba(52,211,153,0.3);border-radius:10px;padding:10px 12px;display:flex;align-items:center;justify-content:center;gap:8px"><span class="material-symbols-sharp" style="font-size:18px;color:#34d399">check_circle</span><div style="text-align:center"><div style="font-size:0.66rem;color:#34d399;font-weight:600;text-transform:uppercase;letter-spacing:0.45px;margin-bottom:1px">Parabens!</div><div style="font-size:0.8rem;color:#34d399;font-weight:700">Frete Gratis Desbloqueado</div></div></div>';
                bar.style.display = 'block';
                return;
            }

            const falta = Math.max(0, limite - carrinho.subtotal);
            const porcentagem = Math.min(100, Math.max(0, (carrinho.subtotal / limite) * 100));

            bar.innerHTML = '<div style="background:linear-gradient(135deg,#1a1a1a 0%,#0f1c2e 100%);border:1px solid rgba(198,167,94,0.2);border-radius:10px;padding:10px 12px"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px"><div style="display:flex;align-items:center;gap:6px"><span class="material-symbols-sharp" style="font-size:16px;color:#bfc5cc">local_shipping</span><span style="font-size:0.68rem;color:#bfc5cc;font-weight:600;text-transform:uppercase;letter-spacing:0.45px">Frete Gratis</span></div><span style="font-size:0.82rem;color:#c6a75e;font-weight:700">R$ ' + formatarDinheiro(carrinho.subtotal) + ' / R$ ' + formatarDinheiro(limite) + '</span></div><div style="height:5px;background:rgba(255,255,255,0.06);border-radius:99px;overflow:hidden;border:1px solid rgba(198,167,94,0.15)"><div style="height:100%;background:linear-gradient(90deg,#c6a75e 0%,#e6d1a3 100%);border-radius:inherit;transition:width 0.45s cubic-bezier(0.34,1.56,0.64,1);width:' + porcentagem + '%;box-shadow:0 0 14px rgba(198,167,94,0.35)"></div></div><div style="font-size:0.7rem;color:#bfc5cc;margin-top:6px;text-align:right">Faltam R$ ' + formatarDinheiro(falta) + ' para frete gratis</div></div>';
            bar.style.display = 'block';
        }
        
        /**
         * Renderizar resumo do pedido
         */
        function renderizarResumo() {
            const container = document.getElementById('summaryItems');
            const cupomBadgeWrap = document.getElementById('summaryCouponBadgeWrap');
            
            container.innerHTML = carrinho.items.map(item => `
                <div class="summary-item">
                    <div class="summary-item-image">
                        <img src="${item.image}" alt="${item.name}" onerror="this.onerror=null;this.src='../assets/images/logo.png';">
                    </div>
                    <div class="summary-item-info">
                        <div class="summary-item-name">${item.name}</div>
                        <div class="summary-item-qty">Qtd: ${item.qty}${item.size ? ` • Tam: ${item.size}` : ''}</div>
                    </div>
                    <div class="summary-item-price">R$ ${formatarDinheiro(item.price * item.qty)}</div>
                </div>
            `).join('');

            if (cupomBadgeWrap) {
                if (carrinho.cupom && carrinho.cupom.codigo) {
                    cupomBadgeWrap.innerHTML = `
                        <span class="coupon-badge">
                            <span class="material-symbols-sharp">confirmation_number</span>
                            ${carrinho.cupom.codigo}
                        </span>
                    `;
                } else {
                    cupomBadgeWrap.innerHTML = '<span class="coupon-empty">Nenhum cupom aplicado</span>';
                }
            }
            
            // Atualizar totais
            document.getElementById('summarySubtotal').textContent = 'R$ ' + formatarDinheiro(carrinho.subtotal);
            
            if (carrinho.desconto > 0) {
                document.getElementById('summaryDiscountRow').style.display = 'flex';
                document.getElementById('summaryDiscount').textContent = '- R$ ' + formatarDinheiro(carrinho.desconto);
            } else {
                document.getElementById('summaryDiscountRow').style.display = 'none';
                document.getElementById('summaryDiscount').textContent = '- R$ 0,00';
            }
            
            if (carrinho.frete) {
                let freteTexto = '';
                if (carrinho.frete.gratis) {
                    freteTexto = 'GRÃTIS';
                } else {
                    freteTexto = 'R$ ' + formatarDinheiro(carrinho.frete.valor);
                }
                
                // Adicionar nome do serviÃ§o e prazo se disponÃ­vel
                if (carrinho.frete.nome) {
                    freteTexto += ` (${carrinho.frete.nome})`;
                }
                if (carrinho.frete.prazo) {
                    freteTexto += ` - ${carrinho.frete.prazo} dias`;
                }
                
                document.getElementById('summaryFrete').textContent = freteTexto;
            } else {
                document.getElementById('summaryFrete').textContent = 'A calcular';
                document.getElementById('summaryFrete').style.color = '#f59e0b';
            }
            
            document.getElementById('summaryTotal').textContent = 'R$ ' + formatarDinheiro(carrinho.total);
        }
        
        /**
         * Formatar nÃºmero como dinheiro
         */
        function formatarDinheiro(valor) {
            return parseFloat(valor).toFixed(2).replace('.', ',');
        }
        
        /**
         * Selecionar forma de pagamento
         */
        function selectPayment(tipo) {
            formaPagamento = tipo;
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
            document.getElementById('payment_' + tipo).closest('.payment-method').classList.add('selected');
            atualizarTextoParcelamento();

            const guidance = document.getElementById('paymentGuidance');
            const cardPreviewFields = document.getElementById('cardPreviewFields');
            if (guidance) {
                if (tipo === 'pix') {
                    guidance.innerHTML = '<strong>Pix selecionado.</strong> Pagamento instantâneo e confirmação rápida do pedido.';
                } else if (tipo === 'cartao' || tipo === 'debito') {
                    guidance.innerHTML = '<strong>Cartão selecionado.</strong> Complete os dados no formulário seguro abaixo e finalize com tranquilidade.';
                } else if (tipo === 'boleto') {
                    guidance.innerHTML = '<strong>Boleto selecionado.</strong> A confirmação ocorre após a compensação bancária.';
                }
            }

            if (cardPreviewFields) {
                cardPreviewFields.style.display = (tipo === 'cartao' || tipo === 'debito') ? 'block' : 'none';
            }
            
            // Gerenciar visibilidade dos containers
            const brickContainer = document.getElementById('cardPaymentBrick_container');
            const pixContainer = document.getElementById('pixContainer');
            const boletoContainer = document.getElementById('boletoContainer');
            
            // Resetar todos os containers
            if (brickContainer) brickContainer.style.display = 'none';
            if (pixContainer) {
                pixContainer.style.display = 'none';
                document.getElementById('pixLoading').style.display = 'block';
                document.getElementById('pixContent').style.display = 'none';
            }
            if (boletoContainer) {
                boletoContainer.style.display = 'none';
                document.getElementById('boletoLoading').style.display = 'block';
                document.getElementById('boletoContent').style.display = 'none';
            }
            
            // Mostrar container apropriado
            if (tipo === 'cartao' || tipo === 'debito') {
                // Card Payment Brick
                if (brickContainer) {
                    brickContainer.style.display = 'block';
                    if (!brickController) {
                        __noopLog('Inicializando Card Payment Brick...');
                        initializeCardPaymentBrick();
                    }
                }
            } else if (tipo === 'pix') {
                // Mostrar container do Pix
                if (pixContainer) {
                    pixContainer.style.display = 'block';
                }
            } else if (tipo === 'boleto') {
                // Mostrar container do Boleto
                if (boletoContainer) {
                    boletoContainer.style.display = 'block';
                }
            }
        }
        
        /**
         * Validar formulÃ¡rio
         */
        function validarFormulario() {
            const campos = [
                { id: 'nome', nome: 'Nome' },
                { id: 'email', nome: 'E-mail' },
                { id: 'telefone', nome: 'Telefone' },
                { id: 'cpf_cnpj', nome: 'CPF/CNPJ' },
                { id: 'cep', nome: 'CEP' },
                { id: 'rua', nome: 'Rua' },
                { id: 'numero', nome: 'NÃºmero' },
                { id: 'bairro', nome: 'Bairro' },
                { id: 'cidade', nome: 'Cidade' },
                { id: 'estado', nome: 'Estado' }
            ];
            
            for (const campo of campos) {
                const valor = document.getElementById(campo.id).value.trim();
                if (!valor) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campo obrigatÃ³rio',
                        text: `Por favor, preencha o campo: ${campo.nome}`,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ff00d4'
                    });
                    document.getElementById(campo.id).focus();
                    return false;
                }
            }
            
            if (!formaPagamento) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Forma de pagamento',
                    text: 'Por favor, selecione uma forma de pagamento',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                return false;
            }
            
            if (!carrinho.frete) {
                Swal.fire({
                    icon: 'warning',
                    title: 'âš ï¸ Frete nÃ£o calculado',
                    text: 'Por favor, volte ao carrinho e calcule o frete antes de finalizar a compra.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                return false;
            }
            
            return true;
        }
        
        /**
         * Processar pagamento transparente (chamado pelo callback do Brick)
         */
        async function processarPagamentoTransparente() {
            try {
                if (!paymentData) {
                    throw new Error('Dados do pagamento nÃ£o disponÃ­veis');
                }
                
                __noopLog('âœ… Dados do cartÃ£o validados:', paymentData);
                
                // Obter carrinho
                const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                const descontoAplicado = calcularDescontoCupom(cupom, carrinho.reduce((sum, item) => sum + (item.price * item.qty), 0));
                
                __noopLog('ðŸ›’ Carrinho:', carrinho);
                __noopLog('ðŸ“¦ Frete:', frete);
                __noopLog('ðŸŽ« Cupom:', cupom);
                
                // Validar se carrinho nÃ£o estÃ¡ vazio
                if (!carrinho || carrinho.length === 0) {
                    throw new Error('Carrinho vazio! Adicione produtos antes de finalizar a compra.');
                }
                
                // Coletar dados do pedido + dados do pagamento
                const dadosPedido = {
                    cliente: {
                        id: CLIENTE_ID,
                        nome: document.getElementById('nome').value,
                        email: document.getElementById('email').value,
                        telefone: document.getElementById('telefone').value,
                        cpf_cnpj: document.getElementById('cpf_cnpj').value
                    },
                    endereco: {
                        cep: document.getElementById('cep').value,
                        rua: document.getElementById('rua').value,
                        numero: document.getElementById('numero').value,
                        complemento: document.getElementById('complemento').value,
                        bairro: document.getElementById('bairro').value,
                        cidade: document.getElementById('cidade').value,
                        estado: document.getElementById('estado').value
                    },
                    carrinho: {
                        items: carrinho,
                        frete: frete,
                        desconto: descontoAplicado,
                        cupom: cupom  // Enviar cupom completo tambÃ©m
                    },
                    pagamento: {
                        forma: formaPagamento,
                        transparente: true,  // Flag para indicar checkout transparente
                        payment_data: paymentData  // Dados tokenizados do Brick
                    }
                };
                
                __noopLog('ðŸ“¤ Enviando dados do pedido para o backend...', dadosPedido);
                
                // Enviar para backend processar pagamento
                const response = await fetch('../api/processar-pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dadosPedido)
                });
                
                // Limpar paymentData apÃ³s enviar
                paymentData = null;
                
                // Verificar se resposta Ã© JSON vÃ¡lido
                const responseText = await response.text();
                __noopLog('ðŸ“¥ Resposta bruta do backend:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('âŒ Erro ao fazer parse da resposta:', parseError);
                    throw new Error('Erro no servidor. Verifique o console do PHP para detalhes.');
                }
                
                __noopLog('ðŸ“¥ Resposta do backend:', result);
                
                if (result.success) {
                    __noopLog('âœ… Pedido criado com sucesso');
                    
                    // Verificar status do pagamento
                    const paymentStatus = result.data.payment_status;
                    const pedidoId = result.data.pedido_id;
                    __noopLog('ðŸ’³ Status do pagamento:', paymentStatus);
                    
                    // Ocultar loading
                    document.getElementById('loadingOverlay').classList.remove('active');
                    document.getElementById('btnFinalizarCompra').disabled = false;
                    
                    if (paymentStatus === 'approved') {
                        __noopLog('âœ… Pagamento APROVADO');
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                        
                        // SweetAlert de sucesso
                        await Swal.fire({
                            icon: 'success',
                            title: 'ðŸŽ‰ Pagamento Aprovado!',
                            html: `
                                <p><strong>Seu pedido foi confirmado com sucesso!</strong></p>
                                <p>NÃºmero do pedido: <strong>#${pedidoId}</strong></p>
                                <p>VocÃª receberÃ¡ um e-mail com todos os detalhes.</p>
                            `,
                            confirmButtonText: 'Ver Meus Pedidos',
                            confirmButtonColor: '#ff00d4',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });
                        
                        window.location.href = 'pedidos.php?status=success&pedido=' + pedidoId;
                    } else if (paymentStatus === 'pending' || paymentStatus === 'in_process') {
                        __noopLog('â³ Pagamento PENDENTE');
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                        
                        // SweetAlert de pendente
                        await Swal.fire({
                            icon: 'info',
                            title: 'â³ Pagamento Pendente',
                            html: `
                                <p><strong>Seu pedido estÃ¡ sendo processado!</strong></p>
                                <p>NÃºmero do pedido: <strong>#${pedidoId}</strong></p>
                                <p>Aguarde a confirmaÃ§Ã£o do pagamento. VocÃª receberÃ¡ um e-mail assim que for aprovado.</p>
                            `,
                            confirmButtonText: 'Ver Meus Pedidos',
                            confirmButtonColor: '#ff00d4'
                        });
                        
                        window.location.href = 'pedidos.php?status=pending&pedido=' + pedidoId;
                    } else {
                        // rejected ou outros status
                        __noopLog('âŒ Pagamento RECUSADO:', paymentStatus);
                        
                        // Restaurar carrinho (NÃƒO limpar)
                        localStorage.setItem('dz_cart', JSON.stringify(carrinho));
                        if (frete) localStorage.setItem('dz_frete', JSON.stringify(frete));
                        if (cupom) localStorage.setItem('dz_cupom', JSON.stringify(cupom));
                        
                        // Mensagem especÃ­fica do Mercado Pago
                        const mensagemErro = result.data.payment_message || 'O pagamento foi recusado. Verifique os dados do cartÃ£o e tente novamente.';
                        const detalheErro = result.data.payment_detail || '';
                        
                        // SweetAlert de erro
                        await Swal.fire({
                            icon: 'error',
                            title: 'âŒ Pagamento Recusado',
                            html: `
                                <p><strong>${mensagemErro}</strong></p>
                                ${detalheErro ? `<p style="color: #666; font-size: 0.9em;">${detalheErro}</p>` : ''}
                                <p style="margin-top: 15px;">Por favor, tente:</p>
                                <ul style="text-align: left; display: inline-block;">
                                    <li>Verificar os dados do cartÃ£o</li>
                                    <li>Usar outro cartÃ£o</li>
                                    <li>Escolher outra forma de pagamento</li>
                                </ul>
                            `,
                            confirmButtonText: 'Tentar Novamente',
                            confirmButtonColor: '#ff00d4'
                        });
                        
                        // Habilitar botÃ£o novamente
                        document.getElementById('loadingOverlay').classList.remove('active');
                        document.getElementById('btnFinalizarCompra').disabled = false;
                    }
                } else {
                    throw new Error(result.message || 'Erro ao processar pagamento');
                }
            } catch (error) {
                console.error('âŒ Erro:', error);
                
                // Ocultar loading
                document.getElementById('loadingOverlay').classList.remove('active');
                document.getElementById('btnFinalizarCompra').disabled = false;
                
                // SweetAlert de erro genÃ©rico
                Swal.fire({
                    icon: 'error',
                    title: 'âŒ Erro ao processar pagamento',
                    text: error.message || 'Ocorreu um erro inesperado. Por favor, tente novamente.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
            }
        }
        
        /**
         * Finalizar compra
         */
        async function finalizarCompra() {
            // Validar se gateway estÃ¡ configurado
            if (!GATEWAY_CONFIGURADO) {
                Swal.fire({
                    icon: 'warning',
                    title: 'âš ï¸ Gateway nÃ£o configurado',
                    text: 'Por favor, configure as credenciais do gateway no painel administrativo antes de processar pagamentos.',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                return;
            }
            
            // Validar formulÃ¡rio
            if (!validarFormulario()) {
                return;
            }
            
            // Mostrar loading
            document.getElementById('loadingOverlay').classList.add('active');
            document.getElementById('btnFinalizarCompra').disabled = true;
            
            try {
                // Se for pagamento com cartÃ£o (transparente), disparar o botÃ£o do Brick
                if (formaPagamento === 'cartao' || formaPagamento === 'debito') {
                    // Verificar se o Brick foi inicializado
                    if (!brickController) {
                        throw new Error('FormulÃ¡rio de pagamento ainda nÃ£o carregou. Aguarde alguns segundos e tente novamente.');
                    }
                    
                    __noopLog('ðŸ”„ Disparando submit do Card Payment Brick...');
                    
                    // Encontrar e clicar no botÃ£o de submit do Brick
                    const brickSubmitButton = document.querySelector('#cardPaymentBrick_container button[type="submit"]');
                    if (brickSubmitButton) {
                        brickSubmitButton.click();
                        // O callback onSubmit serÃ¡ chamado automaticamente e processarÃ¡ o pagamento
                    } else {
                        throw new Error('BotÃ£o de pagamento nÃ£o encontrado. Recarregue a pÃ¡gina e tente novamente.');
                    }
                } else if (formaPagamento === 'pix') {
                    // ===== PIX NATIVO (TRANSPARENTE) =====
                    __noopLog('Processando pagamento Pix...');
                    
                    const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                    const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                    const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                    const descontoAplicado = calcularDescontoCupom(cupom, carrinho.reduce((sum, item) => sum + (item.price * item.qty), 0));
                    
                    const dadosPedido = {
                        cliente: {
                            id: CLIENTE_ID,
                            nome: document.getElementById('nome').value,
                            email: document.getElementById('email').value,
                            telefone: document.getElementById('telefone').value,
                            cpf_cnpj: document.getElementById('cpf_cnpj').value
                        },
                        endereco: {
                            cep: document.getElementById('cep').value,
                            rua: document.getElementById('rua').value,
                            numero: document.getElementById('numero').value,
                            complemento: document.getElementById('complemento').value,
                            bairro: document.getElementById('bairro').value,
                            cidade: document.getElementById('cidade').value,
                            estado: document.getElementById('estado').value
                        },
                        carrinho: {
                            items: carrinho,
                            frete: frete,
                            desconto: descontoAplicado,
                            cupom: cupom
                        },
                        pagamento: {
                            forma: 'pix',
                            transparente: true, // PIX NATIVO
                            payment_method_id: 'pix'
                        }
                    };
                    
                    const response = await fetch('../api/processar-pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dadosPedido)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.data.pix_qr_code) {
                        // Exibir QR Code do Pix
                        exibirQRCodePix(result.data.pix_qr_code, result.data.pix_qr_code_base64);
                        
                        // Esconder loading e botÃ£o finalizar
                        document.getElementById('loadingOverlay').classList.remove('active');
                        document.getElementById('btnFinalizarCompra').style.display = 'none';
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                    } else {
                        throw new Error(result.message || 'Erro ao gerar cÃ³digo Pix');
                    }
                    
                } else if (formaPagamento === 'boleto') {
                    // ===== BOLETO NATIVO (TRANSPARENTE) =====
                    __noopLog('Processando pagamento Boleto...');
                    
                    const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                    const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                    const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                    const descontoAplicado = calcularDescontoCupom(cupom, carrinho.reduce((sum, item) => sum + (item.price * item.qty), 0));
                    
                    const dadosPedido = {
                        cliente: {
                            id: CLIENTE_ID,
                            nome: document.getElementById('nome').value,
                            email: document.getElementById('email').value,
                            telefone: document.getElementById('telefone').value,
                            cpf_cnpj: document.getElementById('cpf_cnpj').value
                        },
                        endereco: {
                            cep: document.getElementById('cep').value,
                            rua: document.getElementById('rua').value,
                            numero: document.getElementById('numero').value,
                            complemento: document.getElementById('complemento').value,
                            bairro: document.getElementById('bairro').value,
                            cidade: document.getElementById('cidade').value,
                            estado: document.getElementById('estado').value
                        },
                        carrinho: {
                            items: carrinho,
                            frete: frete,
                            desconto: descontoAplicado,
                            cupom: cupom
                        },
                        pagamento: {
                            forma: 'boleto',
                            transparente: true, // BOLETO NATIVO
                            payment_method_id: 'bolbancario'
                        }
                    };
                    
                    const response = await fetch('../api/processar-pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dadosPedido)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success && result.data.boleto_url) {
                        // Exibir dados do Boleto
                        exibirBoleto(
                            result.data.boleto_url,
                            result.data.boleto_digitable_line,
                            result.data.boleto_due_date
                        );
                        
                        // Esconder loading e botÃ£o finalizar
                        document.getElementById('loadingOverlay').classList.remove('active');
                        document.getElementById('btnFinalizarCompra').style.display = 'none';
                        
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                    } else {
                        throw new Error(result.message || 'Erro ao gerar Boleto');
                    }
                    
                } else {
                    // ===== OUTROS (FUTURO) =====
                    __noopLog('Processando pagamento com redirect...');
                    
                    const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                    const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                    const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                    const descontoAplicado = calcularDescontoCupom(cupom, carrinho.reduce((sum, item) => sum + (item.price * item.qty), 0));
                    
                    const dadosPedido = {
                        cliente: {
                            id: CLIENTE_ID,
                            nome: document.getElementById('nome').value,
                            email: document.getElementById('email').value,
                            telefone: document.getElementById('telefone').value,
                            cpf_cnpj: document.getElementById('cpf_cnpj').value
                        },
                        endereco: {
                            cep: document.getElementById('cep').value,
                            rua: document.getElementById('rua').value,
                            numero: document.getElementById('numero').value,
                            complemento: document.getElementById('complemento').value,
                            bairro: document.getElementById('bairro').value,
                            cidade: document.getElementById('cidade').value,
                            estado: document.getElementById('estado').value
                        },
                        carrinho: {
                            items: carrinho,
                            frete: frete,
                            desconto: descontoAplicado,
                            cupom: cupom
                        },
                        pagamento: {
                            forma: formaPagamento,
                            transparente: false
                        }
                    };
                    
                    const response = await fetch('../api/processar-pedido.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dadosPedido)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Limpar carrinho
                        localStorage.removeItem('dz_cart');
                        localStorage.removeItem('dz_frete');
                        localStorage.removeItem('dz_cupom');
                        
                        // Redirecionar para Mercado Pago se houver init_point
                        if (result.data.init_point) {
                            window.location.href = result.data.init_point;
                        } else {
                            await Swal.fire({
                                icon: 'success',
                                title: 'âœ… Pedido realizado!',
                                text: 'NÃºmero do pedido: #' + result.data.pedido_id,
                                confirmButtonText: 'Ver Meus Pedidos',
                                confirmButtonColor: '#ff00d4'
                            });
                            window.location.href = 'pedidos.php';
                        }
                    } else {
                        throw new Error(result.message || 'Erro desconhecido');
                    }
                }
            } catch (error) {
                console.error('âŒ Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'âŒ Erro ao finalizar compra',
                    text: error.message,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ff00d4'
                });
                
                // Ocultar loading
                document.getElementById('loadingOverlay').classList.remove('active');
                document.getElementById('btnFinalizarCompra').disabled = false;
            }
        }
        
        /**
         * Buscar CEP na API ViaCEP
         */
        async function buscarCep() {
            const cepInput = document.getElementById('cep');
            const cep = cepInput.value.replace(/\D/g, '');

            if (cep.length !== 8) {
                return;
            }

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();
                
                if (!data.erro) {
                    document.getElementById('rua').value = data.logradouro || '';
                    document.getElementById('bairro').value = data.bairro || '';
                    document.getElementById('cidade').value = data.localidade || '';
                    document.getElementById('estado').value = data.uf || '';
                    document.getElementById('numero').focus();
                }
            } catch (error) {
                console.error('Erro ao buscar CEP:', error);
            }
        }

        document.getElementById('cep').addEventListener('blur', buscarCep);
        
        /**
         * MÃ¡scaras de formataÃ§Ã£o
         */
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substr(0, 11);
            if (value.length > 6) {
                value = `(${value.substr(0, 2)}) ${value.substr(2, 5)}-${value.substr(7)}`;
            } else if (value.length > 2) {
                value = `(${value.substr(0, 2)}) ${value.substr(2)}`;
            }
            e.target.value = value;
        });
        
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.substr(0, 8);
            if (value.length > 5) {
                value = `${value.substr(0, 5)}-${value.substr(5)}`;
            }
            e.target.value = value;
        });
        
        document.getElementById('cpf_cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                // CPF
                if (value.length > 9) {
                    value = `${value.substr(0, 3)}.${value.substr(3, 3)}.${value.substr(6, 3)}-${value.substr(9)}`;
                } else if (value.length > 6) {
                    value = `${value.substr(0, 3)}.${value.substr(3, 3)}.${value.substr(6)}`;
                } else if (value.length > 3) {
                    value = `${value.substr(0, 3)}.${value.substr(3)}`;
                }
            } else {
                // CNPJ
                if (value.length > 14) value = value.substr(0, 14);
                if (value.length > 12) {
                    value = `${value.substr(0, 2)}.${value.substr(2, 3)}.${value.substr(5, 3)}/${value.substr(8, 4)}-${value.substr(12)}`;
                } else if (value.length > 8) {
                    value = `${value.substr(0, 2)}.${value.substr(2, 3)}.${value.substr(5, 3)}/${value.substr(8)}`;
                } else if (value.length > 5) {
                    value = `${value.substr(0, 2)}.${value.substr(2, 3)}.${value.substr(5)}`;
                } else if (value.length > 2) {
                    value = `${value.substr(0, 2)}.${value.substr(2)}`;
                }
            }
            e.target.value = value;
        });
        
        // Desabilitar botÃ£o se gateway inativo
        if (!GATEWAY_ATIVO) {
            document.getElementById('btnFinalizarCompra').disabled = true;
            document.querySelectorAll('.payment-method').forEach(el => {
                el.style.opacity = '0.5';
                el.style.cursor = 'not-allowed';
                el.onclick = () => {};
            });
        }
        
        // ===== FUNÃ‡Ã•ES DO NAVBAR =====
        
        /**
         * Toggle do dropdown do usuÃ¡rio
         */
        function toggleUserDropdown(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            const dropdown = document.querySelector('.user-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.user-dropdown');
            if (dropdown && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        /**
         * Toggle do menu mobile
         */
        function toggleMobileMenu(event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            
            const hamburger = document.querySelector('.hamburger');
            const overlay = document.querySelector('.mobile-menu-overlay');
            const menu = document.querySelector('.mobile-menu');
            
            if (hamburger && overlay && menu) {
                hamburger.classList.toggle('open');
                overlay.classList.toggle('active');
                menu.classList.toggle('active');
                document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
            }
        }
        
        /**
         * Barra de pesquisa
         */
        const searchToggle = document.getElementById('searchToggle');
        const searchPanel = document.getElementById('searchPanel');
        const searchInput = document.getElementById('searchInput');

        function closeSearchPanel() {
            if (!searchPanel || !searchToggle) return;
            searchPanel.classList.remove('active');
            searchToggle.setAttribute('aria-expanded', 'false');
        }

        if (searchToggle && searchPanel) {
            searchToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const isOpen = searchPanel.classList.contains('active');
                const searchValue = searchInput ? searchInput.value.trim() : '';
                
                // Se jÃ¡ estÃ¡ aberto e tem valor, fazer busca
                if (isOpen && searchValue) {
                    window.location.href = '../produtos.php?busca=' + encodeURIComponent(searchValue);
                    return;
                }
                
                // Se nÃ£o estÃ¡ aberto, abrir
                if (!isOpen) {
                    requestAnimationFrame(() => {
                        searchPanel.classList.add('active');
                        searchToggle.setAttribute('aria-expanded', 'true');
                        
                        if (searchInput) {
                            setTimeout(() => {
                                requestAnimationFrame(() => {
                                    searchInput.focus();
                                });
                            }, 350);
                        }
                    });
                    return;
                }
                
                // Fechar se estiver aberto
                closeSearchPanel();
            });
        }

        // Fechar search ao clicar fora
        document.addEventListener('click', (e) => {
            if (!searchPanel || !searchToggle) return;
            if (!searchPanel.classList.contains('active')) return;
            if (searchPanel.contains(e.target) || searchToggle.contains(e.target)) return;
            closeSearchPanel();
        });
        
        // ===== FIM FUNÃ‡Ã•ES DO NAVBAR =====
        
        /**
         * Atualizar contador do carrinho no navbar
         */
        function updateCartBadge() {
            const cart = localStorage.getItem('dz_cart');
            const items = cart ? JSON.parse(cart) : [];
            const totalItems = items.reduce((sum, item) => sum + (parseInt(item.qty) || 1), 0);
            
            const badge = document.getElementById('cartBadge');
            if (badge) {
                badge.textContent = totalItems;
                badge.style.display = totalItems > 0 ? 'flex' : 'none';
            }
        }
        
        // ===== MERCADO PAGO - CHECKOUT TRANSPARENTE =====
        let mpBrickInstance = null;
        let brickController = null;
        let paymentData = null; // Armazenar dados do pagamento tokenizado
        let isInitializing = false; // Flag para prevenir mÃºltiplas inicializaÃ§Ãµes simultÃ¢neas
        
        /**
         * Inicializar Mercado Pago SDK
         */
        async function initializeMercadoPago() {
            if (typeof MP_PUBLIC_KEY === 'undefined' || !MP_PUBLIC_KEY) {
                console.error('âŒ Public Key do Mercado Pago nÃ£o configurada');
                return false;
            }
            
            if (typeof MercadoPago === 'undefined') {
                console.error('âŒ SDK do Mercado Pago nÃ£o carregado. Verifique sua conexÃ£o com a internet.');
                return false;
            }
            
            try {
                __noopLog('ðŸ”„ Inicializando Mercado Pago SDK...');
                __noopLog('Public Key:', MP_PUBLIC_KEY.substring(0, 20) + '...');
                
                mpBrickInstance = new MercadoPago(MP_PUBLIC_KEY, {
                    locale: 'pt-BR'
                });
                
                __noopLog('âœ… Mercado Pago SDK inicializado com sucesso');
                return true;
            } catch (error) {
                console.error('âŒ Erro ao inicializar Mercado Pago:', error);
                return false;
            }
        }
        
        /**
         * Inicializar Card Payment Brick
         */
        async function initializeCardPaymentBrick() {
            __noopLog('ðŸ”„ Tentando inicializar Card Payment Brick...');
            
            // Verificar se jÃ¡ estÃ¡ inicializado ou em processo de inicializaÃ§Ã£o
            if (brickController) {
                __noopLog('âœ… Brick jÃ¡ estÃ¡ inicializado');
                return;
            }
            
            if (isInitializing) {
                __noopLog('â³ Brick jÃ¡ estÃ¡ sendo inicializado, aguardando...');
                return;
            }
            
            // Marcar como em processo de inicializaÃ§Ã£o
            isInitializing = true;
            
            if (!mpBrickInstance) {
                console.error('âŒ Mercado Pago SDK nÃ£o inicializado. Inicializando agora...');
                const initialized = await initializeMercadoPago();
                if (!initialized) {
                    isInitializing = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro no sistema de pagamento',
                        text: 'Erro ao carregar sistema de pagamento. Recarregue a pÃ¡gina e tente novamente.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ff00d4'
                    });
                    return;
                }
            }
            
            try {
                __noopLog('ðŸ”„ Criando Card Payment Brick...');
                
                // Obter valor total do carrinho
                const carrinho = JSON.parse(localStorage.getItem('dz_cart') || '[]');
                const frete = JSON.parse(localStorage.getItem('dz_frete') || '{}');
                const cupom = JSON.parse(localStorage.getItem('dz_cupom') || '{}');
                
                let subtotal = carrinho.reduce((sum, item) => sum + (item.price * item.qty), 0);
                let desconto = calcularDescontoCupom(cupom, subtotal);
                let valorFrete = frete.gratis ? 0 : (frete.valor || 0);
                let total = subtotal - desconto + valorFrete;
                
                __noopLog('ðŸ’° Valor total do pedido: R$', total.toFixed(2));
                
                // Garantir que o valor seja maior que zero
                if (total <= 0) {
                    console.error('âŒ Valor total invÃ¡lido:', total);
                    throw new Error('Valor do pedido deve ser maior que zero');
                }
                
                const bricksBuilder = mpBrickInstance.bricks();
                
                __noopLog('ðŸ“‹ ConfiguraÃ§Ã£o do Brick:', {
                    amount: parseFloat(total.toFixed(2)),
                    locale: 'pt-BR'
                });
                
                brickController = await bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', {
                    initialization: {
                        amount: parseFloat(total.toFixed(2))
                    },
                    locale: 'pt-BR',
                    customization: {
                        visual: {
                            style: {
                                theme: 'default'
                            }
                        }
                    },
                    callbacks: {
                        onReady: () => {
                            __noopLog('âœ… Card Payment Brick pronto para uso');
                            __noopLog('ðŸ” Inspecionando formulÃ¡rio do Brick...');
                            
                            // Esconder botÃ£o padrÃ£o do Brick VISUALMENTE (mas manter funcional)
                            const brickButton = document.querySelector('#cardPaymentBrick_container button[type="submit"]');
                            if (brickButton) {
                                brickButton.style.position = 'absolute';
                                brickButton.style.left = '-9999px';
                                brickButton.style.width = '1px';
                                brickButton.style.height = '1px';
                                brickButton.style.opacity = '0';
                                __noopLog('   - BotÃ£o padrÃ£o escondido (mas funcional)');
                            }
                            
                            // Debug: verificar estrutura do formulÃ¡rio apÃ³s 2 segundos
                            window.setTimeout(function() {
                                const container = document.getElementById('cardPaymentBrick_container');
                                if (container) {
                                    const selects = container.querySelectorAll('select');
                                    const inputs = container.querySelectorAll('input');
                                    __noopLog('ðŸ“Š Campos encontrados no Brick:');
                                    __noopLog('   - Total de <select>:', selects.length);
                                    __noopLog('   - Total de <input>:', inputs.length);
                                    
                                    selects.forEach(function(sel, idx) {
                                        const name = sel.name || sel.getAttribute('name') || '(sem name)';
                                        __noopLog('   - Select #' + idx + ': name="' + name + '" id="' + sel.id + '" options=' + sel.options.length);
                                        if (sel.options.length > 0) {
                                            __noopLog('     Primeira opÃ§Ã£o: "' + sel.options[0].text + '"');
                                        }
                                    });
                                }
                            }, 2000);
                        },
                        onSubmit: async (formData) => {
                            __noopLog('ðŸ“ Dados do formulÃ¡rio recebidos do Brick:', formData);
                            paymentData = formData;
                            __noopLog('âœ… paymentData armazenado, processando pagamento...');
                            
                            // Processar pagamento imediatamente
                            await processarPagamentoTransparente();
                            
                            return new Promise((resolve) => {
                                resolve();
                            });
                        },
                        onError: (error) => {
                            console.error('âŒ Erro no Card Payment Brick:', error);
                            // NÃ£o mostrar alert aqui pois pode ser validaÃ§Ã£o de campo
                        }
                    }
                });
                
                __noopLog('âœ… Card Payment Brick inicializado com sucesso');
                isInitializing = false; // Liberar flag apÃ³s sucesso
            } catch (error) {
                isInitializing = false; // Liberar flag em caso de erro
                console.error('âŒ Erro ao inicializar Card Payment Brick:', error);
                console.error('Detalhes do erro:', error.message, error.stack);
                document.getElementById('cardPaymentBrick_container').innerHTML = 
                    '<div class="alert alert-danger" style="padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00;">' +
                    '<strong>âŒ Erro ao carregar formulÃ¡rio de pagamento</strong><br>' +
                    'Detalhes: ' + error.message + '<br><br>' +
                    '<button onclick="location.reload()" style="padding: 10px 20px; background: #c00; color: white; border: none; border-radius: 4px; cursor: pointer;">Recarregar PÃ¡gina</button>' +
                    '</div>';
            }
        }
        
        // Inicializar ao carregar
        window.addEventListener('DOMContentLoaded', () => {
            __noopLog('ðŸ“„ PÃ¡gina carregada');
            __noopLog('MercadoPago disponÃ­vel?', typeof MercadoPago !== 'undefined');
            __noopLog('MP_PUBLIC_KEY disponÃ­vel?', typeof MP_PUBLIC_KEY !== 'undefined');
            
            carregarCarrinho();
            updateCartBadge(); // Atualizar contador do carrinho
            
            // Aguardar SDK do Mercado Pago estar disponÃ­vel
            const waitForMercadoPago = setInterval(() => {
                if (typeof MercadoPago !== 'undefined' && typeof MP_PUBLIC_KEY !== 'undefined') {
                    clearInterval(waitForMercadoPago);
                    __noopLog('ðŸ”„ SDK do Mercado Pago detectado, inicializando...');
                    initializeMercadoPago();
                }
            }, 100); // Verificar a cada 100ms
            
            // Timeout de seguranÃ§a (10 segundos)
            setTimeout(() => {
                clearInterval(waitForMercadoPago);
                if (typeof MercadoPago === 'undefined') {
                    console.error('âŒ Timeout: SDK do Mercado Pago nÃ£o carregou');
                    console.error('Verifique sua conexÃ£o com a internet');
                }
            }, 10000);
        });
        
        /**
         * Exibir QR Code do Pix
         */
        function exibirQRCodePix(qrCode, qrCodeBase64) {
            const pixContainer = document.getElementById('pixContainer');
            const pixLoading = document.getElementById('pixLoading');
            const pixContent = document.getElementById('pixContent');
            const pixQRCode = document.getElementById('pixQRCode');
            const pixCopyPaste = document.getElementById('pixCopyPaste');
            
            // Criar imagem do QR Code
            pixQRCode.innerHTML = `<img src="data:image/png;base64,${qrCodeBase64}" alt="QR Code Pix" style="width: 100%; max-width: 300px; border: 2px solid #eee; border-radius: 12px;">`;
            
            // Inserir cÃ³digo copia-e-cola
            pixCopyPaste.value = qrCode;
            
            // Exibir conteÃºdo
            pixLoading.style.display = 'none';
            pixContent.style.display = 'block';
            
            __noopLog('âœ… QR Code Pix exibido com sucesso');
        }
        
        /**
         * Copiar cÃ³digo Pix
         */
        function copiarCodigoPix() {
            const pixCopyPaste = document.getElementById('pixCopyPaste');
            pixCopyPaste.select();
            document.execCommand('copy');
            
            Swal.fire({
                icon: 'success',
                title: 'âœ… CÃ³digo copiado!',
                text: 'Cole no seu app de pagamentos',
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        /**
         * Exibir dados do Boleto
         */
        function exibirBoleto(boletoUrl, digitableLine, dueDate) {
            const boletoContainer = document.getElementById('boletoContainer');
            const boletoLoading = document.getElementById('boletoLoading');
            const boletoContent = document.getElementById('boletoContent');
            const boletoPdfLink = document.getElementById('boletoPdfLink');
            const boletoDigitableLine = document.getElementById('boletoDigitableLine');
            const boletoDueDate = document.getElementById('boletoDueDate');
            
            // Inserir dados do boleto
            boletoPdfLink.href = boletoUrl;
            boletoDigitableLine.value = digitableLine;
            boletoDueDate.textContent = dueDate;
            
            // Exibir conteÃºdo
            boletoLoading.style.display = 'none';
            boletoContent.style.display = 'block';
            
            __noopLog('âœ… Boleto exibido com sucesso');
        }
        
        /**
         * Copiar linha digitÃ¡vel do boleto
         */
        function copiarLinhaDigitavel() {
            const boletoDigitableLine = document.getElementById('boletoDigitableLine');
            boletoDigitableLine.select();
            document.execCommand('copy');
            
            Swal.fire({
                icon: 'success',
                title: 'âœ… Linha digitÃ¡vel copiada!',
                text: 'Cole no app do seu banco para pagar',
                timer: 2000,
                showConfirmButton: false
            });
        }
    </script>
    
</body>
</html>


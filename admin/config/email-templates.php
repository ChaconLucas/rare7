<?php
/**
 * Templates de Email - Rare7 Jerseys
 */
function emailTemplatePedidoConfirmado(array $dados): string {
    $nome         = htmlspecialchars($dados['nome_cliente']    ?? 'Cliente');
    $numero       = htmlspecialchars($dados['numero_pedido']   ?? '0');
    $data         = htmlspecialchars($dados['data_pedido']     ?? date('d/m/Y H:i'));
    $pagamento    = htmlspecialchars($dados['forma_pagamento'] ?? 'Não informado');
    $frete_val    = (float)($dados['frete']    ?? 0);
    $desconto_val = (float)($dados['desconto'] ?? 0);
    $subtotal_val = (float)($dados['subtotal'] ?? 0);
    $total_val    = (float)($dados['total']    ?? 0);
    $subtotal_fmt = 'R$&nbsp;' . number_format($subtotal_val, 2, ',', '.');
    $frete_fmt    = $frete_val > 0 ? 'R$&nbsp;' . number_format($frete_val, 2, ',', '.') : 'Gr&aacute;tis';
    $total_fmt    = 'R$&nbsp;' . number_format($total_val,    2, ',', '.');
    $endereco     = htmlspecialchars($dados['endereco']       ?? '');
    $status       = $dados['status']         ?? 'Pagamento Confirmado';
    $msg_extra    = $dados['mensagem_extra'] ?? '';
    $link         = $dados['link_rastreio']  ?? 'http://localhost/rare7/cliente/pages/pedidos.php';
    $itens        = $dados['itens']          ?? [];
    $ano          = date('Y');

    $cfg = [
        'Pagamento Confirmado' => ['#22c55e','#ffffff','&#10003;','Pagamento Confirmado'],
        'Em Preparação'        => ['#f59e0b','#ffffff','&#9881;', 'Em Preparação'],
        'Enviado'              => ['#3b82f6','#ffffff','&#128230;','Pedido a Caminho!'],
        'Entregue'             => ['#10b981','#ffffff','&#127873;','Pedido Entregue!'],
        'Pedido Recebido'      => ['#C6A75E','#0F1C2E','&#128203;','Pedido Recebido!'],
        'Estornado'            => ['#ef4444','#ffffff','&#8635;', 'Pedido Cancelado'],
    ];
    $match = null;
    foreach ($cfg as $key => $val) {
        if (stripos($status, $key) !== false) { $match = $val; break; }
    }
    $match = $match ?? ['#C6A75E','#0F1C2E','&#128203;', htmlspecialchars($status)];
    [$badge_bg, $badge_fg, $icone, $titulo] = $match;

    $itens_html = '';
    if (!empty($itens)) {
        foreach ($itens as $i => $item) {
            $bg  = ($i % 2 === 0) ? '#ffffff' : '#fafafa';
            $n   = htmlspecialchars($item['nome'] ?? 'Produto');
            $q   = intval($item['quantidade']     ?? 1);
            $p   = (float)($item['preco']          ?? 0);
            $sub = 'R$&nbsp;' . number_format($p * $q, 2, ',', '.');
            $pu  = 'R$&nbsp;' . number_format($p, 2, ',', '.');
            $itens_html .= "<tr style='background:{$bg};'>"
                . "<td style='padding:12px 16px;font-size:14px;color:#1e293b;border-bottom:1px solid #f1f5f9;'>{$n}</td>"
                . "<td style='padding:12px 16px;font-size:14px;color:#64748b;text-align:center;border-bottom:1px solid #f1f5f9;'>{$q}</td>"
                . "<td style='padding:12px 16px;font-size:14px;color:#64748b;text-align:right;border-bottom:1px solid #f1f5f9;'>{$pu}</td>"
                . "<td style='padding:12px 16px;font-size:14px;font-weight:700;color:#C6A75E;text-align:right;border-bottom:1px solid #f1f5f9;'>{$sub}</td>"
                . "</tr>";
        }
    } else {
        $itens_html = "<tr><td colspan='4' style='padding:14px;text-align:center;color:#94a3b8;font-size:13px;'>Detalhes dos itens não disponíveis</td></tr>";
    }

    $desconto_html = '';
    if ($desconto_val > 0) {
        $df = 'R$&nbsp;' . number_format($desconto_val, 2, ',', '.');
        $desconto_html = "<tr style='background:#f8fafc;'><td colspan='3' style='padding:6px 16px;text-align:right;font-size:13px;color:#22c55e;'>Desconto</td><td style='padding:6px 16px;text-align:right;font-size:13px;color:#22c55e;font-weight:600;'>-{$df}</td></tr>";
    }

    $endereco_html = '';
    if (!empty($endereco)) {
        $endereco_html = "<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:28px;'><tr><td style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:18px 20px;'><p style='margin:0 0 6px;font-size:11px;font-weight:700;color:#C6A75E;text-transform:uppercase;letter-spacing:1.5px;'>&#128205; Endereço de Entrega</p><p style='margin:0;font-size:14px;color:#334155;line-height:1.7;'>{$endereco}</p></td></tr></table>";
    }

    $msg_html = '';
    if (!empty($msg_extra)) {
        $msg_html = "<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:28px;'><tr><td style='background:#eff6ff;border-left:4px solid #3b82f6;border-radius:0 10px 10px 0;padding:16px 20px;'><p style='margin:0;font-size:14px;color:#1e3a5f;line-height:1.7;'>" . nl2br(htmlspecialchars($msg_extra)) . "</p></td></tr></table>";
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pedido #{$numero} &mdash; Rare7</title></head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:40px 0;">
<tr><td align="center">

<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(15,28,46,0.13);">

  <!-- FAIXA DOURADA TOP -->
  <tr><td style="background:linear-gradient(90deg,#C6A75E,#f0d98a,#C6A75E);height:4px;font-size:0;">&nbsp;</td></tr>

  <!-- HEADER -->
  <tr>
    <td style="background:#0F1C2E;padding:28px 40px 32px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <p style="margin:0;font-size:30px;font-weight:900;color:#C6A75E;letter-spacing:5px;font-family:Georgia,serif;line-height:1;">RARE7</p>
            <p style="margin:3px 0 0;font-size:10px;color:#5a7080;letter-spacing:7px;text-transform:uppercase;">J &nbsp;E &nbsp;R &nbsp;S &nbsp;E &nbsp;Y &nbsp;S</p>
          </td>
          <td align="right" style="vertical-align:top;">
            <p style="margin:0;font-size:11px;color:#4a6070;">Pedido</p>
            <p style="margin:2px 0 0;font-size:22px;font-weight:900;color:#C6A75E;">#{$numero}</p>
            <p style="margin:4px 0 0;font-size:11px;color:#4a6070;">{$data}</p>
          </td>
        </tr>
      </table>

      <div style="margin:22px 0 20px;border-top:1px solid rgba(198,167,94,0.15);"></div>

      <table cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
        <tr><td style="background:{$badge_bg};border-radius:50px;padding:9px 20px;"><span style="font-size:14px;font-weight:700;color:{$badge_fg};">{$icone}&nbsp;&nbsp;{$titulo}</span></td></tr>
      </table>
      <p style="margin:0;font-size:16px;color:#c8d8ea;line-height:1.6;">Olá, <strong style="color:#ffffff;">{$nome}</strong> &mdash; seu pedido foi atualizado!</p>
    </td>
  </tr>

  <!-- BODY -->
  <tr>
    <td style="padding:36px 40px;">

      {$msg_html}

      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;">
        <tr style="background:#f8fafc;">
          <td width="50%" style="padding:16px 20px;border-right:1px solid #e2e8f0;">
            <p style="margin:0 0 4px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;">Pedido</p>
            <p style="margin:0;font-size:18px;font-weight:900;color:#C6A75E;">#{$numero}</p>
          </td>
          <td width="50%" style="padding:16px 20px;">
            <p style="margin:0 0 4px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;">Pagamento</p>
            <p style="margin:0;font-size:14px;font-weight:600;color:#1e293b;">{$pagamento}</p>
          </td>
        </tr>
      </table>

      <p style="margin:0 0 10px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:2px;">&#128230; Itens do Pedido</p>

      <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:28px;">
        <tr style="background:#0F1C2E;">
          <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:#C6A75E;text-transform:uppercase;letter-spacing:1.5px;">Produto</th>
          <th style="padding:11px 16px;text-align:center;font-size:11px;font-weight:700;color:#C6A75E;text-transform:uppercase;letter-spacing:1.5px;width:50px;">Qtd</th>
          <th style="padding:11px 16px;text-align:right;font-size:11px;font-weight:700;color:#C6A75E;text-transform:uppercase;letter-spacing:1.5px;">Unit.</th>
          <th style="padding:11px 16px;text-align:right;font-size:11px;font-weight:700;color:#C6A75E;text-transform:uppercase;letter-spacing:1.5px;">Total</th>
        </tr>
        {$itens_html}
        <tr style="background:#f8fafc;"><td colspan="3" style="padding:10px 16px;text-align:right;font-size:13px;color:#64748b;border-top:1px solid #e2e8f0;">Subtotal</td><td style="padding:10px 16px;text-align:right;font-size:13px;color:#334155;border-top:1px solid #e2e8f0;">{$subtotal_fmt}</td></tr>
        <tr style="background:#f8fafc;"><td colspan="3" style="padding:6px 16px;text-align:right;font-size:13px;color:#64748b;">Frete</td><td style="padding:6px 16px;text-align:right;font-size:13px;color:#334155;">{$frete_fmt}</td></tr>
        {$desconto_html}
        <tr style="background:#0F1C2E;"><td colspan="3" style="padding:14px 16px;text-align:right;font-size:14px;font-weight:700;color:#ffffff;">TOTAL</td><td style="padding:14px 16px;text-align:right;font-size:19px;font-weight:900;color:#C6A75E;">{$total_fmt}</td></tr>
      </table>

      {$endereco_html}

      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
        <tr><td align="center">
          <a href="{$link}" style="display:inline-block;background:linear-gradient(135deg,#C6A75E 0%,#f0d98a 50%,#C6A75E 100%);color:#0F1C2E;text-decoration:none;padding:15px 44px;border-radius:50px;font-size:15px;font-weight:800;letter-spacing:1px;">
            &#128230;&nbsp; Acompanhar Pedido
          </a>
        </td></tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;">
          <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#0F1C2E;">&#128172;&nbsp; Precisa de ajuda?</p>
          <p style="margin:0;font-size:13px;color:#64748b;line-height:1.7;">Fale com a gente pelo WhatsApp:&nbsp;<strong style="color:#C6A75E;">(21) 98513-6806</strong><br>Ou responda este e-mail diretamente.</p>
        </td></tr>
      </table>

    </td>
  </tr>

  <!-- FOOTER -->
  <tr>
    <td style="background:#0F1C2E;padding:0;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="background:linear-gradient(90deg,#C6A75E,#f0d98a,#C6A75E);height:3px;font-size:0;">&nbsp;</td></tr>
      </table>
      <table width="100%" cellpadding="0" cellspacing="0" style="padding:26px 40px;">
        <tr><td align="center">
          <p style="margin:0 0 2px;font-size:20px;font-weight:900;color:#C6A75E;letter-spacing:5px;font-family:Georgia,serif;">RARE7</p>
          <p style="margin:0 0 16px;font-size:10px;color:#3a5060;letter-spacing:6px;text-transform:uppercase;">J E R S E Y S</p>
          <table cellpadding="0" cellspacing="0" style="margin:0 auto 16px;">
            <tr>
              <td style="width:50px;background:linear-gradient(90deg,transparent,#C6A75E);height:1px;font-size:0;">&nbsp;</td>
              <td style="padding:0 10px;font-size:10px;color:#C6A75E;">&#9670;</td>
              <td style="width:50px;background:linear-gradient(90deg,#C6A75E,transparent);height:1px;font-size:0;">&nbsp;</td>
            </tr>
          </table>
          <p style="margin:0;font-size:11px;color:#3a5060;line-height:2;">Este e-mail foi enviado automaticamente &mdash; por favor n&atilde;o responda diretamente.<br>&copy; {$ano} Rare7 Jerseys &mdash; Todos os direitos reservados.</p>
        </td></tr>
      </table>
    </td>
  </tr>

</table>

</td></tr>
</table>
</body>
</html>
HTML;
}

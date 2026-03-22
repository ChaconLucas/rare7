<?php
require_once __DIR__ . '/../../../PHP/conexao.php';

// Verificar se √© uma requisi√ß√£o POST para exportar Excel
if ($_POST['action'] === 'export_excel') {
    
    // Receber dados do POST
    $data_inicio = $_POST['data_inicio'] ?? date('Y-m-01');
    $data_fim = $_POST['data_fim'] ?? date('Y-m-d');
    $kpis = json_decode($_POST['kpis'], true);
    $lista_pedidos = json_decode($_POST['lista_pedidos'], true);
    $dados_evolucao = json_decode($_POST['dados_evolucao'], true);
    $dados_categorias = json_decode($_POST['dados_categorias'], true);
    
    // Gerar arquivo Excel XML
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="DZ_Relatorio_Premium_' . $data_inicio . '_' . $data_fim . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // In√≠cio do XML
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:html="http://www.w3.org/TR/REC-html40">
        
        <!-- Estilos CSS para Excel -->
        <Styles>
            <!-- Estilo do cabe√ßalho principal Rare7 -->
            <Style ss:ID="HeaderDZ">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="2"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="2"/>
                </Borders>
                <Font ss:FontName="Arial" ss:Color="#FFFFFF" ss:Bold="1" ss:Size="14"/>
                <Interior ss:Color="#C6A75E" ss:Pattern="Solid"/>
            </Style>
            
            <!-- Estilo dos cabe√ßalhos das se√ß√µes -->
            <Style ss:ID="SectionHeader">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Color="#333333" ss:Bold="1" ss:Size="12"/>
                <Interior ss:Color="#F8F9FA" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Estilo dos cabe√ßalhos das tabelas -->
            <Style ss:ID="TableHeader">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Color="#FFFFFF" ss:Bold="1" ss:Size="10"/>
                <Interior ss:Color="#6C757D" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Estilo KPI destacado -->
            <Style ss:ID="KPI">
                <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Color="#0F5132" ss:Bold="1" ss:Size="10"/>
                <NumberFormat ss:Format="[$R$-416] #,##0.00"/>
                <Borders>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Status Pago - Verde -->
            <Style ss:ID="StatusPago">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Color="#FFFFFF" ss:Bold="1" ss:Size="9"/>
                <Interior ss:Color="#198754" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Status Pendente - Amarelo -->
            <Style ss:ID="StatusPendente">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Color="#664D03" ss:Bold="1" ss:Size="9"/>
                <Interior ss:Color="#FFC107" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Status Em Prepara√ß√£o - Azul Claro -->
            <Style ss:ID="StatusPreparacao">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Color="#495057" ss:Bold="1" ss:Size="9"/>
                <Interior ss:Color="#F8F9FA" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Status Estornado - Vermelho -->
            <Style ss:ID="StatusEstornado">
                <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Color="#FFFFFF" ss:Bold="1" ss:Size="9"/>
                <Interior ss:Color="#DC3545" ss:Pattern="Solid"/>
                <Borders>
                    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Estilo da c√©lula padr√£o -->
            <Style ss:ID="Default">
                <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="9"/>
                <Borders>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
            
            <!-- Valores monet√°rios -->
            <Style ss:ID="Currency">
                <Alignment ss:Horizontal="Right" ss:Vertical="Center"/>
                <Font ss:FontName="Arial" ss:Size="9"/>
                <NumberFormat ss:Format="[$R$-416] #,##0.00"/>
                <Borders>
                    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
                    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
                </Borders>
            </Style>
        </Styles>
        
        <Worksheet ss:Name="Relat√≥rio D&amp;Z">
            <Table>
                <!-- Definir larguras das colunas -->
                <Column ss:AutoFitWidth="1" ss:Width="120"/>
                <Column ss:AutoFitWidth="1" ss:Width="80"/>
                <Column ss:AutoFitWidth="1" ss:Width="150"/>
                <Column ss:AutoFitWidth="1" ss:Width="80"/>
                <Column ss:AutoFitWidth="1" ss:Width="100"/>
                <Column ss:AutoFitWidth="1" ss:Width="100"/>
                <Column ss:AutoFitWidth="1" ss:Width="100"/>
                <Column ss:AutoFitWidth="1" ss:Width="100"/>
                <Column ss:AutoFitWidth="1" ss:Width="120"/>
                
                <!-- CABE√?ALHO PRINCIPAL Rare7 -->
                <Row ss:Height="35">
                    <Cell ss:MergeAcross="8" ss:StyleID="HeaderDZ">
                        <Data ss:Type="String">Yè¢ D&amp;Z - RELAT√"RIO DE VENDAS</Data>
                    </Cell>
                </Row>
                
                <Row ss:Height="20">
                    <Cell ss:MergeAcross="8" ss:StyleID="Default">
                        <Data ss:Type="String">Y". Per√≠odo: ' . $data_inicio . ' at√© ' . $data_fim . ' | Y"S Gerado em: ' . date('d/m/Y H:i:s') . '</Data>
                    </Cell>
                </Row>
                
                <!-- ESPA√?AMENTO -->
                <Row ss:Height="15">
                    <Cell><Data ss:Type="String"></Data></Cell>
                </Row>
                
                <!-- BLOCO 1: RESUMO EXECUTIVO (KPIs) -->
                <Row ss:Height="25">
                    <Cell ss:MergeAcross="8" ss:StyleID="SectionHeader">
                        <Data ss:Type="String">YZØ RESUMO EXECUTIVO - INDICADORES PRINCIPAIS</Data>
                    </Cell>
                </Row>
                
                <!-- Cabe√ßalhos KPIs -->
                <Row ss:Height="20">
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y'∞ Indicador</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Yè? Valor</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y"S Status</Data></Cell>
                    <Cell><Data ss:Type="String"></Data></Cell>
                    <Cell><Data ss:Type="String"></Data></Cell>
                    <Cell><Data ss:Type="String"></Data></Cell>
                    <Cell><Data ss:Type="String"></Data></Cell>
                    <Cell><Data ss:Type="String"></Data></Cell>
                    <Cell><Data ss:Type="String"></Data></Cell>
                </Row>';
    
    // KPIs din√¢micos
    $kpi_items = [
        ['label' => 'Y'∞ Faturamento Total', 'value' => 'R$ ' . number_format($kpis['faturamento'], 2, ',', '.'), 'status' => '‚o. Sucesso'],
        ['label' => 'Y>' Total de Vendas', 'value' => $kpis['total_vendas'] . ' pedidos', 'status' => 'Y"^ Ativo'],
        ['label' => 'Y'≥ Ticket M√©dio', 'value' => 'R$ ' . number_format($kpis['ticket_medio'], 2, ',', '.'), 'status' => 'Y"S Normal'],
        ['label' => 'Y"¶ Itens Vendidos', 'value' => $kpis['itens_vendidos'] . ' unidades', 'status' => 'Y"• Forte']
    ];
    
    foreach ($kpi_items as $kpi) {
        echo '<Row ss:Height="18">
                <Cell ss:StyleID="Default"><Data ss:Type="String">' . $kpi['label'] . '</Data></Cell>
                <Cell ss:StyleID="KPI"><Data ss:Type="String">' . $kpi['value'] . '</Data></Cell>
                <Cell ss:StyleID="Default"><Data ss:Type="String">' . $kpi['status'] . '</Data></Cell>
                <Cell><Data ss:Type="String"></Data></Cell>
                <Cell><Data ss:Type="String"></Data></Cell>
                <Cell><Data ss:Type="String"></Data></Cell>
                <Cell><Data ss:Type="String"></Data></Cell>
                <Cell><Data ss:Type="String"></Data></Cell>
                <Cell><Data ss:Type="String"></Data></Cell>
            </Row>';
    }
    
    // Espa√ßamento e cabe√ßalho dos pedidos
    echo '
                <!-- ESPA√?AMENTO -->
                <Row ss:Height="15">
                    <Cell><Data ss:Type="String"></Data></Cell>
                </Row>
                
                <!-- BLOCO 2: RELAT√"RIO DETALHADO DE PEDIDOS -->
                <Row ss:Height="25">
                    <Cell ss:MergeAcross="8" ss:StyleID="SectionHeader">
                        <Data ss:Type="String">Y"< RELAT√"RIO DETALHADO DOS PEDIDOS</Data>
                    </Cell>
                </Row>
                
                <!-- Cabe√ßalhos da tabela -->
                <Row ss:Height="20">
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y". Data</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y?" Pedido</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y'§ Cliente</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y"¶ Itens</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y'∞ Subtotal</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Yss Desc.Frete</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">YZ´ Desc.Cupom</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y'≥ Valor Final</Data></Cell>
                    <Cell ss:StyleID="TableHeader"><Data ss:Type="String">Y"S Status</Data></Cell>
                </Row>';
    
    // Dados dos pedidos
    if (!empty($lista_pedidos)) {
        foreach ($lista_pedidos as $pedido) {
            // Formatar valores
            $subtotal = $pedido['subtotal'] ?? 0;
            $descFrete = $pedido['desconto_frete'] ?? 0;
            $descCupom = $pedido['desconto_cupom'] ?? 0;
            $valorFinal = $pedido['valor_total'] ?? 0;
            
            // Determinar estilo do status
            $statusStyle = 'Default';
            $status = strtolower(str_replace(' ', '', $pedido['status'] ?? ''));
            
            switch ($status) {
                case 'pago':
                case 'entregue':
                case 'pedidoconfirmado':
                    $statusStyle = 'StatusPago';
                    break;
                case 'pagamentopendente':
                case 'pendente':
                    $statusStyle = 'StatusPendente';
                    break;
                case 'empreparacao':
                case 'emprepara√ß√£o':
                case 'pedidorecebido':
                    $statusStyle = 'StatusPreparacao';
                    break;
                case 'estornado':
                case 'cancelado':
                    $statusStyle = 'StatusEstornado';
                    break;
            }
            
            echo '<Row ss:Height="16">
                    <Cell ss:StyleID="Default"><Data ss:Type="String">' . date('d/m/Y', strtotime($pedido['data_pedido'])) . '</Data></Cell>
                    <Cell ss:StyleID="Default"><Data ss:Type="String">#' . $pedido['id'] . '</Data></Cell>
                    <Cell ss:StyleID="Default"><Data ss:Type="String">' . htmlspecialchars($pedido['cliente_nome'] ?? 'N/A') . '</Data></Cell>
                    <Cell ss:StyleID="Default"><Data ss:Type="Number">' . ($pedido['total_itens'] ?? 0) . '</Data></Cell>
                    <Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $subtotal . '</Data></Cell>
                    <Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $descFrete . '</Data></Cell>
                    <Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $descCupom . '</Data></Cell>
                    <Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $valorFinal . '</Data></Cell>
                    <Cell ss:StyleID="' . $statusStyle . '"><Data ss:Type="String">' . htmlspecialchars($pedido['status']) . '</Data></Cell>
                </Row>';
        }
    }
    
    // Rodap√© e fechamento
    echo '
                <!-- ESPA√?AMENTO -->
                <Row ss:Height="15">
                    <Cell><Data ss:Type="String"></Data></Cell>
                </Row>
                
                <!-- RODAP√? -->
                <Row ss:Height="20">
                    <Cell ss:MergeAcross="8" ss:StyleID="Default">
                        <Data ss:Type="String">Yè¢ Relat√≥rio gerado automaticamente pelo Sistema D&amp;Z Dashboard ¬© ' . date('Y') . '</Data>
                    </Cell>
                </Row>
                
            </Table>
        </Worksheet>
    </Workbook>';
    
    exit;
}
?>
<?php
session_start();
require_once '../config.php';

$erro = '';
$sucesso = '';

// Processar formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Receber dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $cpfCnpj = trim($_POST['cpf_cnpj'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $dataNascimento = $_POST['data_nascimento'] ?? '';
    $cep = trim($_POST['cep'] ?? '');
    $rua = trim($_POST['rua'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $uf = trim($_POST['uf'] ?? '');
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($cpfCnpj) || 
        empty($telefone) || empty($cep) || empty($rua) || empty($numero) || 
        empty($bairro) || empty($cidade) || empty($uf)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não conferem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        // Normalizar CPF/CNPJ e CEP
        $cpfCnpjNormalizado = normalizarCpfCnpj($cpfCnpj);
        $cepNormalizado = normalizarCep($cep);
        
        // Verificar duplicidades
        if (emailExiste($pdo, $email)) {
            $erro = 'Este e-mail já está cadastrado.';
        } elseif (cpfCnpjExiste($pdo, $cpfCnpjNormalizado)) {
            $erro = 'Este CPF/CNPJ já está cadastrado.';
        } else {
            // Criar endereço completo
            $enderecoCompleto = trim("$rua, $numero" . ($complemento ? " - $complemento" : "") . ", $bairro, $cidade - $uf");
            
            // Hash da senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            
            try {
                // Inserir cliente
                $sql = "INSERT INTO clientes (
                    nome, email, senha, cpf_cnpj, telefone, whatsapp, 
                    data_nascimento, cep, endereco, rua, numero, complemento, 
                    bairro, cidade, uf, status, data_cadastro, data_ultima_atualizacao
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ativo', NOW(), NOW()
                )";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nome, $email, $senhaHash, $cpfCnpjNormalizado, $telefone, $whatsapp,
                    $dataNascimento ?: null, $cepNormalizado, $enderecoCompleto, $rua, $numero, 
                    $complemento, $bairro, $cidade, $uf
                ]);
                
                // Buscar o ID do cliente recém-criado
                $clienteId = $pdo->lastInsertId();
                
                // Criar sessão - LOGIN AUTOMÁTICO
                $_SESSION['cliente'] = [
                    'id' => $clienteId,
                    'nome' => $nome,
                    'email' => $email
                ];
                
                // Redirecionar para home
header('Location: ../index.php');
                exit;
                
            } catch (PDOException $e) {
                error_log("Erro ao cadastrar cliente: " . $e->getMessage());
                $erro = 'Erro ao realizar cadastro. Tente novamente.';
            }
        }
    }
}

$pageTitle = 'Cadastro - D&Z';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        :root {
            --color-magenta: #E6007E;
            --color-magenta-dark: #C4006A;
            --color-rose-light: #FDF2F8;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #FDF2F8 0%, #FCE7F3 50%, #FBCFE8 100%);
            color: #333333;
            line-height: 1.6;
            padding-top: 100px;
            padding-bottom: 60px;
            min-height: 100vh;
        }
        
        .auth-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .auth-header h1 {
            color: var(--color-magenta);
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .auth-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .form-group label .required {
            color: #ef4444;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-magenta);
            box-shadow: 0 0 0 4px rgba(230, 0, 126, 0.1);
        }
        
        .form-section-title {
            grid-column: 1 / -1;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--color-magenta);
            margin-top: 20px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #fce7f3;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .auth-footer a {
            color: var(--color-magenta);
            font-weight: 600;
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .input-group {
            display: flex;
            gap: 10px;
        }
        
        .input-group input {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                padding: 24px;
                margin: 0 16px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            body {
                padding-top: 80px;
            }
        }

        /* Navbar simples para páginas de auth */
        .header-loja {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(230, 0, 126, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            z-index: 1000;
            padding: 16px 0;
        }
        
        .container-header {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .logo-dz-oficial {
            height: 40px;
            width: auto;
        }
        
        .logo-text {
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--color-magenta) 0%, var(--color-magenta-dark) 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .btn-back {
            padding: 10px 20px;
            border-radius: 8px;
            background: transparent;
            color: var(--color-magenta);
            border: 2px solid var(--color-magenta);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: var(--color-magenta);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar Simples -->
    <header class="header-loja">
        <div class="container-header">
            <a href="../index.php" class="logo-container">
                <img src="../assets/images/Logodz.png" alt="D&Z" class="logo-dz-oficial" onerror="this.style.display='none'">
                <span class="logo-text">D&Z</span>
            </a>
            <a href="../index.php" class="btn-back">← Voltar à loja</a>
        </div>
    </header>

    <div class="auth-container">
        <div class="auth-header">
            <h1>Criar Conta</h1>
            <p>Preencha seus dados para começar a comprar</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-grid">
                <!-- Dados Pessoais -->
                <div class="form-section-title">Dados Pessoais</div>
                
                <div class="form-group full-width">
                    <label>Nome Completo <span class="required">*</span></label>
                    <input type="text" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>E-mail <span class="required">*</span></label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>CPF/CNPJ <span class="required">*</span></label>
                    <input type="text" name="cpf_cnpj" required maxlength="18" id="cpfCnpj" value="<?php echo htmlspecialchars($_POST['cpf_cnpj'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Telefone <span class="required">*</span></label>
                    <input type="text" name="telefone" required maxlength="15" id="telefone" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>WhatsApp</label>
                    <input type="text" name="whatsapp" maxlength="15" id="whatsapp" value="<?php echo htmlspecialchars($_POST['whatsapp'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Data de Nascimento</label>
                    <input type="date" name="data_nascimento" value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
                </div>
                
                <!-- Senha -->
                <div class="form-section-title">Senha</div>
                
                <div class="form-group">
                    <label>Senha <span class="required">*</span></label>
                    <input type="password" name="senha" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirmar Senha <span class="required">*</span></label>
                    <input type="password" name="confirmar_senha" required minlength="6">
                </div>
                
                <!-- Endereço -->
                <div class="form-section-title">Endereço</div>
                
                <div class="form-group">
                    <label>CEP <span class="required">*</span></label>
                    <input type="text" name="cep" required maxlength="9" id="cep" value="<?php echo htmlspecialchars($_POST['cep'] ?? ''); ?>">
                </div>
                
                <div class="form-group full-width">
                    <label>Rua <span class="required">*</span></label>
                    <input type="text" name="rua" required id="rua" value="<?php echo htmlspecialchars($_POST['rua'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Número <span class="required">*</span></label>
                    <input type="text" name="numero" required id="numero" value="<?php echo htmlspecialchars($_POST['numero'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Complemento</label>
                    <input type="text" name="complemento" id="complemento" value="<?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Bairro <span class="required">*</span></label>
                    <input type="text" name="bairro" required id="bairro" value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Cidade <span class="required">*</span></label>
                    <input type="text" name="cidade" required id="cidade" value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>UF <span class="required">*</span></label>
                    <select name="uf" required id="uf">
                        <option value="">Selecione</option>
                        <option value="AC">AC</option>
                        <option value="AL">AL</option>
                        <option value="AP">AP</option>
                        <option value="AM">AM</option>
                        <option value="BA">BA</option>
                        <option value="CE">CE</option>
                        <option value="DF">DF</option>
                        <option value="ES">ES</option>
                        <option value="GO">GO</option>
                        <option value="MA">MA</option>
                        <option value="MT">MT</option>
                        <option value="MS">MS</option>
                        <option value="MG">MG</option>
                        <option value="PA">PA</option>
                        <option value="PB">PB</option>
                        <option value="PR">PR</option>
                        <option value="PE">PE</option>
                        <option value="PI">PI</option>
                        <option value="RJ">RJ</option>
                        <option value="RN">RN</option>
                        <option value="RS">RS</option>
                        <option value="RO">RO</option>
                        <option value="RR">RR</option>
                        <option value="SC">SC</option>
                        <option value="SP">SP</option>
                        <option value="SE">SE</option>
                        <option value="TO">TO</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Criar Conta</button>
        </form>
        
        <div class="auth-footer">
            <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
        </div>
    </div>

    <script>
        // ===== MÁSCARAS DE INPUT =====
        function maskCPFCNPJ(value) {
            value = value.replace(/\D/g, '');
            if (value.length <= 11) {
                // CPF: 000.000.000-00
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                // CNPJ: 00.000.000/0000-00
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            return value;
        }
        
        function maskPhone(value) {
            value = value.replace(/\D/g, '');
            if (value.length <= 10) {
                // (00) 0000-0000
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                // (00) 00000-0000
                value = value.replace(/^(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            return value;
        }
        
        function maskCEP(value) {
            value = value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            return value;
        }
        
        // Aplicar máscaras
        document.getElementById('cpfCnpj').addEventListener('input', function(e) {
            e.target.value = maskCPFCNPJ(e.target.value);
        });
        
        document.getElementById('telefone').addEventListener('input', function(e) {
            e.target.value = maskPhone(e.target.value);
        });
        
        document.getElementById('whatsapp').addEventListener('input', function(e) {
            e.target.value = maskPhone(e.target.value);
        });
        
        document.getElementById('cep').addEventListener('input', function(e) {
            e.target.value = maskCEP(e.target.value);
        });
        
        // ===== BUSCA CEP (VIACEP) =====
        document.getElementById('cep').addEventListener('blur', async function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();
                    
                    if (data.erro) {
                        alert('CEP não encontrado!');
                        return;
                    }
                    
                    // Preencher campos
                    document.getElementById('rua').value = data.logradouro || '';
                    document.getElementById('bairro').value = data.bairro || '';
                    document.getElementById('cidade').value = data.localidade || '';
                    document.getElementById('uf').value = data.uf || '';
                    
                    // Focar no campo número
                    document.getElementById('numero').focus();
                    
                } catch (error) {
                    console.error('Erro ao buscar CEP:', error);
                    alert('Erro ao buscar CEP. Verifique sua conexão.');
                }
            }
        });
    </script>

<?php require_once '../includes/chat.php'; ?>
</body>
</html>

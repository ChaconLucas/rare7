<?php
// Configurar frete grátis para o mini-cart
if (!function_exists('getFreteGratisThreshold')) {
    include '../config.php';
}
$freteGratisValor = isset($pdo) ? getFreteGratisThreshold($pdo) : 0;
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (
        empty($nome) || empty($email) || empty($senha) || empty($cpfCnpj) ||
        empty($telefone) || empty($cep) || empty($rua) || empty($numero) ||
        empty($bairro) || empty($cidade) || empty($uf)
    ) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não conferem.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $senha)) {
        $erro = 'A senha deve conter pelo menos uma letra maiúscula.';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
        $erro = 'A senha deve conter pelo menos um caractere especial.';
    } elseif (!preg_match('/[0-9]/', $senha)) {
        $erro = 'A senha deve conter pelo menos um número.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        $cpfCnpjNormalizado = normalizarCpfCnpj($cpfCnpj);
        $cepNormalizado = normalizarCep($cep);

        if (emailExiste($pdo, $email)) {
            $erro = 'Este e-mail já está cadastrado.';
        } elseif (cpfCnpjExiste($pdo, $cpfCnpjNormalizado)) {
            $erro = 'Este CPF/CNPJ já está cadastrado.';
        } else {
            $enderecoCompleto = trim("$rua, $numero" . ($complemento ? " - $complemento" : '') . ", $bairro, $cidade - $uf");
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            try {
                $sql = "INSERT INTO clientes (
                    nome, email, senha, cpf_cnpj, telefone, whatsapp,
                    data_nascimento, cep, endereco, rua, numero, complemento,
                    bairro, cidade, uf, status, data_cadastro, data_ultima_atualizacao
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ativo', NOW(), NOW()
                )";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $nome,
                    $email,
                    $senhaHash,
                    $cpfCnpjNormalizado,
                    $telefone,
                    $whatsapp,
                    $dataNascimento ?: null,
                    $cepNormalizado,
                    $enderecoCompleto,
                    $rua,
                    $numero,
                    $complemento,
                    $bairro,
                    $cidade,
                    $uf
                ]);

                $clienteId = $pdo->lastInsertId();
                $_SESSION['cliente'] = [
                    'id' => $clienteId,
                    'nome' => $nome,
                    'email' => $email
                ];

                header('Location: ../index.php');
                exit;
            } catch (PDOException $e) {
                error_log('Erro ao cadastrar cliente: ' . $e->getMessage());
                $erro = 'Erro ao realizar cadastro. Tente novamente.';
            }
        }
    }
}

$pageTitle = 'Cadastro - RARE7';
$usuarioLogado = isset($_SESSION['cliente']);
$nomeUsuario = $_SESSION['cliente']['nome'] ?? '';
$basePath = '../';
$currentPage = 'register';

include '../includes/header.php';
?>
<body class="register-page">
<?php include '../includes/navbar.php'; ?>

<main class="register-shell login-fade">
    <section class="register-branding" aria-label="Apresentação da área do cliente">
        <p class="register-kicker">AREA DO CLIENTE</p>
        <h1 class="register-title">Crie sua conta e viva a experiencia completa da RARE.</h1>
        <p class="register-description">Cadastre-se para acompanhar pedidos, salvar seus dados, acelerar compras e acessar uma experiencia premium do inicio ao fim.</p>

        <div class="register-info-grid">
            <article class="register-info-card">
                <h3>Pedidos</h3>
                <p>Acompanhe suas compras com praticidade</p>
            </article>
            <article class="register-info-card">
                <h3>Checkout rápido</h3>
                <p>Salve seus dados e compre mais rapido</p>
            </article>
            <article class="register-info-card">
                <h3>Experiencia premium</h3>
                <p>A mesma identidade da loja principal</p>
            </article>
        </div>
    </section>

    <section class="register-form-zone" aria-label="Formulario de cadastro">
        <div class="register-card">
            <p class="register-card-kicker">CRIE SUA CONTA</p>
            <h2 class="register-card-title">Cadastro</h2>

            <?php if ($erro): ?>
                <div class="register-alert" role="alert"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="register-alert register-alert-success" role="status"><?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="register-form" novalidate>
                <div class="register-grid">
                    <div class="register-field register-full">
                        <label for="nome">Nome completo <span class="req">*</span></label>
                        <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="email">E-mail <span class="req">*</span></label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="cpf_cnpj">CPF/CNPJ <span class="req">*</span></label>
                        <input type="text" id="cpf_cnpj" name="cpf_cnpj" required maxlength="18" value="<?php echo htmlspecialchars($_POST['cpf_cnpj'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="telefone">Telefone <span class="req">*</span></label>
                        <input type="text" id="telefone" name="telefone" required maxlength="15" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="whatsapp">WhatsApp</label>
                        <input type="text" id="whatsapp" name="whatsapp" maxlength="15" value="<?php echo htmlspecialchars($_POST['whatsapp'] ?? ''); ?>">
                    </div>

                    <div class="register-field register-full">
                        <label for="data_nascimento">Data de nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($_POST['data_nascimento'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="senha">Senha <span class="req">*</span></label>
                        <input type="password" id="senha" name="senha" required minlength="8">
                        <ul class="senha-hints">
                            <li id="hint-len">Mínimo 8 caracteres</li>
                            <li id="hint-upper">Pelo menos 1 letra maiúscula</li>
                            <li id="hint-special">Pelo menos 1 caractere especial</li>
                            <li id="hint-number">Pelo menos 1 número</li>
                        </ul>
                    </div>

                    <div class="register-field">
                        <label for="confirmar_senha">Confirmar senha <span class="req">*</span></label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8">
                    </div>

                    <div class="register-field">
                        <label for="cep">CEP <span class="req">*</span></label>
                        <input type="text" id="cep" name="cep" required maxlength="9" value="<?php echo htmlspecialchars($_POST['cep'] ?? ''); ?>">
                    </div>

                    <div class="register-field register-full">
                        <label for="rua">Rua <span class="req">*</span></label>
                        <input type="text" id="rua" name="rua" required value="<?php echo htmlspecialchars($_POST['rua'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="numero">Numero <span class="req">*</span></label>
                        <input type="text" id="numero" name="numero" required value="<?php echo htmlspecialchars($_POST['numero'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="complemento">Complemento</label>
                        <input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="bairro">Bairro <span class="req">*</span></label>
                        <input type="text" id="bairro" name="bairro" required value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>">
                    </div>

                    <div class="register-field">
                        <label for="cidade">Cidade <span class="req">*</span></label>
                        <input type="text" id="cidade" name="cidade" required value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>">
                    </div>

                    <div class="register-field register-full">
                        <label for="uf">UF <span class="req">*</span></label>
                        <select id="uf" name="uf" required>
                            <option value="">Selecione</option>
                            <?php
                            $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                            $ufSelecionada = $_POST['uf'] ?? '';
                            foreach ($ufs as $ufItem):
                            ?>
                                <option value="<?php echo $ufItem; ?>" <?php echo $ufSelecionada === $ufItem ? 'selected' : ''; ?>><?php echo $ufItem; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="register-terms">
                    <input type="checkbox" id="termos" name="termos">
                    <span>Li e concordo com os termos e politicas.</span>
                </label>

                <button type="submit" class="register-submit">Criar conta</button>
            </form>

            <p class="register-login">Ja tem conta? <a href="login.php">Entrar</a></p>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>

<script>
(function () {
    function maskCPFCNPJ(value) {
        value = value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
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
            value = value.replace(/^(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        } else {
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

    const cpf = document.getElementById('cpf_cnpj');
    const telefone = document.getElementById('telefone');
    const whatsapp = document.getElementById('whatsapp');
    const cep = document.getElementById('cep');

    if (cpf) {
        cpf.addEventListener('input', function (e) {
            e.target.value = maskCPFCNPJ(e.target.value);
        });
    }

    if (telefone) {
        telefone.addEventListener('input', function (e) {
            e.target.value = maskPhone(e.target.value);
        });
    }

    if (whatsapp) {
        whatsapp.addEventListener('input', function (e) {
            e.target.value = maskPhone(e.target.value);
        });
    }

    if (cep) {
        const senha = document.getElementById('senha');
        if (senha) {
            senha.addEventListener('input', function () {
                const v = this.value;
                document.getElementById('hint-len').classList.toggle('ok', v.length >= 8);
                document.getElementById('hint-upper').classList.toggle('ok', /[A-Z]/.test(v));
                document.getElementById('hint-special').classList.toggle('ok', /[^a-zA-Z0-9]/.test(v));
                document.getElementById('hint-number').classList.toggle('ok', /[0-9]/.test(v));
            });
        }

        cep.addEventListener('input', function (e) {
            e.target.value = maskCEP(e.target.value);
        });

        cep.addEventListener('blur', async function () {
            const cepLimpo = this.value.replace(/\D/g, '');
            if (cepLimpo.length !== 8) return;

            try {
                const response = await fetch('https://viacep.com.br/ws/' + cepLimpo + '/json/');
                const data = await response.json();
                if (data.erro) return;

                const rua = document.getElementById('rua');
                const bairro = document.getElementById('bairro');
                const cidade = document.getElementById('cidade');
                const uf = document.getElementById('uf');
                const numero = document.getElementById('numero');

                if (rua) rua.value = data.logradouro || '';
                if (bairro) bairro.value = data.bairro || '';
                if (cidade) cidade.value = data.localidade || '';
                if (uf) uf.value = data.uf || '';
                if (numero) numero.focus();
            } catch (error) {
                console.error('Erro ao buscar CEP:', error);
            }
        });
    }
})();
</script>

</body>
</html>

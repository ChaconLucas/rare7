<?php
header('Content-Type: text/html; charset=UTF-8');
// Configurar frete grátis para o mini-cart
if (!function_exists('getFreteGratisThreshold')) {
    include '../config.php';
}
$freteGratisValor = isset($pdo) ? getFreteGratisThreshold($pdo) : 0;
require_once '../config.php';
require_once '../conexao.php';
require_once '../cms_data_provider.php';

if (isset($_SESSION['cliente'])) {
    header('Location: ../index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, status FROM clientes WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $cliente = $stmt->fetch();

            if (!$cliente) {
                $erro = 'E-mail ou senha incorretos.';
            } elseif ($cliente['status'] !== 'Ativo') {
                $erro = 'Sua conta está inativa. Entre em contato com o suporte.';
            } elseif (!password_verify($senha, $cliente['senha'])) {
                $erro = 'E-mail ou senha incorretos.';
            } else {
                $_SESSION['cliente'] = [
                    'id' => $cliente['id'],
                    'nome' => $cliente['nome'],
                    'email' => $cliente['email']
                ];

                header('Location: ../index.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Erro no login: ' . $e->getMessage());
            $erro = 'Erro ao processar login. Tente novamente.';
        }
    }
}

$cms = new CMSProvider($conn);
$footerData = $cms->getFooterData();
$footerLinks = $cms->getFooterLinks();

$pageTitle = 'Login - RARE7';
$usuarioLogado = isset($_SESSION['cliente']);
$nomeUsuario = $_SESSION['cliente']['nome'] ?? '';
$basePath = '../';
$currentPage = 'login';

include '../includes/header.php';
?>
<body class="login-page">
<?php include '../includes/navbar.php'; ?>

<main class="login-shell login-fade">
    <section class="login-branding" aria-label="Apresentacao da area do cliente">
        <p class="login-kicker">AREA DO CLIENTE</p>
        <h1 class="login-title">Entre na sua conta e acompanhe tudo com a estetica da RARE.</h1>
        <p class="login-description">Acesse seus pedidos, atualize seus dados, acompanhe rastreios e mantenha sua experiencia premium do inicio ao fim.</p>

        <div class="login-info-grid">
            <article class="login-info-card">
                <h3>Pedidos</h3>
                <p>Acompanhe status em tempo real</p>
            </article>
            <article class="login-info-card">
                <h3>Conta</h3>
                <p>Edite seus dados com facilidade</p>
            </article>
            <article class="login-info-card">
                <h3>Premium</h3>
                <p>Mesma identidade da loja principal</p>
            </article>
        </div>
    </section>

    <section class="login-form-zone" aria-label="Formulario de login">
        <div class="login-card">
            <p class="login-card-kicker">BEM-VINDO DE VOLTA</p>
            <h2 class="login-card-title">Entrar</h2>

            <?php if ($erro): ?>
                <div class="login-alert" role="alert"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form" novalidate>
                <div class="login-field">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seu@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="login-field">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="login-meta-row">
                    <label class="login-remember">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Lembrar de mim</span>
                    </label>
                    <a href="#" class="login-forgot">Esqueci minha senha</a>
                </div>

                <button type="submit" class="login-submit">Entrar na conta</button>
            </form>

            <p class="login-register">Ainda nao tem conta? <a href="register.php">Criar conta</a></p>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>

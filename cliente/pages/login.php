<?php
session_start();
require_once '../config.php';

// Se já está logado, redireciona para home
if (isset($_SESSION['cliente'])) {
    header('Location: ../index.php');
    exit;
}

$erro = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        try {
            // Buscar cliente pelo email
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
                // Login bem-sucedido - criar sessão
                $_SESSION['cliente'] = [
                    'id' => $cliente['id'],
                    'nome' => $cliente['nome'],
                    'email' => $cliente['email']
                ];
                
                // Redirecionar para home
                header('Location: ../index.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $erro = 'Erro ao processar login. Tente novamente.';
        }
    }
}

$pageTitle = 'Login - D&Z';
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auth-container {
            max-width: 480px;
            width: 100%;
            margin: 0 auto;
            padding: 48px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .auth-header .logo {
            font-size: 3rem;
            margin-bottom: 16px;
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
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--color-magenta);
            box-shadow: 0 0 0 4px rgba(230, 0, 126, 0.1);
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -16px;
            margin-bottom: 24px;
        }
        
        .forgot-password a {
            color: var(--color-magenta);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
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
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 0, 126, 0.3);
        }
        
        .auth-divider {
            text-align: center;
            margin: 32px 0;
            position: relative;
        }
        
        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }
        
        .auth-divider span {
            background: white;
            padding: 0 16px;
            position: relative;
            color: #666;
            font-size: 0.9rem;
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
        
        @media (max-width: 768px) {
            .auth-container {
                padding: 32px 24px;
                margin: 0 16px;
            }
        }

        /* Navbar simples */
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
            <div class="logo">💅</div>
            <h1>Entrar</h1>
            <p>Bem-vinda de volta à D&Z!</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="seu@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="••••••••">
            </div>
            
            <div class="forgot-password">
                <a href="recuperar-senha.php">Esqueceu sua senha?</a>
            </div>
            
            <button type="submit" class="btn-submit">Entrar</button>
        </form>
        
        <div class="auth-divider">
            <span>ou</span>
        </div>
        
        <div class="auth-footer">
            <p>Não tem uma conta? <a href="register.php">Cadastre-se grátis</a></p>
        </div>
    </div>

<?php require_once '../includes/chat.php'; ?>
</body>
</html>

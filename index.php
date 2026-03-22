<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>D&Z - Sistema</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ff00d4 0%, #111e88 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .logo {
            font-size: 3rem;
            font-weight: bold;
            background: linear-gradient(135deg, #ff00d4, #111e88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }
        
        .subtitle {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 3rem;
        }
        
        .options {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .option {
            background: linear-gradient(135deg, #ff00d4, #e91e63);
            color: white;
            text-decoration: none;
            padding: 2rem 1.5rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(255, 0, 212, 0.3);
            min-width: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .option:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(255, 0, 212, 0.4);
        }
        
        .option.admin {
            background: linear-gradient(135deg, #111e88, #2c3e50);
            box-shadow: 0 8px 25px rgba(17, 30, 136, 0.3);
        }
        
        .option.admin:hover {
            box-shadow: 0 15px 35px rgba(17, 30, 136, 0.4);
        }
        
        .option-icon {
            font-size: 3rem;
        }
        
        .option-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .option-desc {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 2rem;
            }
            
            .options {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .option {
                min-width: auto;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">D&Z</div>
        <div class="subtitle">Escolha sua área de acesso</div>
        
        <div class="options">
            <a href="admin/" class="option admin">
                <div class="option-icon">🔧</div>
                <h3 class="option-title">Painel Admin</h3>
                <p class="option-desc">Gerenciar produtos, pedidos e relatórios</p>
            </a>
            
            <a href="cliente/" class="option">
                <div class="option-icon">🛍️</div>
                <h3 class="option-title">Loja Virtual</h3>
                <p class="option-desc">Navegar produtos e fazer pedidos</p>
            </a>
        </div>
    </div>
</body>
</html>
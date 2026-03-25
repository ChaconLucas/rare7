<?php
/**
 * Configuração de Conexão PDO - Cliente
 * Banco: adm_rare
 * Tabela: clientes
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Configurações do banco de dados
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'adm_rare');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Fuso horário
date_default_timezone_set('America/Sao_Paulo');

/**
 * Conexão PDO com tratamento de erros
 */
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . ", time_zone = '-03:00'"
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Em produção, registrar erro sem expor detalhes
        error_log("Erro de conexão: " . $e->getMessage());
        die("Erro ao conectar ao banco de dados. Tente novamente mais tarde.");
    }
}

// Cria a conexão global
$pdo = getConnection();

/**
 * Função auxiliar para normalizar CPF/CNPJ
 * Remove todos os caracteres especiais
 */
function normalizarCpfCnpj($cpfCnpj) {
    return preg_replace('/[^0-9]/', '', $cpfCnpj);
}

/**
 * Função auxiliar para normalizar CEP
 * Remove hífens e espaços
 */
function normalizarCep($cep) {
    return preg_replace('/[^0-9]/', '', $cep);
}

/**
 * Função para verificar se email já existe
 */
function emailExiste($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Função para verificar se CPF/CNPJ já existe
 */
function cpfCnpjExiste($pdo, $cpfCnpj) {
    $cpfCnpjNormalizado = normalizarCpfCnpj($cpfCnpj);
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cpf_cnpj = ? LIMIT 1");
    $stmt->execute([$cpfCnpjNormalizado]);
    return $stmt->fetch() !== false;
}

/**
 * Função para buscar valor mínimo de frete grátis
 * @return float Valor configurado ou 99.00 como padrão
 */
function getFreteGratisThreshold($pdo) {
    // Removido cache estático para sempre buscar valor atualizado
    try {
        $stmt = $pdo->prepare("SELECT free_shipping_threshold FROM freight_settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        
        $valor = ($config && isset($config['free_shipping_threshold'])) 
            ? (float)$config['free_shipping_threshold'] 
            : 500.00; // Fallback: valor padrão do admin
        
        // Debug: log do valor recuperado
        error_log("[Frete] Valor recuperado do banco: " . json_encode(['valor' => $valor, 'config' => $config]));
            
        return $valor;
    } catch (PDOException $e) {
        error_log("[Frete] Erro ao buscar configuração: " . $e->getMessage());
        return 500.00; // Fallback consistent
    }
}
?>

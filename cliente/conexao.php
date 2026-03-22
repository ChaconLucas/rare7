<?php
/**
 * Conexão mysqli - Cliente
 * Reutiliza constantes já definidas em config.php
 * Compartilhada para CMS e outras funcionalidades mysqli
 */

// Validar se constantes existem (config.php deve ser incluído antes)
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    die('Erro: Arquivo config.php deve ser incluído antes de conexao.php');
}

// Criar conexão mysqli
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexão
if (!$conn) {
    error_log("Erro mysqli: " . mysqli_connect_error());
    die("Erro ao conectar ao banco de dados. Tente novamente mais tarde.");
}

// Configurar UTF-8MB4 (encoding correto para português)
mysqli_set_charset($conn, 'utf8mb4');
mysqli_query($conn, "SET NAMES utf8mb4");
mysqli_query($conn, "SET time_zone = '-03:00'");

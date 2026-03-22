<?php
// Configurar fuso horário do Brasil
date_default_timezone_set('America/Sao_Paulo');

define('HOST', '127.0.0.1');
define('USUARIO', 'root');
define('SENHA', '');
define('DB', 'adm_rare');

$conexao = mysqli_connect(HOST, USUARIO, SENHA, DB) or die('Não foi possível conectar');

// ===== CONFIGURAR UTF-8MB4 (encoding correto) =====
mysqli_set_charset($conexao, 'utf8mb4');
mysqli_query($conexao, "SET NAMES utf8mb4");

// Configurar timezone do MySQL para o Brasil
mysqli_query($conexao, "SET time_zone = '-03:00'");
?>

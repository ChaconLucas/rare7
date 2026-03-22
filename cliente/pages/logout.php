<?php
/**
 * Logout - Destroi a sessão e redireciona para home
 */

// Inicia a sessão
session_start();

// Destroi todas as variáveis de sessão
$_SESSION = [];

// Destroi a sessão
session_destroy();

// Destroi o cookie da sessão se existir
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redireciona para a home
header('Location: ../index.php');
exit;
?>

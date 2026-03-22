<?php
/**
 * Configuração Global de Caminhos - Admin Panel
 * 
 * Este arquivo define a BASE_URL do projeto para resolver problemas
 * de caminhos relativos em diferentes níveis de diretório.
 * 
 * Inclua este arquivo no início de cada página do painel admin.
 */

// Resolve project base URL from the first segment in SCRIPT_NAME.
// Example: /rare7/admin/... => /rare7/
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$segments = explode('/', trim((string)$scriptName, '/'));
$detectedBase = !empty($segments[0]) ? '/' . $segments[0] . '/' : '/rare7/';
define('BASE_URL', $detectedBase);

// Caminhos completos para recursos comuns
define('ADMIN_URL', BASE_URL . 'admin/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('BANNERS_URL', UPLOADS_URL . 'banners/');
define('API_CONTADOR_URL', ADMIN_URL . 'src/php/dashboard/api-contador.php');
define('API_SISTEMA_URL', ADMIN_URL . 'src/php/sistema.php');

/**
 * Função helper para construir URLs absolutas
 * 
 * @param string $path Caminho relativo à raiz do projeto
 * @return string URL absoluta
 */
function get_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Função helper para URLs de upload
 * 
 * @param string $relativePath Caminho relativo do banco (ex: uploads/banners/file.jpg)
 * @return string URL absoluta completa
 */
function get_upload_url($relativePath) {
    // Remover possível duplicação de "uploads/"
    $path = preg_replace('#^uploads/#', '', $relativePath);
    return UPLOADS_URL . $path;
}

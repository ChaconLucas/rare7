<?php
/**
 * Ponte para reutilizar o sistema de email automático do admin no cliente.
 */

if (!function_exists('dispararEmailAutomaticoRare7')) {
    function dispararEmailAutomaticoRare7(string $tipo, array $dados): bool
    {
        static $emailEngineLoaded = false;
        static $emailEngineAvailable = false;

        if (!$emailEngineLoaded) {
            $emailEngineLoaded = true;
            $emailEnginePath = dirname(__DIR__, 2) . '/admin/src/php/dashboard/email_automatico.php';

            if (file_exists($emailEnginePath)) {
                require_once $emailEnginePath;
                $emailEngineAvailable = function_exists('enviarEmailAutomatico');
            } else {
                error_log('Email automático não encontrado em: ' . $emailEnginePath);
            }
        }

        if (!$emailEngineAvailable) {
            return false;
        }

        try {
            return (bool) enviarEmailAutomatico($tipo, $dados);
        } catch (Throwable $e) {
            error_log('Falha ao disparar email automático (' . $tipo . '): ' . $e->getMessage());
            return false;
        }
    }
}

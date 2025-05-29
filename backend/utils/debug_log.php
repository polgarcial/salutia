<?php
/**
 * Función para registrar mensajes de depuración
 * 
 * @param string $message Mensaje a registrar
 * @param string $level Nivel de log (info, warning, error)
 * @return void
 */
function debug_log($message, $level = 'info') {
    // Directorio de logs
    $logDir = __DIR__ . '/../../logs';
    
    // Crear directorio si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Nombre del archivo de log
    $logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
    
    // Formatear mensaje
    $formattedMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    
    // Escribir en el archivo de log
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

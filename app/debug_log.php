<?php
// Archivo para depuración y registro de errores

// Función para registrar mensajes de depuración
function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $log_message .= " - Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}
?>

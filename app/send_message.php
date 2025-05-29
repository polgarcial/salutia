<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(0);

// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para devolver respuesta JSON
function sendJsonResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(405, ['error' => 'Método no permitido']);
}

try {
    // Obtener los datos enviados
    $input = file_get_contents("php://input");
    
    // Registrar los datos recibidos para depuración (en un archivo de log, no en la salida)
    error_log("Datos recibidos en chat/send_message: " . $input);
    
    // Decodificar JSON
    $data = json_decode($input, true);
    
    // Verificar si hubo errores en la decodificación JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(400, [
            'error' => 'Error al decodificar JSON: ' . json_last_error_msg(),
            'input' => $input
        ]);
    }
    
    // Verificar que se han recibido todos los campos requeridos
    if (!isset($data['sender_id']) || !isset($data['message'])) {
        sendJsonResponse(400, ['error' => 'Faltan campos requeridos (sender_id, message)']);
    }
    
    // Configuración de la base de datos
    $db_host = 'localhost';
    $db_name = 'salutia';
    $db_user = 'root';
    $db_pass = '';
    
    // Conectar a la base de datos usando PDO
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $db = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        sendJsonResponse(500, ['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    }
    
    // Verificar que el remitente existe
    $stmt = $db->prepare("SELECT id, role FROM users WHERE id = :id");
    $stmt->bindParam(':id', $data['sender_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(404, ['error' => 'El usuario remitente no existe']);
    }
    
    $sender = $stmt->fetch();
    
    // Si se especifica un destinatario, verificar que existe
    $receiver_id = isset($data['receiver_id']) ? $data['receiver_id'] : null;
    if ($receiver_id) {
        $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->bindParam(':id', $receiver_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            sendJsonResponse(404, ['error' => 'El usuario destinatario no existe']);
        }
    }
    
    // Guardar el mensaje en la base de datos
    $stmt = $db->prepare("
        INSERT INTO chat_messages (
            sender_id,
            receiver_id,
            message,
            is_ai_message
        ) VALUES (
            :sender_id,
            :receiver_id,
            :message,
            :is_ai_message
        )
    ");
    
    $is_ai_message = false;
    
    $stmt->bindParam(':sender_id', $data['sender_id']);
    $stmt->bindParam(':receiver_id', $receiver_id);
    $stmt->bindParam(':message', $data['message']);
    $stmt->bindParam(':is_ai_message', $is_ai_message, PDO::PARAM_BOOL);
    
    if ($stmt->execute()) {
        $messageId = $db->lastInsertId();
        
        // Obtener el mensaje para la respuesta
        $stmt = $db->prepare("
            SELECT m.*, 
                   s.first_name as sender_first_name, s.last_name as sender_last_name,
                   r.first_name as receiver_first_name, r.last_name as receiver_last_name
            FROM chat_messages m
            JOIN users s ON m.sender_id = s.id
            LEFT JOIN users r ON m.receiver_id = r.id
            WHERE m.id = :id
        ");
        $stmt->bindParam(':id', $messageId);
        $stmt->execute();
        
        $message = $stmt->fetch();
        
        // Si el mensaje es para la IA (no tiene destinatario humano), generar respuesta automática
        if (!$receiver_id) {
            // Simular respuesta de IA (en una aplicación real, aquí se llamaría a una API de IA)
            $aiResponses = [
                "Hola, soy la IA de Salutia. ¿En qué puedo ayudarte?",
                "Basado en tus síntomas, te recomendaría consultar con un médico especialista.",
                "Recuerda que siempre es importante mantener una buena hidratación y descanso adecuado.",
                "Los síntomas que describes podrían estar relacionados con varias condiciones. Te sugiero programar una cita con tu médico.",
                "Para más información sobre este tema, puedes consultar nuestra sección de recursos médicos."
            ];
            
            $aiResponse = $aiResponses[array_rand($aiResponses)];
            
            // Guardar la respuesta de la IA
            $stmt = $db->prepare("
                INSERT INTO chat_messages (
                    sender_id,
                    receiver_id,
                    message,
                    is_ai_message
                ) VALUES (
                    :sender_id,
                    :receiver_id,
                    :message,
                    :is_ai_message
                )
            ");
            
            $ai_sender_id = $data['sender_id']; // Usamos el mismo ID pero marcamos como mensaje de IA
            $is_ai_message = true;
            
            $stmt->bindParam(':sender_id', $ai_sender_id);
            $stmt->bindParam(':receiver_id', $receiver_id);
            $stmt->bindParam(':message', $aiResponse);
            $stmt->bindParam(':is_ai_message', $is_ai_message, PDO::PARAM_BOOL);
            $stmt->execute();
            
            $aiMessageId = $db->lastInsertId();
            
            // Obtener el mensaje de la IA para la respuesta
            $stmt = $db->prepare("
                SELECT m.*, 
                       s.first_name as sender_first_name, s.last_name as sender_last_name,
                       r.first_name as receiver_first_name, r.last_name as receiver_last_name
                FROM chat_messages m
                JOIN users s ON m.sender_id = s.id
                LEFT JOIN users r ON m.receiver_id = r.id
                WHERE m.id = :id
            ");
            $stmt->bindParam(':id', $aiMessageId);
            $stmt->execute();
            
            $aiMessage = $stmt->fetch();
            
            // Devolver respuesta exitosa con el mensaje original y la respuesta de la IA
            sendJsonResponse(201, [
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'data' => [
                    'user_message' => $message,
                    'ai_response' => $aiMessage
                ]
            ]);
        } else {
            // Devolver respuesta exitosa solo con el mensaje original
            sendJsonResponse(201, [
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'data' => $message
            ]);
        }
    } else {
        sendJsonResponse(500, ['error' => 'No se pudo enviar el mensaje']);
    }
} catch (Exception $e) {
    sendJsonResponse(500, ['error' => 'Error en el servidor: ' . $e->getMessage()]);
}

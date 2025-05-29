<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(0);

// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

// Verificar si es una solicitud GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(405, ['error' => 'Método no permitido']);
}

try {
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
    
    // Obtener parámetros de la solicitud
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $other_user_id = isset($_GET['other_user_id']) ? (int)$_GET['other_user_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $ai_only = isset($_GET['ai_only']) ? filter_var($_GET['ai_only'], FILTER_VALIDATE_BOOLEAN) : false;
    
    // Validar parámetros
    if ($user_id <= 0) {
        sendJsonResponse(400, ['error' => 'Se requiere un ID de usuario válido']);
    }
    
    // Verificar que el usuario existe
    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(404, ['error' => 'El usuario no existe']);
    }
    
    // Construir la consulta SQL base
    $sql = "
        SELECT m.*, 
               s.first_name as sender_first_name, s.last_name as sender_last_name,
               r.first_name as receiver_first_name, r.last_name as receiver_last_name
        FROM chat_messages m
        JOIN users s ON m.sender_id = s.id
        LEFT JOIN users r ON m.receiver_id = r.id
        WHERE ";
    
    // Añadir condiciones según los parámetros
    if ($other_user_id > 0) {
        // Conversación entre dos usuarios específicos
        $sql .= "(
            (m.sender_id = :user_id AND m.receiver_id = :other_user_id) OR 
            (m.sender_id = :other_user_id AND m.receiver_id = :user_id)
        )";
    } else if ($ai_only) {
        // Solo mensajes con la IA (sin destinatario o marcados como mensajes de IA)
        $sql .= "(m.sender_id = :user_id AND (m.receiver_id IS NULL OR m.is_ai_message = 1))";
    } else {
        // Todos los mensajes del usuario
        $sql .= "(m.sender_id = :user_id OR m.receiver_id = :user_id)";
    }
    
    // Ordenar por fecha de creación
    $sql .= " ORDER BY m.created_at DESC";
    
    // Añadir límite y offset
    $sql .= " LIMIT :limit OFFSET :offset";
    
    // Preparar y ejecutar la consulta
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    
    if ($other_user_id > 0) {
        $stmt->bindParam(':other_user_id', $other_user_id, PDO::PARAM_INT);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    // Obtener resultados
    $messages = $stmt->fetchAll();
    
    // Invertir el orden para que los mensajes más antiguos aparezcan primero
    $messages = array_reverse($messages);
    
    // Contar el total de mensajes (sin límite ni offset)
    $countSql = "
        SELECT COUNT(*) as total
        FROM chat_messages m
        WHERE ";
    
    if ($other_user_id > 0) {
        $countSql .= "(
            (m.sender_id = :user_id AND m.receiver_id = :other_user_id) OR 
            (m.sender_id = :other_user_id AND m.receiver_id = :user_id)
        )";
    } else if ($ai_only) {
        $countSql .= "(m.sender_id = :user_id AND (m.receiver_id IS NULL OR m.is_ai_message = 1))";
    } else {
        $countSql .= "(m.sender_id = :user_id OR m.receiver_id = :user_id)";
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    
    if ($other_user_id > 0) {
        $countStmt->bindParam(':other_user_id', $other_user_id, PDO::PARAM_INT);
    }
    
    $countStmt->execute();
    $totalCount = $countStmt->fetch()['total'];
    
    // Marcar mensajes como leídos si son para el usuario solicitante
    if (count($messages) > 0) {
        $updateSql = "
            UPDATE chat_messages 
            SET read_at = NOW() 
            WHERE receiver_id = :user_id AND read_at IS NULL
        ";
        
        if ($other_user_id > 0) {
            $updateSql .= " AND sender_id = :other_user_id";
        }
        
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($other_user_id > 0) {
            $updateStmt->bindParam(':other_user_id', $other_user_id, PDO::PARAM_INT);
        }
        
        $updateStmt->execute();
    }
    
    // Devolver respuesta exitosa
    sendJsonResponse(200, [
        'success' => true,
        'total' => $totalCount,
        'limit' => $limit,
        'offset' => $offset,
        'data' => $messages
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(500, ['error' => 'Error en el servidor: ' . $e->getMessage()]);
}

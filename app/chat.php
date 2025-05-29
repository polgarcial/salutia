<?php
// Desactivar la salida de errores HTML para evitar que se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(0);

// Configuración de CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para verificar el token JWT
function verifyToken($token) {
    // En una implementación real, verificaríamos la firma del token
    // Por ahora, simplemente extraemos los datos
    $jwt_secret = 'your_jwt_secret_key_here';
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) != 3) {
        return false;
    }
    
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    
    // Verificar si el token ha expirado
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

// Obtener el token de autorización
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    // Para pruebas, permitimos acceso sin token
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if ($userId <= 0) {
        sendJsonResponse(401, ['error' => 'No autorizado']);
    }
} else {
    $token = $matches[1];
    $payload = verifyToken($token);
    
    if (!$payload) {
        sendJsonResponse(401, ['error' => 'Token inválido o expirado']);
    }
    
    $userId = $payload['data']['id'];
}

// Configuración de la base de datos
$db_host = 'localhost';
$db_name = 'salutia';
$db_user = 'root';
$db_pass = '';

// Conectar a la base de datos
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

// Manejar diferentes métodos HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Obtener mensajes de chat
        getMessages($db, $userId);
        break;
    case 'POST':
        // Enviar nuevo mensaje
        sendMessage($db, $userId);
        break;
    default:
        sendJsonResponse(405, ['error' => 'Método no permitido']);
}

// Función para obtener mensajes de chat
function getMessages($db, $userId) {
    try {
        $receiverId = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;
        
        if ($receiverId > 0) {
            // Obtener conversación con un usuario específico
            $sql = "
                SELECT c.*, 
                       s.first_name as sender_first_name, 
                       s.last_name as sender_last_name,
                       s.role as sender_role,
                       r.first_name as receiver_first_name, 
                       r.last_name as receiver_last_name,
                       r.role as receiver_role
                FROM chat_messages c
                JOIN users s ON c.sender_id = s.id
                LEFT JOIN users r ON c.receiver_id = r.id
                WHERE (c.sender_id = :user_id AND c.receiver_id = :receiver_id)
                   OR (c.sender_id = :receiver_id AND c.receiver_id = :user_id)
                ORDER BY c.created_at ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':receiver_id', $receiverId);
        } else {
            // Obtener todas las conversaciones del usuario
            $sql = "
                SELECT c.*, 
                       s.first_name as sender_first_name, 
                       s.last_name as sender_last_name,
                       s.role as sender_role,
                       r.first_name as receiver_first_name, 
                       r.last_name as receiver_last_name,
                       r.role as receiver_role
                FROM chat_messages c
                JOIN users s ON c.sender_id = s.id
                LEFT JOIN users r ON c.receiver_id = r.id
                WHERE c.sender_id = :user_id OR c.receiver_id = :user_id
                ORDER BY c.created_at DESC
            ";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
        }
        
        $stmt->execute();
        $messages = $stmt->fetchAll();
        
        // Si no hay mensajes, devolver datos de ejemplo para pruebas
        if (count($messages) === 0) {
            // Datos de ejemplo para pruebas
            $sampleMessages = [
                [
                    'id' => 1,
                    'sender_id' => 2,
                    'receiver_id' => $userId,
                    'is_ai_message' => false,
                    'message' => '¡Hola! Soy el Dr. García. ¿En qué puedo ayudarte hoy?',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'sender_first_name' => 'Joan',
                    'sender_last_name' => 'García',
                    'sender_role' => 'doctor'
                ],
                [
                    'id' => 2,
                    'sender_id' => $userId,
                    'receiver_id' => 2,
                    'is_ai_message' => false,
                    'message' => 'Tengo dolor de cabeza desde hace dos días, ¿qué me recomienda?',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days +5 minutes')),
                    'sender_first_name' => 'Usuario',
                    'sender_last_name' => 'Actual',
                    'sender_role' => 'patient'
                ],
                [
                    'id' => 3,
                    'sender_id' => 2,
                    'receiver_id' => $userId,
                    'is_ai_message' => false,
                    'message' => 'Te recomendaría tomar paracetamol y descansar. Si persiste más de 3 días, deberías programar una cita.',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days +10 minutes')),
                    'sender_first_name' => 'Joan',
                    'sender_last_name' => 'García',
                    'sender_role' => 'doctor'
                ],
                [
                    'id' => 4,
                    'sender_id' => 0,
                    'receiver_id' => $userId,
                    'is_ai_message' => true,
                    'message' => 'Hola, soy la IA de Salutia. ¿En qué puedo ayudarte hoy?',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'sender_first_name' => 'Salutia',
                    'sender_last_name' => 'IA',
                    'sender_role' => 'ai'
                ]
            ];
            
            sendJsonResponse(200, [
                'success' => true,
                'message' => 'Datos de ejemplo para pruebas',
                'is_sample_data' => true,
                'messages' => $sampleMessages
            ]);
        }
        
        // Marcar mensajes como leídos
        if ($receiverId > 0) {
            $stmt = $db->prepare("
                UPDATE chat_messages 
                SET read_at = NOW() 
                WHERE sender_id = :receiver_id AND receiver_id = :user_id AND read_at IS NULL
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':receiver_id', $receiverId);
            $stmt->execute();
        }
        
        sendJsonResponse(200, [
            'success' => true,
            'messages' => $messages
        ]);
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => 'Error al obtener mensajes: ' . $e->getMessage()]);
    }
}

// Función para enviar un nuevo mensaje
function sendMessage($db, $userId) {
    try {
        // Obtener los datos enviados
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        // Verificar datos requeridos
        if (!isset($data['message']) || empty(trim($data['message']))) {
            sendJsonResponse(400, ['error' => 'Mensaje requerido']);
        }
        
        $receiverId = isset($data['receiver_id']) ? intval($data['receiver_id']) : 0;
        $isAiMessage = isset($data['is_ai_message']) ? (bool)$data['is_ai_message'] : false;
        
        // Si es un mensaje a la IA, el receiverId es 0
        if ($isAiMessage) {
            $receiverId = 0;
        } else if ($receiverId <= 0) {
            sendJsonResponse(400, ['error' => 'ID de receptor inválido']);
        }
        
        // Insertar el nuevo mensaje
        $stmt = $db->prepare("
            INSERT INTO chat_messages (
                sender_id, 
                receiver_id, 
                is_ai_message, 
                message
            ) VALUES (
                :sender_id, 
                :receiver_id, 
                :is_ai_message, 
                :message
            )
        ");
        
        $stmt->bindParam(':sender_id', $userId);
        $stmt->bindParam(':receiver_id', $receiverId);
        $stmt->bindParam(':is_ai_message', $isAiMessage, PDO::PARAM_BOOL);
        $stmt->bindParam(':message', $data['message']);
        
        if ($stmt->execute()) {
            $messageId = $db->lastInsertId();
            
            // Obtener los datos del mensaje enviado
            $stmt = $db->prepare("
                SELECT c.*, 
                       s.first_name as sender_first_name, 
                       s.last_name as sender_last_name,
                       s.role as sender_role,
                       r.first_name as receiver_first_name, 
                       r.last_name as receiver_last_name,
                       r.role as receiver_role
                FROM chat_messages c
                JOIN users s ON c.sender_id = s.id
                LEFT JOIN users r ON c.receiver_id = r.id
                WHERE c.id = :id
            ");
            $stmt->bindParam(':id', $messageId);
            $stmt->execute();
            
            $message = $stmt->fetch();
            
            // Si es un mensaje a la IA, generar respuesta automática
            if ($isAiMessage) {
                // Respuesta simple de la IA (en una implementación real, se conectaría a un servicio de IA)
                $aiResponses = [
                    "Entiendo tu consulta. Te recomendaría consultar con un médico para un diagnóstico preciso.",
                    "Gracias por tu mensaje. Basado en lo que describes, podría ser útil programar una cita con un especialista.",
                    "He analizado tu consulta. Recuerda que siempre es importante mantener hábitos saludables como una buena alimentación y ejercicio regular.",
                    "Comprendo tu situación. ¿Has considerado hablar con un profesional de la salud sobre estos síntomas?",
                    "Gracias por compartir esa información. Para un mejor diagnóstico, te sugiero que programes una cita con uno de nuestros médicos."
                ];
                
                $aiResponse = $aiResponses[array_rand($aiResponses)];
                
                // Insertar respuesta de la IA
                $stmt = $db->prepare("
                    INSERT INTO chat_messages (
                        sender_id, 
                        receiver_id, 
                        is_ai_message, 
                        message
                    ) VALUES (
                        0, 
                        :receiver_id, 
                        1, 
                        :message
                    )
                ");
                
                $stmt->bindParam(':receiver_id', $userId);
                $stmt->bindParam(':message', $aiResponse);
                $stmt->execute();
                
                $aiMessageId = $db->lastInsertId();
                
                // Obtener los datos del mensaje de la IA
                $stmt = $db->prepare("
                    SELECT c.*, 
                           'Salutia' as sender_first_name, 
                           'IA' as sender_last_name,
                           'ai' as sender_role,
                           s.first_name as receiver_first_name, 
                           s.last_name as receiver_last_name,
                           s.role as receiver_role
                    FROM chat_messages c
                    JOIN users s ON c.receiver_id = s.id
                    WHERE c.id = :id
                ");
                $stmt->bindParam(':id', $aiMessageId);
                $stmt->execute();
                
                $aiMessage = $stmt->fetch();
                
                sendJsonResponse(201, [
                    'success' => true,
                    'message' => 'Mensaje enviado correctamente',
                    'user_message' => $message,
                    'ai_response' => $aiMessage
                ]);
            } else {
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
        sendJsonResponse(500, ['error' => 'Error al enviar mensaje: ' . $e->getMessage()]);
    }
}

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['message']) || empty($data['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El mensaje es requerido']);
    exit();
}

$userMessage = $data['message'];
$chatHistory = isset($data['history']) ? $data['history'] : [];

$apiKey = 'TU_CLAVE_API_DE_OPENAI';
$useOpenAI = false;

if ($useOpenAI && !empty($apiKey)) {
    try {
        $messages = [];
        
        $messages[] = [
            'role' => 'system',
            'content' => 'Eres un asistente médico virtual para Salutia, una plataforma de gestión de citas médicas. Tu objetivo es ayudar a los pacientes con información general sobre salud, orientarlos en el uso de la plataforma y asistirlos en la programación de citas. No debes dar diagnósticos médicos específicos, pero puedes proporcionar información general sobre síntomas y recomendar consultar a un médico cuando sea apropiado. Mantén tus respuestas concisas, amigables y orientadas a la acción.'
        ];
        
        foreach ($chatHistory as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];
        
        $requestData = [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'max_tokens' => 150,
            'temperature' => 0.7
        ];
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            $aiResponse = $responseData['choices'][0]['message']['content'];
            
            echo json_encode([
                'success' => true,
                'message' => $aiResponse
            ]);
        } else {
            $aiResponse = getDefaultResponse($userMessage);
            echo json_encode([
                'success' => true,
                'message' => $aiResponse,
                'note' => 'Respuesta generada localmente debido a un error con la API de OpenAI'
            ]);
        }
    } catch (Exception $e) {
        $aiResponse = getDefaultResponse($userMessage);
        echo json_encode([
            'success' => true,
            'message' => $aiResponse,
            'note' => 'Respuesta generada localmente debido a un error: ' . $e->getMessage()
        ]);
    }
} else {
    $aiResponse = getDefaultResponse($userMessage);
    echo json_encode([
        'success' => true,
        'message' => $aiResponse
    ]);
}

function getDefaultResponse($message) {
    $lowerMessage = strtolower($message);
    if (strpos($lowerMessage, 'cita') !== false || 
        strpos($lowerMessage, 'reservar') !== false || 
        strpos($lowerMessage, 'agendar') !== false) {
        return 'Para solicitar una cita, puedes usar la sección "Buscar Médico" en el dashboard, seleccionar la especialidad deseada, elegir un médico disponible y seleccionar una fecha y hora que te convenga. ¿Necesitas ayuda con alguna especialidad en particular?';
    }
    
    if (strpos($lowerMessage, 'urgencia') !== false || 
        strpos($lowerMessage, 'emergencia') !== false) {
        return 'Si estás experimentando una emergencia médica, debes llamar inmediatamente al 112 o acudir al servicio de urgencias más cercano. No esperes a una cita programada para situaciones que requieren atención inmediata.';
    }
    
    if (strpos($lowerMessage, 'síntoma') !== false || 
        strpos($lowerMessage, 'dolor') !== false || 
        strpos($lowerMessage, 'enfermo') !== false) {
        return 'Aunque puedo proporcionarte información general, es importante que consultes con un médico para un diagnóstico adecuado. Puedes describir tus síntomas durante la cita médica. ¿Te gustaría que te ayude a programar una cita con un especialista?';
    }
    
    if (strpos($lowerMessage, 'historial') !== false || 
        strpos($lowerMessage, 'expediente') !== false || 
        strpos($lowerMessage, 'resultados') !== false) {
        return 'Puedes acceder a tu historial médico desde la sección "Mi Historial" en el menú principal. Allí encontrarás tus consultas anteriores, recetas y resultados de pruebas. Si no puedes acceder a alguna información, contacta con soporte técnico.';
    }
    
    if (strpos($lowerMessage, 'gracias') !== false) {
        return '¡De nada! Estoy aquí para ayudarte. ¿Hay algo más en lo que pueda asistirte?';
    }
    
    if (strpos($lowerMessage, 'hola') !== false || 
        strpos($lowerMessage, 'buenos días') !== false || 
        strpos($lowerMessage, 'buenas tardes') !== false) {
        return '¡Hola! ¿En qué puedo ayudarte hoy con respecto a tu salud o al uso de Salutia?';
    }
    
    if (strpos($lowerMessage, 'especialidad') !== false || 
        strpos($lowerMessage, 'especialista') !== false) {
        return 'En Salutia contamos con diversas especialidades médicas como Cardiología, Dermatología, Ginecología, Medicina Familiar, Neurología, Oftalmología, Pediatría y Traumatología. ¿Con qué especialidad te gustaría programar una cita?';
    }
    
    if (strpos($lowerMessage, 'precio') !== false || 
        strpos($lowerMessage, 'costo') !== false || 
        strpos($lowerMessage, 'tarifa') !== false) {
        return 'Los precios de las consultas varían según la especialidad y el médico. Puedes ver el costo exacto al momento de seleccionar un horario para tu cita. ¿Te gustaría que te ayude a encontrar opciones económicas?';
    }
    
    if (strpos($lowerMessage, 'cancelar') !== false || 
        strpos($lowerMessage, 'anular') !== false || 
        strpos($lowerMessage, 'eliminar cita') !== false) {
        return 'Para cancelar una cita, dirígete a la sección "Mis Citas", busca la cita que deseas cancelar y haz clic en el botón de cancelar. Recuerda que algunas cancelaciones pueden tener cargos si se realizan con menos de 24 horas de anticipación.';
    }
    
    if (strpos($lowerMessage, 'seguro') !== false || 
        strpos($lowerMessage, 'cobertura') !== false) {
        return 'Salutia trabaja con diversas compañías de seguros médicos. Para verificar si tu seguro tiene cobertura, puedes contactar a nuestro servicio de atención al cliente o consultar la sección de "Seguros Aceptados" en la página principal.';
    }
    
    return 'Gracias por tu mensaje. Para brindarte la mejor atención, te recomiendo consultar directamente con uno de nuestros médicos. Puedes programar una cita fácilmente desde la sección "Buscar Médico". ¿Hay algo específico en lo que pueda orientarte mientras tanto?';
}
?>

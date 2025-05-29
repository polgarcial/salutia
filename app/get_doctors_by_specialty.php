<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require_once __DIR__ . '/database_class.php';

// Habilitar CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Crear archivo de log si no existe
if (!file_exists(__DIR__ . '/debug_log.txt')) {
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - Archivo de log creado' . "\n");
}

// Registrar la solicitud para depuración
file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - Solicitud recibida: ' . json_encode($_GET) . "\n", FILE_APPEND);

// Manejar OPTIONS request para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Obtener parámetros
    $specialty = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';
    
    // Registrar la especialidad solicitada
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - Especialidad solicitada: ' . $specialty . "\n", FILE_APPEND);
    
    if (empty($specialty)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Especialidad no especificada']);
        exit();
    }
    
    // Crear instancia de la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Registrar conexión a la base de datos
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - Conexión a la base de datos establecida' . "\n", FILE_APPEND);
    
    // Verificar si la tabla doctor_specialties existe (para MySQL usando PDO)
    $stmt = $db->query("SHOW TABLES LIKE 'doctor_specialties'");
    $tableExists = ($stmt->rowCount() > 0);
    
    if (!$tableExists) {
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - La tabla doctor_specialties no existe' . "\n", FILE_APPEND);
        
        // Devolver datos de prueba si la tabla no existe
        $doctors = [
            [
                'id' => 1,
                'name' => 'Dr. Juan Pérez',
                'email' => 'juan.perez@salutia.com',
                'specialty' => $specialty,
                'rating' => 4.5,
                'availability' => [
                    ['day_of_week' => 'monday', 'day_name' => 'Lunes', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'monday', 'day_name' => 'Lunes', 'start_time' => '16:00:00', 'end_time' => '20:00:00'],
                    ['day_of_week' => 'wednesday', 'day_name' => 'Miércoles', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'friday', 'day_name' => 'Viernes', 'start_time' => '16:00:00', 'end_time' => '20:00:00']
                ],
                'common_reasons' => [
                    'Control rutinario',
                    'Dolor',
                    'Consulta general',
                    'Seguimiento',
                    'Malestar'
                ]
            ],
            [
                'id' => 2,
                'name' => 'Dra. María López',
                'email' => 'maria.lopez@salutia.com',
                'specialty' => $specialty,
                'rating' => 4.8,
                'availability' => [
                    ['day_of_week' => 'tuesday', 'day_name' => 'Martes', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'tuesday', 'day_name' => 'Martes', 'start_time' => '16:00:00', 'end_time' => '20:00:00'],
                    ['day_of_week' => 'thursday', 'day_name' => 'Jueves', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'friday', 'day_name' => 'Viernes', 'start_time' => '09:00:00', 'end_time' => '13:00:00']
                ],
                'common_reasons' => [
                    'Control rutinario',
                    'Dolor',
                    'Consulta general',
                    'Seguimiento',
                    'Malestar'
                ]
            ]
        ];
        
        // Devolver respuesta
        echo json_encode([
            'success' => true,
            'specialty' => $specialty,
            'doctors' => $doctors,
            'message' => 'Datos de prueba (la tabla no existe)'
        ]);
        exit();
    }
    
    // Obtener médicos por especialidad
    $query = "SELECT u.id, u.name, u.email, ds.specialty
        FROM users u
        JOIN doctor_specialties ds ON u.id = ds.doctor_id
        WHERE u.role = 'doctor' AND ds.specialty = :specialty
        ORDER BY u.name ASC";
    
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - Ejecutando consulta: ' . $query . "\n", FILE_APPEND);
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':specialty', $specialty);
    $stmt->execute();
    
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - Médicos encontrados: ' . count($doctors) . "\n", FILE_APPEND);
    
    // Para cada médico, obtener su disponibilidad
    foreach ($doctors as &$doctor) {
        // Obtener disponibilidad
        $stmt = $db->prepare("
            SELECT day_of_week, start_time, end_time
            FROM doctor_availability
            WHERE doctor_id = :doctor_id
            ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), start_time
        ");
        $stmt->bindParam(':doctor_id', $doctor['id']);
        $stmt->execute();
        
        $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Traducir días de la semana
        foreach ($availability as &$slot) {
            switch ($slot['day_of_week']) {
                case 'monday': $slot['day_name'] = 'Lunes'; break;
                case 'tuesday': $slot['day_name'] = 'Martes'; break;
                case 'wednesday': $slot['day_name'] = 'Miércoles'; break;
                case 'thursday': $slot['day_name'] = 'Jueves'; break;
                case 'friday': $slot['day_name'] = 'Viernes'; break;
                case 'saturday': $slot['day_name'] = 'Sábado'; break;
                case 'sunday': $slot['day_name'] = 'Domingo'; break;
            }
        }
        
        $doctor['availability'] = $availability;
        
        // Motivos de consulta comunes según la especialidad
        $commonReasons = [];
        switch ($specialty) {
            case 'Cardiología':
                $commonReasons = [
                    'Dolor en el pecho',
                    'Hipertensión',
                    'Arritmias',
                    'Control rutinario',
                    'Insuficiencia cardíaca'
                ];
                break;
            case 'Dermatología':
                $commonReasons = [
                    'Acné',
                    'Eccema',
                    'Psoriasis',
                    'Lunares sospechosos',
                    'Caída del cabello'
                ];
                break;
            case 'Ginecología':
                $commonReasons = [
                    'Control rutinario',
                    'Infección vaginal',
                    'Planificación familiar',
                    'Dolor pélvico',
                    'Menopausia'
                ];
                break;
            case 'Medicina Familiar':
                $commonReasons = [
                    'Control general',
                    'Vacunación',
                    'Resfriado/Gripe',
                    'Dolor de garganta',
                    'Presión arterial'
                ];
                break;
            case 'Neurología':
                $commonReasons = [
                    'Dolor de cabeza',
                    'Migrañas',
                    'Mareos',
                    'Pérdida de memoria',
                    'Epilepsia'
                ];
                break;
            case 'Oftalmología':
                $commonReasons = [
                    'Revisión de vista',
                    'Dolor ocular',
                    'Visión borrosa',
                    'Conjuntivitis',
                    'Cataratas'
                ];
                break;
            case 'Pediatría':
                $commonReasons = [
                    'Control de crecimiento',
                    'Vacunación',
                    'Fiebre',
                    'Infección respiratoria',
                    'Problemas digestivos'
                ];
                break;
            case 'Traumatología':
                $commonReasons = [
                    'Dolor articular',
                    'Esguince',
                    'Fractura',
                    'Dolor de espalda',
                    'Lesión deportiva'
                ];
                break;
            default:
                $commonReasons = [
                    'Consulta general',
                    'Control rutinario',
                    'Dolor',
                    'Malestar',
                    'Seguimiento'
                ];
        }
        
        $doctor['common_reasons'] = $commonReasons;
        
        // Añadir una valoración aleatoria (en un sistema real, esto vendría de la base de datos)
        $doctor['rating'] = rand(35, 50) / 10; // Valoración entre 3.5 y 5.0
    }
    
    // Si no se encontraron médicos, devolver datos de prueba
    if (empty($doctors)) {
        file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - No se encontraron médicos, devolviendo datos de prueba' . "\n", FILE_APPEND);
        
        // Datos de prueba para la especialidad seleccionada
        $doctors = [
            [
                'id' => 1,
                'name' => 'Dr. Juan Pérez',
                'email' => 'juan.perez@salutia.com',
                'specialty' => $specialty,
                'rating' => 4.5,
                'availability' => [
                    ['day_of_week' => 'monday', 'day_name' => 'Lunes', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'monday', 'day_name' => 'Lunes', 'start_time' => '16:00:00', 'end_time' => '20:00:00'],
                    ['day_of_week' => 'wednesday', 'day_name' => 'Miércoles', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'friday', 'day_name' => 'Viernes', 'start_time' => '16:00:00', 'end_time' => '20:00:00']
                ],
                'common_reasons' => [
                    'Control rutinario',
                    'Dolor',
                    'Consulta general',
                    'Seguimiento',
                    'Malestar'
                ]
            ],
            [
                'id' => 2,
                'name' => 'Dra. María López',
                'email' => 'maria.lopez@salutia.com',
                'specialty' => $specialty,
                'rating' => 4.8,
                'availability' => [
                    ['day_of_week' => 'tuesday', 'day_name' => 'Martes', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'tuesday', 'day_name' => 'Martes', 'start_time' => '16:00:00', 'end_time' => '20:00:00'],
                    ['day_of_week' => 'thursday', 'day_name' => 'Jueves', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                    ['day_of_week' => 'friday', 'day_name' => 'Viernes', 'start_time' => '09:00:00', 'end_time' => '13:00:00']
                ],
                'common_reasons' => [
                    'Control rutinario',
                    'Dolor',
                    'Consulta general',
                    'Seguimiento',
                    'Malestar'
                ]
            ]
        ];
    }
    
    // Devolver respuesta
    echo json_encode([
        'success' => true,
        'specialty' => $specialty,
        'doctors' => $doctors
    ]);
    
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug_log.txt', date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    
    // En caso de error, devolver datos de prueba
    $doctors = [
        [
            'id' => 1,
            'name' => 'Dr. Juan Pérez (Datos de emergencia)',
            'email' => 'juan.perez@salutia.com',
            'specialty' => $specialty,
            'rating' => 4.5,
            'availability' => [
                ['day_of_week' => 'monday', 'day_name' => 'Lunes', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                ['day_of_week' => 'wednesday', 'day_name' => 'Miércoles', 'start_time' => '09:00:00', 'end_time' => '13:00:00']
            ],
            'common_reasons' => ['Consulta general', 'Dolor', 'Control rutinario']
        ],
        [
            'id' => 2,
            'name' => 'Dra. María López (Datos de emergencia)',
            'email' => 'maria.lopez@salutia.com',
            'specialty' => $specialty,
            'rating' => 4.8,
            'availability' => [
                ['day_of_week' => 'tuesday', 'day_name' => 'Martes', 'start_time' => '09:00:00', 'end_time' => '13:00:00'],
                ['day_of_week' => 'thursday', 'day_name' => 'Jueves', 'start_time' => '09:00:00', 'end_time' => '13:00:00']
            ],
            'common_reasons' => ['Consulta general', 'Dolor', 'Control rutinario']
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'specialty' => $specialty,
        'doctors' => $doctors,
        'message' => 'Datos de emergencia debido a un error: ' . $e->getMessage()
    ]);
}
?>

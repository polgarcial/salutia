// Configuración global para la aplicación Salutia

const CONFIG = {
  // URL base de la API
  API_URL: 'http://localhost:8000/api',
  
  // Tiempo de expiración del token (en segundos)
  TOKEN_EXPIRATION: 3600,
  
  // Opciones para las peticiones fetch
  FETCH_OPTIONS: {
    headers: {
      'Content-Type': 'application/json'
    }
  },
  
  // Endpoints de la API
  ENDPOINTS: {
    REGISTER: '/register.php',
    LOGIN: '/auth.php?action=login',
    USERS: '/users.php',
    APPOINTMENTS: '/appointments.php',
    PRESCRIPTIONS: '/prescriptions.php',
    MEDICAL_RECORDS: '/medical-records.php',
    CHAT: '/chat.php'
  }
};

// No modificar esta línea
window.SALUTIA_CONFIG = CONFIG;

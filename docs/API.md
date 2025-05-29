# Documentación de la API de Salutia

## Autenticación

### Registrar un nuevo usuario
```
POST /api/auth/register
```

**Parámetros:**
- `email` (string, requerido)
- `password` (string, requerido)
- `name` (string, requerido)
- `role` (string, opcional, valores: 'patient', 'doctor', 'admin')

### Iniciar sesión
```
POST /api/auth/login
```

**Parámetros:**
- `email` (string, requerido)
- `password` (string, requerido)

## Citas

### Obtener todas las citas
```
GET /api/appointments
```

**Parámetros de consulta:**
- `status` (opcional): Filtra por estado ('pending', 'confirmed', 'cancelled')
- `doctor_id` (opcional): Filtra por ID de médico
- `patient_id` (opcional): Filtra por ID de paciente
- `date_from` (opcional): Fecha de inicio (YYYY-MM-DD)
- `date_to` (opcional): Fecha de fin (YYYY-MM-DD)

### Crear una nueva cita
```
POST /api/appointments
```

**Parámetros:**
- `doctor_id` (integer, requerido)
- `patient_id` (integer, requerido)
- `appointment_date` (string, requerido, formato: YYYY-MM-DD)
- `appointment_time` (string, requerido, formato: HH:MM:SS)
- `reason` (string, opcional)

### Actualizar una cita
```
PUT /api/appointments/:id
```

**Parámetros:**
- `status` (string, opcional): Nuevo estado de la cita
- `notes` (string, opcional): Notas adicionales
- `appointment_date` (string, opcional)
- `appointment_time` (string, opcional)

## Médicos

### Obtener lista de médicos
```
GET /api/doctors
```

**Parámetros de consulta:**
- `specialty` (opcional): Filtra por especialidad
- `available` (opcional, boolean): Filtra por disponibilidad

### Obtener disponibilidad de un médico
```
GET /api/doctors/:id/availability
```

**Parámetros de consulta:**
- `date` (opcional): Fecha para consultar disponibilidad (YYYY-MM-DD)

## Pacientes

### Obtener información de un paciente
```
GET /api/patients/:id
```

### Obtener historial médico
```
GET /api/patients/:id/medical-history
```

## Errores

La API devuelve códigos de estado HTTP estándar:
- 200 OK: La solicitud fue exitosa
- 201 Created: Recurso creado exitosamente
- 400 Bad Request: Error en los parámetros de la solicitud
- 401 Unauthorized: No autenticado
- 403 Forbidden: No autorizado
- 404 Not Found: Recurso no encontrado
- 500 Internal Server Error: Error del servidor

## Autenticación

Todas las peticiones (excepto login/registro) requieren un token de autenticación en la cabecera:
```
Authorization: Bearer <token>
```

## Paginación

Las respuestas que devuelven listas están paginadas y siguen el formato:
```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 15,
    "to": 10,
    "total": 10
  }
}
```

## Ejemplo de Uso

```javascript
// Obtener lista de médicos
fetch('/api/doctors')
  .then(response => response.json())
  .then(data => console.log(data));

// Crear una cita
fetch('/api/appointments', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    doctor_id: 1,
    patient_id: 1,
    appointment_date: '2025-05-30',
    appointment_time: '10:00:00',
    reason: 'Consulta general'
  })
});
```

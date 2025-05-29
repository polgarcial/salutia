# Sistema de Citas Médicas - Salutia

Este sistema permite la gestión completa de citas médicas para la plataforma Salutia, incluyendo la selección de especialidades, médicos, fechas y horarios, así como la administración de citas existentes.

## Características principales

- **Interfaz moderna y responsive**: Diseño atractivo que funciona en cualquier dispositivo
- **Selección intuitiva**: Flujo guiado para crear citas (especialidad → médico → fecha → hora)
- **Gestión de citas**: Ver, cancelar y gestionar todas tus citas médicas
- **Panel de administración**: Funcionalidades especiales para médicos
- **Base de datos**: Almacenamiento persistente de toda la información

## Configuración inicial

Para configurar el sistema por primera vez, sigue estos pasos:

1. **Configurar la base de datos**:
   - Asegúrate de tener MySQL funcionando (por ejemplo, con Laragon)
   - Crea una base de datos llamada `salutia`
   - Ejecuta el script de configuración: [setup_citas.php](backend/database/setup_citas.php)

2. **Acceder al sistema**:
   - Abre el archivo [sistema_citas.html](sistema_citas.html) en tu navegador
   - El sistema está listo para ser utilizado

## Estructura de archivos

- **sistema_citas.html**: Interfaz principal del sistema
- **js/sistema_citas.js**: Lógica principal del sistema
- **js/sistema_citas_parte1.js**, **js/sistema_citas_parte2.js**, **js/sistema_citas_parte3.js**: Componentes de la lógica
- **backend/api/**: Endpoints PHP para comunicación con la base de datos
- **backend/database/**: Scripts para configuración de la base de datos
- **backend/config/**: Archivos de configuración

## Uso del sistema

### Para pacientes

1. **Crear una cita**:
   - Selecciona una especialidad médica
   - Elige un médico de la lista
   - Selecciona una fecha
   - Elige un horario disponible
   - Añade notas si lo deseas
   - Haz clic en "Crear Cita"

2. **Gestionar tus citas**:
   - En la sección "Mis Citas" puedes ver todas tus citas
   - Filtra por estado (pendientes, confirmadas, completadas, canceladas)
   - Usa el menú de acciones para cancelar citas o ver detalles

### Para médicos

1. **Gestionar disponibilidad**:
   - Accede con una cuenta de médico
   - Haz clic en "Gestionar Disponibilidad"
   - Selecciona fechas y marca los horarios disponibles

2. **Gestionar citas de pacientes**:
   - Confirma citas pendientes
   - Marca citas como completadas
   - Ve los detalles de cada cita

## Integración con el sistema de autenticación

El sistema está diseñado para integrarse con el sistema de autenticación existente de Salutia:

- Si hay una sesión activa, el sistema detectará automáticamente el ID y rol del usuario
- Si no hay sesión, se utilizará un ID de usuario por defecto (1) para pruebas

## Personalización

Puedes personalizar el sistema modificando los siguientes archivos:

- **sistema_citas.html**: Para cambiar la interfaz de usuario
- **js/sistema_citas.js**: Para modificar la lógica del sistema
- **backend/api/**: Para ajustar la comunicación con la base de datos

## Solución de problemas

Si encuentras algún problema, verifica lo siguiente:

1. **Errores de conexión a la base de datos**:
   - Asegúrate de que MySQL está en funcionamiento
   - Verifica las credenciales en `backend/config/database_class.php`

2. **No se muestran médicos o horarios**:
   - Ejecuta nuevamente el script `setup_citas.php`
   - Verifica que las tablas se han creado correctamente

3. **Problemas con la sesión**:
   - Si tienes problemas con la autenticación, puedes utilizar el sistema sin iniciar sesión

## Próximas mejoras

Estamos trabajando en las siguientes mejoras para futuras versiones:

- Notificaciones por email para recordar citas
- Integración con historial médico
- Sistema de pagos online
- Videoconsultas integradas

---

Desarrollado para Salutia - Plataforma de Gestión de Citas Médicas © 2025

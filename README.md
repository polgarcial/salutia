# Salutia - Plataforma de Gestión de Citas Médicas

Salutia es una aplicación web de salud con implementación de IA que facilita el día a día tanto para pacientes como para médicos.

## Características principales

- Sistema de gestión de citas médicas
- Panel de control para pacientes y médicos
- Filtrado de médicos por especialidad y nombre
- Sistema de notificaciones
- Integración con IA para asistencia virtual
- Autenticación segura mediante JWT

## Tecnologías utilizadas

### Frontend
- HTML5/CSS3
- JavaScript
- Bootstrap 5
- Librerías: Font Awesome, Flatpickr, Animate.css

### Backend
- PHP
- MySQL
- JWT (autenticación)
- API REST
- Integración con OpenAI/ChatGPT

## APIs principales

1. **API de Gestión de Citas** - Permite a los médicos aceptar citas de pacientes
2. **API de Integración con IA** - Conecta la aplicación con OpenAI para asistencia virtual
3. **API de Autenticación** - Gestiona el inicio de sesión y permisos de usuarios

## Instalación

1. Clona este repositorio
2. Configura un servidor web con PHP y MySQL (recomendado: Laragon)
3. Importa la base de datos desde `/database/salutia.sql`
4. Configura las credenciales de la base de datos en `/config/database.php`
5. Accede a la aplicación a través de tu servidor local

## Estructura del proyecto

```
salutia/
├── app/                  # Lógica principal de la aplicación
├── backend/              # APIs y servicios del backend
├── config/               # Configuración de la aplicación
├── database/             # Scripts de base de datos
├── public/               # Archivos públicos accesibles desde la web
│   ├── css/              # Estilos CSS
│   ├── js/               # Scripts JavaScript
│   ├── img/              # Imágenes
│   └── views/            # Vistas HTML
└── README.md             # Este archivo
```

## Licencia

Este proyecto está bajo la Licencia MIT.

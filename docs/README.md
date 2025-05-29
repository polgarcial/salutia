# Salutia - Plataforma de Gestión de Citas Médicas

## Descripción
Salutia es una plataforma web para la gestión eficiente de citas médicas con integración de IA. El sistema permite a los pacientes solicitar citas, consultar su historial médico y comunicarse con profesionales de la salud.

## Características Principales
- Sistema de gestión de citas
- Historial médico digital
- Comunicación entre pacientes y médicos
- Gestión de recetas electrónicas
- Panel de administración

## Estructura del Proyecto
```
salutia/
├── assets/               # Recursos estáticos (imágenes, estilos)
├── backend/
│   ├── api/              # Endpoints de la API
│   ├── config/           # Configuración de la base de datos
│   ├── database/         # Scripts de base de datos
│   ├── logs/             # Archivos de registro
│   └── utils/            # Utilidades y helpers
├── docs/                 # Documentación
├── js/                   # Código JavaScript del frontend
├── tests/                # Pruebas automatizadas
│   ├── unit/             # Pruebas unitarias
│   └── integration/      # Pruebas de integración
├── .htaccess             # Configuración de Apache
└── index.html            # Página principal
```

## Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Node.js (para desarrollo frontend)

## Instalación

1. **Configurar la base de datos**
   - Crear una base de datos MySQL llamada `salutia`
   - Importar el esquema inicial desde `backend/database/schema.sql`

2. **Configurar el backend**
   ```bash
   cd backend/config
   cp database.php.example database.php
   # Editar database.php con las credenciales de la base de datos
   ```

3. **Configurar el frontend**
   ```bash
   npm install  # Instalar dependencias
   ```

4. **Iniciar el servidor de desarrollo**
   ```bash
   # Para desarrollo con PHP integrado
   php -S localhost:8000 -t .
   ```

## Documentación de la API

Los endpoints principales de la API incluyen:

- `POST /api/auth/register` - Registro de usuarios
- `POST /api/auth/login` - Inicio de sesión
- `GET /api/doctors` - Lista de médicos
- `POST /api/appointments` - Crear cita
- `GET /api/appointments` - Listar citas

Para más detalles, consulta la [documentación completa de la API](docs/API.md).

## Desarrollo

### Estructura del Código

- **Frontend**: HTML5, CSS3, JavaScript puro
- **Backend**: PHP nativo con programación orientada a objetos
- **Base de datos**: MySQL con tablas relacionales

### Convenciones de Código
- Usar nombres descriptivos para variables y funciones
- Comentar el código según sea necesario
- Seguir el estándar PSR-12 para PHP

## Despliegue

1. Configurar las variables de entorno en producción
2. Configurar el servidor web para que apunte a la carpeta pública
3. Asegurarse de que los permisos de archivos sean correctos
4. Configurar HTTPS para conexiones seguras

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

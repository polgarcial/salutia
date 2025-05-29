// Funciones para gestionar la autenticación y sesión de usuario

// Verificar si hay una sesión activa
function checkSession() {
    const token = localStorage.getItem('token');
    const userId = localStorage.getItem('user_id');
    const userName = localStorage.getItem('user_name');
    const userRole = localStorage.getItem('user_role');
    
    if (token && userId) {
        return {
            isLoggedIn: true,
            userId: userId,
            userName: userName,
            userRole: userRole
        };
    }
    
    return { isLoggedIn: false };
}

// Mostrar información del usuario logueado
function updateUserInterface() {
    const sessionInfo = checkSession();
    const loginButton = document.getElementById('loginButton');
    const registerButton = document.getElementById('registerButton');
    const userInfoElement = document.getElementById('userInfo');
    const logoutButton = document.getElementById('logoutButton');
    
    if (sessionInfo.isLoggedIn) {
        // Usuario logueado
        if (loginButton) loginButton.style.display = 'none';
        if (registerButton) registerButton.style.display = 'none';
        
        if (userInfoElement) {
            userInfoElement.style.display = 'block';
            userInfoElement.innerHTML = `
                <span>Bienvenido, ${sessionInfo.userName || 'Usuario'}</span>
                <button id="logoutButton" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            `;
            
            // Añadir evento para cerrar sesión
            document.getElementById('logoutButton').addEventListener('click', logout);
        }
        
        // Eliminar mensajes de advertencia de sesión
        const sessionWarnings = document.querySelectorAll('.session-warning');
        sessionWarnings.forEach(warning => {
            warning.style.display = 'none';
        });
        
        return true;
    } else {
        // Usuario no logueado
        if (loginButton) loginButton.style.display = 'inline-block';
        if (registerButton) registerButton.style.display = 'inline-block';
        if (userInfoElement) userInfoElement.style.display = 'none';
        
        // Mostrar mensajes de advertencia de sesión
        const sessionWarnings = document.querySelectorAll('.session-warning');
        sessionWarnings.forEach(warning => {
            warning.style.display = 'block';
        });
        
        return false;
    }
}

// Iniciar sesión
async function login(email, password) {
    try {
        const response = await fetch('backend/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Guardar información de sesión
            localStorage.setItem('token', data.token);
            localStorage.setItem('user_id', data.user.id);
            localStorage.setItem('user_name', `${data.user.first_name} ${data.user.last_name}`);
            localStorage.setItem('user_role', data.user.role);
            
            // Actualizar interfaz
            updateUserInterface();
            
            return { success: true, message: 'Sesión iniciada correctamente' };
        } else {
            return { success: false, message: data.error || 'Error al iniciar sesión' };
        }
    } catch (error) {
        console.error('Error en login:', error);
        return { success: false, message: 'Error de conexión al servidor' };
    }
}

// Cerrar sesión
function logout() {
    // Eliminar información de sesión
    localStorage.removeItem('token');
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_name');
    localStorage.removeItem('user_role');
    
    // Actualizar interfaz
    updateUserInterface();
    
    // Redirigir a la página principal
    window.location.href = 'index.html';
}

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión y actualizar interfaz
    updateUserInterface();
});

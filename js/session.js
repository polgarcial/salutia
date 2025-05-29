// Sistema centralizado de gestión de sesiones para Salutia
// Este archivo debe incluirse en todas las páginas que requieran autenticación

// Objeto global para la sesión
const SalutiaSession = {
    // Datos de la sesión
    token: null,
    userId: null,
    userName: null,
    userRole: null,
    isLoggedIn: false,
    
    // Inicializar la sesión
    init: function() {
        console.log('Inicializando sistema de sesión de Salutia...');
        this.loadFromStorage();
        this.updateUI();
        
        // Configurar eventos para cerrar sesión
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'logoutButton') {
                SalutiaSession.logout();
            }
        });
    },
    
    // Cargar datos de sesión desde localStorage
    loadFromStorage: function() {
        try {
            this.token = localStorage.getItem('token');
            this.userId = localStorage.getItem('user_id');
            this.userName = localStorage.getItem('user_name');
            this.userRole = localStorage.getItem('user_role');
            
            this.isLoggedIn = !!(this.token && this.userId);
            
            console.log('Estado de sesión:', this.isLoggedIn ? 'Activa' : 'Inactiva');
            if (this.isLoggedIn) {
                console.log('Usuario:', this.userName, '(', this.userRole, ')');
            }
        } catch (e) {
            console.error('Error al cargar datos de sesión:', e);
            this.isLoggedIn = false;
        }
    },
    
    // Guardar datos de sesión en localStorage
    saveToStorage: function() {
        try {
            if (this.isLoggedIn) {
                localStorage.setItem('token', this.token);
                localStorage.setItem('user_id', this.userId);
                localStorage.setItem('user_name', this.userName);
                localStorage.setItem('user_role', this.userRole);
                console.log('Datos de sesión guardados correctamente');
            } else {
                this.clearStorage();
            }
        } catch (e) {
            console.error('Error al guardar datos de sesión:', e);
        }
    },
    
    // Limpiar datos de sesión del localStorage
    clearStorage: function() {
        try {
            localStorage.removeItem('token');
            localStorage.removeItem('user_id');
            localStorage.removeItem('user_name');
            localStorage.removeItem('user_role');
            console.log('Datos de sesión eliminados correctamente');
        } catch (e) {
            console.error('Error al limpiar datos de sesión:', e);
        }
    },
    
    // Actualizar la interfaz de usuario según el estado de la sesión
    updateUI: function() {
        // Elementos comunes en todas las páginas
        const loginButton = document.querySelector('.login-button, #loginButton, [data-action="login"]');
        const registerButton = document.querySelector('.register-button, #registerButton, [data-action="register"]');
        const userInfoElement = document.querySelector('.user-info, #userInfo, .user-info-bar');
        const sessionWarnings = document.querySelectorAll('.session-warning, .login-required');
        
        // Actualizar elementos si existen
        if (this.isLoggedIn) {
            // Usuario logueado
            if (loginButton) loginButton.style.display = 'none';
            if (registerButton) registerButton.style.display = 'none';
            
            if (userInfoElement) {
                userInfoElement.style.display = 'block';
                userInfoElement.innerHTML = `
                    <span>Bienvenido, ${this.userName || 'Usuario'}</span>
                    <span class="user-role">${this.userRole === 'doctor' ? 'Médico' : 'Paciente'}</span>
                    <button id="logoutButton" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </button>
                `;
            }
            
            // Ocultar advertencias de sesión
            sessionWarnings.forEach(warning => {
                if (warning) warning.style.display = 'none';
            });
            
            // Elementos específicos de la página de citas
            const userSelection = document.querySelector('.user-selection');
            if (userSelection) userSelection.style.display = 'none';
            
            // Notificar a la página que hay una sesión activa
            document.dispatchEvent(new CustomEvent('salutia:session-ready', { 
                detail: { 
                    isLoggedIn: true,
                    userId: this.userId,
                    userName: this.userName,
                    userRole: this.userRole
                } 
            }));
        } else {
            // Usuario no logueado
            if (loginButton) loginButton.style.display = 'inline-block';
            if (registerButton) registerButton.style.display = 'inline-block';
            if (userInfoElement) userInfoElement.style.display = 'none';
            
            // Mostrar advertencias de sesión
            sessionWarnings.forEach(warning => {
                if (warning) warning.style.display = 'block';
            });
            
            // Notificar a la página que no hay sesión activa
            document.dispatchEvent(new CustomEvent('salutia:session-ready', { 
                detail: { isLoggedIn: false } 
            }));
        }
    },
    
    // Iniciar sesión
    login: function(userData) {
        this.token = userData.token || 'default-token';
        this.userId = userData.id || userData.user_id;
        this.userName = userData.name || `${userData.first_name} ${userData.last_name}`;
        this.userRole = userData.role || 'patient';
        this.isLoggedIn = true;
        
        this.saveToStorage();
        this.updateUI();
        
        console.log('Sesión iniciada correctamente');
        return true;
    },
    
    // Cerrar sesión
    logout: function() {
        this.token = null;
        this.userId = null;
        this.userName = null;
        this.userRole = null;
        this.isLoggedIn = false;
        
        this.clearStorage();
        this.updateUI();
        
        console.log('Sesión cerrada correctamente');
        
        // Redirigir a la página principal
        window.location.href = 'index.html';
        return true;
    },
    
    // Verificar si hay una sesión activa
    checkSession: function() {
        return {
            isLoggedIn: this.isLoggedIn,
            userId: this.userId,
            userName: this.userName,
            userRole: this.userRole
        };
    }
};

// Inicializar el sistema de sesión inmediatamente
SalutiaSession.init();

// Y también cuando el DOM esté listo para asegurar que la UI se actualice
document.addEventListener('DOMContentLoaded', function() {
    SalutiaSession.updateUI();
    
    // Forzar la actualización de la interfaz después de un breve retraso
    // para asegurar que todos los elementos del DOM estén disponibles
    setTimeout(() => {
        SalutiaSession.updateUI();
    }, 500);
});

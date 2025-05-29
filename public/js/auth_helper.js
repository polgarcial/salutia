/**
 * Script de ayuda para la autenticación en Salutia
 * Este script proporciona funciones para manejar la autenticación y almacenamiento de datos de sesión
 */

const AuthHelper = {
    // Establecer datos de sesión para el médico
    setDoctorSession: function(doctorId, token) {
        localStorage.setItem('user_id', doctorId);
        localStorage.setItem('user_role', 'doctor');
        localStorage.setItem('token', token);
        console.log('Sesión de médico establecida correctamente');
    },
    
    // Limpiar datos de sesión
    clearSession: function() {
        localStorage.removeItem('user_id');
        localStorage.removeItem('user_role');
        localStorage.removeItem('token');
        console.log('Sesión cerrada correctamente');
    },
    
    // Verificar si hay una sesión activa
    isLoggedIn: function() {
        return localStorage.getItem('token') !== null;
    },
    
    // Obtener el rol del usuario actual
    getUserRole: function() {
        return localStorage.getItem('user_role');
    },
    
    // Obtener el ID del usuario actual
    getUserId: function() {
        return localStorage.getItem('user_id');
    }
};

// Establecer datos de sesión de prueba para el médico
// Esto es solo para desarrollo y debe eliminarse en producción
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si estamos en la página del dashboard del médico
    if (window.location.href.includes('doctor_dashboard.html')) {
        // Si no hay sesión activa, establecer datos de prueba
        if (!AuthHelper.isLoggedIn()) {
            // Datos de prueba para el médico
            const doctorId = 1; // ID del médico (Dr. Juan Pérez)
            const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJyb2xlIjoiZG9jdG9yIiwibmFtZSI6IkRyLiBKdWFuIFBlcmV6IiwiaWF0IjoxNjE2MTYyMjIwLCJleHAiOjE2MTYyNDg2MjB9.3f4QIuO-CVIQZJWCe0diCmgbpJXJZxsRlVK4rr1Bvdk';
            
            // Establecer datos de sesión
            AuthHelper.setDoctorSession(doctorId, token);
            console.log('Datos de sesión de prueba establecidos para el médico');
        }
    }
});

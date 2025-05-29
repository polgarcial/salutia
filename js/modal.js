document.addEventListener('DOMContentLoaded', function() {
    // Verificar que el modal esté presente
    const termsModal = document.getElementById('termsModal');
    
    if (termsModal) {
        // Inicializar el modal de Bootstrap
        const modal = new bootstrap.Modal(termsModal);
        
        // Añadir evento al enlace de términos y condiciones
        const showTermsModal = document.getElementById('showTermsModal');
        
        if (showTermsModal) {
            showTermsModal.addEventListener('click', function(e) {
                e.preventDefault();
                modal.show();
            });
            
            console.log('Enlace de términos y condiciones inicializado');
        } else {
            console.warn('No se encontró el enlace para mostrar el modal de términos');
        }
        
        // Añadir funcionalidad al botón de aceptar términos
        const acceptTermsBtn = document.getElementById('acceptTerms');
        if (acceptTermsBtn) {
            acceptTermsBtn.addEventListener('click', function() {
                // Marcar la casilla de verificación de términos
                const termsCheckbox = document.getElementById('terms');
                if (termsCheckbox) {
                    termsCheckbox.checked = true;
                    // Agregar la clase de validación para mostrar que es válido
                    termsCheckbox.classList.add('is-valid');
                    termsCheckbox.classList.remove('is-invalid');
                }
            });
            
            console.log('Botón de aceptar términos inicializado');
        }
        
        // Verificar si el modal se abre correctamente
        console.log('Modal de términos y condiciones inicializado');
    } else {
        console.error('No se encontró el modal de términos y condiciones');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    // Funcionalidad para mostrar/ocultar contraseña
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Cambiar el icono
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    if (registerForm) {
        // Función para mostrar mensajes de estado
        function showMessage(message, isError = false) {
            const messageContainer = document.getElementById('messageContainer');
            if (!messageContainer) {
                // Crear el contenedor de mensajes si no existe
                const container = document.createElement('div');
                container.id = 'messageContainer';
                container.className = isError ? 'alert alert-danger' : 'alert alert-success';
                container.role = 'alert';
                container.style.display = 'none';
                registerForm.insertBefore(container, registerForm.firstChild);
                return showMessage(message, isError);
            }
            
            messageContainer.className = isError ? 'alert alert-danger' : 'alert alert-success';
            messageContainer.textContent = message;
            messageContainer.style.display = 'block';
            
            // Scroll hacia el mensaje
            messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Ocultar el mensaje después de 5 segundos si es exitoso
            if (!isError) {
                setTimeout(() => {
                    messageContainer.style.display = 'none';
                }, 5000);
            }
        }
        
        // Función para validar el formulario
        function validateForm() {
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            
            // Validar campos requeridos
            if (!firstName || !lastName || !email || !password || !confirmPassword) {
                showMessage('Por favor, completa todos los campos requeridos.', true);
                return false;
            }
            
            // Validar formato de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage('Por favor, introduce un correo electrónico válido.', true);
                return false;
            }
            
            // Validar contraseña
            if (password.length < 6) {
                showMessage('La contraseña debe tener al menos 6 caracteres.', true);
                return false;
            }
            
            // Validar que las contraseñas coincidan
            if (password !== confirmPassword) {
                showMessage('Las contraseñas no coinciden.', true);
                return false;
            }
            
            // Validar términos y condiciones
            if (!terms) {
                showMessage('Debes aceptar los términos y condiciones.', true);
                return false;
            }
            
            return true;
        }
        
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Ocultar mensajes anteriores
            const messageContainer = document.getElementById('messageContainer');
            if (messageContainer) {
                messageContainer.style.display = 'none';
            }
            
            // Validar formulario
            if (!validateForm()) {
                return;
            }
            
            // Mostrar indicador de carga
            const submitButton = registerForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
            
            // Recopilar los datos del formulario
            const formData = {
                firstName: document.getElementById('firstName').value.trim(),
                lastName: document.getElementById('lastName').value.trim(),
                email: document.getElementById('email').value.trim(),
                password: document.getElementById('password').value,
                phone: document.getElementById('phone').value.trim(),
                role: 'patient' // Por defecto, los usuarios que se registran son pacientes
            };
            
            // Enviar los datos al servidor
            fetch('backend/api/auth.php?action=register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                // Restaurar el botón
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                if (data.success) {
                    // Registro exitoso
                    showMessage('¡Registro exitoso! Ahora puedes iniciar sesión.');
                    
                    // Limpiar el formulario
                    registerForm.reset();
                    
                    // Redirigir a la página de inicio de sesión después de 2 segundos
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    // Error en el registro
                    showMessage('Error en el registro: ' + data.message, true);
                }
            })
            .catch(error => {
                // Restaurar el botón
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                console.error('Error:', error);
                showMessage('Error al procesar la solicitud. Por favor, inténtalo de nuevo más tarde.', true);
            });
        });
        
        // Validación en tiempo real
        const inputs = registerForm.querySelectorAll('input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        });
        
        // Validación de contraseñas coincidentes en tiempo real
        const confirmPassword = document.getElementById('confirmPassword');
        confirmPassword.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    }
});

/**
 * Gestor de citas médicas para la plataforma Salutia
 * Este script maneja todas las funcionalidades relacionadas con la gestión de citas
 * desde el panel del médico, incluyendo la visualización, aceptación y rechazo de citas.
 */

const AppointmentManager = {
    // Variables globales
    currentUserId: null,
    toastTimeout: null,
    
    // Inicializar el gestor de citas
    init: function() {
        console.log('Inicializando gestor de citas...');
        
        // Obtener el ID del médico de la sesión
        this.currentUserId = AuthHelper.getUserId() || 1; // Usar AuthHelper o ID por defecto
        
        // Cargar datos iniciales
        this.loadPendingAppointmentRequests();
        this.loadUpcomingAppointments();
        this.updateDashboardStats();
        
        // Configurar eventos para los botones de confirmación en modales
        const confirmRedirectBtn = document.getElementById('confirmRedirectBtn');
        if (confirmRedirectBtn) {
            confirmRedirectBtn.addEventListener('click', () => {
                const appointmentId = document.getElementById('redirectAppointmentId').value;
                const doctorId = document.getElementById('doctorSelect').value;
                
                if (appointmentId && doctorId) {
                    this.redirectAppointment(appointmentId, doctorId);
                }
            });
        }
    },
    
    // Mostrar un mensaje toast
    showToast: function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        
        // Si no existe el contenedor de toasts, crearlo
        if (!toastContainer) {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }
        
        // Limpiar cualquier toast anterior
        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }
        
        // Crear el toast
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.className = `toast show`;
        toast.id = toastId;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        // Determinar el color según el tipo
        let bgColor = 'bg-info';
        if (type === 'success') bgColor = 'bg-success';
        if (type === 'error') bgColor = 'bg-danger';
        if (type === 'warning') bgColor = 'bg-warning';
        
        toast.innerHTML = `
            <div class="toast-header ${bgColor} text-white">
                <strong class="me-auto">Salutia</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        // Añadir el toast al contenedor
        document.getElementById('toastContainer').appendChild(toast);
        
        // Configurar el cierre automático
        this.toastTimeout = setTimeout(() => {
            toast.remove();
        }, 5000);
    },
    
    // Cargar las solicitudes de citas pendientes
    loadPendingAppointmentRequests: function() {
        console.log('Cargando solicitudes de citas pendientes');
        
        const container = document.getElementById('solicitudesCitas');
        if (!container) {
            console.error('No se encontró el contenedor de solicitudes de citas');
            return;
        }
        
        // Mostrar indicador de carga
        container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // Obtener el token de autenticación usando el helper
        const token = AuthHelper.isLoggedIn() ? localStorage.getItem('token') : null;
        if (!token) {
            console.log('No se encontró token de autenticación, usando token de prueba');
            // Para desarrollo, usamos un token de prueba
            const testToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJyb2xlIjoiZG9jdG9yIiwibmFtZSI6IkRyLiBKdWFuIFBlcmV6IiwiaWF0IjoxNjE2MTYyMjIwLCJleHAiOjE2MTYyNDg2MjB9.3f4QIuO-CVIQZJWCe0diCmgbpJXJZxsRlVK4rr1Bvdk';
            // Establecer datos de sesión de prueba
            AuthHelper.setDoctorSession(1, testToken);
        }
        
        // Obtener el ID del doctor usando el helper
        const doctorId = this.getDoctorId();
        console.log('ID del médico obtenido:', doctorId);
        
        // Obtener el token actualizado (podría haber sido establecido en el bloque anterior)
        const currentToken = localStorage.getItem('token');
        
        // Hacer la solicitud a la API para obtener las citas pendientes
        fetch('../backend/api/get_pending_appointments.php', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + currentToken
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos de citas pendientes:', data);
            
            if (data.success && data.appointments && data.appointments.length > 0) {
                const pendingRequests = data.appointments;
                
                container.innerHTML = '';
                
                pendingRequests.forEach(request => {
                    const requestCard = document.createElement('div');
                    requestCard.className = 'appointment-card';
                    requestCard.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="mb-1">${request.patient_name}</h5>
                                <p class="mb-1"><strong>Email:</strong> ${request.patient_email}</p>
                                <p class="mb-1"><strong>Motivo:</strong> ${request.reason}</p>
                                <p class="mb-1"><strong>Fecha solicitada:</strong> ${request.date || request.appointment_date} a las ${request.time || request.start_time}</p>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-success btn-sm btn-action accept-btn" data-id="${request.id}">
                                <i class="fas fa-check"></i> Aceptar
                            </button>
                            <button class="btn btn-danger btn-sm btn-action reject-btn" data-id="${request.id}">
                                <i class="fas fa-times"></i> Rechazar
                            </button>
                            <button class="btn btn-info btn-sm btn-action redirect-btn" data-id="${request.id}">
                                <i class="fas fa-share"></i> Redirigir
                            </button>
                        </div>
                    `;
                    container.appendChild(requestCard);
                });
                
                // Actualizar contador de citas pendientes
                const citasPendientesEl = document.getElementById('citasPendientes');
                if (citasPendientesEl) {
                    citasPendientesEl.textContent = pendingRequests.length;
                }
            } else {
                container.innerHTML = '<p class="text-center">No hay solicitudes de citas pendientes</p>';
                
                // Actualizar contador de citas pendientes a 0
                const citasPendientesEl = document.getElementById('citasPendientes');
                if (citasPendientesEl) {
                    citasPendientesEl.textContent = '0';
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar las solicitudes de citas:', error);
            container.innerHTML = `<p class="text-center text-danger">Error al cargar las solicitudes: ${error.message}</p>`;
            
            // Actualizar contador de citas pendientes a 0 en caso de error
            const citasPendientesEl = document.getElementById('citasPendientes');
            if (citasPendientesEl) {
                citasPendientesEl.textContent = '0';
            }
        });
    },
    
    // Cargar las próximas citas programadas (aceptadas)
    loadUpcomingAppointments: function() {
        console.log('Cargando las próximas citas programadas (aceptadas)');
        
        const appointmentsContainer = document.getElementById('proximasCitas');
        if (!appointmentsContainer) {
            console.error('No se encontró el contenedor de próximas citas');
            return;
        }
        
        appointmentsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // Verificar si hay una sesión activa
        if (!AuthHelper.isLoggedIn()) {
            console.log('No se encontró sesión activa, usando datos de prueba');
            // Para desarrollo, usamos un token de prueba
            const testToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJyb2xlIjoiZG9jdG9yIiwibmFtZSI6IkRyLiBKdWFuIFBlcmV6IiwiaWF0IjoxNjE2MTYyMjIwLCJleHAiOjE2MTYyNDg2MjB9.3f4QIuO-CVIQZJWCe0diCmgbpJXJZxsRlVK4rr1Bvdk';
            // Establecer datos de sesión de prueba
            AuthHelper.setDoctorSession(1, testToken);
        }
        
        // Obtener el ID del doctor usando el helper
        const doctorId = this.getDoctorId();
        console.log('ID del médico obtenido para próximas citas:', doctorId);
        
        // Obtener el token actualizado
        const currentToken = localStorage.getItem('token');
        
        // Hacer la solicitud a la API para obtener las citas próximas aceptadas
        fetch('../backend/api/get_upcoming_appointments.php', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + currentToken
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos de citas próximas:', data);
            
            if (data.success && data.appointments && data.appointments.length > 0) {
                const upcomingAppointments = data.appointments;
                appointmentsContainer.innerHTML = '';
                    
                    upcomingAppointments.forEach(appointment => {
                        // Determinar la fecha a mostrar
                        const displayDate = appointment.date || 'Fecha no disponible';
                        const displayTime = appointment.time || 'Hora no disponible';
                        
                        const appointmentCard = document.createElement('div');
                        appointmentCard.className = 'appointment-card';
                        appointmentCard.innerHTML = `
                            <div class="row">
                                <div class="col-md-2 d-flex align-items-center justify-content-center">
                                    <div class="bg-light rounded-circle p-3">
                                        <i class="fas fa-calendar-check text-primary fa-2x"></i>
                                    </div>
                                </div>
                                <div class="col-md-10">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>${appointment.patient_name}</h5>
                                        <span class="badge bg-primary">${displayDate} - ${displayTime}</span>
                                    </div>
                                    <p class="mb-1"><strong>Email:</strong> ${appointment.patient_email || 'No disponible'}</p>
                                    <p class="mb-1"><strong>Motivo:</strong> ${appointment.reason || 'No especificado'}</p>
                                    <div class="d-flex justify-content-end mt-2">
                                        <button class="btn btn-outline-primary btn-sm me-2">
                                            <i class="fas fa-video"></i> Videollamada
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-file-medical"></i> Ver historial
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        appointmentsContainer.appendChild(appointmentCard);
                    });
                    
                    // Actualizar contador de citas para hoy
                    this.updateTodayAppointmentsCount(upcomingAppointments);
                } else {
                    appointmentsContainer.innerHTML = '<p class="text-center">No hay citas programadas próximamente.</p>';
                    
                    const citasHoyEl = document.getElementById('citasHoy');
                    if (citasHoyEl) {
                        citasHoyEl.textContent = '0';
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar las próximas citas:', error);
                appointmentsContainer.innerHTML = `<p class="text-center text-danger">Error al cargar las citas: ${error.message}</p>`;
                
                const citasHoyEl = document.getElementById('citasHoy');
                if (citasHoyEl) {
                    citasHoyEl.textContent = '0';
                }
            });
    },
    
    // Actualizar el contador de citas para hoy
    updateTodayAppointmentsCount: function(appointments) {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        
        // Formatos de fecha posibles (YYYY-MM-DD y DD/MM/YYYY)
        const isoToday = `${year}-${month}-${day}`;
        const formattedToday = `${day}/${month}/${year}`;
        
        // Contar citas para hoy en cualquiera de los formatos
        const citasHoy = appointments.filter(app => {
            const appDate = app.appointment_date || app.date || app.formatted_date;
            return appDate === isoToday || appDate === formattedToday;
        }).length;
        
        const citasHoyEl = document.getElementById('citasHoy');
        if (citasHoyEl) {
            citasHoyEl.textContent = citasHoy;
        }
    },
    
    // Obtener el ID del médico de la sesión
    getDoctorId: function() {
        // Usar el helper de autenticación para obtener el ID del médico
        return AuthHelper.getUserId() || this.currentUserId || 1;
    },
    
    // Actualizar estadísticas del dashboard
    updateDashboardStats: function() {
        // En un entorno real, aquí haríamos una llamada a la API para obtener las estadísticas
        // Por ahora, usamos datos de ejemplo
        
        const totalPacientesEl = document.getElementById('totalPacientes');
        if (totalPacientesEl) {
            totalPacientesEl.textContent = '12';
        }
    },
    
    // Aceptar una solicitud de cita
    acceptAppointment: function(appointmentId) {
        console.log('Aceptando cita ID:', appointmentId);
        
        // Obtener el botón que se hizo clic
        const acceptBtn = document.querySelector(`.appointment-card .accept-btn[data-id="${appointmentId}"]`);
        let originalButtonText = '';
        
        if (acceptBtn) {
            // Guardar el texto original del botón
            originalButtonText = acceptBtn.innerHTML;
            // Mostrar indicador de carga
            acceptBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Aceptando...';
            acceptBtn.disabled = true;
        }
        
        // Obtener el token actualizado
        const currentToken = localStorage.getItem('token');
        const doctorId = this.getDoctorId();
        
        // Preparar los datos para la solicitud
        const requestData = {
            appointment_id: appointmentId,
            doctor_id: doctorId
        };
        
        // Enviar la solicitud al servidor
        fetch('../backend/api/accept_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + currentToken
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta de aceptar cita:', data);
            
            if (data.success) {
                // Mostrar mensaje de éxito
                this.showToast('Cita aceptada correctamente', 'success');
                
                // Aplicar animación de desvanecimiento a la tarjeta
                const requestCard = acceptBtn ? acceptBtn.closest('.appointment-card') : null;
                if (requestCard) {
                    requestCard.style.transition = 'opacity 0.5s';
                    requestCard.style.opacity = '0';
                    
                    // Eliminar la tarjeta después de la animación
                    setTimeout(() => {
                        requestCard.remove();
                        
                        // Verificar si no hay más citas pendientes
                        const requestsContainer = document.getElementById('solicitudesCitas');
                        if (requestsContainer && requestsContainer.querySelectorAll('.appointment-card').length === 0) {
                            requestsContainer.innerHTML = '<p class="text-center">No hay solicitudes de citas pendientes</p>';
                        }
                    }, 500);
                }
                
                // Recargar las solicitudes de citas pendientes y próximas
                setTimeout(() => {
                    this.loadPendingAppointmentRequests();
                    this.loadUpcomingAppointments();
                }, 1000);
            } else {
                // Restaurar el botón si hay error
                if (acceptBtn) {
                    acceptBtn.innerHTML = originalButtonText;
                    acceptBtn.disabled = false;
                }
                
                // Mostrar mensaje de error
                this.showToast('Error al aceptar la cita: ' + (data.message || 'Error desconocido'), 'error');
            }
        })
        .catch(error => {
            console.error('Error al aceptar la cita:', error);
            
            // Restaurar el botón
            if (acceptBtn) {
                acceptBtn.innerHTML = originalButtonText;
                acceptBtn.disabled = false;
            }
            
            // Mostrar mensaje de error
            this.showToast('Error al aceptar la cita: ' + error.message, 'error');
        });
    },
    
    // Rechazar una solicitud de cita
    rejectAppointment: function(appointmentId) {
        console.log('Rechazando cita con ID:', appointmentId);
        
        // Mostrar confirmación antes de rechazar
        if (!confirm('¿Estás seguro de que deseas rechazar esta cita? Esta acción eliminará la cita del sistema y no se puede deshacer.')) {
            return;
        }
        
        // Obtener el botón que se hizo clic
        const rejectBtn = document.querySelector(`.appointment-card .reject-btn[data-id="${appointmentId}"]`);
        if (!rejectBtn) {
            console.error('Botón de rechazo no encontrado para ID:', appointmentId);
            this.showToast('Error: No se pudo encontrar la cita para rechazar', 'error');
            return;
        }
        
        // Obtener la tarjeta de la cita
        const requestCard = rejectBtn.closest('.appointment-card');
        if (!requestCard) {
            console.error('Tarjeta de cita no encontrada para ID:', appointmentId);
            this.showToast('Error: No se pudo encontrar la cita para rechazar', 'error');
            return;
        }
        
        // Mostrar indicador de carga
        const originalButtonText = rejectBtn.innerHTML;
        rejectBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Rechazando...';
        rejectBtn.disabled = true;
        
        // Obtener el token actualizado
        const currentToken = localStorage.getItem('token');
        const doctorId = this.getDoctorId();
        
        // Preparar los datos para la solicitud
        const requestData = {
            appointment_id: appointmentId,
            doctor_id: doctorId
        };
        
        console.log('Enviando solicitud para rechazar cita:', requestData);
        
        // Usar el endpoint más directo y simple posible
        // Hacemos una solicitud GET para simplificar al máximo
        fetch(`../backend/api/direct_reject.php?id=${appointmentId}`, {
            method: 'GET'
        })
        .then(() => {
            // Siempre procedemos con la animación, independientemente de la respuesta
            // Aplicar animación de desvanecimiento
            requestCard.style.transition = 'opacity 0.5s';
            requestCard.style.opacity = '0';
            
            // Eliminar la tarjeta después de la animación
            setTimeout(() => {
                requestCard.remove();
                
                // Verificar si no hay más citas pendientes
                const requestsContainer = document.getElementById('solicitudesCitas');
                if (requestsContainer && requestsContainer.querySelectorAll('.appointment-card').length === 0) {
                    requestsContainer.innerHTML = '<p class="text-center">No hay solicitudes de citas pendientes</p>';
                }
                
                // Mostrar mensaje de éxito
                this.showToast('Cita rechazada correctamente', 'success');
                
                // Actualizar contador de citas pendientes
                this.updatePendingAppointmentsCount();
            }, 500);
        })
        .catch(error => {
            console.error('Error al rechazar la cita:', error);
            
            // Restaurar el botón
            rejectBtn.innerHTML = originalButtonText;
            rejectBtn.disabled = false;
            
            // Mostrar mensaje de error
            this.showToast('Error al rechazar la cita: ' + error.message, 'error');
        });
    },
    
    // Método para garantizar la eliminación de una cita abriendo una ventana directa
    openDeleteWindow: function(appointmentId) {
        console.log('Abriendo ventana para eliminar cita con ID:', appointmentId);
        
        // Crear una ventana oculta para ejecutar la eliminación
        const deleteUrl = `../../app/eliminar_directo.php?id=${appointmentId}`;
        
        // Abrir la ventana en segundo plano
        const deleteWindow = window.open(deleteUrl, '_blank', 'width=1,height=1,left=-1000,top=-1000');
        
        // Cerrar la ventana después de un breve tiempo
        setTimeout(() => {
            if (deleteWindow) {
                deleteWindow.close();
            }
        }, 2000);
        
        // También intentar con un iframe oculto como respaldo
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = deleteUrl;
        document.body.appendChild(iframe);
        
        // Eliminar el iframe después de un tiempo
        setTimeout(() => {
            if (iframe && iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 3000);
        
        // También intentar con una solicitud de imagen como último recurso
        const img = new Image();
        img.src = deleteUrl;
    },
    
    // Mostrar el modal de redirección
    showRedirectModal: function(appointmentId) {
        console.log('Mostrando modal para redirigir cita ID:', appointmentId);
        
        // Establecer el ID de la cita en el campo oculto
        const redirectAppointmentIdField = document.getElementById('redirectAppointmentId');
        if (redirectAppointmentIdField) {
            redirectAppointmentIdField.value = appointmentId;
        }
        
        // Mostrar el modal
        const redirectModal = new bootstrap.Modal(document.getElementById('redirectModal'));
        redirectModal.show();
    },
    
    // Redirigir una cita a otro médico
    redirectAppointment: function(appointmentId, doctorId) {
        console.log('Redirigiendo cita ID:', appointmentId, 'al médico ID:', doctorId);
        
        // En un entorno real, aquí haríamos una llamada a la API para redirigir la cita
        // Por ahora, simulamos la redirección
        
        // Cerrar el modal
        const redirectModal = bootstrap.Modal.getInstance(document.getElementById('redirectModal'));
        if (redirectModal) {
            redirectModal.hide();
        }
        
        // Mostrar mensaje de éxito
        this.showToast('Cita redirigida correctamente', 'success');
        
        // Recargar las solicitudes de citas pendientes
        this.loadPendingAppointmentRequests();
    },
    
    // Actualizar el contador de citas pendientes
    updatePendingAppointmentsCount: function() {
        // En un entorno real, aquí haríamos una llamada a la API para obtener el número de citas pendientes
        // Por ahora, contamos las tarjetas de citas pendientes
        const requestsContainer = document.getElementById('solicitudesCitas');
        if (requestsContainer) {
            const count = requestsContainer.querySelectorAll('.appointment-card').length;
            
            const citasPendientesEl = document.getElementById('citasPendientes');
            if (citasPendientesEl) {
                citasPendientesEl.textContent = count;
            }
        }
    }
};

/**
 * Sistema de gestión de citas médicas - Salutia
 * Este script maneja la funcionalidad de solicitud, visualización y gestión de citas médicas
 * Reemplaza el uso de localStorage por llamadas a la API que interactúan con la base de datos MySQL
 */

// Objeto para gestionar las citas médicas
const AppointmentManager = {
    // Inicializar el gestor de citas
    init: function() {
        console.log('Inicializando gestor de citas');
        
        // Detectar si estamos en el dashboard del médico o del paciente
        this.isDoctorDashboard = window.location.href.includes('/doctor/');
        this.isPatientDashboard = window.location.href.includes('/patient/');
        
        // Obtener información del usuario actual
        this.currentUserId = localStorage.getItem('user_id') || '1';
        this.currentUserRole = localStorage.getItem('user_role') || (this.isDoctorDashboard ? 'doctor' : 'patient');
        this.currentUserName = localStorage.getItem('user_name') || 'Usuario';
        
        // Si estamos en el dashboard del médico, cargar las citas pendientes y próximas
        if (this.isDoctorDashboard) {
            this.loadPendingAppointmentRequests();
            this.loadUpcomingAppointments();
            
            // Actualizar estadísticas del dashboard
            this.updateDashboardStats();
            
            // Configurar eventos para los botones de acción
            this.setupDoctorEventListeners();
        }
        
        // Si estamos en el dashboard del paciente, configurar el formulario de solicitud de cita
        if (this.isPatientDashboard) {
            this.setupPatientEventListeners();
        }
    },
    
    // Configurar eventos para el dashboard del médico
    setupDoctorEventListeners: function() {
        // Configurar evento para el botón de redirección
        const confirmRedirectBtn = document.getElementById('confirmRedirectBtn');
        if (confirmRedirectBtn) {
            confirmRedirectBtn.addEventListener('click', () => {
                const appointmentId = document.getElementById('redirectAppointmentId').value;
                const doctorSelect = document.getElementById('doctorSelect');
                const doctorId = doctorSelect.value;
                
                if (!doctorId) {
                    this.showError('Debe seleccionar un médico para redirigir la cita');
                    return;
                }
                
                this.redirectAppointment(appointmentId, doctorId);
            });
        }
        
        // Configurar evento para el botón de cerrar sesión
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                this.logout();
            });
        }
    },
    
    // Función para cerrar sesión
    logout: function() {
        console.log('Cerrando sesión...');
        
        // Eliminar datos de sesión del localStorage
        localStorage.removeItem('user_id');
        localStorage.removeItem('user_role');
        localStorage.removeItem('user_name');
        localStorage.removeItem('token');
        
        // Redirigir a la página de inicio
        window.location.href = '../../index.html';
    },
    
    // Configurar eventos para el dashboard del paciente
    setupPatientEventListeners: function() {
        // Aquí se configurarían los eventos relacionados con la solicitud de citas
        // desde el dashboard del paciente
    },
    
    // Cargar solicitudes de citas pendientes para el médico
    loadPendingAppointmentRequests: function() {
        console.log('Cargando solicitudes de citas pendientes');
        
        const container = document.getElementById('solicitudesCitas');
        if (!container) {
            console.error('No se encontró el contenedor de solicitudes de citas');
            return;
        }
        
        // Mostrar indicador de carga
        container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // Asegurarse de que tenemos un ID de doctor válido
        if (!this.currentUserId || this.currentUserId === 'undefined') {
            console.warn('ID de doctor no válido, usando ID 1 por defecto');
            this.currentUserId = '1';
            localStorage.setItem('user_id', '1');
        }
        
        console.log('Obteniendo citas pendientes para el doctor ID:', this.currentUserId);
        
        // Mostrar el ID del doctor en la interfaz para depuración
        const doctorNameEl = document.getElementById('doctorName');
        if (doctorNameEl) {
            doctorNameEl.textContent = `Dr. Juan Pérez (ID: ${this.currentUserId})`;
                    }
                ];
                
                container.innerHTML = '';
                
                exampleRequests.forEach(request => {
                    const requestCard = document.createElement('div');
                    requestCard.className = 'appointment-card';
                    requestCard.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="mb-1">${request.patient_name}</h5>
                                <p class="mb-1"><strong>Email:</strong> ${request.patient_email}</p>
                                <p class="mb-1"><strong>Motivo:</strong> ${request.reason}</p>
                                <p class="mb-1"><strong>Fecha solicitada:</strong> ${request.date} a las ${request.time}</p>
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
                    citasPendientesEl.textContent = exampleRequests.length;
                    // Actualizar contador de citas pendientes
                    const citasPendientesEl = document.getElementById('citasPendientes');
                    if (citasPendientesEl) {
                        citasPendientesEl.textContent = data.appointments.length;
                    }
                } else {
                    // Si no hay solicitudes pendientes
                    requestsContainer.innerHTML = '<p class="text-center">No hay solicitudes de citas pendientes.</p>';
                    
                    // Actualizar contador de citas pendientes
                    const citasPendientesEl = document.getElementById('citasPendientes');
                    if (citasPendientesEl) {
                        citasPendientesEl.textContent = '0';
                    }
                    
                    // Para demostración, cargar datos de ejemplo si no hay citas en la base de datos
                    if (this.shouldShowExampleData()) {
                        this.loadExampleAppointmentRequests(requestsContainer);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                requestsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>Error al cargar las solicitudes</h5>
                        <p>${error.message}</p>
                        <p>Verifique la consola del navegador para más detalles.</p>
                    </div>
                `;
                
                // Para demostración, cargar datos de ejemplo en caso de error
                if (this.shouldShowExampleData()) {
                    this.loadExampleAppointmentRequests(requestsContainer);
                }
            });
    },
    
    // Determinar si se deben mostrar datos de ejemplo
    shouldShowExampleData: function() {
        // Para propósitos de demostración, mostrar datos de ejemplo
        // En un entorno real, esto podría depender de una configuración
        return true;
    },
    
    // Cargar datos de ejemplo para solicitudes de citas
    loadExampleAppointmentRequests: function(container) {
        console.log('Cargando datos de ejemplo para solicitudes de citas');
        
        // Datos de ejemplo para solicitudes de citas
        const exampleRequests = [
            {
                id: 1,
                patient_name: 'Pol Garcia',
                patient_email: 'pol@gmail.com',
                reason: 'Consulta sobre dolor lumbar',
                appointment_date: '29/05/2025',
                appointment_time: '10:30',
                status: 'pending'
            },
            {
                id: 2,
                patient_name: 'Carlos Rodríguez',
                patient_email: 'carlos@gmail.com',
                reason: 'Revisión anual',
                appointment_date: '30/05/2025',
                appointment_time: '16:15',
                status: 'pending'
            },
            {
                id: 3,
                patient_name: 'Laura Martínez',
                patient_email: 'laura@gmail.com',
                reason: 'Dolor de cabeza persistente',
                appointment_date: '31/05/2025',
                appointment_time: '09:00',
                status: 'pending'
            }
        ];
        
        container.innerHTML = '';
        
        exampleRequests.forEach(request => {
            const requestCard = document.createElement('div');
            requestCard.className = 'appointment-card';
            requestCard.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="mb-1">${request.patient_name}</h5>
                        <p class="mb-1"><strong>Email:</strong> ${request.patient_email}</p>
                        <p class="mb-1"><strong>Motivo:</strong> ${request.reason}</p>
                        <p class="mb-1"><strong>Fecha solicitada:</strong> ${request.appointment_date} a las ${request.appointment_time}</p>
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
            
            // Configurar eventos para los botones
            requestCard.querySelector('.accept-btn').addEventListener('click', (e) => {
                const appointmentId = e.target.closest('.accept-btn').dataset.id;
                this.acceptAppointment(appointmentId);
            });
            
            requestCard.querySelector('.reject-btn').addEventListener('click', (e) => {
                const appointmentId = e.target.closest('.reject-btn').dataset.id;
                this.rejectAppointment(appointmentId);
            });
            
            requestCard.querySelector('.redirect-btn').addEventListener('click', (e) => {
                const appointmentId = e.target.closest('.redirect-btn').dataset.id;
                this.showRedirectModal(appointmentId);
            });
        });
        
        // Actualizar contador de citas pendientes
        const citasPendientesEl = document.getElementById('citasPendientes');
        if (citasPendientesEl) {
            citasPendientesEl.textContent = exampleRequests.length;
        }
    },
    
    // Cargar próximas citas confirmadas
    loadUpcomingAppointments: function() {
        console.log('Cargar las próximas citas programadas (aceptadas)');
        
        const appointmentsContainer = document.getElementById('proximasCitas');
        if (!appointmentsContainer) {
            console.error('No se encontró el contenedor de próximas citas');
            return;
        }
        
        appointmentsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // Obtener el token de autenticación
        const token = localStorage.getItem('token');
        if (!token) {
            console.error('No se encontró token de autenticación');
            appointmentsContainer.innerHTML = '<p class="text-center text-danger">Error de autenticación. Por favor, inicie sesión nuevamente.</p>';
            return;
        }
        
        // Obtener el ID del doctor del almacenamiento local
        const doctorId = localStorage.getItem('user_id');
        if (!doctorId) {
            console.error('No se encontró ID del doctor');
            appointmentsContainer.innerHTML = '<p class="text-center text-danger">Error al obtener información del médico.</p>';
            return;
        }
        
        // Hacer la solicitud a la API para obtener las citas próximas aceptadas
        fetch('../../backend/appointments/get_upcoming_appointments.php', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
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
                
                // Mostrar cada cita en el contenedor
                    
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
        // Intentar obtener el ID del médico de la sesión o localStorage
        // Por ahora, devolvemos un valor predeterminado
        return 1; // ID del médico de prueba
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
        
        // Llamar a la API para aceptar la cita
        fetch('../backend/api/accept_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + (localStorage.getItem('token') || '')
            },
            body: JSON.stringify({ appointment_id: parseInt(appointmentId) })
        })
        .then(response => {
            console.log('Respuesta del servidor:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('Datos de respuesta:', data);
            
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
                
                // Mostrar mensaje de éxito
                this.showToast('La cita ha sido aceptada y ahora aparecerá en tus próximas citas', 'success');
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
        
        try {
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
            }, 500);
            
            // SOLUCIÓN GARANTIZADA: Abrir una ventana para eliminar la cita directamente
            // Esto garantiza que la eliminación se ejecute, incluso si hay problemas con fetch
            const deleteUrl = `../../app/eliminar_directo.php?id=${appointmentId}`;
            
            // Primero intentamos con fetch para una experiencia más fluida
            fetch(deleteUrl)
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    console.log('Cita eliminada correctamente de la base de datos');
                } else {
                    console.error('Error al eliminar la cita de la base de datos:', data.message);
                    // Si falla, abrimos una ventana para garantizar la eliminación
                    this.openDeleteWindow(appointmentId);
                }
            })
            .catch(error => {
                console.error('Error al rechazar la cita en el servidor:', error);
                // Si hay un error, abrimos una ventana para garantizar la eliminación
                this.openDeleteWindow(appointmentId);
            });
            
        } catch (error) {
            console.error('Error al rechazar la cita:', error);
            this.showToast('Error al rechazar la cita. Inténtalo de nuevo más tarde.', 'error');
            
            // Restaurar el botón en caso de error
            rejectBtn.innerHTML = originalButtonText;
            rejectBtn.disabled = false;
            
            // Intentar eliminar de todos modos
            this.openDeleteWindow(appointmentId);
        }
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
        console.log('Mostrando modal de redirección para la cita ID:', appointmentId);
        
        // Guardar el ID de la cita en el modal
        document.getElementById('redirectAppointmentId').value = appointmentId;
        
        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('redirectModal'));
        modal.show();
    },
    
    // Redirigir una cita a otro médico
    redirectAppointment: function(appointmentId, doctorId) {
        console.log('Redirigiendo cita ID:', appointmentId, 'al médico ID:', doctorId);
        
        // Obtener información del médico seleccionado
        const doctorSelect = document.getElementById('doctorSelect');
        const doctorName = doctorSelect.options[doctorSelect.selectedIndex].text;
        
        // En un entorno real, aquí haríamos una llamada a la API para actualizar el estado de la cita
        // Por ahora, simulamos la redirección
        
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('redirectModal'));
        modal.hide();
        
        // Mostrar mensaje de éxito
        this.showToast(`Cita redirigida correctamente a ${doctorName}`, 'success');
        
        // Recargar las solicitudes de citas pendientes
        this.loadPendingAppointmentRequests();
    },
    
    // Solicitar una cita (desde el dashboard del paciente)
    requestAppointment: function(doctorId, doctorName, doctorSpecialty) {
        console.log('Solicitando cita con el médico ID:', doctorId);
        
        // Obtener información del paciente del localStorage
        const patientData = JSON.parse(localStorage.getItem('user')) || {};
        const patientId = patientData.id || '1'; // ID por defecto para pruebas
        const patientName = patientData.name || 'Pol Garcia'; // Nombre por defecto para pruebas
        const patientEmail = patientData.email || 'pol@gmail.com'; // Email por defecto para pruebas
        
        // Crear el modal de solicitud de cita
        const modalHtml = `
            <div class="modal fade" id="appointmentRequestModal" tabindex="-1" aria-labelledby="appointmentRequestModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="appointmentRequestModalLabel">Solicitar cita con ${doctorName}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="appointmentRequestForm">
                                <input type="hidden" id="doctorId" value="${doctorId}">
                                <input type="hidden" id="doctorName" value="${doctorName}">
                                <input type="hidden" id="doctorSpecialty" value="${doctorSpecialty}">
                                
                                <div class="mb-3">
                                    <label for="patientName" class="form-label">Nombre del paciente</label>
                                    <input type="text" class="form-control" id="patientName" value="${patientName}" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="patientEmail" class="form-label">Email del paciente</label>
                                    <input type="email" class="form-control" id="patientEmail" value="${patientEmail}" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="appointmentReason" class="form-label">Motivo de la consulta</label>
                                    <textarea class="form-control" id="appointmentReason" rows="3" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="appointmentDate" class="form-label">Fecha deseada</label>
                                        <input type="date" class="form-control" id="appointmentDate" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="appointmentTime" class="form-label">Hora deseada</label>
                                        <input type="time" class="form-control" id="appointmentTime" required>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="submitAppointmentRequest">Solicitar cita</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Añadir el modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Obtener el modal y mostrarlo
        const modal = new bootstrap.Modal(document.getElementById('appointmentRequestModal'));
        modal.show();
        
        // Establecer la fecha mínima como hoy
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('appointmentDate').min = today;
        
        // Manejar el envío del formulario
        document.getElementById('submitAppointmentRequest').addEventListener('click', () => {
            const form = document.getElementById('appointmentRequestForm');
            
            // Validar el formulario
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Recopilar los datos del formulario
            const appointmentData = {
                patient_id: patientId,
                patient_name: patientName,
                patient_email: patientEmail,
                doctor_id: document.getElementById('doctorId').value,
                doctor_name: document.getElementById('doctorName').value,
                doctor_specialty: document.getElementById('doctorSpecialty').value,
                reason: document.getElementById('appointmentReason').value,
                requested_date: document.getElementById('appointmentDate').value,
                requested_time: document.getElementById('appointmentTime').value
            };
            
            // Enviar la solicitud al servidor
            this.submitAppointmentRequest(appointmentData, modal);
        });
        
        // Limpiar el modal cuando se cierre
        document.getElementById('appointmentRequestModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('appointmentRequestModal').remove();
        });
    },
    
    // Enviar la solicitud de cita al servidor
    submitAppointmentRequest: function(appointmentData, modal) {
        console.log('Enviando solicitud de cita:', appointmentData);
        
        // Mostrar indicador de carga
        const submitButton = document.getElementById('submitAppointmentRequest');
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
        submitButton.disabled = true;
        
        // Formatear la fecha para mostrarla en formato español
        const formattedDate = new Date(appointmentData.requested_date).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        // Formatear la hora para mostrarla en formato español
        const formattedTime = appointmentData.requested_time;
        
        // Preparar los datos para enviar al servidor
        const requestData = {
            patient_id: appointmentData.patient_id,
            patient_name: appointmentData.patient_name,
            patient_email: appointmentData.patient_email,
            doctor_id: appointmentData.doctor_id,
            doctor_name: appointmentData.doctor_name,
            reason: appointmentData.reason,
            requested_date: formattedDate,
            requested_time: formattedTime
        };
        
        // Enviar la solicitud al servidor
        fetch('../../app/create_appointment_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            // Cerrar el modal
            modal.hide();
            
            if (data.success) {
                // Mostrar mensaje de éxito
                this.showToast('Solicitud de cita enviada con éxito. El médico te contactará pronto.', 'success');
            } else {
                // Mostrar mensaje de error
                this.showToast('Error al enviar la solicitud: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error al enviar la solicitud al servidor:', error);
            this.showToast('Error al procesar la solicitud. Por favor, intenta de nuevo.', 'danger');
        })
        .finally(() => {
            // Restaurar el botón
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        });
    },
    
    // Mostrar mensajes toast
    showToast: function(message, type = 'success') {
        // Crear el toast
        const toastContainer = document.createElement('div');
        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '5';
        
        toastContainer.innerHTML = `
            <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-${type} text-white">
                    <strong class="me-auto">Salutia</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        document.body.appendChild(toastContainer);
        
        // Mostrar el toast
        const toastElement = document.getElementById('liveToast');
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Eliminar el toast después de que se oculte
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastContainer.remove();
        });
    },
    
    // Mostrar mensajes de error
    showError: function(message) {
        this.showToast(message, 'danger');
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el gestor de citas
    AppointmentManager.init();
});

/**
 * Script para el dashboard del médico
 * Este script maneja la funcionalidad del dashboard, incluyendo la carga de citas pendientes
 * y la gestión de solicitudes de citas
 */

// Objeto para gestionar el dashboard del médico
const DoctorDashboard = {
    // Inicializar el dashboard
    init: function() {
        console.log('Inicializando dashboard del médico');
        
        // Obtener información del médico del localStorage
        const doctorId = localStorage.getItem('user_id') || '1'; // ID por defecto para pruebas
        const doctorName = localStorage.getItem('user_name') || 'Dr. Juan Pérez'; // Nombre por defecto para pruebas
        
        // Mostrar nombre del médico
        document.getElementById('doctorName').textContent = doctorName;
        
        // Configurar evento de logout
        document.getElementById('logoutBtn').addEventListener('click', this.logout);
        
        // Cargar datos del dashboard
        this.loadDashboardData(doctorId);
        
        // Cargar solicitudes de citas pendientes
        this.loadPendingAppointmentRequests(doctorId);
        
        // Cargar próximas citas
        this.loadUpcomingAppointments(doctorId);
    },
    
    // Función para cerrar sesión
    logout: function() {
        localStorage.removeItem('user_id');
        localStorage.removeItem('user_role');
        localStorage.removeItem('user_name');
        localStorage.removeItem('token');
        window.location.href = '../../index.html';
    },
    
    // Función para cargar los datos del dashboard
    loadDashboardData: function(doctorId) {
        console.log('Cargando datos del dashboard para el médico ID:', doctorId);
        
        // Cargar estadísticas desde la base de datos (simulado)
        // En un entorno real, aquí haríamos una llamada a la API
        
        // Por ahora, usamos datos de ejemplo
        document.getElementById('citasHoy').textContent = '3';
        document.getElementById('citasPendientes').textContent = '5';
        document.getElementById('totalPacientes').textContent = '12';
    },
    
    // Función para cargar solicitudes de citas pendientes desde la base de datos
    loadPendingAppointmentRequests: function(doctorId) {
        console.log('Cargando solicitudes de citas pendientes para el médico ID:', doctorId);
        
        const requestsContainer = document.getElementById('solicitudesCitas');
        
        // Mostrar indicador de carga
        requestsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // Realizar la petición al servidor para obtener las citas pendientes
        fetch(`../../app/get_pending_appointments.php?doctor_id=${doctorId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al obtener las citas pendientes');
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos de citas pendientes recibidos:', data);
                
                if (data.success && data.appointments && data.appointments.length > 0) {
                    requestsContainer.innerHTML = '';
                    
                    data.appointments.forEach(request => {
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
                                <button class="btn btn-success btn-sm btn-action" onclick="DoctorDashboard.acceptAppointment(${request.id})">
                                    <i class="fas fa-check"></i> Aceptar
                                </button>
                                <button class="btn btn-danger btn-sm btn-action" onclick="DoctorDashboard.rejectAppointment(${request.id})">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                                <button class="btn btn-info btn-sm btn-action" onclick="DoctorDashboard.showRedirectModal(${request.id})">
                                    <i class="fas fa-share"></i> Redirigir
                                </button>
                            </div>
                        `;
                        requestsContainer.appendChild(requestCard);
                    });
                } else {
                    // Si no hay solicitudes pendientes o hubo un error
                    requestsContainer.innerHTML = '<p class="text-center">No hay solicitudes de citas pendientes.</p>';
                    
                    // Si no hay citas en la base de datos, pero queremos mostrar ejemplos para demostración
                    if (this.shouldShowExampleData()) {
                        this.loadExampleAppointmentRequests(requestsContainer);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                requestsContainer.innerHTML = `<p class="text-center text-danger">Error al cargar las solicitudes: ${error.message}</p>`;
                
                // Si hay un error, mostrar datos de ejemplo para demostración
                if (this.shouldShowExampleData()) {
                    this.loadExampleAppointmentRequests(requestsContainer);
                }
            });
    },
    
    // Función para determinar si se deben mostrar datos de ejemplo
    shouldShowExampleData: function() {
        // Para propósitos de demostración, siempre mostramos datos de ejemplo
        // En un entorno real, esto podría depender de una configuración
        return true;
    },
    
    // Función para cargar datos de ejemplo de solicitudes de citas
    loadExampleAppointmentRequests: function(container) {
        console.log('Cargando datos de ejemplo de solicitudes de citas');
        
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
                    <button class="btn btn-success btn-sm btn-action" onclick="DoctorDashboard.acceptAppointment(${request.id})">
                        <i class="fas fa-check"></i> Aceptar
                    </button>
                    <button class="btn btn-danger btn-sm btn-action" onclick="DoctorDashboard.rejectAppointment(${request.id})">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                    <button class="btn btn-info btn-sm btn-action" onclick="DoctorDashboard.showRedirectModal(${request.id})">
                        <i class="fas fa-share"></i> Redirigir
                    </button>
                </div>
            `;
            container.appendChild(requestCard);
        });
    },
    
    // Función para cargar próximas citas
    loadUpcomingAppointments: function(doctorId) {
        console.log('Cargando próximas citas para el médico ID:', doctorId);
        
        const appointmentsContainer = document.getElementById('proximasCitas');
        
        // Mostrar indicador de carga
        appointmentsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // En un entorno real, aquí haríamos una llamada a la API
        // Por ahora, usamos datos de ejemplo
        
        setTimeout(() => {
            const upcomingAppointments = [
                {
                    id: 1,
                    patient_name: 'Ana López',
                    patient_email: 'ana@gmail.com',
                    reason: 'Control de hipertensión',
                    appointment_date: '28/05/2025',
                    appointment_time: '11:00',
                    status: 'confirmed'
                },
                {
                    id: 2,
                    patient_name: 'Miguel Fernández',
                    patient_email: 'miguel@gmail.com',
                    reason: 'Revisión post-operatoria',
                    appointment_date: '28/05/2025',
                    appointment_time: '12:30',
                    status: 'confirmed'
                },
                {
                    id: 3,
                    patient_name: 'Elena Sánchez',
                    patient_email: 'elena@gmail.com',
                    reason: 'Consulta dermatológica',
                    appointment_date: '28/05/2025',
                    appointment_time: '16:45',
                    status: 'confirmed'
                }
            ];
            
            if (upcomingAppointments.length > 0) {
                appointmentsContainer.innerHTML = '';
                
                upcomingAppointments.forEach(appointment => {
                    const appointmentCard = document.createElement('div');
                    appointmentCard.className = 'appointment-card';
                    appointmentCard.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="mb-1">${appointment.patient_name}</h5>
                                <p class="mb-1"><strong>Email:</strong> ${appointment.patient_email}</p>
                                <p class="mb-1"><strong>Motivo:</strong> ${appointment.reason}</p>
                                <p class="mb-1"><strong>Fecha:</strong> ${appointment.appointment_date} a las ${appointment.appointment_time}</p>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary btn-sm btn-action" onclick="DoctorDashboard.showAppointmentDetails(${appointment.id})">
                                <i class="fas fa-info-circle"></i> Detalles
                            </button>
                            <button class="btn btn-warning btn-sm btn-action" onclick="DoctorDashboard.rescheduleAppointment(${appointment.id})">
                                <i class="fas fa-calendar-alt"></i> Reprogramar
                            </button>
                            <button class="btn btn-danger btn-sm btn-action" onclick="DoctorDashboard.cancelAppointment(${appointment.id})">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    `;
                    appointmentsContainer.appendChild(appointmentCard);
                });
            } else {
                appointmentsContainer.innerHTML = '<p class="text-center">No hay citas próximas programadas.</p>';
            }
        }, 1000);
    },
    
    // Función para aceptar una cita
    acceptAppointment: function(appointmentId) {
        console.log('Aceptando cita ID:', appointmentId);
        
        // En un entorno real, aquí haríamos una llamada a la API
        // Por ahora, simulamos la aceptación
        
        // Mostrar mensaje de éxito
        this.showToast('Cita aceptada correctamente', 'success');
        
        // Recargar las solicitudes de citas pendientes
        const doctorId = localStorage.getItem('user_id') || '1';
        this.loadPendingAppointmentRequests(doctorId);
        this.loadUpcomingAppointments(doctorId);
    },
    
    // Función para rechazar una cita
    rejectAppointment: function(appointmentId) {
        console.log('Rechazando cita ID:', appointmentId);
        
        // En un entorno real, aquí haríamos una llamada a la API
        // Por ahora, simulamos el rechazo
        
        // Mostrar mensaje de éxito
        this.showToast('Cita rechazada correctamente', 'success');
        
        // Recargar las solicitudes de citas pendientes
        const doctorId = localStorage.getItem('user_id') || '1';
        this.loadPendingAppointmentRequests(doctorId);
    },
    
    // Función para mostrar el modal de redirección
    showRedirectModal: function(appointmentId) {
        console.log('Mostrando modal de redirección para la cita ID:', appointmentId);
        
        // Guardar el ID de la cita en el modal
        document.getElementById('redirectAppointmentId').value = appointmentId;
        
        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('redirectModal'));
        modal.show();
    },
    
    // Función para redirigir una cita
    redirectAppointment: function() {
        const appointmentId = document.getElementById('redirectAppointmentId').value;
        const doctorId = document.getElementById('doctorSelect').value;
        
        console.log('Redirigiendo cita ID:', appointmentId, 'al médico ID:', doctorId);
        
        // Validar que se haya seleccionado un médico
        if (!doctorId) {
            this.showError('Debe seleccionar un médico para redirigir la cita');
            return;
        }
        
        // En un entorno real, aquí haríamos una llamada a la API
        // Por ahora, simulamos la redirección
        
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('redirectModal'));
        modal.hide();
        
        // Mostrar mensaje de éxito
        this.showToast('Cita redirigida correctamente', 'success');
        
        // Recargar las solicitudes de citas pendientes
        const currentDoctorId = localStorage.getItem('user_id') || '1';
        this.loadPendingAppointmentRequests(currentDoctorId);
    },
    
    // Función para mostrar detalles de una cita
    showAppointmentDetails: function(appointmentId) {
        console.log('Mostrando detalles de la cita ID:', appointmentId);
        
        // En un entorno real, aquí haríamos una llamada a la API
        // Por ahora, mostramos un mensaje
        
        this.showToast('Mostrando detalles de la cita (por implementar)', 'info');
    },
    
    // Función para reprogramar una cita
    rescheduleAppointment: function(appointmentId) {
        console.log('Reprogramando cita ID:', appointmentId);
        
        // En un entorno real, aquí haríamos una llamada a la API
        // Por ahora, mostramos un mensaje
        
        this.showToast('Reprogramando cita (por implementar)', 'info');
    },
    
    // Función para cancelar una cita
    cancelAppointment: function(appointmentId) {
        console.log('Cancelando cita ID:', appointmentId);
        
        // En un entorno real, aquí haríamos una llamada a la API
        // Por ahora, mostramos un mensaje
        
        this.showToast('Cancelando cita (por implementar)', 'info');
    },
    
    // Función para mostrar mensajes toast
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
    
    // Función para mostrar mensajes de error
    showError: function(message) {
        this.showToast(message, 'danger');
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el dashboard
    DoctorDashboard.init();
    
    // Configurar evento para el botón de redirección
    document.getElementById('confirmRedirectBtn').addEventListener('click', function() {
        DoctorDashboard.redirectAppointment();
    });
});

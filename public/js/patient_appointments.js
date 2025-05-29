/**
 * Script para gestionar las citas del paciente en Salutia
 * Este script se encarga de cargar y mostrar las citas del paciente en el dashboard
 */

// Objeto para gestionar las citas del paciente
const PatientAppointments = {
    // Inicializar el gestor de citas
    init: function() {
        console.log('Inicializando gestor de citas del paciente');
        
        // Cargar las citas del paciente al iniciar
        this.loadUserAppointments();
        
        // Configurar eventos para el botón de cancelar cita
        document.addEventListener('click', (event) => {
            if (event.target.matches('.cancel-appointment-btn') || 
                event.target.closest('.cancel-appointment-btn')) {
                const button = event.target.matches('.cancel-appointment-btn') ? 
                              event.target : event.target.closest('.cancel-appointment-btn');
                const appointmentId = button.getAttribute('data-id');
                if (appointmentId) {
                    this.cancelAppointment(appointmentId);
                }
            }
        });
    },
    
    // Cargar las citas del paciente
    loadUserAppointments: function() {
        try {
            console.log('Cargando citas del paciente...');
            
            // Mostrar indicador de carga
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Obtener datos del usuario
            const userData = JSON.parse(localStorage.getItem('user'));
            if (!userData || !userData.id) {
                console.error('No hay datos de usuario');
                this.showToast('Error: No se pudo identificar al usuario', 'danger');
                document.getElementById('loadingOverlay').style.display = 'none';
                return;
            }
            
            console.log('Cargando citas para el paciente ID:', userData.id);
            
            // Hacer la solicitud al servidor
            fetch(`../../views/backend/api/get_patient_appointments.php?patient_id=${userData.id}`, {
                headers: {
                    'Authorization': `Bearer ${userData.token || ''}`
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error en la respuesta del servidor: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos de citas recibidos:', data);
                
                // Ocultar indicador de carga
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.success) {
                    // Mostrar las citas en la tabla
                    this.displayAppointments(data.appointments);
                    console.log('Citas mostradas correctamente');
                } else {
                    console.error('Error en la respuesta:', data.message);
                    this.showToast(`Error: ${data.message}`, 'danger');
                }
            })
            .catch(error => {
                // Ocultar indicador de carga
                document.getElementById('loadingOverlay').style.display = 'none';
                
                console.error('Error al cargar citas:', error);
                this.showToast(`Error al cargar las citas: ${error.message}`, 'danger');
            });
        } catch (error) {
            // Ocultar indicador de carga
            document.getElementById('loadingOverlay').style.display = 'none';
            
            console.error('Error general:', error);
            this.showToast(`Error: ${error.message}`, 'danger');
        }
    },
    
    // Mostrar las citas en la tabla
    displayAppointments: function(appointments) {
        const tbody = document.getElementById('appointmentsList');
        tbody.innerHTML = '';

        if (!appointments || appointments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">No tienes citas programadas</td>
                </tr>
            `;
            return;
        }

        appointments.forEach(appointment => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${this.formatDateDisplay(appointment.date)}</td>
                <td>${this.formatTime(appointment.time)}</td>
                <td>Dr. ${appointment.doctor_name}</td>
                <td>${appointment.specialty}</td>
                <td>
                    <span class="appointment-status-${appointment.status.toLowerCase()}">
                        ${this.getStatusText(appointment.status)}
                    </span>
                </td>
                <td>
                    ${appointment.status === 'pending' || appointment.status === 'confirmed' ? `
                        <button class="btn btn-sm btn-danger cancel-appointment-btn" 
                                data-id="${appointment.id}">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    ` : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });
    },
    
    // Cancelar una cita
    cancelAppointment: function(appointmentId) {
        if (!confirm('¿Estás seguro de que deseas cancelar esta cita?')) {
            return;
        }
        
        try {
            // Mostrar indicador de carga
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Obtener datos del usuario
            const userData = JSON.parse(localStorage.getItem('user'));
            
            // Hacer la solicitud al servidor
            fetch('backend/api/appointments.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${userData.token || ''}`
                },
                body: JSON.stringify({
                    appointment_id: appointmentId,
                    status: 'cancelled'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error en la respuesta del servidor: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Ocultar indicador de carga
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.success) {
                    this.loadUserAppointments();
                    this.showToast('Cita cancelada con éxito');
                } else {
                    this.showToast(data.message || 'Error al cancelar la cita', 'danger');
                }
            })
            .catch(error => {
                // Ocultar indicador de carga
                document.getElementById('loadingOverlay').style.display = 'none';
                
                console.error('Error al cancelar cita:', error);
                this.showToast('Error al procesar la solicitud', 'danger');
            });
        } catch (error) {
            // Ocultar indicador de carga
            document.getElementById('loadingOverlay').style.display = 'none';
            
            console.error('Error general:', error);
            this.showToast(`Error: ${error.message}`, 'danger');
        }
    },
    
    // Mostrar un mensaje de toast
    showToast: function(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast show bg-${type} text-white`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="toast-header bg-${type} text-white">
                <strong class="me-auto">Salutia</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Eliminar el toast después de 5 segundos
        setTimeout(() => {
            toast.remove();
        }, 5000);
    },
    
    // Funciones auxiliares
    formatDateDisplay: function(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-ES', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    },
    
    formatTime: function(time) {
        return time.substring(0, 5);
    },
    
    getStatusText: function(status) {
        const statusMap = {
            'confirmed': 'Confirmada',
            'pending': 'Pendiente',
            'cancelled': 'Cancelada'
        };
        return statusMap[status] || status;
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    PatientAppointments.init();
});

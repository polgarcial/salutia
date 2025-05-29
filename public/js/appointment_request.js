/**
 * Gestión de solicitudes de citas médicas
 * Este script maneja la funcionalidad de solicitud de citas desde el dashboard del paciente
 */

// Objeto para gestionar las solicitudes de citas
const AppointmentManager = {
    // Función para solicitar una cita
    requestAppointment: function(doctorId, doctorName, doctorSpecialty) {
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
                                
                                <div class="mb-3">
                                    <label for="appointmentNotes" class="form-label">Notas adicionales</label>
                                    <textarea class="form-control" id="appointmentNotes" rows="3" placeholder="Describa brevemente su situación o síntomas..."></textarea>
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
        document.getElementById('submitAppointmentRequest').addEventListener('click', function() {
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
                requested_time: document.getElementById('appointmentTime').value,
                notes: document.getElementById('appointmentNotes').value
            };
            
            // Enviar la solicitud al servidor
            AppointmentManager.submitAppointmentRequest(appointmentData, modal);
        });
        
        // Limpiar el modal cuando se cierre
        document.getElementById('appointmentRequestModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('appointmentRequestModal').remove();
        });
    },
    
    // Función para enviar la solicitud de cita al servidor
    submitAppointmentRequest: function(appointmentData, modal) {
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
            requested_time: formattedTime,
            notes: appointmentData.notes
        };
        
        // Hacer una llamada real al servidor
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
            
            // Si la solicitud al servidor fue exitosa, actualizar la interfaz
            if (data.success) {
                // Guardar la solicitud en localStorage para la interfaz
                const existingRequests = JSON.parse(localStorage.getItem('appointment_requests')) || [];
                
                // Generar un ID único para la solicitud
                const newRequest = {
                    id: existingRequests.length + 1,
                    ...requestData,
                    status: 'pending',
                    created_at: new Date().toISOString()
                };
                
                // Si el usuario actual es Pol García, asegurarse de que su solicitud tenga ID 1
                if (requestData.patient_email === 'pol@gmail.com') {
                    newRequest.id = 1;
                    
                    // Eliminar cualquier solicitud existente de Pol García
                    const filteredRequests = existingRequests.filter(req => req.patient_email !== 'pol@gmail.com');
                    filteredRequests.push(newRequest);
                    localStorage.setItem('appointment_requests', JSON.stringify(filteredRequests));
                } else {
                    existingRequests.push(newRequest);
                    localStorage.setItem('appointment_requests', JSON.stringify(existingRequests));
                }
                
                // Cerrar el modal
                modal.hide();
                
                // Mostrar mensaje de éxito
                showToast('Solicitud de cita enviada con éxito. El médico te contactará pronto.', 'success');
            } else {
                // Mostrar mensaje de error
                showToast('Error al enviar la solicitud: ' + (data.message || 'Error desconocido'), 'error');
                
                // Restaurar el botón
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al enviar la solicitud al servidor:', error);
            // Mostrar mensaje de error
            showToast('Error al conectar con el servidor. Inténtalo de nuevo más tarde.', 'error');
            
            // Restaurar el botón
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
        });
    }
    }
};

// Función para mostrar notificaciones toast
function showToast(message, type = 'success') {
    // Verificar si el contenedor de toasts existe
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Eliminar el toast después de que se oculte
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

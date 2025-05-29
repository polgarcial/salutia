// Script para el dashboard de pacientes
document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticación
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    if (!token || !user || user.role !== 'patient') {
        // Redirigir al login si no hay token o no es un paciente
        window.location.href = '../../login.html';
        return;
    }
    
    // Mostrar nombre del paciente
    const pacienteElement = document.querySelector('.patient-name');
    if (pacienteElement) {
        pacienteElement.textContent = user.name || 'Paciente';
    }
    
    // Cargar las próximas citas
    loadUpcomingAppointments();
    
    // Cargar el historial de citas
    loadAppointmentsHistory();
    
    // Configurar el buscador de médicos
    setupDoctorSearch();
    
    // Ya no es necesario configurar el botón de salir porque ahora es un enlace directo a la página inicial
});

// Función para cargar las próximas citas
function loadUpcomingAppointments() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const token = localStorage.getItem('token');
    
    // Elemento donde mostrar las próximas citas
    const upcomingAppointmentsContainer = document.querySelector('#upcoming-appointments');
    if (!upcomingAppointmentsContainer) return;
    
    // Mostrar indicador de carga
    upcomingAppointmentsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
    
    // Realizar petición al servidor
    fetch('../../app/get_patient_appointments.php?patient_id=' + user.id + '&status=upcoming', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al cargar las próximas citas');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.appointments && data.appointments.length > 0) {
            // Mostrar las próximas citas
            let html = '';
            
            data.appointments.forEach(appointment => {
                const date = new Date(appointment.appointment_date);
                const formattedDate = date.toLocaleDateString('es-ES', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                html += `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Cita con Dr. ${appointment.doctor_name}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">${formattedDate} - ${appointment.start_time} a ${appointment.end_time}</h6>
                        <p class="card-text">${appointment.reason}</p>
                        <span class="badge ${getStatusBadgeClass(appointment.status)}">${getStatusText(appointment.status)}</span>
                        ${appointment.status === 'pending' ? 
                            `<button class="btn btn-sm btn-outline-danger ms-2" onclick="cancelAppointment(${appointment.id})">Cancelar</button>` : ''}
                    </div>
                </div>`;
            });
            
            upcomingAppointmentsContainer.innerHTML = html;
        } else {
            // No hay próximas citas
            upcomingAppointmentsContainer.innerHTML = '<div class="alert alert-info">No tienes próximas citas programadas.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        upcomingAppointmentsContainer.innerHTML = '<div class="alert alert-danger">Error al cargar las próximas citas. Por favor, intenta de nuevo más tarde.</div>';
    });
}

// Función para cargar el historial de citas
function loadAppointmentsHistory() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const token = localStorage.getItem('token');
    
    // Elemento donde mostrar el historial de citas
    const appointmentsTable = document.querySelector('#appointments-table tbody');
    if (!appointmentsTable) return;
    
    // Mostrar indicador de carga
    appointmentsTable.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';
    
    // Realizar petición al servidor
    fetch('../../app/get_patient_appointments.php?patient_id=' + user.id, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al cargar el historial de citas');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.appointments && data.appointments.length > 0) {
            // Mostrar el historial de citas
            let html = '';
            
            data.appointments.forEach(appointment => {
                html += `
                <tr>
                    <td>${appointment.appointment_date}</td>
                    <td>${appointment.start_time} - ${appointment.end_time}</td>
                    <td>Dr. ${appointment.doctor_name}</td>
                    <td>${appointment.specialty || 'General'}</td>
                    <td><span class="badge ${getStatusBadgeClass(appointment.status)}">${getStatusText(appointment.status)}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewAppointmentDetails(${appointment.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${appointment.status === 'pending' ? 
                            `<button class="btn btn-sm btn-outline-danger ms-1" onclick="cancelAppointment(${appointment.id})">
                                <i class="fas fa-times"></i>
                            </button>` : ''}
                    </td>
                </tr>`;
            });
            
            appointmentsTable.innerHTML = html;
        } else {
            // No hay citas en el historial
            appointmentsTable.innerHTML = '<tr><td colspan="6" class="text-center">No tienes citas en tu historial.</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        appointmentsTable.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar el historial de citas. Por favor, intenta de nuevo más tarde.</td></tr>';
    });
}

// Función para configurar el buscador de médicos
function setupDoctorSearch() {
    const specialtySelect = document.querySelector('#specialty-select');
    const doctorNameInput = document.querySelector('#doctor-name-input');
    const searchButton = document.querySelector('#search-doctor-button');
    const resultsContainer = document.querySelector('#doctor-search-results');
    
    if (!specialtySelect || !doctorNameInput || !searchButton || !resultsContainer) return;
    
    // Cargar especialidades
    console.log('Cargando especialidades...');
    
    // Lista de especialidades (hardcoded para asegurar que se muestren)
    const hardcodedSpecialties = [
        'Cardiología',
        'Dermatología',
        'Endocrinología',
        'Gastroenterología',
        'Geriatría',
        'Ginecología',
        'Hematología',
        'Infectología',
        'Medicina Familiar',
        'Medicina Interna',
        'Nefrología',
        'Neumología',
        'Neurología',
        'Oftalmología',
        'Oncología',
        'Otorrinolaringología',
        'Pediatría',
        'Psiquiatría',
        'Reumatología',
        'Traumatología',
        'Urología'
    ];
    
    // Intentar cargar desde el servidor primero
    fetch('../../app/get_specialties.php')
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta del servidor de especialidades:', data);
        
        let specialtiesToUse = [];
        
        if (data.success && data.specialties && data.specialties.length > 0) {
            specialtiesToUse = data.specialties;
            console.log('Usando especialidades del servidor:', specialtiesToUse);
        } else {
            specialtiesToUse = hardcodedSpecialties;
            console.log('Usando especialidades hardcoded:', specialtiesToUse);
        }
        
        // Ordenar especialidades alfabéticamente
        const sortedSpecialties = [...specialtiesToUse].sort();
        
        let options = '<option value="">Todas las especialidades</option>';
        sortedSpecialties.forEach(specialty => {
            options += `<option value="${specialty}">${specialty}</option>`;
        });
        
        specialtySelect.innerHTML = options;
    })
    .catch(error => {
        console.error('Error al cargar especialidades:', error);
        
        // En caso de error, usar especialidades hardcoded
        console.log('Usando especialidades hardcoded debido a error');
        const sortedSpecialties = [...hardcodedSpecialties].sort();
        
        let options = '<option value="">Todas las especialidades</option>';
        sortedSpecialties.forEach(specialty => {
            options += `<option value="${specialty}">${specialty}</option>`;
        });
        
        specialtySelect.innerHTML = options;
    });
    
    // Configurar evento de búsqueda al hacer clic en el botón
    searchButton.addEventListener('click', performSearch);
    
    // Configurar evento de búsqueda al presionar Enter en el campo de nombre
    doctorNameInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Función para realizar la búsqueda
    function performSearch() {
        const specialty = specialtySelect.value;
        const searchTerm = document.getElementById('searchDoctor').value.toLowerCase().trim();
        
        // Mostrar indicador de carga
        resultsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // Realizar petición al servidor
        fetch(`../../app/get_doctors.php?specialty=${specialty}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.doctors && data.doctors.length > 0) {
                // Filtrar por nombre si hay término de búsqueda
                let filteredDoctors = data.doctors;
                if (searchTerm) {
                    filteredDoctors = data.doctors.filter(doctor => 
                        doctor.name.toLowerCase().includes(searchTerm)
                    );
                }
                
                if (filteredDoctors.length === 0) {
                    resultsContainer.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No se encontraron médicos con el nombre "${searchTerm}".
                            <hr>
                            <p class="mb-0">Sugerencias:</p>
                            <ul>
                                <li>Verifica que el nombre esté escrito correctamente</li>
                                <li>Intenta con otro nombre o deja el campo vacío</li>
                                <li>Prueba con otra especialidad</li>
                            </ul>
                        </div>`;
                    return;
                }
                
                // Generar HTML para los médicos
                let html = '';
                
                filteredDoctors.forEach(doctor => {
                    // Generar HTML para las especialidades
                    let specialtiesHtml = '';
                    if (doctor.specialties && doctor.specialties.length > 0) {
                        specialtiesHtml = '<p class="card-text"><i class="fas fa-stethoscope me-2"></i>';
                        specialtiesHtml += doctor.specialties.join(', ');
                        specialtiesHtml += '</p>';
                    } else {
                        specialtiesHtml = `<p class="card-text"><i class="fas fa-stethoscope me-2"></i>${doctor.specialty || 'Medicina General'}</p>`;
                    }
                    
                    html += `<div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm" data-doctor-id="${doctor.id}" data-doctor-name="${doctor.name}" data-doctor-specialty="${doctor.specialty || 'General'}">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">${doctor.name}</h5>
                            </div>
                            <div class="card-body">
                                ${specialtiesHtml}
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt me-2"></i>${doctor.address || 'Dirección no disponible'}
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-phone me-2"></i>${doctor.phone || 'Teléfono no disponible'}
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        ${renderStars(doctor.rating || 0)}
                                    </div>
                                    <button class="btn btn-primary w-100" onclick="bookAppointment(${doctor.id})">
                                        <i class="fas fa-calendar-plus me-2"></i>Solicitar Cita
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
                
                resultsContainer.innerHTML = `
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-check-circle me-2"></i>Se encontraron ${filteredDoctors.length} médicos disponibles
                    </div>
                    <div class="row">${html}</div>`;
            } else {
                resultsContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No se encontraron médicos con los criterios especificados.
                        <hr>
                        <p class="mb-0">Sugerencias:</p>
                        <ul>
                            <li>Intenta con otra especialidad</li>
                            <li>Deja el campo de nombre vacío para ver todos los médicos</li>
                            <li>Verifica que el nombre esté escrito correctamente</li>
                        </ul>
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultsContainer.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al buscar médicos. Por favor, intenta de nuevo más tarde.</div>';
        });
    }
}

// Función para cancelar una cita
function cancelAppointment(appointmentId) {
    if (!confirm('¿Estás seguro de que deseas cancelar esta cita?')) {
        return;
    }
    
    const token = localStorage.getItem('token');
    
    fetch('../../app/update_appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({
            appointment_id: appointmentId,
            status: 'cancelled'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cita cancelada correctamente.');
            // Recargar las citas
            loadUpcomingAppointments();
            loadAppointmentsHistory();
        } else {
            alert('Error al cancelar la cita: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cancelar la cita. Por favor, intenta de nuevo más tarde.');
    });
}

// Función para ver detalles de una cita
function viewAppointmentDetails(appointmentId) {
    const token = localStorage.getItem('token');
    
    fetch('../../app/get_appointment_details.php?id=' + appointmentId, {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.appointment) {
            const appointment = data.appointment;
            
            // Crear modal con los detalles
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'appointmentDetailsModal';
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('aria-labelledby', 'appointmentDetailsModalLabel');
            modal.setAttribute('aria-hidden', 'true');
            
            const date = new Date(appointment.appointment_date);
            const formattedDate = date.toLocaleDateString('es-ES', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appointmentDetailsModalLabel">Detalles de la Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Fecha:</strong> ${formattedDate}</p>
                        <p><strong>Hora:</strong> ${appointment.start_time} a ${appointment.end_time}</p>
                        <p><strong>Médico:</strong> Dr. ${appointment.doctor_name}</p>
                        <p><strong>Especialidad:</strong> ${appointment.specialty || 'General'}</p>
                        <p><strong>Motivo:</strong> ${appointment.reason}</p>
                        <p><strong>Estado:</strong> <span class="badge ${getStatusBadgeClass(appointment.status)}">${getStatusText(appointment.status)}</span></p>
                        ${appointment.notes ? `<p><strong>Notas:</strong> ${appointment.notes}</p>` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        ${appointment.status === 'pending' ? 
                            `<button type="button" class="btn btn-danger" onclick="cancelAppointment(${appointment.id}); bootstrap.Modal.getInstance(document.getElementById('appointmentDetailsModal')).hide();">Cancelar Cita</button>` : ''}
                    </div>
                </div>
            </div>`;
            
            // Añadir modal al body
            document.body.appendChild(modal);
            
            // Mostrar modal
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            // Eliminar modal del DOM cuando se cierre
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        } else {
            alert('Error al cargar los detalles de la cita: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cargar los detalles de la cita. Por favor, intenta de nuevo más tarde.');
    });
}

// Función para solicitar una cita
function bookAppointment(doctorId) {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    
    // Crear modal para solicitar cita
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'bookAppointmentModal';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'bookAppointmentModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookAppointmentModalLabel">Solicitar Cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="appointment-form">
                    <div class="mb-3">
                        <label for="appointment-date" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="appointment-date" required min="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="mb-3">
                        <label for="appointment-time" class="form-label">Hora</label>
                        <select class="form-select" id="appointment-time" required disabled>
                            <option value="">Selecciona una fecha primero</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="appointment-reason" class="form-label">Motivo de la consulta</label>
                        <textarea class="form-control" id="appointment-reason" rows="3" required></textarea>
                    </div>
                </form>
                <div id="appointment-error" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="submit-appointment">Solicitar</button>
            </div>
        </div>
    </div>`;
    
    // Añadir modal al body
    document.body.appendChild(modal);
    
    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    // Configurar evento para cargar horarios disponibles cuando se seleccione una fecha
    const dateInput = document.getElementById('appointment-date');
    const timeSelect = document.getElementById('appointment-time');
    
    dateInput.addEventListener('change', function() {
        const selectedDate = dateInput.value;
        
        if (!selectedDate) {
            timeSelect.disabled = true;
            timeSelect.innerHTML = '<option value="">Selecciona una fecha primero</option>';
            return;
        }
        
        // Mostrar indicador de carga
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">Cargando horarios disponibles...</option>';
        
        // Cargar horarios disponibles
        fetch(`../../app/get_doctor_availability.php?doctor_id=${doctorId}&date=${selectedDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.slots && data.slots.length > 0) {
                let options = '<option value="">Selecciona una hora</option>';
                
                data.slots.forEach(slot => {
                    options += `<option value="${slot.start_time}-${slot.end_time}">${slot.start_time} - ${slot.end_time}</option>`;
                });
                
                timeSelect.innerHTML = options;
                timeSelect.disabled = false;
            } else {
                timeSelect.innerHTML = '<option value="">No hay horarios disponibles para esta fecha</option>';
                timeSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            timeSelect.innerHTML = '<option value="">Error al cargar horarios</option>';
            timeSelect.disabled = true;
        });
    });
    
    // Configurar evento para enviar formulario
    const submitButton = document.getElementById('submit-appointment');
    const appointmentForm = document.getElementById('appointment-form');
    const errorContainer = document.getElementById('appointment-error');
    
    submitButton.addEventListener('click', function() {
        // Validar formulario
        if (!appointmentForm.checkValidity()) {
            appointmentForm.reportValidity();
            return;
        }
        
        const selectedDate = dateInput.value;
        const selectedTimeRange = timeSelect.value;
        const reason = document.getElementById('appointment-reason').value;
        
        if (!selectedDate || !selectedTimeRange || !reason) {
            errorContainer.textContent = 'Por favor, completa todos los campos.';
            errorContainer.classList.remove('d-none');
            return;
        }
        
        const [startTime, endTime] = selectedTimeRange.split('-');
        
        // Deshabilitar botón mientras se procesa
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        // Enviar solicitud
        const token = localStorage.getItem('token');
        
        fetch('../../app/request_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({
                doctor_id: doctorId,
                patient_id: user.id,
                appointment_date: selectedDate,
                start_time: startTime,
                end_time: endTime,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal
                modalInstance.hide();
                
                // Añadir la nueva cita directamente a la tabla sin recargar toda la página
                addNewAppointmentToTable({
                    id: data.appointment_id,
                    doctor_id: doctorId,
                    doctor_name: document.querySelector(`[data-doctor-id="${doctorId}"]`)?.getAttribute('data-doctor-name') || 'Médico',
                    specialty: document.querySelector(`[data-doctor-id="${doctorId}"]`)?.getAttribute('data-doctor-specialty') || 'Especialista',
                    appointment_date: selectedDate,
                    start_time: startTime,
                    end_time: endTime,
                    reason: reason,
                    status: 'pending'
                });
                
                // Mostrar mensaje de éxito con SweetAlert o similar si está disponible
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¡Cita solicitada!',
                        text: 'Tu cita ha sido solicitada correctamente. El médico la confirmará próximamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    alert('Cita solicitada correctamente. El médico confirmará tu cita próximamente.');
                }
                
                // Desplazar la página hacia la sección de 'Mis Citas'
                const misCitasSection = document.getElementById('mis-citas');
                if (misCitasSection) {
                    misCitasSection.scrollIntoView({ behavior: 'smooth' });
                }
            } else {
                errorContainer.textContent = data.message || 'Error al solicitar la cita. Por favor, intenta de nuevo.';
                errorContainer.classList.remove('d-none');
                
                // Restaurar botón
                submitButton.disabled = false;
                submitButton.textContent = 'Solicitar';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorContainer.textContent = 'Error al solicitar la cita. Por favor, intenta de nuevo más tarde.';
            errorContainer.classList.remove('d-none');
            
            // Restaurar botón
            submitButton.disabled = false;
            submitButton.textContent = 'Solicitar';
        });
    });
    
    // Eliminar modal del DOM cuando se cierre
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

// Función para obtener la clase de badge según el estado
function getStatusBadgeClass(status) {
    switch (status) {
        case 'pending':
            return 'bg-warning';
        case 'confirmed':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        case 'completed':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Función para obtener el texto del estado
function getStatusText(status) {
    switch (status) {
        case 'pending':
            return 'Pendiente';
        case 'confirmed':
            return 'Confirmada';
        case 'cancelled':
            return 'Cancelada';
        case 'completed':
            return 'Completada';
        default:
            return status;
    }
}

// Función para renderizar estrellas según valoración
function renderStars(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
    
    let html = '';
    
    // Estrellas completas
    for (let i = 0; i < fullStars; i++) {
        html += '<i class="fas fa-star text-warning"></i>';
    }
    
    // Media estrella
    if (halfStar) {
        html += '<i class="fas fa-star-half-alt text-warning"></i>';
    }
    
    // Estrellas vacías
    for (let i = 0; i < emptyStars; i++) {
        html += '<i class="far fa-star text-warning"></i>';
    }
    
    return html + ` <span class="text-muted">(${rating.toFixed(1)})</span>`;
}

// Función para añadir una nueva cita directamente a la tabla
function addNewAppointmentToTable(appointment) {
    // Verificar si tenemos los elementos necesarios en el DOM
    const appointmentsTable = document.querySelector('#appointmentsList');
    const upcomingAppointmentsContainer = document.querySelector('#upcoming-appointments');
    
    if (!appointmentsTable && !upcomingAppointmentsContainer) {
        console.error('No se encontraron los contenedores para mostrar las citas');
        return;
    }
    
    // Formatear la fecha para mostrarla
    const date = new Date(appointment.appointment_date);
    const formattedDate = date.toLocaleDateString('es-ES', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Añadir la cita a la tabla principal
    if (appointmentsTable) {
        // Crear una nueva fila para la tabla
        const newRow = document.createElement('tr');
        newRow.className = 'new-appointment animate__animated animate__fadeIn';
        newRow.setAttribute('data-appointment-id', appointment.id);
        
        newRow.innerHTML = `
            <td>${appointment.appointment_date}</td>
            <td>${appointment.start_time} - ${appointment.end_time}</td>
            <td>Dr. ${appointment.doctor_name}</td>
            <td>${appointment.specialty || 'General'}</td>
            <td><span class="badge ${getStatusBadgeClass(appointment.status)}">${getStatusText(appointment.status)}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewAppointmentDetails(${appointment.id})">
                    <i class="fas fa-eye"></i>
                </button>
                ${appointment.status === 'pending' ? 
                    `<button class="btn btn-sm btn-outline-danger ms-1" onclick="cancelAppointment(${appointment.id})">
                        <i class="fas fa-times"></i>
                    </button>` : ''}
            </td>
        `;
        
        // Añadir la fila al inicio de la tabla
        if (appointmentsTable.firstChild) {
            appointmentsTable.insertBefore(newRow, appointmentsTable.firstChild);
        } else {
            appointmentsTable.appendChild(newRow);
        }
        
        // Aplicar un estilo destacado temporalmente
        setTimeout(() => {
            newRow.classList.add('highlight-appointment');
            setTimeout(() => {
                newRow.classList.remove('highlight-appointment');
            }, 3000);
        }, 100);
    }
    
    // Añadir la cita a la sección de próximas citas
    if (upcomingAppointmentsContainer) {
        // Crear una nueva tarjeta para la cita
        const newCard = document.createElement('div');
        newCard.className = 'card mb-3 animate__animated animate__fadeIn';
        newCard.setAttribute('data-appointment-id', appointment.id);
        
        newCard.innerHTML = `
            <div class="card-body">
                <h5 class="card-title">Cita con Dr. ${appointment.doctor_name}</h5>
                <h6 class="card-subtitle mb-2 text-muted">${formattedDate} - ${appointment.start_time} a ${appointment.end_time}</h6>
                <p class="card-text">${appointment.reason}</p>
                <span class="badge ${getStatusBadgeClass(appointment.status)}">${getStatusText(appointment.status)}</span>
                ${appointment.status === 'pending' ? 
                    `<button class="btn btn-sm btn-outline-danger ms-2" onclick="cancelAppointment(${appointment.id})">Cancelar</button>` : ''}
            </div>
        `;
        
        // Verificar si hay un mensaje de "no hay citas" y eliminarlo
        const noAppointmentsMessage = upcomingAppointmentsContainer.querySelector('.alert-info');
        if (noAppointmentsMessage) {
            upcomingAppointmentsContainer.innerHTML = '';
        }
        
        // Añadir la tarjeta al inicio del contenedor
        if (upcomingAppointmentsContainer.firstChild) {
            upcomingAppointmentsContainer.insertBefore(newCard, upcomingAppointmentsContainer.firstChild);
        } else {
            upcomingAppointmentsContainer.appendChild(newCard);
        }
        
        // Aplicar un estilo destacado temporalmente
        setTimeout(() => {
            newCard.classList.add('highlight-appointment');
            setTimeout(() => {
                newCard.classList.remove('highlight-appointment');
            }, 3000);
        }, 100);
    }
    
    // Añadir estilos CSS para la animación de destacado si no existen
    if (!document.getElementById('appointment-highlight-styles')) {
        const styleElement = document.createElement('style');
        styleElement.id = 'appointment-highlight-styles';
        styleElement.textContent = `
            .highlight-appointment {
                background-color: rgba(52, 152, 219, 0.1);
                box-shadow: 0 0 10px rgba(52, 152, 219, 0.5);
                transition: background-color 0.5s ease, box-shadow 0.5s ease;
            }
            .new-appointment {
                position: relative;
            }
            .new-appointment::after {
                content: 'Nueva';
                position: absolute;
                top: -10px;
                right: -10px;
                background-color: #e74c3c;
                color: white;
                font-size: 12px;
                padding: 2px 8px;
                border-radius: 10px;
                opacity: 1;
                transition: opacity 0.5s ease;
                animation: fadeOut 5s forwards;
            }
            @keyframes fadeOut {
                0% { opacity: 1; }
                70% { opacity: 1; }
                100% { opacity: 0; }
            }
        `;
        document.head.appendChild(styleElement);
    }
}

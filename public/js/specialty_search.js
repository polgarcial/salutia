// Script para la búsqueda de médicos por especialidad

// Función para cargar médicos por especialidad
function loadDoctorsBySpecialty() {
    console.log('Iniciando búsqueda de médicos por especialidad');
    
    const specialty = document.getElementById('specialty').value;
    console.log('Especialidad seleccionada:', specialty);
    
    if (!specialty) {
        console.log('No se seleccionó ninguna especialidad');
        // Si no hay especialidad seleccionada, mostrar mensaje
        document.getElementById('doctorsList').innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Selecciona una especialidad para ver los médicos disponibles.
            </div>
        `;
        return;
    }
    
    // Mostrar indicador de carga
    document.getElementById('doctorsList').innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Buscando médicos especialistas en ${specialty}...</p>
        </div>
    `;
    
    // Construir la URL de la API
    const apiUrl = `http://localhost:8000/app/get_doctors_by_specialty.php?specialty=${encodeURIComponent(specialty)}`;
    console.log('Realizando petición a:', apiUrl);
    
    // Realizar petición al servidor
    fetch(apiUrl)
        .then(response => {
            console.log('Respuesta recibida, status:', response.status);
            if (!response.ok) {
                throw new Error(`Error en la respuesta del servidor: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            if (data.success && data.doctors && data.doctors.length > 0) {
                console.log(`Se encontraron ${data.doctors.length} médicos para la especialidad ${specialty}`);
                displayDoctorsWithDetails(data.doctors, data.specialty);
            } else {
                console.log('No se encontraron médicos para esta especialidad');
                document.getElementById('doctorsList').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se encontraron médicos para la especialidad ${specialty}.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error al cargar médicos:', error);
            document.getElementById('doctorsList').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error al cargar los médicos. Por favor, intenta de nuevo más tarde.<br>
                    <small class="text-muted">Detalle: ${error.message}</small>
                </div>
            `;
        });
}

// Función para mostrar médicos con detalles de motivos de visita y horas disponibles
function displayDoctorsWithDetails(doctors, specialty) {
    const container = document.getElementById('doctorsList');
    container.innerHTML = '';
    
    // Título de la sección
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="text-primary">Médicos especialistas en ${specialty}</h4>
                <p class="text-muted">Selecciona un médico para ver sus horarios disponibles y solicitar una cita</p>
            </div>
        </div>
    `;
    
    // Contenedor para las tarjetas de médicos
    const doctorsRow = document.createElement('div');
    doctorsRow.className = 'row';
    container.appendChild(doctorsRow);
    
    doctors.forEach(doctor => {
        // Formatear disponibilidad
        let availabilityHtml = '';
        if (doctor.availability && doctor.availability.length > 0) {
            // Agrupar por día de la semana
            const availabilityByDay = {};
            doctor.availability.forEach(slot => {
                if (!availabilityByDay[slot.day_name]) {
                    availabilityByDay[slot.day_name] = [];
                }
                availabilityByDay[slot.day_name].push(`${slot.start_time.substring(0, 5)} - ${slot.end_time.substring(0, 5)}`);
            });
            
            // Mostrar disponibilidad por día
            availabilityHtml = '<div class="mt-3"><h6 class="mb-2">Horarios disponibles:</h6><ul class="list-group">';
            for (const day in availabilityByDay) {
                availabilityHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-day me-2"></i>${day}</span>
                        <span>${availabilityByDay[day].join(', ')}</span>
                    </li>
                `;
            }
            availabilityHtml += '</ul></div>';
        }
        
        // Formatear motivos de consulta
        let reasonsHtml = '';
        if (doctor.common_reasons && doctor.common_reasons.length > 0) {
            reasonsHtml = `
                <div class="mt-3">
                    <h6 class="mb-2">Motivos comunes de consulta:</h6>
                    <div class="d-flex flex-wrap">
            `;
            
            doctor.common_reasons.forEach(reason => {
                reasonsHtml += `<span class="badge bg-light text-dark me-1 mb-1">${reason}</span>`;
            });
            
            reasonsHtml += '</div></div>';
        }
        
        // Crear tarjeta de médico
        const doctorCard = document.createElement('div');
        doctorCard.className = 'col-md-6 mb-4';
        doctorCard.innerHTML = `
            <div class="card doctor-card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">${doctor.name}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <p class="card-text text-muted mb-0">${doctor.specialty || specialty}</p>
                        <div class="doctor-rating">
                            ${getRatingStars(doctor.rating || 4)}
                        </div>
                    </div>
                    <div class="doctor-info mb-3">
                        <p class="card-text small mb-1">
                            <i class="fas fa-envelope me-2"></i>${doctor.email || 'No disponible'}
                        </p>
                    </div>
                    ${reasonsHtml}
                    ${availabilityHtml}
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-primary w-100" onclick="selectDoctor(${doctor.id}, '${doctor.name}', '${doctor.specialty || specialty}')">
                        <i class="fas fa-calendar-plus me-2"></i>Solicitar Cita
                    </button>
                </div>
            </div>
        `;
        
        doctorsRow.appendChild(doctorCard);
    });
}

// Función para generar estrellas de valoración
function getRatingStars(rating) {
    const fullStar = '<i class="fas fa-star text-warning"></i>';
    const halfStar = '<i class="fas fa-star-half-alt text-warning"></i>';
    const emptyStar = '<i class="far fa-star text-warning"></i>';
    
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let stars = '';
    
    for (let i = 0; i < 5; i++) {
        if (i < fullStars) {
            stars += fullStar;
        } else if (i === fullStars && hasHalfStar) {
            stars += halfStar;
        } else {
            stars += emptyStar;
        }
    }
    
    return stars + ` <span class="text-muted">(${rating.toFixed(1)})</span>`;
}

// Función para seleccionar un médico y solicitar cita
function selectDoctor(doctorId, doctorName, specialty) {
    // Mostrar modal de solicitud de cita
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'appointmentModal';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'appointmentModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="appointmentModalLabel">Solicitar cita con ${doctorName}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="appointment-form">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="appointment-date" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="appointment-date" required min="${new Date().toISOString().split('T')[0]}" onchange="loadAvailableHours()">
                            </div>
                            <div class="col-md-6">
                                <label for="appointment-reason" class="form-label">Motivo de la consulta</label>
                                <select class="form-select" id="appointment-reason" required onchange="toggleOtherReason()">
                                    <option value="">Selecciona un motivo</option>
                                    <option value="Control rutinario">Control rutinario</option>
                                    <option value="Consulta por dolor">Consulta por dolor</option>
                                    <option value="Seguimiento de tratamiento">Seguimiento de tratamiento</option>
                                    <option value="otro">Otro motivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="appointment-time" class="form-label">Hora</label>
                                <select class="form-select" id="appointment-time" required>
                                    <option value="">Selecciona una hora</option>
                                    <option value="09:00">09:00</option>
                                    <option value="10:00">10:00</option>
                                    <option value="11:00">11:00</option>
                                    <option value="12:00">12:00</option>
                                    <option value="16:00">16:00</option>
                                    <option value="17:00">17:00</option>
                                    <option value="18:00">18:00</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="time-loading" style="display: none;">
                                <div class="d-flex align-items-center h-100">
                                    <div class="spinner-border text-primary me-2" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <span>Cargando horarios disponibles...</span>
                                </div>
                            </div>
                        </div>
                        <div id="other-reason-container" class="mb-3" style="display: none;">
                            <label for="other-reason" class="form-label">Especificar otro motivo</label>
                            <input type="text" class="form-control" id="other-reason" placeholder="Describe el motivo de tu consulta">
                        </div>
                        <div class="mb-3">
                            <label for="appointment-details" class="form-label">Detalles adicionales</label>
                            <textarea class="form-control" id="appointment-details" rows="3" placeholder="Describe brevemente tu situación o síntomas"></textarea>
                        </div>
                    </form>
                    <div id="appointment-error" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="submit-appointment">Solicitar Cita</button>
                </div>
            </div>
        </div>
    `;
    
    // Añadir modal al body
    document.body.appendChild(modal);
    
    // Mostrar modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    // Cargar motivos de consulta según la especialidad
    fetch(`../../app/get_doctors_by_specialty.php?specialty=${encodeURIComponent(specialty)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.doctors && data.doctors.length > 0) {
                // Buscar el médico seleccionado
                const selectedDoctor = data.doctors.find(doc => doc.id == doctorId);
                
                if (selectedDoctor && selectedDoctor.common_reasons) {
                    const reasonSelect = document.getElementById('appointment-reason');
                    
                    // Añadir los motivos de consulta al select
                    selectedDoctor.common_reasons.forEach(reason => {
                        const option = document.createElement('option');
                        option.value = reason;
                        option.textContent = reason;
                        reasonSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Error al cargar motivos de consulta:', error));
    
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
        
        const selectedDate = document.getElementById('appointment-date').value;
        let reason = document.getElementById('appointment-reason').value;
        
        // Si el motivo es "otro", usar el texto ingresado por el usuario
        if (reason === 'otro') {
            const otherReason = document.getElementById('other-reason').value;
            if (!otherReason.trim()) {
                alert('Por favor, especifica el motivo de tu consulta');
                return;
            }
            reason = otherReason.trim();
        }
        const details = document.getElementById('appointment-details').value;
        
        if (!selectedDate || !reason) {
            errorContainer.textContent = 'Por favor, completa todos los campos requeridos.';
            errorContainer.classList.remove('d-none');
            return;
        }
        
        // Deshabilitar botón mientras se procesa
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        // Obtener datos del usuario del localStorage
        const userData = JSON.parse(localStorage.getItem('user')) || {};
        const patientId = userData.id || '1';
        const patientName = userData.name || 'Paciente de prueba';
        const patientEmail = userData.email || 'paciente@ejemplo.com';
        
        // Preparar los datos de la solicitud
        const appointmentData = {
            patient_id: patientId,
            patient_name: patientName,
            patient_email: patientEmail,
            doctor_id: doctorId,
            doctor_name: doctorName,
            reason: reason,
            requested_date: selectedDate,
            requested_time: document.getElementById('appointment-time').value,
            details: details
        };
        
        console.log('Enviando solicitud de cita:', appointmentData);
        
        // Enviar la solicitud al servidor
        fetch('http://localhost:8000/app/create_appointment_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(appointmentData)
        })
        .then(response => {
            // Intentar parsear la respuesta como JSON primero
            return response.text().then(text => {
                try {
                    // Intentar parsear como JSON
                    const data = JSON.parse(text);
                    return { ok: response.ok, data: data };
                } catch (e) {
                    // Si no es JSON válido, mostrar el texto como error
                    console.error('Respuesta no válida del servidor:', text);
                    throw new Error('El servidor devolvió una respuesta inválida. Por favor, inténtalo de nuevo más tarde.');
                }
            });
        })
        .then(result => {
            if (!result.ok) {
                throw new Error(result.data.message || `Error en la respuesta del servidor: ${result.status}`);
            }
            
            console.log('Respuesta del servidor:', result.data);
            
            if (result.data.success) {
                // Cerrar modal
                modalInstance.hide();
                
                // Mostrar mensaje de éxito
                alert('Cita solicitada correctamente. El médico confirmará tu cita próximamente.');
            } else {
                throw new Error(result.data.message || 'Error al solicitar la cita');
            }
        })
        .catch(error => {
            console.error('Error al solicitar cita:', error);
            errorContainer.textContent = `Error: ${error.message}`;
            errorContainer.classList.remove('d-none');
            
            // Habilitar el botón nuevamente
            submitButton.disabled = false;
            submitButton.innerHTML = 'Solicitar Cita';
        })
        .finally(() => {
            // Eliminar modal del DOM cuando se cierre
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.removeChild(modal);
            });
        });
    });
    
    // Eliminar modal del DOM cuando se cierre
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

// Función para mostrar u ocultar el campo de otro motivo
function toggleOtherReason() {
    const reasonSelect = document.getElementById('appointment-reason');
    const otherReasonContainer = document.getElementById('other-reason-container');
    const otherReasonInput = document.getElementById('other-reason');
    
    if (reasonSelect && otherReasonContainer && otherReasonInput) {
        if (reasonSelect.value === 'otro') {
            otherReasonContainer.style.display = 'block';
            otherReasonInput.setAttribute('required', 'required');
        } else {
            otherReasonContainer.style.display = 'none';
            otherReasonInput.removeAttribute('required');
        }
    }
}

// Función para cargar los horarios disponibles del médico según la fecha seleccionada
function loadAvailableHours() {
    const dateInput = document.getElementById('appointment-date');
    const timeSelect = document.getElementById('appointment-time');
    const timeLoading = document.getElementById('time-loading');
    
    if (!dateInput || !dateInput.value || !timeSelect) return;
    
    // Obtener el día de la semana (0-6, donde 0 es domingo)
    const selectedDate = new Date(dateInput.value);
    const dayOfWeek = selectedDate.getDay();
    
    // Mapear el día de la semana a un nombre en inglés
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const dayName = dayNames[dayOfWeek];
    
    // Mostrar indicador de carga
    timeLoading.style.display = 'block';
    timeSelect.disabled = true;
    
    // Limpiar opciones actuales excepto la primera
    while (timeSelect.options.length > 1) {
        timeSelect.remove(1);
    }
    
    // Simular carga de horarios disponibles según el día de la semana
    setTimeout(() => {
        // Ocultar indicador de carga
        timeLoading.style.display = 'none';
        timeSelect.disabled = false;
        
        // Horarios predefinidos según el día de la semana
        let availableHours = [];
        
        if (dayName === 'saturday' || dayName === 'sunday') {
            // Sin horarios disponibles los fines de semana
            timeSelect.innerHTML = '<option value="">No hay horarios disponibles</option>';
            return;
        } else if (dayName === 'monday' || dayName === 'wednesday') {
            availableHours = ['09:00', '10:00', '11:00', '12:00'];
        } else if (dayName === 'tuesday' || dayName === 'thursday') {
            availableHours = ['16:00', '17:00', '18:00', '19:00'];
        } else { // viernes
            availableHours = ['09:00', '10:00', '16:00', '17:00'];
        }
        
        // Añadir opciones al select
        timeSelect.innerHTML = '<option value="">Selecciona una hora</option>';
        availableHours.forEach(hour => {
            const option = document.createElement('option');
            option.value = hour;
            option.textContent = hour;
            timeSelect.appendChild(option);
        });
    }, 1000);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Configurar evento de cambio en el select de especialidades
    const specialtySelect = document.getElementById('specialty');
    if (specialtySelect) {
        specialtySelect.addEventListener('change', loadDoctorsBySpecialty);
        
        // Cargar médicos si hay una especialidad seleccionada
        if (specialtySelect.value) {
            loadDoctorsBySpecialty();
        }
    }
});

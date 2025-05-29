/**
 * Script para la búsqueda de médicos por especialidad
 * Este script maneja la carga y visualización de médicos según la especialidad seleccionada
 */

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando búsqueda de médicos por especialidad');
    
    // Obtener elementos del DOM
    const specialtySelect = document.getElementById('specialty');
    const doctorsListContainer = document.getElementById('doctorsList');
    
    // Verificar que existan los elementos necesarios
    if (specialtySelect && doctorsListContainer) {
        console.log('Elementos encontrados, configurando eventos');
        
        // Configurar evento de cambio en el select de especialidades
        specialtySelect.addEventListener('change', function() {
            console.log('Especialidad seleccionada:', this.value);
            loadDoctorsBySpecialty();
        });
        
        // Cargar médicos si hay una especialidad seleccionada inicialmente
        if (specialtySelect.value) {
            console.log('Especialidad inicial seleccionada:', specialtySelect.value);
            loadDoctorsBySpecialty();
        }
    } else {
        console.error('Elementos no encontrados');
    }
});

/**
 * Carga los médicos según la especialidad seleccionada
 */
function loadDoctorsBySpecialty() {
    console.log('Ejecutando loadDoctorsBySpecialty()');
    
    // Obtener elementos del DOM
    const specialtySelect = document.getElementById('specialty');
    const doctorsListContainer = document.getElementById('doctorsList');
    
    // Verificar que existan los elementos necesarios
    if (!specialtySelect || !doctorsListContainer) {
        console.error('Elementos no encontrados');
        return;
    }
    
    // Obtener la especialidad seleccionada
    const specialty = specialtySelect.value;
    console.log('Especialidad seleccionada:', specialty);
    
    if (!specialty) {
        // Si no hay especialidad seleccionada, limpiar la lista
        doctorsListContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Selecciona una especialidad para ver los médicos disponibles.
            </div>
        `;
        return;
    }
    
    // Mostrar indicador de carga
    doctorsListContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando médicos...</p>
        </div>
    `;
    
    // Realizar la solicitud al servidor
    fetch(`../../app/get_doctors_by_specialty.php?specialty=${encodeURIComponent(specialty)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (data.success) {
                // Mostrar los médicos
                displayDoctorsWithDetails(data.doctors, specialty);
            } else {
                // Mostrar mensaje de error
                doctorsListContainer.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'No se encontraron médicos para la especialidad seleccionada.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Mostrar mensaje de error
            doctorsListContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error al cargar los médicos. Por favor, intenta de nuevo más tarde.
                </div>
            `;
        });
}

/**
 * Muestra los médicos con sus detalles
 * @param {Array} doctors - Lista de médicos
 * @param {string} specialty - Especialidad seleccionada
 */
function displayDoctorsWithDetails(doctors, specialty) {
    console.log('Mostrando médicos con detalles:', { doctors, specialty });
    
    const container = document.getElementById('doctorsList');
    container.innerHTML = '';
    
    if (!doctors || doctors.length === 0) {
        console.log('No hay médicos disponibles');
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No hay médicos disponibles para la especialidad ${specialty}.
            </div>
        `;
        return;
    }
    
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
                reasonsHtml += `
                    <span class="badge bg-light text-dark me-2 mb-2 p-2">
                        <i class="fas fa-tag me-1"></i>${reason}
                    </span>
                `;
            });
            
            reasonsHtml += '</div></div>';
        }
        
        // Crear tarjeta de médico
        const doctorCol = document.createElement('div');
        doctorCol.className = 'col-md-6 mb-4';
        doctorCol.innerHTML = `
            <div class="card h-100 doctor-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-md fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">${doctor.name}</h5>
                            <p class="text-muted mb-0">${specialty}</p>
                        </div>
                    </div>
                    <p class="card-text">${doctor.bio || 'Especialista en ' + specialty}</p>
                    ${reasonsHtml}
                    ${availabilityHtml}
                    <div class="mt-3 d-flex justify-content-end">
                        <button class="btn btn-primary" onclick="AppointmentManager.requestAppointment(${doctor.id || 1}, '${doctor.name}', '${specialty}')">
                            <i class="fas fa-calendar-plus me-2"></i> Solicitar cita
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        doctorsRow.appendChild(doctorCol);
    });
}

/**
 * Filtra los médicos según el texto de búsqueda
 */
function filterDoctors() {
    const searchInput = document.getElementById('searchDoctor');
    const doctorCards = document.querySelectorAll('.doctor-card');
    
    if (!searchInput || !doctorCards.length) {
        return;
    }
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    // Si hay un término de búsqueda, añadirlo al historial
    if (searchTerm) {
        addToSearchHistory(searchTerm);
    }
    
    doctorCards.forEach(card => {
        const doctorName = card.querySelector('.card-title').textContent.toLowerCase();
        const parent = card.closest('.col-md-6');
        
        if (doctorName.includes(searchTerm) || !searchTerm) {
            parent.style.display = 'block';
        } else {
            parent.style.display = 'none';
        }
    });
}

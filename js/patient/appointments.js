// Variables globales
let currentUser = null;
let availableDoctors = [];
let availableTimeSlots = {};
let selectedDoctor = null;
let selectedDate = null;
let selectedTimeSlot = null;

// Función para formatear la hora (HH:MM:SS a formato 12h)
function formatTime(timeString) {
    if (!timeString) return '';
    
    try {
        const timeParts = timeString.split(':');
        let hours = parseInt(timeParts[0]);
        const minutes = timeParts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // La hora '0' debe ser '12'
        
        return `${hours}:${minutes} ${ampm}`;
    } catch (e) {
        console.error('Error al formatear la hora:', e);
        return timeString;
    }
}

// Inicializar la aplicación cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Establecer la fecha mínima como hoy
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    document.getElementById('selected_date').min = formattedDate;
    document.getElementById('selected_date').value = formattedDate;
    selectedDate = formattedDate;
    
    // Configurar eventos
    setupEventListeners();
});

// Escuchar el evento de sesión lista
document.addEventListener('salutia:session-ready', function(e) {
    console.log('Evento de sesión recibido:', e.detail);
    
    if (e.detail.isLoggedIn) {
        // Si hay sesión activa, establecer el usuario actual
        currentUser = {
            id: e.detail.userId,
            name: e.detail.userName,
            role: e.detail.userRole
        };
        
        console.log('Usuario actual establecido:', currentUser);
        
        // Si es médico, mostrar sus citas
        if (e.detail.userRole === 'doctor') {
            setRole('doctor');
        } else {
            setRole('patient');
        }
        
        // Cargar citas del usuario actual
        loadAppointments();
    } else {
        // Si no hay sesión, cargar usuarios para el selector
        loadUsers();
    }
});

// Configurar los event listeners
function setupEventListeners() {
    // Cambio de especialidad
    document.getElementById('specialty').addEventListener('change', handleSpecialtyChange);
    
    // Cambio de fecha
    document.getElementById('selected_date').addEventListener('change', handleDateChange);
    
    // Botones de rol
    document.getElementById('patientRoleBtn').addEventListener('click', function() {
        setRole('patient');
    });
    
    document.getElementById('doctorRoleBtn').addEventListener('click', function() {
        setRole('doctor');
    });
    
    // Tabs de estado
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            loadAppointments();
        });
    });
    
    // Botón de cargar citas
    document.getElementById('loadAppointments').addEventListener('click', loadAppointments);
    
    // Formulario de citas
    document.getElementById('appointmentForm').addEventListener('submit', handleAppointmentSubmit);
}

// Función para mostrar mensajes de error
function showError(message) {
    const errorDiv = document.getElementById('error');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

// Función para mostrar mensajes de éxito
function showSuccess(message) {
    const successDiv = document.getElementById('success');
    successDiv.textContent = message;
    successDiv.style.display = 'block';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        successDiv.style.display = 'none';
    }, 5000);
}

// Cargar usuarios para el selector
async function loadUsers() {
    try {
        const response = await fetch('backend/api/users.php');
        const data = await response.json();
        
        if (data.success) {
            const userSelect = document.getElementById('user_id');
            userSelect.innerHTML = '<option value="">Seleccione un usuario</option>';
            
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.first_name} ${user.last_name} (${user.role})`;
                option.dataset.role = user.role;
                userSelect.appendChild(option);
            });
            
            // Si hay un usuario en localStorage, seleccionarlo
            const savedUserId = localStorage.getItem('user_id');
            if (savedUserId) {
                userSelect.value = savedUserId;
                const selectedOption = userSelect.options[userSelect.selectedIndex];
                if (selectedOption) {
                    const role = selectedOption.dataset.role;
                    setRole(role);
                    loadAppointments();
                }
            }
        } else {
            showError('Error al cargar usuarios: ' + data.error);
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        showError('Error al cargar usuarios: ' + error.message);
    }
}

// Manejar cambio de especialidad
function handleSpecialtyChange() {
    const specialty = document.getElementById('specialty').value;
    if (specialty) {
        loadDoctorsBySpecialty(specialty);
    } else {
        document.getElementById('doctorSelectionContainer').style.display = 'none';
        document.getElementById('timeSlotContainer').style.display = 'none';
    }
}

// Cargar médicos por especialidad
async function loadDoctorsBySpecialty(specialty) {
    try {
        const response = await fetch(`backend/api/doctors.php?specialty=${specialty}`);
        const data = await response.json();
        
        if (data.success) {
            availableDoctors = data.doctors;
            renderDoctors();
            document.getElementById('doctorSelectionContainer').style.display = 'block';
        } else {
            showError('Error al cargar médicos: ' + data.error);
        }
    } catch (error) {
        console.error('Error al cargar médicos:', error);
        showError('Error al cargar médicos: ' + error.message);
    }
}

// Renderizar tarjetas de médicos
function renderDoctors() {
    const doctorSelection = document.getElementById('doctorSelection');
    doctorSelection.innerHTML = '';
    
    if (availableDoctors.length === 0) {
        doctorSelection.innerHTML = '<p>No hay médicos disponibles para esta especialidad</p>';
        return;
    }
    
    availableDoctors.forEach(doctor => {
        const doctorCard = document.createElement('div');
        doctorCard.className = 'doctor-card';
        doctorCard.dataset.id = doctor.id;
        
        const initials = doctor.first_name.charAt(0) + doctor.last_name.charAt(0);
        
        doctorCard.innerHTML = `
            <div class="doctor-info">
                <div class="doctor-avatar">${initials}</div>
                <div>
                    <div class="doctor-name">Dr. ${doctor.first_name} ${doctor.last_name}</div>
                    <div class="doctor-specialty">${doctor.specialty}</div>
                </div>
            </div>
        `;
        
        doctorCard.addEventListener('click', function() {
            document.querySelectorAll('.doctor-card').forEach(card => {
                card.classList.remove('selected');
            });
            this.classList.add('selected');
            selectedDoctor = doctor;
            document.getElementById('doctor_id').value = doctor.id;
            
            // Cargar horarios disponibles
            loadAvailableTimeSlots();
        });
        
        doctorSelection.appendChild(doctorCard);
    });
}

// Manejar cambio de fecha
function handleDateChange() {
    selectedDate = document.getElementById('selected_date').value;
    if (selectedDoctor) {
        loadAvailableTimeSlots();
    }
}

// Cargar horarios disponibles
async function loadAvailableTimeSlots() {
    try {
        if (!selectedDoctor || !selectedDate) {
            return;
        }
        
        document.getElementById('timeSlotContainer').style.display = 'block';
        const timeSlots = document.getElementById('timeSlots');
        timeSlots.innerHTML = '<div class="loading-spinner"></div><p>Cargando horarios disponibles...</p>';
        
        // Intentar primero con doctor_slots.php (nueva API)
        try {
            const response = await fetch(`backend/api/doctor_slots.php?doctor_id=${selectedDoctor.id}&start_date=${selectedDate}&end_date=${selectedDate}`);
            const data = await response.json();
            
            if (data.success) {
                renderTimeSlots(data.available_slots || []);
                return;
            }
        } catch (e) {
            console.log('Error con doctor_slots.php, intentando con doctor_availability.php');
        }
        
        // Si falla, intentar con doctor_availability.php (API original)
        try {
            const response = await fetch(`backend/api/doctor_availability.php?doctor_id=${selectedDoctor.id}&start_date=${selectedDate}&end_date=${selectedDate}`);
            const data = await response.json();
            
            if (data.success) {
                // Adaptar el formato de los datos si es necesario
                let slots = data.available_slots || [];
                
                // Comprobar si los slots tienen time_slot en lugar de time
                if (slots.length > 0 && 'time_slot' in slots[0]) {
                    slots = slots.map(slot => ({
                        ...slot,
                        time: slot.time_slot,
                        formatted_time: formatTime(slot.time_slot)
                    }));
                }
                
                renderTimeSlots(slots);
                return;
            } else {
                timeSlots.innerHTML = `<p class="error-message">Error: ${data.error || 'No se pudieron cargar los horarios'}</p>`;
            }
        } catch (error) {
            console.error('Error al cargar horarios disponibles:', error);
            document.getElementById('timeSlots').innerHTML = `<p class="error-message">Error: ${error.message}</p>`;
        }
    } catch (error) {
        console.error('Error general al cargar horarios:', error);
        document.getElementById('timeSlots').innerHTML = `<p class="error-message">Error: ${error.message}</p>`;
    }
}

// Renderizar horarios disponibles
function renderTimeSlots(slots) {
    const timeSlots = document.getElementById('timeSlots');
    timeSlots.innerHTML = '';
    
    // Filtrar slots para la fecha seleccionada
    const filteredSlots = slots.filter(slot => slot.date === selectedDate);
    
    if (filteredSlots.length === 0) {
        timeSlots.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>No hay horarios disponibles para esta fecha</p>
                <small>Por favor, seleccione otra fecha o médico</small>
            </div>
        `;
        return;
    }
    
    // Ordenar por hora
    filteredSlots.sort((a, b) => a.time.localeCompare(b.time));
    
    // Crear elementos para cada slot
    filteredSlots.forEach(slot => {
        const timeSlot = document.createElement('div');
        timeSlot.className = 'time-slot';
        timeSlot.textContent = slot.formatted_time || formatTime(slot.time);
        timeSlot.dataset.time = slot.time;
        timeSlot.dataset.date = slot.date;
        
        timeSlot.addEventListener('click', function() {
            document.querySelectorAll('.time-slot').forEach(ts => {
                ts.classList.remove('selected');
            });
            this.classList.add('selected');
            selectedTimeSlot = {
                date: slot.date,
                time: slot.time
            };
            
            // Actualizar el campo oculto de fecha y hora
            document.getElementById('appointment_date').value = `${slot.date}T${slot.time}`;
        });
        
        timeSlots.appendChild(timeSlot);
    });
}

// Manejar envío del formulario de cita
async function handleAppointmentSubmit(event) {
    event.preventDefault();
    
    // Validar que se haya seleccionado un médico y un horario
    if (!selectedDoctor) {
        showError('Por favor, seleccione un médico');
        return;
    }
    
    if (!selectedTimeSlot) {
        showError('Por favor, seleccione un horario disponible');
        return;
    }
    
    // Obtener datos del formulario
    const patientId = document.getElementById('patient_id').value || localStorage.getItem('user_id');
    if (!patientId) {
        showError('Por favor, inicie sesión o seleccione un paciente');
        return;
    }
    
    const formData = {
        patient_id: patientId,
        doctor_id: selectedDoctor.id,
        date: selectedTimeSlot.date,
        time: selectedTimeSlot.time,
        duration: document.getElementById('duration').value,
        reason: document.getElementById('reason').value,
        notes: document.getElementById('notes').value
    };
    
    try {
        const response = await fetch('backend/api/appointments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Cita creada correctamente');
            document.getElementById('appointmentForm').reset();
            
            // Reiniciar selecciones
            selectedDoctor = null;
            selectedTimeSlot = null;
            document.getElementById('doctorSelectionContainer').style.display = 'none';
            document.getElementById('timeSlotContainer').style.display = 'none';
            
            // Recargar citas
            loadAppointments();
        } else {
            showError('Error al crear la cita: ' + data.error);
        }
    } catch (error) {
        console.error('Error al crear la cita:', error);
        showError('Error al crear la cita: ' + error.message);
    }
}

// Establecer el rol seleccionado
function setRole(role) {
    document.getElementById('role').value = role;
    
    if (role === 'patient') {
        document.getElementById('patientRoleBtn').classList.add('active');
        document.getElementById('doctorRoleBtn').classList.remove('active');
    } else {
        document.getElementById('patientRoleBtn').classList.remove('active');
        document.getElementById('doctorRoleBtn').classList.add('active');
    }
    
    // Recargar citas si hay un usuario seleccionado
    if (document.getElementById('user_id').value) {
        loadAppointments();
    }
}

// Función para cargar las citas
async function loadAppointments() {
    try {
        const userId = document.getElementById('user_id').value;
        const role = document.getElementById('role').value;
        const status = document.querySelector('.tab.active').dataset.status;
        
        if (!userId) {
            // No mostrar error, simplemente mostrar tabla vacía
            document.getElementById('appointmentsEmpty').style.display = 'block';
            document.getElementById('appointmentsList').style.display = 'none';
            return;
        }
        
        // Mostrar indicador de carga
        document.getElementById('appointmentsLoading').style.display = 'flex';
        document.getElementById('appointmentsEmpty').style.display = 'none';
        document.getElementById('appointmentsList').style.display = 'none';
        
        // Intentar primero con el endpoint directo
        try {
            const response = await fetch(`backend/api/appointments_direct.php?user_id=${userId}&role=${role}&status=${status}`);
            const data = await response.json();
            
            // Ocultar indicador de carga
            document.getElementById('appointmentsLoading').style.display = 'none';
            
            if (data.success) {
                renderAppointments(data.appointments);
                return;
            }
        } catch (directError) {
            console.log('Error con endpoint directo, intentando con el original:', directError);
        }
        
        // Si falla, intentar con el endpoint original
        const response = await fetch(`backend/api/appointments.php?user_id=${userId}&role=${role}&status=${status}`);
        const data = await response.json();
        
        // Ocultar indicador de carga
        document.getElementById('appointmentsLoading').style.display = 'none';
        
        if (data.success) {
            renderAppointments(data.appointments);
        } else {
            showError('Error al cargar citas: ' + data.error);
            document.getElementById('appointmentsEmpty').style.display = 'block';
        }
    } catch (error) {
        console.error('Error al cargar citas:', error);
        showError('Error al cargar citas: ' + error.message);
        document.getElementById('appointmentsLoading').style.display = 'none';
        document.getElementById('appointmentsEmpty').style.display = 'block';
    }
}

// Función para renderizar las citas en la tabla
function renderAppointments(appointments) {
    const tableBody = document.getElementById('appointmentsTableBody');
    tableBody.innerHTML = '';
    
    if (appointments.length === 0) {
        document.getElementById('appointmentsEmpty').style.display = 'block';
        document.getElementById('appointmentsList').style.display = 'none';
        return;
    }
    
    document.getElementById('appointmentsEmpty').style.display = 'none';
    document.getElementById('appointmentsList').style.display = 'block';
    
    appointments.forEach(appointment => {
        const row = document.createElement('tr');
        
        // Separar fecha y hora para mejor visualización
        const formattedDate = formatDate(appointment.date);
        const formattedTime = formatTime(appointment.time);
        
        row.innerHTML = `
            <td>${appointment.id}</td>
            <td>
                <div class="user-info">
                    <div class="user-avatar patient-avatar">${getInitials(appointment.patient_name)}</div>
                    <div>${appointment.patient_name}</div>
                </div>
            </td>
            <td>
                <div class="user-info">
                    <div class="user-avatar doctor-avatar">${getInitials(appointment.doctor_name)}</div>
                    <div>
                        <div>${appointment.doctor_name}</div>
                        <small>${appointment.doctor_specialty || 'Médico'}</small>
                    </div>
                </div>
            </td>
            <td>${formattedDate}</td>
            <td>${formattedTime}</td>
            <td>${appointment.duration} min</td>
            <td>${getStatusBadge(appointment.status)}</td>
            <td>
                <div class="appointment-reason" title="${appointment.reason}">
                    ${truncateText(appointment.reason, 30)}
                    ${appointment.notes ? `<i class="fas fa-sticky-note" title="${appointment.notes}"></i>` : ''}
                </div>
            </td>
            <td class="appointment-actions">
                ${getActionButtons(appointment)}
            </td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Agregar event listeners a los botones de acción
    document.querySelectorAll('.confirm-btn').forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.dataset.id;
            updateAppointmentStatus(appointmentId, 'confirmed');
        });
    });
    
    document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.dataset.id;
            updateAppointmentStatus(appointmentId, 'cancelled');
        });
    });
    
    document.querySelectorAll('.complete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const appointmentId = this.dataset.id;
            updateAppointmentStatus(appointmentId, 'completed');
        });
    });
}

// Obtener iniciales de un nombre
function getInitials(name) {
    if (!name) return '??';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
    }
    return parts[0].charAt(0).toUpperCase();
}

// Truncar texto largo
function truncateText(text, maxLength) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// Formatear fecha
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Formatear hora
function formatTime(timeString) {
    if (!timeString) return '';
    // Convertir de formato 24h a 12h
    const timeParts = timeString.split(':');
    let hours = parseInt(timeParts[0]);
    const minutes = timeParts[1];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // La hora '0' debe ser '12'
    return `${hours}:${minutes} ${ampm}`;
}

// Obtener badge para el estado
function getStatusBadge(status) {
    const statusMap = {
        'pending': '<span class="status-badge pending"><i class="fas fa-clock"></i> Pendiente</span>',
        'confirmed': '<span class="status-badge confirmed"><i class="fas fa-check"></i> Confirmada</span>',
        'completed': '<span class="status-badge completed"><i class="fas fa-check-double"></i> Completada</span>',
        'cancelled': '<span class="status-badge cancelled"><i class="fas fa-times"></i> Cancelada</span>'
    };
    
    return statusMap[status] || `<span class="status-badge">${status}</span>`;
}

// Obtener botones de acción según el estado
function getActionButtons(appointment) {
    const role = document.getElementById('role').value;
    const buttons = [];
    
    if (appointment.status === 'pending') {
        if (role === 'doctor') {
            buttons.push(`<button class="confirm-btn" data-id="${appointment.id}"><i class="fas fa-check"></i> Confirmar</button>`);
        }
        buttons.push(`<button class="cancel-btn" data-id="${appointment.id}"><i class="fas fa-times"></i> Cancelar</button>`);
    } else if (appointment.status === 'confirmed') {
        if (role === 'doctor') {
            buttons.push(`<button class="complete-btn" data-id="${appointment.id}"><i class="fas fa-check-double"></i> Completar</button>`);
        }
        buttons.push(`<button class="cancel-btn" data-id="${appointment.id}"><i class="fas fa-times"></i> Cancelar</button>`);
    }
    
    return buttons.join('');
}

// Actualizar el estado de una cita
async function updateAppointmentStatus(appointmentId, status) {
    try {
        const response = await fetch('backend/api/appointments.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: appointmentId,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(`Cita ${getStatusText(status)} correctamente`);
            loadAppointments();
        } else {
            showError('Error al actualizar la cita: ' + data.error);
        }
    } catch (error) {
        console.error('Error al actualizar la cita:', error);
        showError('Error al actualizar la cita: ' + error.message);
    }
}

// Obtener texto para el estado
function getStatusText(status) {
    const statusMap = {
        'confirmed': 'confirmada',
        'completed': 'completada',
        'cancelled': 'cancelada'
    };
    
    return statusMap[status] || status;
}

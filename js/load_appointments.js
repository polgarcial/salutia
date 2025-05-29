// Script para cargar citas en el dashboard del médico
console.log('Inicializando carga de citas...');

// Función para cargar las citas desde la API
async function loadAppointments() {
    try {
        const token = localStorage.getItem('token');
        if (!token) {
            throw new Error('No hay token disponible');
        }

        const response = await fetch('./backend/api/get_doctor_appointments.php', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Citas cargadas correctamente:', data.appointments);
            return data.appointments;
        } else {
            console.error('Error al cargar citas:', data.message);
            return [];
        }
    } catch (error) {
        console.error('Error al cargar citas:', error);
        return [];
    }
}

// Función para cargar o crear citas de demostración
async function loadOrCreateDemoAppointments() {
    try {
        console.log('Verificando si existen citas...');
        
        // Intentar cargar citas existentes
        const appointments = await loadAppointments(1); // Asumimos ID 1 para el médico de demostración
        
        if (appointments && appointments.length > 0) {
            console.log('Se encontraron citas existentes:', appointments.length);
            displayAppointments(appointments);
            updateDashboardStats(appointments);
            return;
        }
        
        console.log('No se encontraron citas. Creando citas de demostración...');
        
        // Si no hay citas, crear citas de demostración
        const response = await fetch('backend/api/save_demo_appointments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Citas de demostración creadas:', data.appointments);
            // Cargar las citas recién creadas
            const newAppointments = await loadAppointments(1);
            displayAppointments(newAppointments);
            updateDashboardStats(newAppointments);
        } else {
            console.error('Error al crear citas de demostración:', data.message);
            // Mostrar citas de ejemplo en la interfaz aunque no se hayan guardado en la BD
            displayDemoAppointments();
        }
    } catch (error) {
        console.error('Error en el proceso de carga/creación de citas:', error);
        // En caso de error, mostrar citas de ejemplo en la interfaz
        displayDemoAppointments();
    }
}

// Función para mostrar citas de demostración en la interfaz (sin guardarlas en la BD)
function displayDemoAppointments() {
    console.log('Mostrando citas de demostración en la interfaz...');
    
    // Obtener la fecha actual
    const today = new Date();
    
    // Crear citas de ejemplo
    const demoAppointments = [
        {
            id: 1001,
            appointment_date: today.toISOString().split('T')[0],
            appointment_time: '10:30:00',
            patient_name: 'María García',
            reason: 'Revisión anual'
        },
        {
            id: 1002,
            appointment_date: today.toISOString().split('T')[0],
            appointment_time: '12:00:00',
            patient_name: 'Carlos Rodríguez',
            reason: 'Dolor de espalda'
        },
        {
            id: 1003,
            appointment_date: new Date(today.getTime() + 86400000).toISOString().split('T')[0], // Mañana
            appointment_time: '09:30:00',
            patient_name: 'Ana Martínez',
            reason: 'Consulta dermatológica'
        },
        {
            id: 1004,
            appointment_date: new Date(today.getTime() + 86400000 * 2).toISOString().split('T')[0], // Pasado mañana
            appointment_time: '11:00:00',
            patient_name: 'Juan López',
            reason: 'Control de hipertensión'
        },
        {
            id: 1005,
            appointment_date: new Date(today.getTime() + 86400000 * 5).toISOString().split('T')[0], // En 5 días
            appointment_time: '16:30:00',
            patient_name: 'Sofía Fernández',
            reason: 'Dolor de cabeza recurrente'
        }
    ];
    
    displayAppointments(demoAppointments);
    updateDashboardStats(demoAppointments);
}

// Función para actualizar las estadísticas del dashboard
function updateDashboardStats(appointments) {
    if (!appointments || !Array.isArray(appointments)) {
        console.error('No hay citas válidas para actualizar estadísticas');
        return;
    }
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Filtrar citas de hoy
    const todayAppointments = appointments.filter(appointment => {
        const appointmentDate = new Date(appointment.appointment_date);
        appointmentDate.setHours(0, 0, 0, 0);
        return appointmentDate.getTime() === today.getTime();
    });
    
    // Filtrar citas pendientes (hoy y futuras)
    const pendingAppointments = appointments.filter(appointment => {
        const appointmentDate = new Date(appointment.appointment_date);
        appointmentDate.setHours(0, 0, 0, 0);
        return appointmentDate.getTime() >= today.getTime();
    });
    
    // Obtener pacientes únicos
    const uniquePatients = new Set();
    appointments.forEach(appointment => {
        if (appointment.patient_id) {
            uniquePatients.add(appointment.patient_id);
        } else if (appointment.patient_name) {
            uniquePatients.add(appointment.patient_name);
        }
    });
    
    // Actualizar contadores en el dashboard
    document.getElementById('todayAppointmentsCount').textContent = todayAppointments.length;
}

// Función para mostrar las citas en el dashboard
function displayAppointments(appointments) {
    console.log('Mostrando citas en el dashboard...');
    
    const citasContainer = document.getElementById('proximasCitas');
    
    // Limpiar el contenedor
    citasContainer.innerHTML = '';
    
    if (!appointments || appointments.length === 0) {
        citasContainer.innerHTML = '<p class="text-center">No hay citas programadas.</p>';
        return;
    }
    
    // Crear elementos HTML para cada cita
    appointments.forEach(cita => {
        const fecha = new Date(cita.fecha);
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const horaFormateada = fecha.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit'
        });

        const citaElement = document.createElement('div');
        citaElement.className = 'appointment-card';
        citaElement.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${fechaFormateada} - ${horaFormateada}</strong><br>
                    Paciente: ${cita.paciente_nombre}<br>
                    Motivo: ${cita.motivo}
                </div>
                <button class="btn btn-primary btn-sm" onclick="viewAppointmentDetails(${cita.id})">Ver detalles</button>
            </div>
        `;
        
        citasContainer.appendChild(citaElement);
    });
}

// Función para ver detalles de una cita
function viewAppointmentDetails(appointmentId) {
    alert(`Ver detalles de la cita ID: ${appointmentId}`);
    // Aquí se implementaría la lógica para mostrar los detalles de la cita
}

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, iniciando carga de citas...');
    loadAppointments().then(appointments => {
        if (appointments && appointments.length > 0) {
            displayAppointments(appointments);
            updateDashboardStats(appointments);
        } else {
            // Si no hay citas, mostrar mensajes por defecto
            document.getElementById('citasHoy').textContent = '0';
            document.getElementById('citasPendientes').textContent = '0';
            document.getElementById('totalPacientes').textContent = '0';
            document.getElementById('proximasCitas').innerHTML = 
                '<p class="text-center">No hay citas programadas.</p>';
        }
    });
});

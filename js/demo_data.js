// Script para generar datos de demostración para el dashboard
console.log('Cargando datos de demostración para el dashboard');

// Función para generar citas de ejemplo
function generarCitasEjemplo() {
    // Obtener la fecha actual
    const hoy = new Date();
    
    // Crear un array de pacientes de ejemplo
    const pacientes = [
        { id: 101, nombre: "María García", edad: 42, motivo: "Revisión anual" },
        { id: 102, nombre: "Carlos Rodríguez", edad: 35, motivo: "Dolor de espalda" },
        { id: 103, nombre: "Ana Martínez", edad: 28, motivo: "Consulta dermatológica" },
        { id: 104, nombre: "Juan López", edad: 56, motivo: "Control de hipertensión" },
        { id: 105, nombre: "Sofía Fernández", edad: 31, motivo: "Dolor de cabeza recurrente" },
        { id: 106, nombre: "Miguel Sánchez", edad: 45, motivo: "Revisión post-operatoria" },
        { id: 107, nombre: "Laura Gómez", edad: 39, motivo: "Análisis de resultados" }
    ];
    
    // Crear un array para almacenar las citas
    const citas = [];
    
    // Generar citas para los próximos 10 días
    for (let i = 0; i < 10; i++) {
        // Crear una nueva fecha sumando i días a la fecha actual
        const fecha = new Date(hoy);
        fecha.setDate(hoy.getDate() + i);
        
        // Formatear la fecha como YYYY-MM-DD
        const fechaFormateada = fecha.toISOString().split('T')[0];
        
        // Generar entre 1 y 3 citas para cada día
        const numCitas = Math.floor(Math.random() * 3) + 1;
        
        for (let j = 0; j < numCitas; j++) {
            // Seleccionar un paciente aleatorio
            const paciente = pacientes[Math.floor(Math.random() * pacientes.length)];
            
            // Generar una hora aleatoria entre 9:00 y 17:00
            const hora = 9 + Math.floor(Math.random() * 8);
            const minutos = Math.random() < 0.5 ? "00" : "30";
            const horaFormateada = `${hora.toString().padStart(2, '0')}:${minutos}`;
            
            // Crear el objeto de cita
            const cita = {
                id: 1000 + citas.length,
                date: fechaFormateada,
                time: `${horaFormateada}:00`,
                patient_id: paciente.id,
                patient_name: paciente.nombre,
                patient_age: paciente.edad,
                reason: paciente.motivo,
                status: "scheduled",
                notes: ""
            };
            
            citas.push(cita);
        }
    }
    
    return citas;
}

// Función para insertar las citas de ejemplo en el dashboard
function mostrarCitasEjemplo() {
    // Obtener el contenedor de citas
    const contenedorCitas = document.getElementById('upcomingAppointments');
    
    // Si no existe el contenedor, salir
    if (!contenedorCitas) {
        console.error('No se encontró el contenedor de citas');
        return;
    }
    
    // Verificar si ya hay contenido en el contenedor (que no sea el mensaje de carga)
    if (contenedorCitas.innerHTML.includes('No tienes citas programadas próximamente') || 
        contenedorCitas.innerHTML.includes('Cargando citas...')) {
        
        console.log('No hay citas reales, mostrando citas de ejemplo');
        
        // Generar citas de ejemplo
        const citasEjemplo = generarCitasEjemplo();
        
        // Mostrar las citas utilizando la función existente si está disponible
        if (typeof displayAppointments === 'function') {
            displayAppointments(citasEjemplo);
        } else {
            // Si la función no está disponible, mostrar las citas manualmente
            contenedorCitas.innerHTML = '';
            
            // Mostrar solo las primeras 5 citas
            const citasMostradas = citasEjemplo.slice(0, 5);
            
            citasMostradas.forEach(cita => {
                const fechaCita = new Date(`${cita.date}T${cita.time}`);
                
                const itemCita = document.createElement('div');
                itemCita.className = 'appointment-item';
                
                const opcionesFecha = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                const opcionesHora = { hour: '2-digit', minute: '2-digit' };
                
                const fechaFormateada = fechaCita.toLocaleDateString('es-ES', opcionesFecha);
                const horaFormateada = fechaCita.toLocaleTimeString('es-ES', opcionesHora);
                
                itemCita.innerHTML = `
                    <div class="appointment-date">${fechaFormateada} - ${horaFormateada}</div>
                    <div class="appointment-patient">
                        <strong>Paciente:</strong> ${cita.patient_name}
                    </div>
                    <div class="appointment-reason">
                        <strong>Motivo:</strong> ${cita.reason}
                    </div>
                    <div class="appointment-actions">
                        <button onclick="viewAppointmentDetails(${cita.id})">Ver detalles</button>
                    </div>
                `;
                
                contenedorCitas.appendChild(itemCita);
            });
            
            // Si hay más citas, mostrar un enlace para ver todas
            if (citasEjemplo.length > 5) {
                const enlaceVerTodas = document.createElement('div');
                enlaceVerTodas.className = 'view-all';
                enlaceVerTodas.innerHTML = `<a href="#">Ver todas las citas (${citasEjemplo.length})</a>`;
                contenedorCitas.appendChild(enlaceVerTodas);
            }
        }
        
        // Actualizar los contadores en las tarjetas de estadísticas
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        const citasHoy = citasEjemplo.filter(cita => {
            const fechaCita = new Date(cita.date);
            fechaCita.setHours(0, 0, 0, 0);
            return fechaCita.getTime() === hoy.getTime();
        });
        
        const citasPendientes = citasEjemplo.filter(cita => {
            const fechaCita = new Date(cita.date);
            fechaCita.setHours(0, 0, 0, 0);
            return fechaCita >= hoy;
        });
        
        // Actualizar contadores
        document.getElementById('todayAppointmentsCount').textContent = citasHoy.length;
        document.getElementById('pendingAppointmentsCount').textContent = citasPendientes.length;
        document.getElementById('totalPatientsCount').textContent = 7; // Número de pacientes de ejemplo
    }
}

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, esperando 2 segundos para verificar si hay citas reales...');
    
    // Esperar 2 segundos para dar tiempo a que se carguen las citas reales
    setTimeout(mostrarCitasEjemplo, 2000);
});

console.log('Script de datos de demostración inicializado');

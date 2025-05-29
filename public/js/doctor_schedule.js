/**
 * Script para la gestión de horarios del médico
 * Este script maneja la funcionalidad de configuración de horarios y días no disponibles
 */

// Objeto para gestionar los horarios del médico
const DoctorScheduleManager = {
    // Inicializar el gestor de horarios
    init: function() {
        console.log('Inicializando gestor de horarios');
        
        // Cargar horarios guardados
        this.loadSchedule();
        
        // Cargar días no disponibles
        this.loadUnavailableDays();
        
        // Configurar eventos
        this.setupEventListeners();
    },
    
    // Configurar los eventos de la página
    setupEventListeners: function() {
        // Botón para añadir horario
        document.getElementById('addScheduleBtn').addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('addScheduleModal'));
            modal.show();
        });
        
        // Botón para guardar horario
        document.getElementById('saveScheduleBtn').addEventListener('click', () => {
            this.saveSchedule();
        });
        
        // Botón para añadir día no disponible
        document.getElementById('addUnavailableDayBtn').addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('addUnavailableDayModal'));
            modal.show();
        });
        
        // Botón para guardar día no disponible
        document.getElementById('saveUnavailableDayBtn').addEventListener('click', () => {
            this.saveUnavailableDay();
        });
        
        // Configurar eventos para los botones de editar y eliminar horarios
        document.querySelectorAll('.edit-schedule-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.editSchedule(e.target.closest('.time-slot-item').dataset.id);
            });
        });
        
        document.querySelectorAll('.delete-schedule-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.deleteSchedule(e.target.closest('.time-slot-item').dataset.id);
            });
        });
        
        // Configurar eventos para los botones de eliminar días no disponibles
        document.querySelectorAll('.delete-unavailable-day-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.deleteUnavailableDay(e.target.closest('tr').dataset.id);
            });
        });
    },
    
    // Cargar horarios guardados
    loadSchedule: function() {
        console.log('Cargando horarios');
        
        // Obtener horarios guardados en localStorage
        const savedSchedules = JSON.parse(localStorage.getItem('doctor_schedules')) || [];
        
        // Si no hay horarios guardados, usar datos de ejemplo
        if (savedSchedules.length === 0) {
            const exampleSchedules = [
                { id: 1, day: 1, dayName: 'Lunes', startTime: '09:00', endTime: '12:00' },
                { id: 2, day: 2, dayName: 'Martes', startTime: '09:00', endTime: '12:00' },
                { id: 3, day: 2, dayName: 'Martes', startTime: '15:00', endTime: '18:00' },
                { id: 4, day: 3, dayName: 'Miércoles', startTime: '09:00', endTime: '12:00' },
                { id: 5, day: 4, dayName: 'Jueves', startTime: '15:00', endTime: '18:00' },
                { id: 6, day: 5, dayName: 'Viernes', startTime: '09:00', endTime: '14:00' }
            ];
            
            localStorage.setItem('doctor_schedules', JSON.stringify(exampleSchedules));
            this.displaySchedules(exampleSchedules);
        } else {
            this.displaySchedules(savedSchedules);
        }
    },
    
    // Mostrar horarios en la interfaz
    displaySchedules: function(schedules) {
        console.log('Mostrando horarios:', schedules);
        
        // Limpiar contenedores de horarios
        const dayContainers = {
            1: document.getElementById('monday-slots'),
            2: document.getElementById('tuesday-slots'),
            3: document.getElementById('wednesday-slots'),
            4: document.getElementById('thursday-slots'),
            5: document.getElementById('friday-slots'),
            6: document.getElementById('saturday-slots')
        };
        
        Object.values(dayContainers).forEach(container => {
            if (container) container.innerHTML = '';
        });
        
        // Agrupar horarios por día
        const schedulesByDay = {};
        schedules.forEach(schedule => {
            if (!schedulesByDay[schedule.day]) {
                schedulesByDay[schedule.day] = [];
            }
            schedulesByDay[schedule.day].push(schedule);
        });
        
        // Mostrar horarios por día
        for (const day in schedulesByDay) {
            const container = dayContainers[day];
            if (!container) continue;
            
            schedulesByDay[day].forEach(schedule => {
                const timeSlot = document.createElement('div');
                timeSlot.className = 'time-slot-item';
                timeSlot.dataset.id = schedule.id;
                timeSlot.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <span>${schedule.startTime} - ${schedule.endTime}</span>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-1 edit-schedule-btn">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-schedule-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(timeSlot);
                
                // Añadir eventos a los botones
                timeSlot.querySelector('.edit-schedule-btn').addEventListener('click', () => {
                    this.editSchedule(schedule.id);
                });
                
                timeSlot.querySelector('.delete-schedule-btn').addEventListener('click', () => {
                    this.deleteSchedule(schedule.id);
                });
            });
            
            // Si no hay horarios para este día, mostrar mensaje
            if (schedulesByDay[day].length === 0 && day !== '6') {
                container.innerHTML = `
                    <div class="alert alert-light text-center py-2">
                        No hay horarios configurados
                    </div>
                `;
            }
        }
        
        // Para sábado, mostrar mensaje de no disponible
        if (!schedulesByDay[6] || schedulesByDay[6].length === 0) {
            const saturdayContainer = dayContainers[6];
            if (saturdayContainer) {
                saturdayContainer.innerHTML = `
                    <div class="alert alert-light text-center py-2">
                        No disponible
                    </div>
                `;
            }
        }
    },
    
    // Guardar un nuevo horario
    saveSchedule: function() {
        console.log('Guardando horario');
        
        // Obtener datos del formulario
        const dayOfWeek = document.getElementById('dayOfWeek').value;
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        
        // Validar datos
        if (!dayOfWeek || !startTime || !endTime) {
            alert('Por favor complete todos los campos');
            return;
        }
        
        // Validar que la hora de inicio sea anterior a la hora de fin
        if (startTime >= endTime) {
            alert('La hora de inicio debe ser anterior a la hora de fin');
            return;
        }
        
        // Obtener horarios guardados
        const savedSchedules = JSON.parse(localStorage.getItem('doctor_schedules')) || [];
        
        // Generar ID para el nuevo horario
        const newId = savedSchedules.length > 0 ? Math.max(...savedSchedules.map(s => s.id)) + 1 : 1;
        
        // Obtener nombre del día
        const dayNames = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        
        // Crear nuevo horario
        const newSchedule = {
            id: newId,
            day: parseInt(dayOfWeek),
            dayName: dayNames[parseInt(dayOfWeek)],
            startTime: startTime,
            endTime: endTime
        };
        
        // Añadir a la lista de horarios
        savedSchedules.push(newSchedule);
        
        // Guardar en localStorage
        localStorage.setItem('doctor_schedules', JSON.stringify(savedSchedules));
        
        // Actualizar interfaz
        this.displaySchedules(savedSchedules);
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addScheduleModal'));
        modal.hide();
        
        // Limpiar formulario
        document.getElementById('dayOfWeek').value = '';
        document.getElementById('startTime').value = '';
        document.getElementById('endTime').value = '';
        
        // Mostrar mensaje de éxito
        this.showAlert('Horario guardado correctamente', 'success');
    },
    
    // Editar un horario existente
    editSchedule: function(scheduleId) {
        console.log('Editando horario:', scheduleId);
        
        // Obtener horarios guardados
        const savedSchedules = JSON.parse(localStorage.getItem('doctor_schedules')) || [];
        
        // Encontrar el horario a editar
        const scheduleToEdit = savedSchedules.find(s => s.id === parseInt(scheduleId));
        
        if (!scheduleToEdit) {
            alert('Horario no encontrado');
            return;
        }
        
        // Rellenar formulario con datos del horario
        document.getElementById('dayOfWeek').value = scheduleToEdit.day;
        document.getElementById('startTime').value = scheduleToEdit.startTime;
        document.getElementById('endTime').value = scheduleToEdit.endTime;
        
        // Guardar ID del horario a editar
        document.getElementById('scheduleId').value = scheduleId;
        
        // Cambiar texto del botón
        document.getElementById('saveScheduleBtn').textContent = 'Actualizar';
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('addScheduleModal'));
        modal.show();
    },
    
    // Eliminar un horario
    deleteSchedule: function(scheduleId) {
        console.log('Eliminando horario:', scheduleId);
        
        if (confirm('¿Está seguro de que desea eliminar este horario?')) {
            // Obtener horarios guardados
            const savedSchedules = JSON.parse(localStorage.getItem('doctor_schedules')) || [];
            
            // Filtrar el horario a eliminar
            const updatedSchedules = savedSchedules.filter(s => s.id !== parseInt(scheduleId));
            
            // Guardar en localStorage
            localStorage.setItem('doctor_schedules', JSON.stringify(updatedSchedules));
            
            // Actualizar interfaz
            this.displaySchedules(updatedSchedules);
            
            // Mostrar mensaje de éxito
            this.showAlert('Horario eliminado correctamente', 'success');
        }
    },
    
    // Cargar días no disponibles
    loadUnavailableDays: function() {
        console.log('Cargando días no disponibles');
        
        // Obtener días no disponibles guardados en localStorage
        const savedUnavailableDays = JSON.parse(localStorage.getItem('doctor_unavailable_days')) || [];
        
        // Si no hay días no disponibles guardados, usar datos de ejemplo
        if (savedUnavailableDays.length === 0) {
            const exampleUnavailableDays = [
                { id: 1, startDate: '2025-08-01', endDate: '2025-08-15', reason: 'Vacaciones de verano' },
                { id: 2, startDate: '2025-12-24', endDate: '2025-12-26', reason: 'Navidad' }
            ];
            
            localStorage.setItem('doctor_unavailable_days', JSON.stringify(exampleUnavailableDays));
            this.displayUnavailableDays(exampleUnavailableDays);
        } else {
            this.displayUnavailableDays(savedUnavailableDays);
        }
    },
    
    // Mostrar días no disponibles en la interfaz
    displayUnavailableDays: function(unavailableDays) {
        console.log('Mostrando días no disponibles:', unavailableDays);
        
        // Obtener tabla de días no disponibles
        const tableBody = document.getElementById('unavailableDaysTable');
        
        // Limpiar tabla
        tableBody.innerHTML = '';
        
        // Mostrar días no disponibles
        unavailableDays.forEach(day => {
            const row = document.createElement('tr');
            row.dataset.id = day.id;
            
            // Formatear fechas para mostrar
            const startDate = this.formatDate(day.startDate);
            const endDate = this.formatDate(day.endDate);
            
            row.innerHTML = `
                <td>${startDate}</td>
                <td>${endDate}</td>
                <td>${day.reason}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger delete-unavailable-day-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(row);
            
            // Añadir evento al botón de eliminar
            row.querySelector('.delete-unavailable-day-btn').addEventListener('click', () => {
                this.deleteUnavailableDay(day.id);
            });
        });
        
        // Si no hay días no disponibles, mostrar mensaje
        if (unavailableDays.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td colspan="4" class="text-center">No hay días no disponibles configurados</td>
            `;
            tableBody.appendChild(row);
        }
    },
    
    // Guardar un nuevo día no disponible
    saveUnavailableDay: function() {
        console.log('Guardando día no disponible');
        
        // Obtener datos del formulario
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const reason = document.getElementById('reason').value;
        
        // Validar datos
        if (!startDate || !endDate || !reason) {
            alert('Por favor complete todos los campos');
            return;
        }
        
        // Validar que la fecha de inicio sea anterior o igual a la fecha de fin
        if (startDate > endDate) {
            alert('La fecha de inicio debe ser anterior o igual a la fecha de fin');
            return;
        }
        
        // Obtener días no disponibles guardados
        const savedUnavailableDays = JSON.parse(localStorage.getItem('doctor_unavailable_days')) || [];
        
        // Generar ID para el nuevo día no disponible
        const newId = savedUnavailableDays.length > 0 ? Math.max(...savedUnavailableDays.map(d => d.id)) + 1 : 1;
        
        // Crear nuevo día no disponible
        const newUnavailableDay = {
            id: newId,
            startDate: startDate,
            endDate: endDate,
            reason: reason
        };
        
        // Añadir a la lista de días no disponibles
        savedUnavailableDays.push(newUnavailableDay);
        
        // Guardar en localStorage
        localStorage.setItem('doctor_unavailable_days', JSON.stringify(savedUnavailableDays));
        
        // Actualizar interfaz
        this.displayUnavailableDays(savedUnavailableDays);
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addUnavailableDayModal'));
        modal.hide();
        
        // Limpiar formulario
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('reason').value = '';
        
        // Mostrar mensaje de éxito
        this.showAlert('Día no disponible guardado correctamente', 'success');
    },
    
    // Eliminar un día no disponible
    deleteUnavailableDay: function(dayId) {
        console.log('Eliminando día no disponible:', dayId);
        
        if (confirm('¿Está seguro de que desea eliminar este día no disponible?')) {
            // Obtener días no disponibles guardados
            const savedUnavailableDays = JSON.parse(localStorage.getItem('doctor_unavailable_days')) || [];
            
            // Filtrar el día no disponible a eliminar
            const updatedUnavailableDays = savedUnavailableDays.filter(d => d.id !== parseInt(dayId));
            
            // Guardar en localStorage
            localStorage.setItem('doctor_unavailable_days', JSON.stringify(updatedUnavailableDays));
            
            // Actualizar interfaz
            this.displayUnavailableDays(updatedUnavailableDays);
            
            // Mostrar mensaje de éxito
            this.showAlert('Día no disponible eliminado correctamente', 'success');
        }
    },
    
    // Formatear fecha para mostrar
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    },
    
    // Mostrar alerta
    showAlert: function(message, type) {
        // Crear alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Añadir alerta al principio del contenido principal
        const mainContent = document.querySelector('.main-content');
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        // Eliminar alerta después de 3 segundos
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 3000);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gestor de horarios
    DoctorScheduleManager.init();
});

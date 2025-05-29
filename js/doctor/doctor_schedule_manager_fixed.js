const app = {
    // Estado
    selectedWeek: null,
    selectedYear: null,
    doctorId: null,
    weeklySchedule: [],

    // Inicialización
    init() {
        // Verificar autenticación
        const token = localStorage.getItem('token');
        const userId = localStorage.getItem('user_id');
        const userRole = localStorage.getItem('user_role');
        
        if (!token || !userId || userRole !== 'doctor') {
            this.showToast('Debe iniciar sesión como médico para acceder a esta página', 'error');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
            return;
        }
        
        // Establecer el ID del médico
        this.doctorId = userId;
        
        this.initializeWeekPicker();
        this.setupEventListeners();
    },

    setupEventListeners() {
        // Week picker
        document.getElementById('weekSelector').addEventListener('change', (e) => {
            const weekValue = e.target.value;
            const parts = weekValue.split('-W');
            this.selectedYear = parseInt(parts[0]);
            this.selectedWeek = parseInt(parts[1]);
            this.loadWeekSchedule();
        });

        // Template buttons
        document.querySelectorAll('.template-button').forEach(button => {
            button.addEventListener('click', () => {
                const template = {
                    startTime: button.dataset.startTime,
                    endTime: button.dataset.endTime
                };
                this.applyTemplate(template);
            });
        });

        // Save button
        document.getElementById('saveSchedule').addEventListener('click', () => {
            this.saveWeeklySchedule();
        });
    },

    initializeWeekPicker() {
        const today = new Date();
        const weekInput = document.getElementById('weekSelector');
        const weekString = this.getWeekString(today);
        weekInput.value = weekString;
        
        // Establecer semana y año seleccionados
        const parts = weekString.split('-W');
        this.selectedYear = parseInt(parts[0]);
        this.selectedWeek = parseInt(parts[1]);
        
        this.generateTimeTable();
        this.loadWeekSchedule();
    },

    generateTimeTable() {
        const tbody = document.querySelector('tbody');
        tbody.innerHTML = '';
        
        // Generar filas para cada hora de 9:00 a 20:00
        for (let hour = 9; hour < 20; hour++) {
            const tr = document.createElement('tr');
            
            // Columna de hora
            const tdHour = document.createElement('td');
            tdHour.textContent = `${hour}:00`;
            tr.appendChild(tdHour);
            
            // Columnas para cada día (1-5 = Lunes a Viernes)
            for (let day = 1; day <= 5; day++) {
                const tdDay = document.createElement('td');
                const cell = document.createElement('div');
                cell.className = 'schedule-cell';
                cell.dataset.day = day;
                cell.dataset.time = `${hour}:00`;
                
                // Configurar el evento de clic
                cell.addEventListener('click', function() {
                    this.classList.toggle('available');
                    if (this.classList.contains('available')) {
                        this.title = 'Disponible - Haz clic para quitar';
                    } else {
                        this.title = 'No disponible - Haz clic para añadir';
                    }
                });
                
                cell.title = 'No disponible - Haz clic para añadir';
                tdDay.appendChild(cell);
                tr.appendChild(tdDay);
            }
            
            tbody.appendChild(tr);
        }
    },

    async loadWeekSchedule() {
        try {
            // Obtener el token del localStorage
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No hay token de autenticación');
            }
            
            // Usar el ID del médico del localStorage si está disponible
            const storedDoctorId = localStorage.getItem('user_id');
            if (storedDoctorId) {
                this.doctorId = storedDoctorId;
            }
            
            const response = await fetch(`./backend/api/doctor_schedules.php?weekNumber=${this.selectedWeek}&year=${this.selectedYear}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Respuesta de carga de horarios:', data);
            
            if (data.success) {
                this.weeklySchedule = data.schedules || [];
                this.displaySchedule();
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando horarios:', error);
            this.showToast('Error cargando los horarios', 'error');
        }
    },

    async saveWeeklySchedule() {
        const schedules = this.collectScheduleData();
        
        if (schedules.length === 0) {
            this.showToast('No hay horarios para guardar', 'error');
            return;
        }
        
        this.showToast('Guardando horarios...', 'success');
        
        try {
            // Obtener el token del localStorage
            const token = localStorage.getItem('token');
            if (!token) {
                throw new Error('No hay token de autenticación');
            }
            
            // Usar el ID del médico del localStorage si está disponible
            const storedDoctorId = localStorage.getItem('user_id');
            if (storedDoctorId) {
                this.doctorId = storedDoctorId;
            }
            
            console.log('Guardando horarios:', {
                doctorId: this.doctorId,
                weekNumber: this.selectedWeek,
                year: this.selectedYear,
                schedules: schedules
            });
            
            const response = await fetch('./backend/api/doctor_schedules.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    weekNumber: this.selectedWeek,
                    year: this.selectedYear,
                    schedules: schedules
                })
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();
            if (data.success) {
                this.showToast('Horarios guardados correctamente');
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error guardando horarios:', error);
            this.showToast('Error guardando los horarios', 'error');
        }
    },

    collectScheduleData() {
        const schedules = [];
        const cells = document.querySelectorAll('.schedule-cell.available');
        
        cells.forEach(cell => {
            schedules.push({
                dayOfWeek: parseInt(cell.dataset.day),
                startTime: cell.dataset.time + ':00',
                endTime: this.calculateEndTime(cell.dataset.time) + ':00'
            });
        });
        
        return schedules;
    },

    applyTemplate(template) {
        const weekDays = [1, 2, 3, 4, 5]; // Lunes a Viernes
        const startHour = parseInt(template.startTime.split(':')[0]);
        const endHour = parseInt(template.endTime.split(':')[0]);

        weekDays.forEach(day => {
            for (let hour = startHour; hour < endHour; hour++) {
                const cell = document.querySelector(`.schedule-cell[data-day="${day}"][data-time="${hour}:00"]`);
                if (cell) {
                    cell.classList.add('available');
                    cell.title = 'Disponible - Haz clic para quitar';
                }
            }
        });
    },

    displaySchedule() {
        // Limpiar selecciones anteriores
        document.querySelectorAll('.schedule-cell').forEach(cell => {
            cell.classList.remove('available');
            cell.title = 'No disponible - Haz clic para añadir';
        });

        // Mostrar horarios guardados
        this.weeklySchedule.forEach(schedule => {
            const startHour = parseInt(schedule.startTime.split(':')[0]);
            const endHour = parseInt(schedule.endTime.split(':')[0]);
            
            for (let hour = startHour; hour < endHour; hour++) {
                const cell = document.querySelector(
                    `.schedule-cell[data-day="${schedule.dayOfWeek}"][data-time="${hour}:00"]`
                );
                if (cell) {
                    cell.classList.add('available');
                    cell.title = 'Disponible - Haz clic para quitar';
                }
            }
        });
    },

    // Funciones auxiliares
    getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    },

    getWeekString(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const weekNumber = this.getWeekNumber(d);
        return `${year}-W${weekNumber.toString().padStart(2, '0')}`;
    },

    calculateEndTime(startTime) {
        const [hour] = startTime.split(':');
        return `${parseInt(hour) + 1}`;
    },

    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => app.init());

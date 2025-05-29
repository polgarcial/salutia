const app = {
    // Estado
    selectedWeek: null,
    selectedYear: null,
    scheduleTemplate: null,
    weeklySchedule: [],

    // Inicialización
    init() {
        this.loadUserData();
        this.setupEventListeners();
    },

    loadUserData() {
        const token = localStorage.getItem('token');
        const userId = localStorage.getItem('user_id');
        
        this.doctorId = userId;
        this.token = token;
        this.initializeWeekPicker();
    },

    setupEventListeners() {
        // Week picker
        document.getElementById('weekSelector').addEventListener('change', (e) => {
            const date = new Date(e.target.value);
            this.loadWeekSchedule(date);
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

        // El evento de clic en las celdas se configura en generateTimeTable()
    },

    initializeWeekPicker() {
        const today = new Date();
        const weekInput = document.getElementById('weekSelector');
        weekInput.value = this.getWeekString(today);
        this.generateTimeTable();
        this.loadWeekSchedule(today);
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
                
                // Configurar el evento de clic directamente
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

    async loadWeekSchedule(date) {
        let weekNumber, year;
        
        if (typeof date === 'string') {
            // Formato YYYY-Www
            const parts = date.split('-W');
            year = parseInt(parts[0]);
            weekNumber = parseInt(parts[1]);
        } else {
            weekNumber = this.getWeekNumber(date);
            year = date.getFullYear();
        }
        
        try {
            const response = await fetch(`./backend/api/doctor_schedules_test.php?weekNumber=${weekNumber}&year=${year}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.weeklySchedule = data.schedules || [];
                this.displaySchedule();
                this.showToast('Horarios cargados correctamente');
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error cargando horarios:', error);
            this.showToast(`Error: ${error.message}`, 'error');
            if (error.message.includes('Token inválido')) {
                // Redirigir al login si el token es inválido
                window.location.href = 'login.html';
            }
        }
    },

    async saveWeeklySchedule() {
        const weekNumber = this.selectedWeek;
        const year = this.selectedYear;
        const schedules = this.collectScheduleData();
        
        if (!weekNumber || !year) {
            this.showToast('Por favor, selecciona una semana', 'error');
            return;
        }
        
        if (schedules.length === 0) {
            this.showToast('No hay horarios para guardar', 'error');
            return;
        }
        
        this.showToast('Guardando horarios...', 'success');
        
        try {
            const response = await fetch('./backend/api/doctor_schedules_test.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    doctorId: this.doctorId,
                    weekNumber: weekNumber,
                    year: year,
                    schedules: schedules
                })
            });

            const data = await response.json();
            if (data.success) {
                this.showToast('Horarios guardados correctamente');
            } else {
                throw new Error(data.error);
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
                startTime: cell.dataset.time + ':00',  // Aseguramos que tenga el formato HH:MM:SS
                endTime: this.calculateEndTime(cell.dataset.time) + ':00'  // Aseguramos que tenga el formato HH:MM:SS
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
                    cell.innerHTML = '';
                    cell.title = 'Disponible - Haz clic para quitar';
                }
            }
        });
    },

    displaySchedule() {
        // Limpiar selecciones anteriores
        document.querySelectorAll('.schedule-cell').forEach(cell => {
            cell.classList.remove('available');
            cell.innerHTML = '';
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
                    cell.innerHTML = '';
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

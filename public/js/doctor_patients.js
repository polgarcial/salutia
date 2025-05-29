/**
 * Script para la gestión de pacientes del médico
 * Este script maneja la funcionalidad de visualización, búsqueda y contacto con pacientes
 */

// Objeto para gestionar los pacientes del médico
const DoctorPatientsManager = {
    // Inicializar el gestor de pacientes
    init: function() {
        console.log('Inicializando gestor de pacientes');
        
        // Cargar pacientes
        this.loadPatients();
        
        // Configurar eventos
        this.setupEventListeners();
    },
    
    // Configurar los eventos de la página
    setupEventListeners: function() {
        // Búsqueda de pacientes
        const searchInput = document.getElementById('searchPatient');
        if (searchInput) {
            searchInput.addEventListener('input', this.filterPatients.bind(this));
        }
        
        // Filtro de pacientes
        const filterSelect = document.getElementById('filterPatients');
        if (filterSelect) {
            filterSelect.addEventListener('change', this.filterPatients.bind(this));
        }
        
        // Botón de exportar
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                const modal = new bootstrap.Modal(document.getElementById('exportModal'));
                modal.show();
            });
        }
        
        // Botón de confirmar exportación
        const confirmExportBtn = document.getElementById('confirmExportBtn');
        if (confirmExportBtn) {
            confirmExportBtn.addEventListener('click', this.exportPatients.bind(this));
        }
        
        // Selector de rango de fechas
        const dateRangeSelect = document.getElementById('dateRange');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', function() {
                const customDateRange = document.getElementById('customDateRange');
                if (customDateRange) {
                    customDateRange.style.display = this.value === 'custom' ? 'flex' : 'none';
                }
            });
        }
        
        // Botones de paginación
        document.querySelectorAll('.pagination .page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (!link.parentElement.classList.contains('disabled') && !link.parentElement.classList.contains('active')) {
                    const page = link.textContent;
                    this.changePage(page);
                }
            });
        });
    },
    
    // Cargar pacientes
    loadPatients: function() {
        console.log('Cargando pacientes');
        
        // Obtener pacientes guardados en localStorage
        const savedPatients = JSON.parse(localStorage.getItem('doctor_patients')) || [];
        
        // Si no hay pacientes guardados, usar datos de ejemplo
        if (savedPatients.length === 0) {
            const examplePatients = [
                {
                    id: 1,
                    name: 'Pol Garcia',
                    age: 33,
                    gender: 'Hombre',
                    email: 'pol@gmail.com',
                    phone: '+34 612 345 678',
                    lastVisit: '15/05/2025',
                    nextAppointment: '29/05/2025'
                },
                {
                    id: 2,
                    name: 'Laura Martínez',
                    age: 42,
                    gender: 'Mujer',
                    email: 'laura@gmail.com',
                    phone: '+34 623 456 789',
                    lastVisit: '20/05/2025',
                    nextAppointment: '31/05/2025'
                },
                {
                    id: 3,
                    name: 'Carlos Rodríguez',
                    age: 28,
                    gender: 'Hombre',
                    email: 'carlos@gmail.com',
                    phone: '+34 634 567 890',
                    lastVisit: '10/05/2025',
                    nextAppointment: '30/05/2025'
                },
                {
                    id: 4,
                    name: 'Ana López',
                    age: 55,
                    gender: 'Mujer',
                    email: 'ana@gmail.com',
                    phone: '+34 645 678 901',
                    lastVisit: '05/05/2025',
                    nextAppointment: null
                }
            ];
            
            localStorage.setItem('doctor_patients', JSON.stringify(examplePatients));
            this.displayPatients(examplePatients);
        } else {
            this.displayPatients(savedPatients);
        }
    },
    
    // Mostrar pacientes en la interfaz
    displayPatients: function(patients) {
        console.log('Mostrando pacientes:', patients);
        
        // Obtener contenedor de pacientes
        const patientsContainer = document.getElementById('patientsList');
        
        // Limpiar contenedor
        if (patientsContainer) {
            patientsContainer.innerHTML = '';
            
            // Mostrar pacientes
            patients.forEach(patient => {
                const patientCard = document.createElement('div');
                patientCard.className = 'col-md-6 mb-4';
                patientCard.dataset.id = patient.id;
                
                // Determinar color del avatar según el género
                const avatarColor = patient.gender === 'Hombre' ? 'bg-primary' : 'bg-info';
                
                // Crear HTML para el paciente
                patientCard.innerHTML = `
                    <div class="patient-card">
                        <div class="d-flex">
                            <div class="avatar ${avatarColor}">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h5>${patient.name}</h5>
                                <p class="text-muted mb-1">${patient.age} años - ${patient.gender}</p>
                                <p class="mb-1"><i class="fas fa-envelope me-2"></i> ${patient.email}</p>
                                <p class="mb-1"><i class="fas fa-phone me-2"></i> ${patient.phone}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-success me-2">Última visita: ${patient.lastVisit}</span>
                                ${patient.nextAppointment ? 
                                    `<span class="badge bg-info">Próxima cita: ${patient.nextAppointment}</span>` : 
                                    '<span class="badge bg-secondary">Sin citas programadas</span>'}
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-1 view-history-btn" data-patient-id="${patient.id}">
                                    <i class="fas fa-history"></i> Historial
                                </button>
                                <button class="btn btn-sm btn-outline-secondary contact-patient-btn" data-patient-id="${patient.id}">
                                    <i class="fas fa-envelope"></i> Contactar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                patientsContainer.appendChild(patientCard);
                
                // Añadir eventos a los botones
                patientCard.querySelector('.view-history-btn').addEventListener('click', () => {
                    this.viewPatientHistory(patient.id);
                });
                
                patientCard.querySelector('.contact-patient-btn').addEventListener('click', () => {
                    this.contactPatient(patient.id);
                });
            });
            
            // Si no hay pacientes, mostrar mensaje
            if (patients.length === 0) {
                patientsContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No se encontraron pacientes que coincidan con los criterios de búsqueda.
                        </div>
                    </div>
                `;
            }
        }
    },
    
    // Filtrar pacientes según búsqueda y filtro
    filterPatients: function() {
        console.log('Filtrando pacientes');
        
        // Obtener valores de búsqueda y filtro
        const searchTerm = document.getElementById('searchPatient')?.value.toLowerCase() || '';
        const filterValue = document.getElementById('filterPatients')?.value || 'all';
        
        // Obtener pacientes guardados
        const savedPatients = JSON.parse(localStorage.getItem('doctor_patients')) || [];
        
        // Filtrar pacientes
        const filteredPatients = savedPatients.filter(patient => {
            // Filtrar por término de búsqueda
            const matchesSearch = patient.name.toLowerCase().includes(searchTerm) || 
                                patient.email.toLowerCase().includes(searchTerm) ||
                                patient.phone.includes(searchTerm);
            
            // Filtrar por tipo
            let matchesFilter = true;
            if (filterValue === 'recent') {
                // Pacientes con visita reciente (últimos 30 días)
                const lastVisitDate = this.parseDate(patient.lastVisit);
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                matchesFilter = lastVisitDate >= thirtyDaysAgo;
            } else if (filterValue === 'upcoming') {
                // Pacientes con cita próxima
                matchesFilter = patient.nextAppointment !== null;
            }
            
            return matchesSearch && matchesFilter;
        });
        
        // Mostrar pacientes filtrados
        this.displayPatients(filteredPatients);
    },
    
    // Ver historial de un paciente
    viewPatientHistory: function(patientId) {
        console.log('Viendo historial del paciente:', patientId);
        
        // Obtener pacientes guardados
        const savedPatients = JSON.parse(localStorage.getItem('doctor_patients')) || [];
        
        // Encontrar el paciente
        const patient = savedPatients.find(p => p.id === patientId);
        
        if (patient) {
            // Obtener historial del paciente
            const patientHistory = this.getPatientHistory(patientId);
            
            // Mostrar historial en el modal
            const historyContent = document.getElementById('patientHistoryContent');
            if (historyContent) {
                historyContent.innerHTML = `
                    <div class="patient-info mb-4">
                        <h4>${patient.name}</h4>
                        <p><strong>Edad:</strong> ${patient.age} años | <strong>Género:</strong> ${patient.gender} | <strong>Grupo sanguíneo:</strong> A+</p>
                        <p><strong>Alergias:</strong> ${patientHistory.allergies || 'Ninguna conocida'}</p>
                    </div>
                    
                    <h5>Historial de Visitas</h5>
                    <div class="timeline">
                        ${patientHistory.visits.map(visit => `
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong>${visit.date}</strong> - ${visit.type}
                                </div>
                                <div class="card-body">
                                    <p><strong>Motivo:</strong> ${visit.reason}</p>
                                    <p><strong>Diagnóstico:</strong> ${visit.diagnosis}</p>
                                    <p><strong>Tratamiento:</strong> ${visit.treatment}</p>
                                    <p><strong>Notas:</strong> ${visit.notes}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('patientHistoryModal'));
                modal.show();
            }
        } else {
            this.showAlert('Paciente no encontrado', 'danger');
        }
    },
    
    // Obtener historial de un paciente (simulado)
    getPatientHistory: function(patientId) {
        // Datos de ejemplo para Pol Garcia
        if (patientId === 1) {
            return {
                allergies: 'Ninguna conocida',
                visits: [
                    {
                        date: '15/05/2025',
                        type: 'Consulta Regular',
                        reason: 'Dolor lumbar',
                        diagnosis: 'Lumbalgia mecánica',
                        treatment: 'Ibuprofeno 600mg/8h durante 5 días, reposo relativo',
                        notes: 'Paciente refiere dolor lumbar de 2 semanas de evolución tras levantar peso. Se recomienda fisioterapia.'
                    },
                    {
                        date: '10/03/2025',
                        type: 'Revisión',
                        reason: 'Revisión general',
                        diagnosis: 'Estado de salud normal',
                        treatment: 'No requiere',
                        notes: 'Analítica normal. Se recomienda mantener hábitos saludables.'
                    }
                ]
            };
        } else if (patientId === 2) {
            return {
                allergies: 'Penicilina',
                visits: [
                    {
                        date: '20/05/2025',
                        type: 'Consulta Regular',
                        reason: 'Dolor de cabeza',
                        diagnosis: 'Migraña',
                        treatment: 'Paracetamol 1g/8h durante 3 días, descanso en ambiente oscuro',
                        notes: 'Paciente refiere dolor de cabeza intenso con fotofobia. Se recomienda revisión neurológica si persisten los síntomas.'
                    }
                ]
            };
        } else if (patientId === 3) {
            return {
                allergies: 'Polen',
                visits: [
                    {
                        date: '10/05/2025',
                        type: 'Consulta Regular',
                        reason: 'Congestión nasal',
                        diagnosis: 'Rinitis alérgica',
                        treatment: 'Cetirizina 10mg/24h durante 15 días',
                        notes: 'Paciente refiere congestión nasal y estornudos frecuentes. Se recomienda evitar exposición a alérgenos.'
                    }
                ]
            };
        } else if (patientId === 4) {
            return {
                allergies: 'Lactosa',
                visits: [
                    {
                        date: '05/05/2025',
                        type: 'Consulta Regular',
                        reason: 'Control de hipertensión',
                        diagnosis: 'Hipertensión arterial controlada',
                        treatment: 'Continuar con Enalapril 10mg/24h',
                        notes: 'Paciente con tensión arterial en rango normal. Se recomienda mantener dieta baja en sal y ejercicio moderado.'
                    }
                ]
            };
        } else {
            return {
                allergies: 'No disponible',
                visits: []
            };
        }
    },
    
    // Contactar a un paciente
    contactPatient: function(patientId) {
        console.log('Contactando al paciente:', patientId);
        
        // Obtener pacientes guardados
        const savedPatients = JSON.parse(localStorage.getItem('doctor_patients')) || [];
        
        // Encontrar el paciente
        const patient = savedPatients.find(p => p.id === patientId);
        
        if (patient) {
            // Redirigir a la página de mensajes con el paciente seleccionado
            localStorage.setItem('selected_patient', JSON.stringify(patient));
            window.location.href = 'messages.html';
        } else {
            this.showAlert('Paciente no encontrado', 'danger');
        }
    },
    
    // Exportar pacientes
    exportPatients: function() {
        console.log('Exportando pacientes');
        
        // Obtener datos del formulario
        const exportFormat = document.getElementById('exportFormat').value;
        const exportBasicInfo = document.getElementById('exportBasicInfo').checked;
        const exportContactInfo = document.getElementById('exportContactInfo').checked;
        const exportAppointments = document.getElementById('exportAppointments').checked;
        const dateRange = document.getElementById('dateRange').value;
        
        // Validar que se haya seleccionado al menos un tipo de información
        if (!exportBasicInfo && !exportContactInfo && !exportAppointments) {
            this.showAlert('Seleccione al menos un tipo de información para exportar', 'warning');
            return;
        }
        
        // Simular exportación
        this.showAlert(`Exportación iniciada en formato ${exportFormat}. El archivo estará disponible en breve.`, 'success');
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
        modal.hide();
    },
    
    // Cambiar página de pacientes
    changePage: function(page) {
        console.log('Cambiando a página:', page);
        
        // Actualizar paginación
        document.querySelectorAll('.pagination .page-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Activar página actual
        if (page === 'Anterior' || page === 'Siguiente') {
            // Obtener página actual
            const activePage = document.querySelector('.pagination .page-item.active');
            if (activePage) {
                const currentPage = parseInt(activePage.textContent);
                const newPage = page === 'Anterior' ? currentPage - 1 : currentPage + 1;
                
                // Activar nueva página
                document.querySelectorAll('.pagination .page-item').forEach(item => {
                    if (item.textContent === newPage.toString()) {
                        item.classList.add('active');
                    }
                });
            }
        } else {
            // Activar página seleccionada
            document.querySelectorAll('.pagination .page-item').forEach(item => {
                if (item.textContent === page) {
                    item.classList.add('active');
                }
            });
        }
        
        // Simular carga de pacientes para la página seleccionada
        this.loadPatients();
    },
    
    // Parsear fecha en formato DD/MM/YYYY a objeto Date
    parseDate: function(dateString) {
        const parts = dateString.split('/');
        return new Date(parts[2], parts[1] - 1, parts[0]);
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
    // Inicializar gestor de pacientes
    DoctorPatientsManager.init();
});

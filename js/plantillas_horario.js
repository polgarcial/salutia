// Script para aplicar plantillas de horario directamente
console.log('Script de plantillas de horario cargado');

// Función para aplicar plantilla de mañana (9:00 - 14:00)
function aplicarPlantillaMañana() {
    console.log('Aplicando plantilla de mañana');
    aplicarPlantillaHorario(9, 14);
}

// Función para aplicar plantilla de tarde (15:00 - 20:00)
function aplicarPlantillaTarde() {
    console.log('Aplicando plantilla de tarde');
    aplicarPlantillaHorario(15, 20);
}

// Función para aplicar plantilla de día completo (9:00 - 20:00)
function aplicarPlantillaCompleta() {
    console.log('Aplicando plantilla de día completo');
    aplicarPlantillaHorario(9, 20);
}

// Función genérica para aplicar plantilla de horario
function aplicarPlantillaHorario(horaInicio, horaFin) {
    // Obtener todas las celdas de la cuadrícula
    const celdas = document.querySelectorAll('.schedule-cell');
    console.log(`Aplicando plantilla de horario: ${horaInicio}:00 - ${horaFin}:00`);
    console.log(`Número de celdas encontradas: ${celdas.length}`);
    
    // Limpiar todas las celdas primero
    celdas.forEach(celda => {
        celda.classList.remove('available');
        celda.innerHTML = '';
    });
    
    let celdasMarcadas = 0;
    
    // Marcar las celdas según el rango de horas
    celdas.forEach(celda => {
        if (!celda.dataset.timeSlot) {
            console.warn('Celda sin atributo timeSlot:', celda);
            return;
        }
        
        const hora = parseInt(celda.dataset.timeSlot.split(':')[0]);
        
        if (hora >= horaInicio && hora < horaFin) {
            celda.classList.add('available');
            celda.innerHTML = '<i class="fas fa-check"></i>';
            celdasMarcadas++;
        }
    });
    
    console.log(`Celdas marcadas como disponibles: ${celdasMarcadas}`);
    
    // Mostrar notificación
    mostrarNotificacion(`Plantilla aplicada: ${horaInicio}:00 - ${horaFin}:00`, `Se han marcado ${celdasMarcadas} franjas horarias como disponibles.`);
}

// Función para mostrar notificación
function mostrarNotificacion(titulo, mensaje) {
    const notificacion = document.createElement('div');
    notificacion.className = 'position-fixed bottom-0 end-0 p-3';
    notificacion.style.zIndex = '5';
    notificacion.innerHTML = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-primary text-white">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">${titulo}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <p class="mb-0">${mensaje}</p>
                <p class="small text-muted mb-0">No olvides guardar los cambios</p>
            </div>
        </div>
    `;
    document.body.appendChild(notificacion);
    
    // Eliminar la notificación después de 3 segundos
    setTimeout(() => {
        notificacion.remove();
    }, 3000);
}

// Función eliminada: agregarBotonesPlantilla

// Conectar los botones originales con las nuevas funciones
function conectarBotonesOriginales() {
    // Obtener los botones originales
    const botonesOriginales = document.querySelectorAll('button[onclick^="applyTemplate"]');
    
    botonesOriginales.forEach(boton => {
        // Eliminar el atributo onclick original
        const onclickOriginal = boton.getAttribute('onclick');
        boton.removeAttribute('onclick');
        
        // Determinar qué plantilla debe aplicar
        if (onclickOriginal.includes("'morning'")) {
            boton.addEventListener('click', aplicarPlantillaMañana);
        } else if (onclickOriginal.includes("'afternoon'")) {
            boton.addEventListener('click', aplicarPlantillaTarde);
        } else if (onclickOriginal.includes("'fullday'")) {
            boton.addEventListener('click', aplicarPlantillaCompleta);
        }
    });
    
    console.log('Botones originales conectados');
}

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, configurando botones de plantilla...');
    // Esperar un poco para asegurar que la cuadrícula esté cargada
    setTimeout(() => {
        conectarBotonesOriginales();
        // Ya no se agregan los botones flotantes
    }, 1000);
});

console.log('Script de plantillas de horario inicializado');

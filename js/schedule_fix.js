// Script de diagnóstico y corrección para el gestor de horarios
console.log('Script de diagnóstico y corrección cargado');

// Función para verificar la estructura de la página
function diagnosticarPagina() {
    console.log('Iniciando diagnóstico de la página...');
    
    // Verificar elementos críticos
    const scheduleGrid = document.getElementById('scheduleGrid');
    console.log('¿Existe scheduleGrid?', scheduleGrid ? 'Sí' : 'No');
    
    if (scheduleGrid) {
        const cells = scheduleGrid.querySelectorAll('.schedule-cell');
        console.log('Número de celdas encontradas:', cells.length);
        
        if (cells.length > 0) {
            // Mostrar información de algunas celdas para diagnóstico
            console.log('Muestra de celdas:');
            for (let i = 0; i < Math.min(5, cells.length); i++) {
                const cell = cells[i];
                console.log(`Celda ${i}:`, {
                    'dataset.date': cell.dataset.date,
                    'dataset.timeSlot': cell.dataset.timeSlot,
                    'classList': Array.from(cell.classList),
                    'innerHTML': cell.innerHTML
                });
            }
        }
    }
    
    // Verificar botones de plantilla
    const morningBtn = document.querySelector('button[onclick="applyTemplate(\'morning\')"]');
    const afternoonBtn = document.querySelector('button[onclick="applyTemplate(\'afternoon\')"]');
    const fulldayBtn = document.querySelector('button[onclick="applyTemplate(\'fullday\')"]');
    
    console.log('¿Existe botón de mañana?', morningBtn ? 'Sí' : 'No');
    console.log('¿Existe botón de tarde?', afternoonBtn ? 'Sí' : 'No');
    console.log('¿Existe botón de día completo?', fulldayBtn ? 'Sí' : 'No');
    
    // Verificar función applyTemplate
    console.log('¿Existe función applyTemplate?', typeof applyTemplate === 'function' ? 'Sí' : 'No');
    
    return {
        scheduleGridExists: !!scheduleGrid,
        cellCount: scheduleGrid ? scheduleGrid.querySelectorAll('.schedule-cell').length : 0,
        morningBtnExists: !!morningBtn,
        afternoonBtnExists: !!afternoonBtn,
        fulldayBtnExists: !!fulldayBtn,
        applyTemplateFunctionExists: typeof applyTemplate === 'function'
    };
}

// Función para corregir problemas comunes
function corregirProblemas() {
    console.log('Iniciando corrección de problemas...');
    
    // Verificar si hay celdas sin los atributos necesarios
    const scheduleGrid = document.getElementById('scheduleGrid');
    if (scheduleGrid) {
        const cells = scheduleGrid.querySelectorAll('.schedule-cell');
        cells.forEach((cell, index) => {
            // Asegurarse de que todas las celdas tengan los atributos necesarios
            if (!cell.dataset.date || !cell.dataset.timeSlot) {
                console.warn(`Celda ${index} sin atributos completos:`, cell);
                
                // Intentar reconstruir los atributos basados en la posición
                const row = Math.floor(index / 5) + 1; // +1 porque la primera fila es de encabezados
                const col = index % 5;
                
                // Reconstruir timeSlot basado en la fila
                const timeSlots = [
                    '9:00', '9:30', '10:00', '10:30', '11:00', '11:30', 
                    '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
                    '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
                    '18:00', '18:30', '19:00', '19:30'
                ];
                
                if (row <= timeSlots.length) {
                    cell.dataset.timeSlot = timeSlots[row - 1];
                    console.log(`Corregido timeSlot para celda ${index}: ${timeSlots[row - 1]}`);
                }
            }
        });
    }
    
    // Verificar si la función applyTemplate existe y corregirla si es necesario
    if (typeof applyTemplate !== 'function') {
        console.warn('La función applyTemplate no existe, creándola...');
        
        window.applyTemplate = function(template) {
            console.log('Aplicando plantilla (función reconstruida):', template);
            
            const scheduleGrid = document.getElementById('scheduleGrid');
            if (!scheduleGrid) {
                console.error('Error: No se encontró el elemento scheduleGrid');
                alert('Error: No se pudo encontrar la cuadrícula de horarios');
                return;
            }
            
            const cells = scheduleGrid.querySelectorAll('.schedule-cell');
            console.log('Número de celdas encontradas:', cells.length);
            
            if (cells.length === 0) {
                console.error('Error: No se encontraron celdas en la cuadrícula');
                alert('Error: La cuadrícula de horarios está vacía');
                return;
            }
            
            // Limpiar todas las celdas primero
            cells.forEach(cell => {
                cell.classList.remove('available');
                cell.innerHTML = '';
            });
            
            let cellsMarked = 0;
            
            // Aplicar la plantilla seleccionada
            cells.forEach(cell => {
                const timeSlot = cell.dataset.timeSlot;
                if (!timeSlot) {
                    console.warn('Celda sin atributo timeSlot:', cell);
                    return;
                }
                
                const hour = parseInt(timeSlot.split(':')[0]);
                console.log('Procesando celda:', timeSlot, 'hora:', hour);
                
                let shouldMark = false;
                
                if (template === 'morning' && hour >= 9 && hour < 14) {
                    shouldMark = true;
                } else if (template === 'afternoon' && hour >= 15 && hour < 20) {
                    shouldMark = true;
                } else if (template === 'fullday' && hour >= 9 && hour < 20) {
                    shouldMark = true;
                }
                
                if (shouldMark) {
                    cell.classList.add('available');
                    cell.innerHTML = '<i class="fas fa-check"></i>';
                    cellsMarked++;
                }
            });
            
            console.log('Celdas marcadas como disponibles:', cellsMarked);
            
            alert(`Se ha aplicado la plantilla "${template}" a ${cellsMarked} franjas horarias.`);
        };
        
        console.log('Función applyTemplate reconstruida correctamente');
        
        // Reconectar los botones
        const morningBtn = document.querySelector('button[onclick="applyTemplate(\'morning\')"]');
        const afternoonBtn = document.querySelector('button[onclick="applyTemplate(\'afternoon\')"]');
        const fulldayBtn = document.querySelector('button[onclick="applyTemplate(\'fullday\')"]');
        
        if (morningBtn) morningBtn.onclick = () => applyTemplate('morning');
        if (afternoonBtn) afternoonBtn.onclick = () => applyTemplate('afternoon');
        if (fulldayBtn) fulldayBtn.onclick = () => applyTemplate('fullday');
    }
    
    console.log('Corrección de problemas completada');
}

// Ejecutar diagnóstico cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, ejecutando diagnóstico...');
    const diagnostico = diagnosticarPagina();
    
    console.log('Resultado del diagnóstico:', diagnostico);
    
    // Si hay problemas, intentar corregirlos
    if (!diagnostico.scheduleGridExists || 
        diagnostico.cellCount === 0 || 
        !diagnostico.applyTemplateFunctionExists) {
        console.warn('Se detectaron problemas, intentando corregir...');
        corregirProblemas();
    }
});

// También ejecutar diagnóstico después de 2 segundos para asegurar que todo esté cargado
setTimeout(function() {
    console.log('Ejecutando diagnóstico después de timeout...');
    const diagnostico = diagnosticarPagina();
    
    console.log('Resultado del diagnóstico (después de timeout):', diagnostico);
    
    // Si hay problemas, intentar corregirlos
    if (!diagnostico.scheduleGridExists || 
        diagnostico.cellCount === 0 || 
        !diagnostico.applyTemplateFunctionExists) {
        console.warn('Se detectaron problemas después de timeout, intentando corregir...');
        corregirProblemas();
    }
}, 2000);

// Ya no se crean botones alternativos

console.log('Script de diagnóstico y corrección inicializado');

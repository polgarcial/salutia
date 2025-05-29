/**
 * Funciones para filtrar médicos por nombre
 */

// Variable para almacenar el historial de búsqueda
let searchHistory = [];

// Función para filtrar médicos por nombre
function filterDoctors() {
    console.log('Filtrando médicos...');
    const searchTerm = document.getElementById('searchDoctor').value.toLowerCase().trim();
    
    // Guardar en el historial si es una búsqueda válida
    if (searchTerm && !searchHistory.includes(searchTerm)) {
        addToSearchHistory(searchTerm);
    }
    
    // Obtener el contenedor de médicos
    const doctorsContainer = document.getElementById('doctorsList');
    if (!doctorsContainer) {
        console.error('No se encontró el contenedor de médicos');
        return;
    }
    
    // Obtener todas las tarjetas de médicos (pueden estar en diferentes niveles de anidamiento)
    const doctorCards = doctorsContainer.querySelectorAll('.card');
    console.log('Tarjetas de médicos encontradas:', doctorCards.length);
    
    // Si no hay tarjetas, no hay nada que filtrar
    if (doctorCards.length === 0) {
        console.log('No hay tarjetas de médicos para filtrar');
        return;
    }
    
    let visibleCount = 0;
    
    // Filtrar las tarjetas según el término de búsqueda
    doctorCards.forEach(card => {
        // Buscar el nombre del médico en diferentes elementos posibles
        const titleElement = card.querySelector('.card-title, h5');
        if (!titleElement) {
            console.log('No se encontró el título en una tarjeta');
            return;
        }
        
        const doctorName = titleElement.textContent.toLowerCase();
        console.log('Nombre del médico:', doctorName);
        
        // Verificar si coincide con la búsqueda
        const matchesSearch = !searchTerm || doctorName.includes(searchTerm);
        
        // Encontrar el elemento contenedor adecuado para mostrar/ocultar
        let containerToToggle = card;
        while (containerToToggle && !containerToToggle.classList.contains('col-md-6') && 
               !containerToToggle.classList.contains('col-lg-4') &&
               containerToToggle !== doctorsContainer) {
            containerToToggle = containerToToggle.parentElement;
        }
        
        if (containerToToggle && containerToToggle !== doctorsContainer) {
            containerToToggle.style.display = matchesSearch ? 'block' : 'none';
            if (matchesSearch) visibleCount++;
        } else {
            console.log('No se encontró un contenedor adecuado para la tarjeta');
        }
    });
    
    console.log('Tarjetas visibles después del filtrado:', visibleCount);
    
    // Actualizar mensaje si no hay resultados
    updateNoResultsMessage(visibleCount === 0 && searchTerm);
}

// Función para limpiar la búsqueda
function clearSearch() {
    console.log('Limpiando búsqueda...');
    const searchInput = document.getElementById('searchDoctor');
    if (searchInput) {
        searchInput.value = '';
        filterDoctors();
    }
    
    // Ocultar el historial de búsqueda
    const searchHistoryElement = document.getElementById('searchHistory');
    if (searchHistoryElement) {
        searchHistoryElement.style.display = 'none';
    }
}

// Función para añadir un término al historial de búsqueda
function addToSearchHistory(term) {
    console.log('Añadiendo al historial:', term);
    // Evitar duplicados
    if (!searchHistory.includes(term)) {
        // Añadir al principio del array
        searchHistory.unshift(term);
        
        // Limitar a 5 elementos
        if (searchHistory.length > 5) {
            searchHistory.pop();
        }
        
        // Actualizar el historial en la interfaz
        updateSearchHistoryUI();
    }
}

// Función para actualizar la interfaz del historial de búsqueda
function updateSearchHistoryUI() {
    const historyContainer = document.getElementById('searchHistory');
    if (!historyContainer) {
        console.error('No se encontró el contenedor del historial');
        return;
    }
    
    if (searchHistory.length === 0) {
        historyContainer.style.display = 'none';
        return;
    }
    
    let html = '<p class="small text-muted mb-1">Búsquedas recientes:</p>';
    
    searchHistory.forEach(term => {
        html += `
            <div class="search-history-item p-1" onclick="selectHistoryItem('${term}')">
                <i class="fas fa-history me-1"></i> ${term}
            </div>
        `;
    });
    
    historyContainer.innerHTML = html;
    historyContainer.style.display = 'block';
}

// Función para seleccionar un elemento del historial
function selectHistoryItem(term) {
    console.log('Seleccionando del historial:', term);
    const searchInput = document.getElementById('searchDoctor');
    if (searchInput) {
        searchInput.value = term;
        filterDoctors();
    }
    
    // Ocultar el historial después de seleccionar
    const historyContainer = document.getElementById('searchHistory');
    if (historyContainer) {
        historyContainer.style.display = 'none';
    }
}

// Función para actualizar el mensaje cuando no hay resultados
function updateNoResultsMessage(noResults) {
    console.log('Actualizando mensaje de no resultados:', noResults);
    const doctorsList = document.getElementById('doctorsList');
    if (!doctorsList) return;
    
    // Eliminar mensaje existente si hay
    const existingMessage = doctorsList.querySelector('.no-results-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    if (noResults) {
        // No hay resultados con los filtros actuales
        const noResultsMessage = document.createElement('div');
        noResultsMessage.className = 'alert alert-info no-results-message';
        noResultsMessage.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            No se encontraron médicos que coincidan con tu búsqueda.
            <hr>
            <p class="mb-0">Sugerencias:</p>
            <ul>
                <li>Verifica que el nombre esté escrito correctamente</li>
                <li>Intenta con otra especialidad</li>
                <li>Usa términos más generales</li>
            </ul>
        `;
        
        // Añadir el mensaje al principio del contenedor
        const firstChild = doctorsList.querySelector('.row');
        if (firstChild) {
            doctorsList.insertBefore(noResultsMessage, firstChild);
        } else {
            doctorsList.appendChild(noResultsMessage);
        }
    }
}

// Inicializar el filtro cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando filtro de médicos...');
    const searchInput = document.getElementById('searchDoctor');
    
    if (searchInput) {
        // Configurar evento de foco para mostrar el historial
        searchInput.addEventListener('focus', function() {
            if (searchHistory.length > 0) {
                updateSearchHistoryUI();
            }
        });
        
        // Configurar evento de entrada para filtrar en tiempo real
        searchInput.addEventListener('input', function() {
            filterDoctors();
        });
        
        // Ocultar el historial al hacer clic fuera
        document.addEventListener('click', function(event) {
            const historyContainer = document.getElementById('searchHistory');
            if (historyContainer && !searchInput.contains(event.target) && !historyContainer.contains(event.target)) {
                historyContainer.style.display = 'none';
            }
        });
    }
    
    // Verificar si hay un select de especialidad y configurar evento
    const specialtySelect = document.getElementById('specialty');
    if (specialtySelect) {
        specialtySelect.addEventListener('change', function() {
            // Esperar a que se carguen los médicos y luego aplicar el filtro
            setTimeout(filterDoctors, 1000);
        });
    }
});

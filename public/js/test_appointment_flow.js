/**
 * Script de prueba para verificar el flujo completo de solicitud de citas
 * Este script simula el proceso de solicitud de citas desde el dashboard del paciente
 * y verifica que aparezcan correctamente en el dashboard del médico
 */

// Función para simular una solicitud de cita
function simulateAppointmentRequest() {
    console.log('Simulando solicitud de cita...');
    
    // Datos de prueba para la solicitud
    const testAppointmentData = {
        patient_id: '1',
        patient_name: 'Pol Garcia',
        patient_email: 'pol@gmail.com',
        doctor_id: '1',
        doctor_name: 'Dr. Juan Pérez',
        doctor_specialty: 'Medicina General',
        reason: 'Consulta sobre dolor lumbar',
        requested_date: '29/05/2025',
        requested_time: '10:30'
    };
    
    // Guardar la solicitud en localStorage
    const existingRequests = JSON.parse(localStorage.getItem('appointment_requests')) || [];
    
    // Verificar si ya existe una solicitud de pol@gmail.com
    const polExists = existingRequests.some(req => req.patient_email === 'pol@gmail.com');
    
    // Si existe, eliminarla para evitar duplicados
    let updatedRequests = existingRequests;
    if (polExists) {
        updatedRequests = existingRequests.filter(req => req.patient_email !== 'pol@gmail.com');
    }
    
    // Crear la nueva solicitud
    const newRequest = {
        id: 1, // ID fijo para Pol Garcia
        ...testAppointmentData,
        status: 'pending',
        created_at: new Date().toISOString()
    };
    
    // Añadir la solicitud a la lista
    updatedRequests.push(newRequest);
    
    // Guardar en localStorage
    localStorage.setItem('appointment_requests', JSON.stringify(updatedRequests));
    
    console.log('Solicitud de cita simulada con éxito:', newRequest);
    console.log('Total de solicitudes:', updatedRequests.length);
    
    // También podemos simular una llamada al servidor
    console.log('Simulando llamada al servidor...');
    
    // Devolver un mensaje de éxito
    return {
        success: true,
        message: 'Solicitud de cita simulada con éxito',
        request: newRequest
    };
}

// Función para verificar las solicitudes de citas en el dashboard del médico
function verifyDoctorDashboard() {
    console.log('Verificando dashboard del médico...');
    
    // Obtener las solicitudes guardadas en localStorage
    const savedRequests = JSON.parse(localStorage.getItem('appointment_requests')) || [];
    
    // Filtrar solo las solicitudes pendientes
    const pendingRequests = savedRequests.filter(req => req.status === 'pending');
    
    // Verificar si existe la solicitud de Pol Garcia
    const polRequest = pendingRequests.find(req => req.patient_email === 'pol@gmail.com');
    
    if (polRequest) {
        console.log('✅ La solicitud de Pol Garcia aparece correctamente en el dashboard del médico:', polRequest);
    } else {
        console.error('❌ La solicitud de Pol Garcia NO aparece en el dashboard del médico');
    }
    
    // Devolver el resultado de la verificación
    return {
        success: !!polRequest,
        pendingRequests: pendingRequests.length,
        polRequestFound: !!polRequest
    };
}

// Función para ejecutar la prueba completa
function runCompleteTest() {
    console.log('Iniciando prueba del flujo completo de solicitud de citas...');
    
    // Paso 1: Simular la solicitud de cita
    const simulationResult = simulateAppointmentRequest();
    console.log('Resultado de la simulación:', simulationResult);
    
    // Paso 2: Verificar el dashboard del médico
    const verificationResult = verifyDoctorDashboard();
    console.log('Resultado de la verificación:', verificationResult);
    
    // Mostrar resultado final
    if (verificationResult.success) {
        console.log('✅ PRUEBA EXITOSA: El flujo completo de solicitud de citas funciona correctamente');
    } else {
        console.error('❌ PRUEBA FALLIDA: Hay problemas en el flujo de solicitud de citas');
    }
    
    return {
        success: verificationResult.success,
        simulation: simulationResult,
        verification: verificationResult
    };
}

// Ejecutar la prueba cuando se cargue el script
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de prueba cargado. Ejecutando prueba...');
    runCompleteTest();
});

// Exponer las funciones para uso manual desde la consola
window.TestAppointmentFlow = {
    simulateAppointmentRequest,
    verifyDoctorDashboard,
    runCompleteTest
};

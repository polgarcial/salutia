// AppointmentList Component for Salutia

const AppointmentList = ({ user }) => {
  const [appointments, setAppointments] = React.useState([]);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState(null);
  const [filter, setFilter] = React.useState('upcoming');
  const [searchTerm, setSearchTerm] = React.useState('');
  
  // Fetch appointments on component mount
  React.useEffect(() => {
    const fetchAppointments = async () => {
      try {
        setLoading(true);
        
        // In a real application, this would be an API call
        // For demo purposes, we'll use mock data
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Mock data based on user role
        let mockAppointments = [];
        
        if (user.role === 'patient') {
          mockAppointments = [
            {
              id: 1,
              date: '2025-05-10',
              time: '09:30:00',
              duration: 30,
              doctor_id: 2,
              doctor_first_name: 'Joan',
              doctor_last_name: 'Metge',
              doctor_specialty: 'Medicina General',
              reason: 'Revisió general',
              status: 'scheduled',
              notes: 'Portar resultats analítica recent'
            },
            {
              id: 2,
              date: '2025-05-15',
              time: '16:00:00',
              duration: 45,
              doctor_id: 5,
              doctor_first_name: 'Laura',
              doctor_last_name: 'Cardio',
              doctor_specialty: 'Cardiologia',
              reason: 'Seguiment hipertensió',
              status: 'scheduled',
              notes: ''
            },
            {
              id: 3,
              date: '2025-05-20',
              time: '11:15:00',
              duration: 30,
              doctor_id: 8,
              doctor_first_name: 'Marta',
              doctor_last_name: 'Dermato',
              doctor_specialty: 'Dermatologia',
              reason: 'Revisió lunar',
              status: 'scheduled',
              notes: ''
            },
            {
              id: 4,
              date: '2025-04-25',
              time: '10:00:00',
              duration: 30,
              doctor_id: 2,
              doctor_first_name: 'Joan',
              doctor_last_name: 'Metge',
              doctor_specialty: 'Medicina General',
              reason: 'Grip',
              status: 'completed',
              notes: 'Recepta antibiòtic'
            },
            {
              id: 5,
              date: '2025-04-15',
              time: '12:30:00',
              duration: 60,
              doctor_id: 12,
              doctor_first_name: 'Pau',
              doctor_last_name: 'Psico',
              doctor_specialty: 'Psicologia',
              reason: 'Teràpia',
              status: 'completed',
              notes: 'Seguiment en 1 mes'
            },
            {
              id: 6,
              date: '2025-04-05',
              time: '15:45:00',
              duration: 30,
              doctor_id: 5,
              doctor_first_name: 'Laura',
              doctor_last_name: 'Cardio',
              doctor_specialty: 'Cardiologia',
              reason: 'Electrocardiograma',
              status: 'cancelled',
              notes: 'Cancel·lat pel pacient'
            }
          ];
        } else if (user.role === 'doctor') {
          mockAppointments = [
            {
              id: 1,
              date: '2025-05-10',
              time: '09:30:00',
              duration: 30,
              patient_id: 3,
              patient_first_name: 'Maria',
              patient_last_name: 'Pacient',
              reason: 'Revisió general',
              status: 'scheduled',
              notes: 'Portar resultats analítica recent'
            },
            {
              id: 2,
              date: '2025-05-10',
              time: '10:00:00',
              duration: 30,
              patient_id: 15,
              patient_first_name: 'Josep',
              patient_last_name: 'Garcia',
              reason: 'Dolor d\'esquena',
              status: 'scheduled',
              notes: ''
            },
            {
              id: 3,
              date: '2025-05-10',
              time: '10:30:00',
              duration: 30,
              patient_id: 22,
              patient_first_name: 'Anna',
              patient_last_name: 'Martí',
              reason: 'Seguiment tractament',
              status: 'scheduled',
              notes: ''
            },
            {
              id: 4,
              date: '2025-05-10',
              time: '11:00:00',
              duration: 30,
              patient_id: 18,
              patient_first_name: 'Jordi',
              patient_last_name: 'Puig',
              reason: 'Resultats analítica',
              status: 'scheduled',
              notes: ''
            },
            {
              id: 5,
              date: '2025-05-11',
              time: '09:00:00',
              duration: 30,
              patient_id: 25,
              patient_first_name: 'Montserrat',
              patient_last_name: 'Vila',
              reason: 'Revisió',
              status: 'scheduled',
              notes: ''
            },
            {
              id: 6,
              date: '2025-04-25',
              time: '16:30:00',
              duration: 30,
              patient_id: 30,
              patient_first_name: 'Carles',
              patient_last_name: 'Roca',
              reason: 'Mal de cap',
              status: 'completed',
              notes: 'Recepta analgèsics'
            }
          ];
        }
        
        setAppointments(mockAppointments);
      } catch (err) {
        console.error('Error fetching appointments:', err);
        setError('Error en carregar les cites. Si us plau, torna-ho a provar més tard.');
      } finally {
        setLoading(false);
      }
    };
    
    fetchAppointments();
  }, [user]);
  
  // Filter appointments based on filter and search term
  const filteredAppointments = React.useMemo(() => {
    let filtered = [...appointments];
    
    // Apply status filter
    if (filter === 'upcoming') {
      filtered = filtered.filter(appointment => 
        appointment.status === 'scheduled' && new Date(`${appointment.date}T${appointment.time}`) >= new Date()
      );
    } else if (filter === 'past') {
      filtered = filtered.filter(appointment => 
        appointment.status === 'completed' || new Date(`${appointment.date}T${appointment.time}`) < new Date()
      );
    } else if (filter === 'cancelled') {
      filtered = filtered.filter(appointment => appointment.status === 'cancelled');
    }
    
    // Apply search term
    if (searchTerm.trim() !== '') {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(appointment => {
        if (user.role === 'patient') {
          return (
            appointment.doctor_first_name.toLowerCase().includes(term) ||
            appointment.doctor_last_name.toLowerCase().includes(term) ||
            appointment.doctor_specialty.toLowerCase().includes(term) ||
            appointment.reason.toLowerCase().includes(term)
          );
        } else {
          return (
            appointment.patient_first_name.toLowerCase().includes(term) ||
            appointment.patient_last_name.toLowerCase().includes(term) ||
            appointment.reason.toLowerCase().includes(term)
          );
        }
      });
    }
    
    // Sort by date and time
    filtered.sort((a, b) => {
      const dateA = new Date(`${a.date}T${a.time}`);
      const dateB = new Date(`${b.date}T${b.time}`);
      return dateA - dateB;
    });
    
    return filtered;
  }, [appointments, filter, searchTerm, user.role]);
  
  // Format date for display
  const formatDate = (dateString) => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('ca-ES', options);
  };
  
  // Format time for display
  const formatTime = (timeString) => {
    return timeString.substring(0, 5);
  };
  
  // Handle appointment cancellation
  const handleCancelAppointment = (id) => {
    // In a real application, this would be an API call
    // For demo purposes, we'll update the state directly
    setAppointments(prevAppointments => 
      prevAppointments.map(appointment => 
        appointment.id === id ? { ...appointment, status: 'cancelled' } : appointment
      )
    );
  };
  
  // Render loading state
  if (loading) {
    return (
      <div className="container mt-5">
        <div className="text-center">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Carregant...</span>
          </div>
          <p className="mt-2">Carregant les cites...</p>
        </div>
      </div>
    );
  }
  
  // Render error state
  if (error) {
    return (
      <div className="container mt-5">
        <div className="alert alert-danger" role="alert">
          <i className="fas fa-exclamation-circle me-2"></i>
          {error}
        </div>
      </div>
    );
  }
  
  return (
    <div className="container mt-4">
      <div className="row mb-4">
        <div className="col-md-6">
          <h1 className="mb-0">
            <i className="fas fa-calendar-alt me-2 text-primary"></i>
            Les meves cites
          </h1>
          <p className="text-muted">Gestiona les teves cites mèdiques</p>
        </div>
        <div className="col-md-6 text-md-end">
          {user.role === 'patient' && (
            <button className="btn btn-primary">
              <i className="fas fa-plus me-2"></i>
              Nova cita
            </button>
          )}
        </div>
      </div>
      
      {/* Filters and Search */}
      <div className="card mb-4">
        <div className="card-body">
          <div className="row g-3">
            <div className="col-md-6">
              <div className="btn-group" role="group" aria-label="Filtres de cites">
                <button 
                  type="button" 
                  className={`btn ${filter === 'upcoming' ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => setFilter('upcoming')}
                >
                  Properes
                </button>
                <button 
                  type="button" 
                  className={`btn ${filter === 'past' ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => setFilter('past')}
                >
                  Passades
                </button>
                <button 
                  type="button" 
                  className={`btn ${filter === 'cancelled' ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => setFilter('cancelled')}
                >
                  Cancel·lades
                </button>
                <button 
                  type="button" 
                  className={`btn ${filter === 'all' ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => setFilter('all')}
                >
                  Totes
                </button>
              </div>
            </div>
            <div className="col-md-6">
              <div className="input-group">
                <span className="input-group-text">
                  <i className="fas fa-search"></i>
                </span>
                <input 
                  type="text" 
                  className="form-control" 
                  placeholder={user.role === 'patient' ? "Cerca per metge, especialitat..." : "Cerca per pacient, motiu..."}
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      
      {/* Appointments List */}
      {filteredAppointments.length > 0 ? (
        <div className="appointment-list">
          {filteredAppointments.map(appointment => (
            <div key={appointment.id} className="card mb-3">
              <div className="card-body">
                <div className="row">
                  {/* Date and Time */}
                  <div className="col-md-3">
                    <div className="d-flex align-items-center h-100">
                      <div className="text-center me-3">
                        <div className="calendar-icon bg-light p-2 rounded">
                          <div className="month bg-primary text-white py-1 rounded-top">
                            {new Date(appointment.date).toLocaleDateString('ca-ES', { month: 'short' })}
                          </div>
                          <div className="day fw-bold fs-4 py-1">
                            {new Date(appointment.date).getDate()}
                          </div>
                        </div>
                      </div>
                      <div>
                        <p className="mb-0 fw-bold">{formatTime(appointment.time)}</p>
                        <p className="mb-0 text-muted small">
                          {appointment.duration} min
                        </p>
                      </div>
                    </div>
                  </div>
                  
                  {/* Doctor/Patient Info */}
                  <div className="col-md-4">
                    <h5 className="mb-1">
                      {user.role === 'patient' 
                        ? `Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}`
                        : `${appointment.patient_first_name} ${appointment.patient_last_name}`
                      }
                    </h5>
                    {user.role === 'patient' && (
                      <p className="mb-0 text-muted">
                        <i className="fas fa-stethoscope me-1"></i>
                        {appointment.doctor_specialty}
                      </p>
                    )}
                    <p className="mb-0">
                      <i className="fas fa-comment me-1 text-primary"></i>
                      {appointment.reason}
                    </p>
                  </div>
                  
                  {/* Status and Notes */}
                  <div className="col-md-3">
                    <div className="mb-2">
                      <span className={`badge ${
                        appointment.status === 'scheduled' ? 'bg-primary' :
                        appointment.status === 'completed' ? 'bg-success' :
                        appointment.status === 'cancelled' ? 'bg-danger' : 'bg-warning'
                      }`}>
                        {appointment.status === 'scheduled' ? 'Programada' :
                         appointment.status === 'completed' ? 'Completada' :
                         appointment.status === 'cancelled' ? 'Cancel·lada' : 'No assistida'}
                      </span>
                    </div>
                    {appointment.notes && (
                      <p className="mb-0 small">
                        <i className="fas fa-sticky-note me-1"></i>
                        {appointment.notes}
                      </p>
                    )}
                  </div>
                  
                  {/* Actions */}
                  <div className="col-md-2 text-end">
                    <div className="btn-group-vertical">
                      <button className="btn btn-sm btn-outline-primary mb-1">
                        <i className="fas fa-eye me-1"></i>
                        Detalls
                      </button>
                      
                      {appointment.status === 'scheduled' && (
                        <>
                          <button className="btn btn-sm btn-outline-secondary mb-1">
                            <i className="fas fa-edit me-1"></i>
                            Editar
                          </button>
                          <button 
                            className="btn btn-sm btn-outline-danger"
                            onClick={() => handleCancelAppointment(appointment.id)}
                          >
                            <i className="fas fa-times me-1"></i>
                            Cancel·lar
                          </button>
                        </>
                      )}
                      
                      {user.role === 'doctor' && appointment.status === 'scheduled' && (
                        <button className="btn btn-sm btn-outline-success">
                          <i className="fas fa-check me-1"></i>
                          Completar
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="card">
          <div className="card-body text-center py-5">
            <i className="fas fa-calendar-times fa-4x text-muted mb-3"></i>
            <h4>No s'han trobat cites</h4>
            <p className="text-muted">
              {filter === 'upcoming' 
                ? "No tens cites programades pròximament." 
                : filter === 'past' 
                ? "No tens cites passades." 
                : filter === 'cancelled' 
                ? "No tens cites cancel·lades." 
                : "No s'han trobat cites que coincideixin amb la teva cerca."}
            </p>
            {user.role === 'patient' && filter === 'upcoming' && (
              <button className="btn btn-primary mt-2">
                <i className="fas fa-plus me-2"></i>
                Programar una cita
              </button>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

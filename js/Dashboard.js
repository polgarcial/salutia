// Dashboard Component for Salutia

const Dashboard = ({ user }) => {
  const [stats, setStats] = React.useState({
    upcomingAppointments: 0,
    pendingPrescriptions: 0,
    unreadMessages: 0,
    completedAppointments: 0
  });
  
  const [recentAppointments, setRecentAppointments] = React.useState([]);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState(null);
  
  // Fetch dashboard data on component mount
  React.useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setLoading(true);
        
        // In a real application, these would be API calls
        // For demo purposes, we'll use mock data
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Mock data based on user role
        if (user.role === 'patient') {
          setStats({
            upcomingAppointments: 3,
            pendingPrescriptions: 2,
            unreadMessages: 5,
            completedAppointments: 8
          });
          
          setRecentAppointments([
            {
              id: 1,
              date: '2025-05-10',
              time: '09:30:00',
              doctor_first_name: 'Joan',
              doctor_last_name: 'Metge',
              doctor_specialty: 'Medicina General',
              status: 'scheduled'
            },
            {
              id: 2,
              date: '2025-05-15',
              time: '16:00:00',
              doctor_first_name: 'Laura',
              doctor_last_name: 'Cardio',
              doctor_specialty: 'Cardiologia',
              status: 'scheduled'
            },
            {
              id: 3,
              date: '2025-05-20',
              time: '11:15:00',
              doctor_first_name: 'Marta',
              doctor_last_name: 'Dermato',
              doctor_specialty: 'Dermatologia',
              status: 'scheduled'
            }
          ]);
        } else if (user.role === 'doctor') {
          setStats({
            upcomingAppointments: 12,
            pendingPrescriptions: 5,
            unreadMessages: 8,
            completedAppointments: 24
          });
          
          setRecentAppointments([
            {
              id: 1,
              date: '2025-05-10',
              time: '09:30:00',
              patient_first_name: 'Maria',
              patient_last_name: 'Pacient',
              reason: 'Revisió general',
              status: 'scheduled'
            },
            {
              id: 2,
              date: '2025-05-10',
              time: '10:00:00',
              patient_first_name: 'Josep',
              patient_last_name: 'Garcia',
              reason: 'Dolor d\'esquena',
              status: 'scheduled'
            },
            {
              id: 3,
              date: '2025-05-10',
              time: '10:30:00',
              patient_first_name: 'Anna',
              patient_last_name: 'Martí',
              reason: 'Seguiment tractament',
              status: 'scheduled'
            },
            {
              id: 4,
              date: '2025-05-10',
              time: '11:00:00',
              patient_first_name: 'Jordi',
              patient_last_name: 'Puig',
              reason: 'Resultats analítica',
              status: 'scheduled'
            }
          ]);
        } else {
          // Admin dashboard
          setStats({
            totalUsers: 245,
            totalDoctors: 18,
            totalAppointments: 567,
            activeChats: 32
          });
          
          setRecentAppointments([]);
        }
      } catch (err) {
        console.error('Error fetching dashboard data:', err);
        setError('Error en carregar les dades del tauler. Si us plau, torna-ho a provar més tard.');
      } finally {
        setLoading(false);
      }
    };
    
    fetchDashboardData();
  }, [user]);
  
  // Format date for display
  const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('ca-ES', options);
  };
  
  // Format time for display
  const formatTime = (timeString) => {
    return timeString.substring(0, 5);
  };
  
  // Render loading state
  if (loading) {
    return (
      <div className="container mt-5">
        <div className="text-center">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Carregant...</span>
          </div>
          <p className="mt-2">Carregant el tauler...</p>
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
        <div className="col-12">
          <h1 className="mb-0">
            <i className="fas fa-tachometer-alt me-2 text-primary"></i>
            Tauler
          </h1>
          <p className="text-muted">
            Benvingut/da, {user.first_name} {user.last_name}
          </p>
        </div>
      </div>
      
      {/* Stats Cards */}
      <div className="row mb-4">
        {user.role === 'patient' || user.role === 'doctor' ? (
          <>
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                      <i className="fas fa-calendar-check text-primary fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.upcomingAppointments}</h3>
                      <p className="text-muted mb-0">Cites properes</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                      <i className="fas fa-prescription text-success fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.pendingPrescriptions}</h3>
                      <p className="text-muted mb-0">Receptes pendents</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                      <i className="fas fa-comment-medical text-danger fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.unreadMessages}</h3>
                      <p className="text-muted mb-0">Missatges nous</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                      <i className="fas fa-check-circle text-info fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.completedAppointments}</h3>
                      <p className="text-muted mb-0">Cites completades</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </>
        ) : (
          // Admin stats
          <>
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                      <i className="fas fa-users text-primary fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.totalUsers}</h3>
                      <p className="text-muted mb-0">Usuaris totals</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                      <i className="fas fa-user-md text-success fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.totalDoctors}</h3>
                      <p className="text-muted mb-0">Metges</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                      <i className="fas fa-calendar-alt text-danger fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.totalAppointments}</h3>
                      <p className="text-muted mb-0">Cites totals</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div className="col-md-3 col-sm-6 mb-4">
              <div className="card h-100">
                <div className="card-body">
                  <div className="d-flex align-items-center">
                    <div className="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                      <i className="fas fa-comments text-info fa-2x"></i>
                    </div>
                    <div>
                      <h3 className="mb-0">{stats.activeChats}</h3>
                      <p className="text-muted mb-0">Xats actius</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </>
        )}
      </div>
      
      {/* Main Content */}
      <div className="row">
        {/* Left Column */}
        <div className="col-lg-8 mb-4">
          {/* Upcoming Appointments */}
          <div className="card mb-4">
            <div className="card-header d-flex justify-content-between align-items-center">
              <h5 className="mb-0">
                <i className="fas fa-calendar-alt me-2 text-primary"></i>
                {user.role === 'doctor' ? 'Cites d\'avui' : 'Cites properes'}
              </h5>
              <a href="#" className="btn btn-sm btn-primary">
                {user.role === 'patient' ? 'Nova cita' : 'Veure totes'}
              </a>
            </div>
            <div className="card-body">
              {recentAppointments.length > 0 ? (
                <div className="table-responsive">
                  <table className="table table-hover">
                    <thead>
                      <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        {user.role === 'patient' ? <th>Metge</th> : <th>Pacient</th>}
                        {user.role === 'patient' ? <th>Especialitat</th> : <th>Motiu</th>}
                        <th>Estat</th>
                        <th>Accions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {recentAppointments.map(appointment => (
                        <tr key={appointment.id}>
                          <td>{formatDate(appointment.date)}</td>
                          <td>{formatTime(appointment.time)}</td>
                          {user.role === 'patient' ? (
                            <td>{appointment.doctor_first_name} {appointment.doctor_last_name}</td>
                          ) : (
                            <td>{appointment.patient_first_name} {appointment.patient_last_name}</td>
                          )}
                          {user.role === 'patient' ? (
                            <td>{appointment.doctor_specialty}</td>
                          ) : (
                            <td>{appointment.reason}</td>
                          )}
                          <td>
                            <span className={`badge bg-${
                              appointment.status === 'scheduled' ? 'primary' :
                              appointment.status === 'completed' ? 'success' :
                              appointment.status === 'cancelled' ? 'danger' : 'warning'
                            }`}>
                              {appointment.status === 'scheduled' ? 'Programada' :
                               appointment.status === 'completed' ? 'Completada' :
                               appointment.status === 'cancelled' ? 'Cancel·lada' : 'No assistida'}
                            </span>
                          </td>
                          <td>
                            <div className="btn-group">
                              <button className="btn btn-sm btn-outline-primary">
                                <i className="fas fa-eye"></i>
                              </button>
                              {appointment.status === 'scheduled' && (
                                <button className="btn btn-sm btn-outline-danger">
                                  <i className="fas fa-times"></i>
                                </button>
                              )}
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-4">
                  <i className="fas fa-calendar-day fa-3x text-muted mb-3"></i>
                  <p className="mb-0">No hi ha cites programades.</p>
                  {user.role === 'patient' && (
                    <button className="btn btn-primary mt-3">
                      <i className="fas fa-plus me-2"></i>
                      Programar una cita
                    </button>
                  )}
                </div>
              )}
            </div>
          </div>
          
          {/* Recent Activity */}
          <div className="card">
            <div className="card-header">
              <h5 className="mb-0">
                <i className="fas fa-history me-2 text-primary"></i>
                Activitat recent
              </h5>
            </div>
            <div className="card-body">
              <ul className="list-group list-group-flush">
                <li className="list-group-item d-flex align-items-center py-3">
                  <div className="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                    <i className="fas fa-calendar-check text-primary"></i>
                  </div>
                  <div className="flex-grow-1">
                    <p className="mb-0">S'ha programat una nova cita per al 15 de maig de 2025</p>
                    <small className="text-muted">Fa 2 hores</small>
                  </div>
                </li>
                <li className="list-group-item d-flex align-items-center py-3">
                  <div className="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                    <i className="fas fa-prescription text-success"></i>
                  </div>
                  <div className="flex-grow-1">
                    <p className="mb-0">S'ha afegit una nova recepta al teu historial</p>
                    <small className="text-muted">Ahir</small>
                  </div>
                </li>
                <li className="list-group-item d-flex align-items-center py-3">
                  <div className="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                    <i className="fas fa-comment-medical text-info"></i>
                  </div>
                  <div className="flex-grow-1">
                    <p className="mb-0">Has rebut un nou missatge del Dr. Joan Metge</p>
                    <small className="text-muted">Fa 2 dies</small>
                  </div>
                </li>
                <li className="list-group-item d-flex align-items-center py-3">
                  <div className="rounded-circle bg-warning bg-opacity-10 p-2 me-3">
                    <i className="fas fa-notes-medical text-warning"></i>
                  </div>
                  <div className="flex-grow-1">
                    <p className="mb-0">S'ha actualitzat el teu historial mèdic</p>
                    <small className="text-muted">Fa 3 dies</small>
                  </div>
                </li>
              </ul>
            </div>
            <div className="card-footer text-center">
              <a href="#" className="text-decoration-none">Veure tota l'activitat</a>
            </div>
          </div>
        </div>
        
        {/* Right Column */}
        <div className="col-lg-4">
          {/* AI Assistant */}
          <div className="card mb-4">
            <div className="card-header">
              <h5 className="mb-0">
                <i className="fas fa-robot me-2 text-primary"></i>
                Assistent IA
              </h5>
            </div>
            <div className="card-body">
              <div className="d-flex flex-column align-items-center text-center py-3">
                <div className="rounded-circle bg-primary bg-opacity-10 p-4 mb-3">
                  <i className="fas fa-robot text-primary fa-3x"></i>
                </div>
                <h5>Com puc ajudar-te avui?</h5>
                <p className="text-muted mb-4">Pregunta'm sobre els teus símptomes, cites o historial mèdic.</p>
                <div className="d-grid gap-2 w-100">
                  <button className="btn btn-outline-primary">
                    <i className="fas fa-calendar-alt me-2"></i>
                    Programar una cita
                  </button>
                  <button className="btn btn-outline-primary">
                    <i className="fas fa-prescription me-2"></i>
                    Consultar receptes
                  </button>
                  <button className="btn btn-outline-primary">
                    <i className="fas fa-comment-medical me-2"></i>
                    Xat amb l'assistent
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          {/* Calendar */}
          <div className="card mb-4">
            <div className="card-header">
              <h5 className="mb-0">
                <i className="fas fa-calendar me-2 text-primary"></i>
                Calendari
              </h5>
            </div>
            <div className="card-body p-3">
              <div className="calendar-header d-flex justify-content-between align-items-center mb-3">
                <button className="btn btn-sm btn-outline-secondary">
                  <i className="fas fa-chevron-left"></i>
                </button>
                <h6 className="mb-0">Maig 2025</h6>
                <button className="btn btn-sm btn-outline-secondary">
                  <i className="fas fa-chevron-right"></i>
                </button>
              </div>
              
              <div className="calendar-grid mb-2">
                <div className="text-center fw-bold">Dl</div>
                <div className="text-center fw-bold">Dt</div>
                <div className="text-center fw-bold">Dc</div>
                <div className="text-center fw-bold">Dj</div>
                <div className="text-center fw-bold">Dv</div>
                <div className="text-center fw-bold">Ds</div>
                <div className="text-center fw-bold">Dg</div>
                
                <div className="calendar-day text-muted">28</div>
                <div className="calendar-day text-muted">29</div>
                <div className="calendar-day text-muted">30</div>
                <div className="calendar-day">1</div>
                <div className="calendar-day">2</div>
                <div className="calendar-day">3</div>
                <div className="calendar-day">4</div>
                
                <div className="calendar-day">5</div>
                <div className="calendar-day">6</div>
                <div className="calendar-day">7</div>
                <div className="calendar-day">8</div>
                <div className="calendar-day">9</div>
                <div className="calendar-day has-appointment active">10</div>
                <div className="calendar-day">11</div>
                
                <div className="calendar-day">12</div>
                <div className="calendar-day">13</div>
                <div className="calendar-day">14</div>
                <div className="calendar-day has-appointment">15</div>
                <div className="calendar-day">16</div>
                <div className="calendar-day">17</div>
                <div className="calendar-day">18</div>
                
                <div className="calendar-day">19</div>
                <div className="calendar-day has-appointment">20</div>
                <div className="calendar-day">21</div>
                <div className="calendar-day">22</div>
                <div className="calendar-day">23</div>
                <div className="calendar-day">24</div>
                <div className="calendar-day">25</div>
                
                <div className="calendar-day">26</div>
                <div className="calendar-day">27</div>
                <div className="calendar-day">28</div>
                <div className="calendar-day">29</div>
                <div className="calendar-day">30</div>
                <div className="calendar-day">31</div>
                <div className="calendar-day text-muted">1</div>
              </div>
              
              <div className="d-flex justify-content-between align-items-center mt-3">
                <small className="text-muted">
                  <span className="badge bg-primary me-1"></span> Cites programades
                </small>
                <a href="#" className="text-decoration-none small">Veure totes</a>
              </div>
            </div>
          </div>
          
          {/* Health Tips */}
          <div className="card">
            <div className="card-header">
              <h5 className="mb-0">
                <i className="fas fa-heartbeat me-2 text-primary"></i>
                Consells de salut
              </h5>
            </div>
            <div className="card-body">
              <div className="health-tip mb-3 pb-3 border-bottom">
                <h6>Mantén-te hidratat</h6>
                <p className="text-muted small mb-0">
                  Beure suficient aigua és essencial per mantenir el cos funcionant correctament. Intenta beure almenys 8 gots d'aigua al dia.
                </p>
              </div>
              <div className="health-tip mb-3 pb-3 border-bottom">
                <h6>Fes exercici regularment</h6>
                <p className="text-muted small mb-0">
                  Almenys 30 minuts d'activitat física moderada la majoria dels dies de la setmana pot millorar significativament la teva salut.
                </p>
              </div>
              <div className="health-tip">
                <h6>Dorm suficient</h6>
                <p className="text-muted small mb-0">
                  Els adults necessiten entre 7 i 9 hores de son cada nit per mantenir una bona salut física i mental.
                </p>
              </div>
            </div>
            <div className="card-footer text-center">
              <a href="#" className="text-decoration-none">Més consells de salut</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

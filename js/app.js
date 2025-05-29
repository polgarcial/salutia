// Salutia - Main React Application

// Define the main App component
const App = () => {
  const [currentPage, setCurrentPage] = React.useState('home');
  const [isLoggedIn, setIsLoggedIn] = React.useState(false);
  const [user, setUser] = React.useState(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState(null);

  // Check if user is logged in on app load
  React.useEffect(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    
    if (token && userData) {
      try {
        setUser(JSON.parse(userData));
        setIsLoggedIn(true);
      } catch (err) {
        console.error('Error parsing user data:', err);
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      }
    }
    
    setLoading(false);
  }, []);

  // Handle login
  const handleLogin = (userData, token) => {
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(userData));
    setUser(userData);
    setIsLoggedIn(true);
    setCurrentPage('dashboard');
  };

  // Handle logout
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    setIsLoggedIn(false);
    setCurrentPage('home');
  };

  // Handle navigation
  const navigate = (page) => {
    setCurrentPage(page);
  };

  // Render loading state
  if (loading) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ height: '100vh' }}>
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Carregant...</span>
        </div>
      </div>
    );
  }

  // Render content based on authentication state and current page
  const renderContent = () => {
    if (!isLoggedIn && (currentPage === 'login' || currentPage === 'register')) {
      return currentPage === 'login' 
        ? <Login onLogin={handleLogin} navigate={navigate} /> 
        : <Register onRegister={handleLogin} navigate={navigate} />;
    }

    if (isLoggedIn) {
      switch (currentPage) {
        case 'dashboard':
          return <Dashboard user={user} />;
        case 'appointments':
          return <AppointmentList user={user} />;
        case 'new-appointment':
          return <AppointmentForm user={user} />;
        case 'medical-records':
          return <MedicalRecords user={user} />;
        case 'prescriptions':
          return <Prescriptions user={user} />;
        case 'chat':
          return <Chat user={user} />;
        default:
          return <Dashboard user={user} />;
      }
    }

    // Default home page for non-authenticated users
    return (
      <div className="container mt-5">
        <div className="row align-items-center">
          <div className="col-md-6">
            <h1 className="display-4 mb-4">Benvingut a Salutia</h1>
            <p className="lead mb-4">
              La plataforma innovadora per a la gestió eficient de cites mèdiques mitjançant intel·ligència artificial.
            </p>
            <p className="mb-4">
              Salutia facilita la programació de visites, l'accés a l'historial mèdic i millora la comunicació entre pacients i professionals sanitaris.
            </p>
            <div className="d-flex gap-3">
              <button 
                className="btn btn-primary btn-lg" 
                onClick={() => navigate('login')}
              >
                Iniciar sessió
              </button>
              <button 
                className="btn btn-outline-primary btn-lg" 
                onClick={() => window.location.href = '/nuevo_registro.html'}
              >
                Registrar-se
              </button>
            </div>
          </div>
          <div className="col-md-6">
            <img 
              src="img/hero-image.svg" 
              alt="Salutia Platform" 
              className="img-fluid" 
              onError={(e) => {
                e.target.onerror = null;
                e.target.src = 'https://via.placeholder.com/600x400?text=Salutia';
              }}
            />
          </div>
        </div>

        <div className="row mt-5 pt-5">
          <div className="col-12 text-center mb-5">
            <h2 className="mb-4">Els nostres serveis</h2>
            <p className="lead">Descobreix com Salutia pot millorar la teva experiència sanitària</p>
          </div>

          <div className="col-md-4 mb-4">
            <div className="card h-100">
              <div className="card-body text-center">
                <i className="fas fa-calendar-check fa-3x mb-3 text-primary"></i>
                <h3 className="card-title">Gestió de Cites</h3>
                <p className="card-text">Programa cites mèdiques de manera fàcil i ràpida amb els professionals sanitaris.</p>
              </div>
            </div>
          </div>

          <div className="col-md-4 mb-4">
            <div className="card h-100">
              <div className="card-body text-center">
                <i className="fas fa-notes-medical fa-3x mb-3 text-primary"></i>
                <h3 className="card-title">Historial Mèdic</h3>
                <p className="card-text">Accedeix al teu historial mèdic complet i comparteix-lo amb els teus metges.</p>
              </div>
            </div>
          </div>

          <div className="col-md-4 mb-4">
            <div className="card h-100">
              <div className="card-body text-center">
                <i className="fas fa-robot fa-3x mb-3 text-primary"></i>
                <h3 className="card-title">Assistència IA</h3>
                <p className="card-text">Resol dubtes i consultes ràpides mitjançant el nostre xat amb intel·ligència artificial.</p>
              </div>
            </div>
          </div>
        </div>

        <div className="row mt-5 pt-5">
          <div className="col-12 text-center mb-5">
            <h2 className="mb-4">Per què escollir Salutia?</h2>
            <p className="lead">La nostra plataforma ofereix múltiples avantatges</p>
          </div>

          <div className="col-md-6 mb-4">
            <div className="d-flex">
              <div className="me-3">
                <i className="fas fa-shield-alt fa-2x text-primary"></i>
              </div>
              <div>
                <h4>Seguretat i Privacitat</h4>
                <p>Les teves dades mèdiques estan protegides amb els més alts estàndards de seguretat.</p>
              </div>
            </div>
          </div>

          <div className="col-md-6 mb-4">
            <div className="d-flex">
              <div className="me-3">
                <i className="fas fa-clock fa-2x text-primary"></i>
              </div>
              <div>
                <h4>Estalvi de Temps</h4>
                <p>Redueix el temps d'espera i gestiona les teves cites des de qualsevol lloc.</p>
              </div>
            </div>
          </div>

          <div className="col-md-6 mb-4">
            <div className="d-flex">
              <div className="me-3">
                <i className="fas fa-user-md fa-2x text-primary"></i>
              </div>
              <div>
                <h4>Professionals Qualificats</h4>
                <p>Accedeix a una xarxa de professionals sanitaris de confiança.</p>
              </div>
            </div>
          </div>

          <div className="col-md-6 mb-4">
            <div className="d-flex">
              <div className="me-3">
                <i className="fas fa-mobile-alt fa-2x text-primary"></i>
              </div>
              <div>
                <h4>Accessibilitat</h4>
                <p>Utilitza la plataforma des de qualsevol dispositiu, en qualsevol moment.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  };

  return (
    <React.Fragment>
      <Navbar 
        isLoggedIn={isLoggedIn} 
        currentPage={currentPage} 
        navigate={navigate} 
        onLogout={handleLogout}
        user={user}
      />
      
      <main>
        {renderContent()}
      </main>
      
      <Footer />
    </React.Fragment>
  );
};

// Render the App component to the DOM
ReactDOM.createRoot(document.getElementById('root')).render(<App />);

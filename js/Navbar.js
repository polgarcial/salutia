// Navbar Component for Salutia

const Navbar = ({ isLoggedIn, currentPage, navigate, onLogout, user }) => {
  return (
    <nav className="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
      <div className="container">
        <a className="navbar-brand" href="#" onClick={() => navigate(isLoggedIn ? 'dashboard' : 'home')}>
          <i className="fas fa-heartbeat me-2 text-primary"></i>
          Salutia
        </a>
        
        <button 
          className="navbar-toggler" 
          type="button" 
          data-bs-toggle="collapse" 
          data-bs-target="#navbarNav" 
          aria-controls="navbarNav" 
          aria-expanded="false" 
          aria-label="Toggle navigation"
        >
          <span className="navbar-toggler-icon"></span>
        </button>
        
        <div className="collapse navbar-collapse" id="navbarNav">
          {isLoggedIn ? (
            // Logged in navigation
            <ul className="navbar-nav ms-auto">
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'dashboard' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('dashboard')}
                >
                  <i className="fas fa-tachometer-alt me-1"></i> Tauler
                </a>
              </li>
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'appointments' || currentPage === 'new-appointment' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('appointments')}
                >
                  <i className="fas fa-calendar-alt me-1"></i> Cites
                </a>
              </li>
              {user && user.role === 'patient' && (
                <li className="nav-item">
                  <a 
                    className={`nav-link ${currentPage === 'medical-records' ? 'active' : ''}`} 
                    href="#" 
                    onClick={() => navigate('medical-records')}
                  >
                    <i className="fas fa-notes-medical me-1"></i> Historial
                  </a>
                </li>
              )}
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'prescriptions' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('prescriptions')}
                >
                  <i className="fas fa-prescription me-1"></i> Receptes
                </a>
              </li>
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'chat' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('chat')}
                >
                  <i className="fas fa-comment-medical me-1"></i> Xat
                </a>
              </li>
              <li className="nav-item dropdown">
                <a 
                  className="nav-link dropdown-toggle" 
                  href="#" 
                  id="navbarDropdown" 
                  role="button" 
                  data-bs-toggle="dropdown" 
                  aria-expanded="false"
                >
                  <i className="fas fa-user-circle me-1"></i> 
                  {user ? `${user.first_name} ${user.last_name}` : 'Perfil'}
                </a>
                <ul className="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                  <li>
                    <a className="dropdown-item" href="#" onClick={() => navigate('profile')}>
                      <i className="fas fa-id-card me-2"></i> El meu perfil
                    </a>
                  </li>
                  <li>
                    <a className="dropdown-item" href="#" onClick={() => navigate('settings')}>
                      <i className="fas fa-cog me-2"></i> Configuració
                    </a>
                  </li>
                  <li><hr className="dropdown-divider" /></li>
                  <li>
                    <a className="dropdown-item text-danger" href="#" onClick={onLogout}>
                      <i className="fas fa-sign-out-alt me-2"></i> Tancar sessió
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
          ) : (
            // Logged out navigation
            <ul className="navbar-nav ms-auto">
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'home' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('home')}
                >
                  Inici
                </a>
              </li>
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'about' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('about')}
                >
                  Sobre nosaltres
                </a>
              </li>
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'services' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('services')}
                >
                  Serveis
                </a>
              </li>
              <li className="nav-item">
                <a 
                  className={`nav-link ${currentPage === 'contact' ? 'active' : ''}`} 
                  href="#" 
                  onClick={() => navigate('contact')}
                >
                  Contacte
                </a>
              </li>
              <li className="nav-item">
                <a 
                  className="nav-link btn btn-primary text-white px-3 ms-2" 
                  href="#" 
                  onClick={() => navigate('login')}
                >
                  Iniciar sessió
                </a>
              </li>
            </ul>
          )}
        </div>
      </div>
    </nav>
  );
};

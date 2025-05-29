// Login Component for Salutia

const Login = ({ onLogin, navigate }) => {
  const [email, setEmail] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState('');
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validate form
    if (!email || !password) {
      setError('Si us plau, introdueix el correu electrònic i la contrasenya.');
      return;
    }
    
    try {
      setLoading(true);
      setError('');
      
      // In a real application, this would be an API call
      // For demo purposes, we'll simulate a successful login
      const response = await fetch('http://localhost/salutia/backend/auth?action=login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.error || 'Error en iniciar sessió');
      }
      
      // Call the onLogin callback with user data and token
      onLogin(data.data.user, data.data.token);
    } catch (err) {
      console.error('Login error:', err);
      setError(err.message || 'Error en iniciar sessió. Comprova les teves credencials.');
      
      // For demo purposes, let's provide a way to login without a backend
      if (email === 'patient@salutia.com' && password === 'password') {
        const demoUser = {
          id: 3,
          email: 'patient@salutia.com',
          first_name: 'Maria',
          last_name: 'Pacient',
          role: 'patient'
        };
        const demoToken = 'demo_token_for_testing';
        
        onLogin(demoUser, demoToken);
      } else if (email === 'doctor@salutia.com' && password === 'password') {
        const demoUser = {
          id: 2,
          email: 'doctor@salutia.com',
          first_name: 'Joan',
          last_name: 'Metge',
          role: 'doctor',
          specialty: 'Medicina General'
        };
        const demoToken = 'demo_token_for_testing';
        
        onLogin(demoUser, demoToken);
      } else if (email === 'admin@salutia.com' && password === 'password') {
        const demoUser = {
          id: 1,
          email: 'admin@salutia.com',
          first_name: 'Admin',
          last_name: 'User',
          role: 'admin'
        };
        const demoToken = 'demo_token_for_testing';
        
        onLogin(demoUser, demoToken);
      }
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="container mt-5">
      <div className="row justify-content-center">
        <div className="col-md-6 col-lg-5">
          <div className="card shadow">
            <div className="card-body p-5">
              <div className="text-center mb-4">
                <i className="fas fa-heartbeat text-primary fa-3x mb-3"></i>
                <h2 className="card-title">Iniciar sessió</h2>
                <p className="text-muted">Accedeix al teu compte de Salutia</p>
              </div>
              
              {error && (
                <div className="alert alert-danger" role="alert">
                  {error}
                </div>
              )}
              
              <form onSubmit={handleSubmit}>
                <div className="mb-3">
                  <label htmlFor="email" className="form-label">Correu electrònic</label>
                  <div className="input-group">
                    <span className="input-group-text">
                      <i className="fas fa-envelope"></i>
                    </span>
                    <input
                      type="email"
                      className="form-control"
                      id="email"
                      placeholder="exemple@correu.com"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      required
                    />
                  </div>
                </div>
                
                <div className="mb-4">
                  <div className="d-flex justify-content-between">
                    <label htmlFor="password" className="form-label">Contrasenya</label>
                    <a href="#" className="text-decoration-none small" onClick={() => navigate('forgot-password')}>
                      Has oblidat la contrasenya?
                    </a>
                  </div>
                  <div className="input-group">
                    <span className="input-group-text">
                      <i className="fas fa-lock"></i>
                    </span>
                    <input
                      type="password"
                      className="form-control"
                      id="password"
                      placeholder="Contrasenya"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      required
                    />
                  </div>
                </div>
                
                <div className="mb-4 form-check">
                  <input type="checkbox" className="form-check-input" id="rememberMe" />
                  <label className="form-check-label" htmlFor="rememberMe">Recorda'm</label>
                </div>
                
                <button 
                  type="submit" 
                  className="btn btn-primary w-100 py-2"
                  disabled={loading}
                >
                  {loading ? (
                    <span>
                      <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      Iniciant sessió...
                    </span>
                  ) : 'Iniciar sessió'}
                </button>
              </form>
              
              <div className="text-center mt-4">
                <p className="mb-0">
                  No tens un compte? {' '}
                  <a href="#" className="text-decoration-none" onClick={() => navigate('register')}>
                    Registra't
                  </a>
                </p>
              </div>
              
              <div className="text-center mt-4">
                <p className="text-muted small mb-0">Credencials de demostració:</p>
                <p className="text-muted small mb-0">Pacient: patient@salutia.com / password</p>
                <p className="text-muted small mb-0">Metge: doctor@salutia.com / password</p>
                <p className="text-muted small mb-0">Admin: admin@salutia.com / password</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

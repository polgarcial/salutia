// Register Component for Salutia

const Register = ({ onRegister, navigate }) => {
  const [formData, setFormData] = React.useState({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    confirm_password: '',
    date_of_birth: '',
    phone: '',
    address: '',
    role: 'patient'
  });
  
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState('');
  const [successMessage, setSuccessMessage] = React.useState('');
  
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prevData => ({
      ...prevData,
      [name]: value
    }));
  };
  
  const validateForm = () => {
    // Validar campos obligatorios
    if (!formData.first_name || !formData.last_name || !formData.email || !formData.password) {
      setError('Por favor, completa todos los campos obligatorios.');
      return false;
    }
    
    // Validar formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      setError('Por favor, introduce un correo electrónico válido.');
      return false;
    }
    
    // Validar que las contraseñas coincidan
    if (formData.password !== formData.confirm_password) {
      setError('Las contraseñas no coinciden.');
      return false;
    }
    
    // Validar longitud de la contraseña
    if (formData.password.length < 6) {
      setError('La contraseña debe tener al menos 6 caracteres.');
      return false;
    }
    
    return true;
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Validar el formulario
    if (!validateForm()) {
      return;
    }
    
    try {
      setLoading(true);
      setError('');
      
      // Preparar los datos para enviar al servidor
      const dataToSend = {
        first_name: formData.first_name,
        last_name: formData.last_name,
        email: formData.email,
        password: formData.password,
        date_of_birth: formData.date_of_birth || null,
        phone: formData.phone || null,
        address: formData.address || null,
        role: formData.role
      };
      
      // Realizar la petición al servidor
      const response = await fetch('/backend/api/register.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(dataToSend),
      });
      
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.error || 'Error al registrar el usuario');
      }
      
      // Mostrar mensaje de éxito
      setSuccessMessage('¡Registro completado con éxito!');
      
      // Esperar un momento antes de redirigir
      setTimeout(() => {
        // Llamar a la función onRegister con los datos del usuario y el token
        onRegister(data.data.user, data.data.token);
      }, 1500);
      
    } catch (err) {
      console.error('Error de registro:', err);
      setError(err.message || 'Error al registrar el usuario. Por favor, inténtalo de nuevo.');
    } finally {
      setLoading(false);
    }
  };
  
  return (
    <div className="container mt-5">
      <div className="row justify-content-center">
        <div className="col-md-8 col-lg-6">
          <div className="card shadow">
            <div className="card-body p-5">
              <div className="text-center mb-4">
                <i className="fas fa-user-plus text-primary fa-3x mb-3"></i>
                <h2 className="card-title">Crear cuenta</h2>
                <p className="text-muted">Regístrate para acceder a Salutia</p>
              </div>
              
              {error && (
                <div className="alert alert-danger" role="alert">
                  <i className="fas fa-exclamation-circle me-2"></i>
                  {error}
                </div>
              )}
              
              {successMessage && (
                <div className="alert alert-success" role="alert">
                  <i className="fas fa-check-circle me-2"></i>
                  {successMessage}
                </div>
              )}
              
              <form onSubmit={handleSubmit}>
                <div className="row mb-3">
                  <div className="col-md-6 mb-3 mb-md-0">
                    <label htmlFor="first_name" className="form-label">Nombre *</label>
                    <input
                      type="text"
                      className="form-control"
                      id="first_name"
                      name="first_name"
                      value={formData.first_name}
                      onChange={handleChange}
                      required
                    />
                  </div>
                  <div className="col-md-6">
                    <label htmlFor="last_name" className="form-label">Apellidos *</label>
                    <input
                      type="text"
                      className="form-control"
                      id="last_name"
                      name="last_name"
                      value={formData.last_name}
                      onChange={handleChange}
                      required
                    />
                  </div>
                </div>
                
                <div className="mb-3">
                  <label htmlFor="email" className="form-label">Correo electrónico *</label>
                  <input
                    type="email"
                    className="form-control"
                    id="email"
                    name="email"
                    value={formData.email}
                    onChange={handleChange}
                    required
                  />
                </div>
                
                <div className="row mb-3">
                  <div className="col-md-6 mb-3 mb-md-0">
                    <label htmlFor="password" className="form-label">Contraseña *</label>
                    <input
                      type="password"
                      className="form-control"
                      id="password"
                      name="password"
                      value={formData.password}
                      onChange={handleChange}
                      required
                    />
                    <small className="text-muted">Mínimo 6 caracteres</small>
                  </div>
                  <div className="col-md-6">
                    <label htmlFor="confirm_password" className="form-label">Confirmar contraseña *</label>
                    <input
                      type="password"
                      className="form-control"
                      id="confirm_password"
                      name="confirm_password"
                      value={formData.confirm_password}
                      onChange={handleChange}
                      required
                    />
                  </div>
                </div>
                
                <div className="mb-3">
                  <label htmlFor="date_of_birth" className="form-label">Fecha de nacimiento</label>
                  <input
                    type="date"
                    className="form-control"
                    id="date_of_birth"
                    name="date_of_birth"
                    value={formData.date_of_birth}
                    onChange={handleChange}
                  />
                </div>
                
                <div className="mb-3">
                  <label htmlFor="phone" className="form-label">Teléfono</label>
                  <input
                    type="tel"
                    className="form-control"
                    id="phone"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                  />
                </div>
                
                <div className="mb-3">
                  <label htmlFor="address" className="form-label">Dirección</label>
                  <textarea
                    className="form-control"
                    id="address"
                    name="address"
                    rows="2"
                    value={formData.address}
                    onChange={handleChange}
                  ></textarea>
                </div>
                
                <div className="mb-4">
                  <label className="form-label">Tipo de usuario *</label>
                  <div className="d-flex">
                    <div className="form-check me-3">
                      <input
                        className="form-check-input"
                        type="radio"
                        name="role"
                        id="role_patient"
                        value="patient"
                        checked={formData.role === 'patient'}
                        onChange={handleChange}
                      />
                      <label className="form-check-label" htmlFor="role_patient">
                        Paciente
                      </label>
                    </div>
                    <div className="form-check">
                      <input
                        className="form-check-input"
                        type="radio"
                        name="role"
                        id="role_doctor"
                        value="doctor"
                        checked={formData.role === 'doctor'}
                        onChange={handleChange}
                      />
                      <label className="form-check-label" htmlFor="role_doctor">
                        Médico
                      </label>
                    </div>
                  </div>
                </div>
                
                {formData.role === 'doctor' && (
                  <div className="mb-4">
                    <div className="alert alert-info" role="alert">
                      <i className="fas fa-info-circle me-2"></i>
                      Los perfiles de médicos requieren verificación adicional. Te contactaremos para completar el proceso.
                    </div>
                  </div>
                )}
                
                <div className="mb-4 form-check">
                  <input type="checkbox" className="form-check-input" id="terms" required />
                  <label className="form-check-label" htmlFor="terms">
                    Acepto los <a href="#" onClick={(e) => e.preventDefault()}>términos y condiciones</a> y la <a href="#" onClick={(e) => e.preventDefault()}>política de privacidad</a>
                  </label>
                </div>
                
                <button 
                  type="submit" 
                  className="btn btn-primary w-100 py-2"
                  disabled={loading}
                >
                  {loading ? (
                    <span>
                      <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                      Registrando...
                    </span>
                  ) : 'Crear cuenta'}
                </button>
              </form>
              
              <div className="text-center mt-4">
                <p className="mb-0">
                  ¿Ya tienes una cuenta? {' '}
                  <a href="#" className="text-decoration-none" onClick={() => navigate('login')}>
                    Iniciar sesión
                  </a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

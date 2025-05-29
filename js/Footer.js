// Footer Component for Salutia

const Footer = () => {
  const currentYear = new Date().getFullYear();
  
  return (
    <footer className="footer mt-auto">
      <div className="container">
        <div className="footer-content">
          <div className="footer-brand">
            <div className="footer-logo">
              <i className="fas fa-heartbeat me-2"></i>
              Salutia
            </div>
            <p>
              Plataforma innovadora per a la gestió eficient de cites mèdiques mitjançant intel·ligència artificial.
            </p>
            <div className="social-links mt-3">
              <a href="#" className="me-3"><i className="fab fa-facebook-f"></i></a>
              <a href="#" className="me-3"><i className="fab fa-twitter"></i></a>
              <a href="#" className="me-3"><i className="fab fa-instagram"></i></a>
              <a href="#" className="me-3"><i className="fab fa-linkedin-in"></i></a>
            </div>
          </div>
          
          <div className="footer-links">
            <h4>Enllaços ràpids</h4>
            <ul>
              <li><a href="#">Inici</a></li>
              <li><a href="#">Sobre nosaltres</a></li>
              <li><a href="#">Serveis</a></li>
              <li><a href="#">Contacte</a></li>
              <li><a href="#">Blog</a></li>
            </ul>
          </div>
          
          <div className="footer-links">
            <h4>Serveis</h4>
            <ul>
              <li><a href="#">Cites mèdiques</a></li>
              <li><a href="#">Historial mèdic</a></li>
              <li><a href="#">Receptes electròniques</a></li>
              <li><a href="#">Xat amb IA</a></li>
              <li><a href="#">Consultes en línia</a></li>
            </ul>
          </div>
          
          <div className="footer-links">
            <h4>Contacte</h4>
            <ul>
              <li><i className="fas fa-map-marker-alt me-2"></i> Carrer Principal, 123, Barcelona</li>
              <li><i className="fas fa-phone me-2"></i> +34 93 123 45 67</li>
              <li><i className="fas fa-envelope me-2"></i> info@salutia.com</li>
            </ul>
          </div>
        </div>
        
        <div className="footer-bottom">
          <p>&copy; {currentYear} Salutia. Tots els drets reservats.</p>
        </div>
      </div>
    </footer>
  );
};

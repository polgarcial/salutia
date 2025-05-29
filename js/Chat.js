// Chat Component for Salutia

const Chat = ({ user }) => {
  const [messages, setMessages] = React.useState([]);
  const [newMessage, setNewMessage] = React.useState('');
  const [loading, setLoading] = React.useState(false);
  const [contacts, setContacts] = React.useState([]);
  const [selectedContact, setSelectedContact] = React.useState(null);
  const [showAIChat, setShowAIChat] = React.useState(false);
  
  const messagesEndRef = React.useRef(null);
  
  // Scroll to bottom of messages
  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };
  
  // Fetch contacts and messages on component mount
  React.useEffect(() => {
    const fetchData = async () => {
      try {
        // In a real application, these would be API calls
        // For demo purposes, we'll use mock data
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Mock contacts based on user role
        let mockContacts = [];
        
        if (user.role === 'patient') {
          mockContacts = [
            {
              id: 'ai',
              name: 'Assistent IA',
              role: 'ai',
              avatar: null,
              isOnline: true,
              lastMessage: 'Com puc ajudar-te avui?',
              unreadCount: 0
            },
            {
              id: 2,
              name: 'Dr. Joan Metge',
              role: 'doctor',
              specialty: 'Medicina General',
              avatar: null,
              isOnline: true,
              lastMessage: 'Recorda prendre la medicació cada 8 hores.',
              unreadCount: 1
            },
            {
              id: 5,
              name: 'Dra. Laura Cardio',
              role: 'doctor',
              specialty: 'Cardiologia',
              avatar: null,
              isOnline: false,
              lastMessage: 'Els resultats de la prova són normals.',
              unreadCount: 0
            },
            {
              id: 8,
              name: 'Dra. Marta Dermato',
              role: 'doctor',
              specialty: 'Dermatologia',
              avatar: null,
              isOnline: false,
              lastMessage: 'Aplica la crema dues vegades al dia.',
              unreadCount: 0
            }
          ];
        } else if (user.role === 'doctor') {
          mockContacts = [
            {
              id: 'ai',
              name: 'Assistent IA',
              role: 'ai',
              avatar: null,
              isOnline: true,
              lastMessage: 'Com puc ajudar-te avui?',
              unreadCount: 0
            },
            {
              id: 3,
              name: 'Maria Pacient',
              role: 'patient',
              avatar: null,
              isOnline: true,
              lastMessage: 'Gràcies per la recepta, doctor.',
              unreadCount: 0
            },
            {
              id: 15,
              name: 'Josep Garcia',
              role: 'patient',
              avatar: null,
              isOnline: false,
              lastMessage: 'El dolor ha disminuït amb la medicació.',
              unreadCount: 2
            },
            {
              id: 22,
              name: 'Anna Martí',
              role: 'patient',
              avatar: null,
              isOnline: true,
              lastMessage: 'Tinc una pregunta sobre el tractament.',
              unreadCount: 1
            }
          ];
        }
        
        setContacts(mockContacts);
        
        // Set AI as default contact if no contact is selected
        if (!selectedContact) {
          setSelectedContact(mockContacts[0]);
          setShowAIChat(true);
          
          // Load AI welcome messages
          setMessages([
            {
              id: 1,
              sender: 'ai',
              content: 'Hola! Sóc l\'assistent IA de Salutia. Com puc ajudar-te avui?',
              timestamp: new Date(Date.now() - 1000 * 60 * 5).toISOString(),
              isRead: true
            },
            {
              id: 2,
              sender: 'ai',
              content: 'Pots fer-me preguntes sobre els teus símptomes, cites o historial mèdic, o demanar-me que programi una cita per a tu.',
              timestamp: new Date(Date.now() - 1000 * 60 * 5 + 1000).toISOString(),
              isRead: true
            }
          ]);
        }
      } catch (err) {
        console.error('Error fetching chat data:', err);
      }
    };
    
    fetchData();
  }, [user]);
  
  // Scroll to bottom when messages change
  React.useEffect(() => {
    scrollToBottom();
  }, [messages]);
  
  // Handle contact selection
  const handleContactSelect = (contact) => {
    setSelectedContact(contact);
    setShowAIChat(contact.id === 'ai');
    
    // In a real application, we would fetch messages for this contact
    // For demo purposes, we'll use mock data
    
    if (contact.id === 'ai') {
      // AI chat messages
      setMessages([
        {
          id: 1,
          sender: 'ai',
          content: 'Hola! Sóc l\'assistent IA de Salutia. Com puc ajudar-te avui?',
          timestamp: new Date(Date.now() - 1000 * 60 * 5).toISOString(),
          isRead: true
        },
        {
          id: 2,
          sender: 'ai',
          content: 'Pots fer-me preguntes sobre els teus símptomes, cites o historial mèdic, o demanar-me que programi una cita per a tu.',
          timestamp: new Date(Date.now() - 1000 * 60 * 5 + 1000).toISOString(),
          isRead: true
        }
      ]);
    } else if (contact.id === 2 || contact.id === 3) {
      // Dr. Joan Metge or Maria Pacient
      setMessages([
        {
          id: 1,
          sender: contact.id,
          content: 'Bon dia! Com et trobes avui?',
          timestamp: new Date(Date.now() - 1000 * 60 * 60 * 2).toISOString(),
          isRead: true
        },
        {
          id: 2,
          sender: user.id,
          content: 'Bon dia! Em trobo millor, gràcies.',
          timestamp: new Date(Date.now() - 1000 * 60 * 60 * 2 + 1000 * 60 * 5).toISOString(),
          isRead: true
        },
        {
          id: 3,
          sender: contact.id,
          content: 'M\'alegro molt. Has seguit el tractament?',
          timestamp: new Date(Date.now() - 1000 * 60 * 60 * 1).toISOString(),
          isRead: true
        },
        {
          id: 4,
          sender: user.id,
          content: 'Sí, he pres la medicació tal com em vas indicar.',
          timestamp: new Date(Date.now() - 1000 * 60 * 60 * 1 + 1000 * 60 * 2).toISOString(),
          isRead: true
        },
        {
          id: 5,
          sender: contact.id,
          content: 'Perfecte! Recorda prendre la medicació cada 8 hores.',
          timestamp: new Date(Date.now() - 1000 * 60 * 30).toISOString(),
          isRead: contact.id !== 2 // Unread if Dr. Joan
        }
      ]);
    } else {
      // Other contacts - empty chat
      setMessages([]);
    }
    
    // Mark messages as read
    setContacts(prevContacts => 
      prevContacts.map(c => 
        c.id === contact.id ? { ...c, unreadCount: 0 } : c
      )
    );
  };
  
  // Handle sending a new message
  const handleSendMessage = async (e) => {
    e.preventDefault();
    
    if (!newMessage.trim()) return;
    
    // Add user message to chat
    const userMessage = {
      id: messages.length + 1,
      sender: user.id,
      content: newMessage,
      timestamp: new Date().toISOString(),
      isRead: false
    };
    
    setMessages(prevMessages => [...prevMessages, userMessage]);
    setNewMessage('');
    
    // If AI chat, generate response
    if (showAIChat) {
      setLoading(true);
      
      try {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Generate AI response based on user message
        let aiResponse = '';
        
        if (newMessage.toLowerCase().includes('cita') || newMessage.toLowerCase().includes('programar')) {
          aiResponse = 'Puc ajudar-te a programar una cita. Amb quin especialista vols visitar-te? Tenim disponibilitat amb medicina general, cardiologia i dermatologia.';
        } else if (newMessage.toLowerCase().includes('mal de cap') || newMessage.toLowerCase().includes('dolor')) {
          aiResponse = 'El mal de cap pot tenir diverses causes. Quant de temps fa que el tens? És constant o intermitent? Has pres algun medicament?';
        } else if (newMessage.toLowerCase().includes('recepta') || newMessage.toLowerCase().includes('medicament')) {
          aiResponse = 'Per a renovar una recepta, necessitaràs contactar amb el teu metge. Vols que programi una cita o envïi un missatge al teu metge per a la renovació?';
        } else if (newMessage.toLowerCase().includes('gràcies') || newMessage.toLowerCase().includes('gracies')) {
          aiResponse = 'De res! Estic aquí per ajudar-te. Hi ha alguna altra cosa en què pugui assistir-te?';
        } else if (newMessage.toLowerCase().includes('hola') || newMessage.toLowerCase().includes('bon dia') || newMessage.toLowerCase().includes('bona tarda')) {
          aiResponse = 'Hola! En què puc ajudar-te avui?';
        } else {
          aiResponse = 'Entenc. Pots donar-me més detalls per poder ajudar-te millor? Estic aquí per assistir-te amb cites, consultes mèdiques i informació sobre el teu historial.';
        }
        
        // Add AI response to chat
        const aiMessageObj = {
          id: messages.length + 2,
          sender: 'ai',
          content: aiResponse,
          timestamp: new Date().toISOString(),
          isRead: true
        };
        
        setMessages(prevMessages => [...prevMessages, aiMessageObj]);
      } catch (err) {
        console.error('Error generating AI response:', err);
      } finally {
        setLoading(false);
      }
    } else {
      // In a real application, we would send the message to the backend
      // For demo purposes, we'll simulate a response
      
      setTimeout(() => {
        // Add response message
        const responseMessage = {
          id: messages.length + 2,
          sender: selectedContact.id,
          content: 'D\'acord, ho tindré en compte. Gràcies per informar-me.',
          timestamp: new Date().toISOString(),
          isRead: true
        };
        
        setMessages(prevMessages => [...prevMessages, responseMessage]);
      }, 3000);
    }
  };
  
  // Format timestamp for display
  const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
      // Today - show time
      return date.toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDays === 1) {
      // Yesterday
      return 'Ahir ' + date.toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDays < 7) {
      // This week - show day name
      return date.toLocaleDateString('ca-ES', { weekday: 'long' }) + ' ' + 
             date.toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' });
    } else {
      // Older - show full date
      return date.toLocaleDateString('ca-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
             date.toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' });
    }
  };
  
  // Get quick response suggestions for AI chat
  const getQuickResponses = () => {
    if (!showAIChat) return [];
    
    return [
      'Vull programar una cita',
      'Tinc mal de cap',
      'Necessito renovar una recepta',
      'Quan és la meva pròxima cita?'
    ];
  };
  
  return (
    <div className="container mt-4">
      <div className="row mb-4">
        <div className="col-12">
          <h1 className="mb-0">
            <i className="fas fa-comment-medical me-2 text-primary"></i>
            Xat mèdic
          </h1>
          <p className="text-muted">
            Comunica't amb professionals sanitaris i el nostre assistent IA
          </p>
        </div>
      </div>
      
      <div className="card">
        <div className="card-body p-0">
          <div className="row g-0">
            {/* Contacts Sidebar */}
            <div className="col-md-4 col-lg-3 border-end">
              <div className="p-3 border-bottom">
                <div className="input-group">
                  <span className="input-group-text">
                    <i className="fas fa-search"></i>
                  </span>
                  <input 
                    type="text" 
                    className="form-control" 
                    placeholder="Cerca contactes..." 
                  />
                </div>
              </div>
              
              <div className="contacts-list" style={{ height: '600px', overflowY: 'auto' }}>
                {contacts.map(contact => (
                  <div 
                    key={contact.id} 
                    className={`contact-item d-flex align-items-center p-3 border-bottom ${selectedContact?.id === contact.id ? 'bg-light' : ''}`}
                    onClick={() => handleContactSelect(contact)}
                    style={{ cursor: 'pointer' }}
                  >
                    <div className="position-relative me-3">
                      {contact.avatar ? (
                        <img 
                          src={contact.avatar} 
                          alt={contact.name} 
                          className="rounded-circle" 
                          width="50" 
                          height="50" 
                        />
                      ) : (
                        <div 
                          className={`rounded-circle d-flex justify-content-center align-items-center text-white ${
                            contact.role === 'doctor' ? 'bg-primary' : 
                            contact.role === 'ai' ? 'bg-success' : 'bg-secondary'
                          }`}
                          style={{ width: '50px', height: '50px' }}
                        >
                          {contact.role === 'doctor' ? (
                            <i className="fas fa-user-md"></i>
                          ) : contact.role === 'ai' ? (
                            <i className="fas fa-robot"></i>
                          ) : (
                            <i className="fas fa-user"></i>
                          )}
                        </div>
                      )}
                      {contact.isOnline && (
                        <span 
                          className="position-absolute bottom-0 end-0 bg-success rounded-circle"
                          style={{ width: '12px', height: '12px', border: '2px solid white' }}
                        ></span>
                      )}
                    </div>
                    
                    <div className="flex-grow-1">
                      <h6 className="mb-0">{contact.name}</h6>
                      <p className="text-muted small mb-0">
                        {contact.role === 'doctor' ? (
                          <span>{contact.specialty}</span>
                        ) : contact.role === 'ai' ? (
                          <span>Assistent IA</span>
                        ) : (
                          <span>Pacient</span>
                        )}
                      </p>
                    </div>
                    
                    {contact.unreadCount > 0 && (
                      <span className="badge bg-danger rounded-pill ms-2">
                        {contact.unreadCount}
                      </span>
                    )}
                  </div>
                ))}
              </div>
            </div>
            
            {/* Chat Area */}
            <div className="col-md-8 col-lg-9">
              {selectedContact ? (
                <>
                  {/* Chat Header */}
                  <div className="chat-header p-3 border-bottom d-flex align-items-center">
                    <div className="position-relative me-3">
                      {selectedContact.avatar ? (
                        <img 
                          src={selectedContact.avatar} 
                          alt={selectedContact.name} 
                          className="rounded-circle" 
                          width="40" 
                          height="40" 
                        />
                      ) : (
                        <div 
                          className={`rounded-circle d-flex justify-content-center align-items-center text-white ${
                            selectedContact.role === 'doctor' ? 'bg-primary' : 
                            selectedContact.role === 'ai' ? 'bg-success' : 'bg-secondary'
                          }`}
                          style={{ width: '40px', height: '40px' }}
                        >
                          {selectedContact.role === 'doctor' ? (
                            <i className="fas fa-user-md"></i>
                          ) : selectedContact.role === 'ai' ? (
                            <i className="fas fa-robot"></i>
                          ) : (
                            <i className="fas fa-user"></i>
                          )}
                        </div>
                      )}
                      {selectedContact.isOnline && (
                        <span 
                          className="position-absolute bottom-0 end-0 bg-success rounded-circle"
                          style={{ width: '10px', height: '10px', border: '2px solid white' }}
                        ></span>
                      )}
                    </div>
                    
                    <div>
                      <h6 className="mb-0">{selectedContact.name}</h6>
                      <p className="text-muted small mb-0">
                        {selectedContact.isOnline ? 'En línia' : 'Fora de línia'}
                        {selectedContact.role === 'doctor' && ` • ${selectedContact.specialty}`}
                      </p>
                    </div>
                    
                    <div className="ms-auto">
                      {selectedContact.role !== 'ai' && (
                        <>
                          <button className="btn btn-sm btn-outline-primary me-2">
                            <i className="fas fa-phone"></i>
                          </button>
                          <button className="btn btn-sm btn-outline-primary me-2">
                            <i className="fas fa-video"></i>
                          </button>
                        </>
                      )}
                      <button className="btn btn-sm btn-outline-secondary">
                        <i className="fas fa-info-circle"></i>
                      </button>
                    </div>
                  </div>
                  
                  {/* Chat Messages */}
                  <div className="chat-messages p-3" style={{ height: '450px', overflowY: 'auto' }}>
                    {messages.length > 0 ? (
                      messages.map(message => (
                        <div 
                          key={message.id} 
                          className={`message-container d-flex ${message.sender === user.id ? 'justify-content-end' : 'justify-content-start'} mb-3`}
                        >
                          {message.sender !== user.id && message.sender !== 'ai' && (
                            <div className="me-2">
                              <div 
                                className={`rounded-circle d-flex justify-content-center align-items-center text-white ${
                                  selectedContact.role === 'doctor' ? 'bg-primary' : 
                                  'bg-secondary'
                                }`}
                                style={{ width: '30px', height: '30px', fontSize: '0.8rem' }}
                              >
                                {selectedContact.role === 'doctor' ? (
                                  <i className="fas fa-user-md"></i>
                                ) : (
                                  <i className="fas fa-user"></i>
                                )}
                              </div>
                            </div>
                          )}
                          
                          <div style={{ maxWidth: '75%' }}>
                            <div 
                              className={`message p-3 rounded-3 ${
                                message.sender === user.id 
                                  ? 'message-sent bg-primary text-white' 
                                  : message.sender === 'ai'
                                  ? 'message-ai bg-success text-white'
                                  : 'message-received bg-light'
                              }`}
                            >
                              {message.content}
                            </div>
                            <div className="message-meta d-flex align-items-center mt-1">
                              <small className="text-muted">
                                {formatTimestamp(message.timestamp)}
                              </small>
                              {message.sender === user.id && (
                                <small className="ms-2">
                                  {message.isRead ? (
                                    <i className="fas fa-check-double text-primary"></i>
                                  ) : (
                                    <i className="fas fa-check text-muted"></i>
                                  )}
                                </small>
                              )}
                            </div>
                          </div>
                          
                          {message.sender === user.id && (
                            <div className="ms-2">
                              <div 
                                className="rounded-circle d-flex justify-content-center align-items-center text-white bg-info"
                                style={{ width: '30px', height: '30px', fontSize: '0.8rem' }}
                              >
                                <i className="fas fa-user"></i>
                              </div>
                            </div>
                          )}
                        </div>
                      ))
                    ) : (
                      <div className="text-center py-5">
                        <i className="fas fa-comment-dots fa-3x text-muted mb-3"></i>
                        <p>Encara no hi ha missatges.</p>
                        <p className="text-muted">Envia un missatge per iniciar la conversa.</p>
                      </div>
                    )}
                    
                    {loading && (
                      <div className="message-container d-flex justify-content-start mb-3">
                        <div className="me-2">
                          <div 
                            className="rounded-circle d-flex justify-content-center align-items-center text-white bg-success"
                            style={{ width: '30px', height: '30px', fontSize: '0.8rem' }}
                          >
                            <i className="fas fa-robot"></i>
                          </div>
                        </div>
                        <div className="message p-3 rounded-3 message-ai bg-success text-white">
                          <div className="typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                          </div>
                        </div>
                      </div>
                    )}
                    
                    <div ref={messagesEndRef} />
                  </div>
                  
                  {/* Quick Responses for AI Chat */}
                  {showAIChat && getQuickResponses().length > 0 && (
                    <div className="quick-responses p-2 border-top">
                      <div className="d-flex flex-wrap gap-2">
                        {getQuickResponses().map((response, index) => (
                          <button 
                            key={index} 
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => setNewMessage(response)}
                          >
                            {response}
                          </button>
                        ))}
                      </div>
                    </div>
                  )}
                  
                  {/* Chat Input */}
                  <div className="chat-input p-3 border-top">
                    <form onSubmit={handleSendMessage}>
                      <div className="input-group">
                        <button 
                          type="button" 
                          className="btn btn-outline-secondary"
                          title="Adjuntar arxiu"
                        >
                          <i className="fas fa-paperclip"></i>
                        </button>
                        <input 
                          type="text" 
                          className="form-control" 
                          placeholder="Escriu un missatge..." 
                          value={newMessage}
                          onChange={(e) => setNewMessage(e.target.value)}
                          disabled={loading}
                        />
                        <button 
                          type="submit" 
                          className="btn btn-primary"
                          disabled={!newMessage.trim() || loading}
                        >
                          <i className="fas fa-paper-plane"></i>
                        </button>
                      </div>
                    </form>
                  </div>
                </>
              ) : (
                <div className="text-center py-5">
                  <i className="fas fa-comments fa-4x text-muted mb-3"></i>
                  <h4>Selecciona un contacte</h4>
                  <p className="text-muted">Tria un contacte per iniciar una conversa.</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
      
      <style jsx>{`
        .typing-indicator {
          display: flex;
          align-items: center;
        }
        
        .typing-indicator span {
          height: 8px;
          width: 8px;
          background-color: rgba(255, 255, 255, 0.7);
          border-radius: 50%;
          display: inline-block;
          margin-right: 5px;
          animation: typing 1.4s infinite ease-in-out both;
        }
        
        .typing-indicator span:nth-child(1) {
          animation-delay: 0s;
        }
        
        .typing-indicator span:nth-child(2) {
          animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
          animation-delay: 0.4s;
          margin-right: 0;
        }
        
        @keyframes typing {
          0% {
            transform: scale(1);
          }
          50% {
            transform: scale(1.5);
          }
          100% {
            transform: scale(1);
          }
        }
      `}</style>
    </div>
  );
};

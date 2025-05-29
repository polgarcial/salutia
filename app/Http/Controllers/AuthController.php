<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication, registration, and password reset functionality.
 */

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Handle login requests
     */
    public function login() {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                return;
            }
            
            // Get user by email
            $stmt = $this->db->prepare("
                SELECT id, email, password, first_name, last_name, role 
                FROM users 
                WHERE email = :email
            ");
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            // Check if user exists and verify password
            if (!$user || !password_verify($data['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }
            
            // Generate JWT token
            $token = $this->generateJWT($user);
            
            // Remove password from response
            unset($user['password']);
            
            // Return user data and token
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handle user registration
     */
    public function register() {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['email']) || !isset($data['password']) || 
                !isset($data['first_name']) || !isset($data['last_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                return;
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Set default role to patient if not specified
            $role = isset($data['role']) ? $data['role'] : 'patient';
            
            // Insert new user
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    email, password, first_name, last_name, date_of_birth, 
                    phone, address, role
                ) VALUES (
                    :email, :password, :first_name, :last_name, :date_of_birth, 
                    :phone, :address, :role
                )
            ");
            
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':date_of_birth', $data['date_of_birth'] ?? null);
            $stmt->bindParam(':phone', $data['phone'] ?? null);
            $stmt->bindParam(':address', $data['address'] ?? null);
            $stmt->bindParam(':role', $role);
            
            $stmt->execute();
            $userId = $this->db->lastInsertId();
            
            // Get the newly created user
            $stmt = $this->db->prepare("
                SELECT id, email, first_name, last_name, role 
                FROM users 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            // Generate JWT token
            $token = $this->generateJWT($user);
            
            // Return success response
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handle password reset requests
     */
    public function requestPasswordReset() {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email is required']);
                return;
            }
            
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id, first_name FROM users WHERE email = :email");
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if (!$user) {
                // Don't reveal that the email doesn't exist for security reasons
                echo json_encode([
                    'success' => true,
                    'message' => 'If your email exists in our system, you will receive a password reset link'
                ]);
                return;
            }
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            // Note: In a real application, you would have a password_resets table
            // For this example, we'll just simulate the process
            
            // Send password reset email
            // In a real application, you would use a proper email library
            // For this example, we'll just simulate the process
            
            echo json_encode([
                'success' => true,
                'message' => 'If your email exists in our system, you will receive a password reset link'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Password reset request failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handle logout requests
     */
    public function logout() {
        // In a JWT-based authentication system, the client simply discards the token
        // The server doesn't need to do anything special
        echo json_encode([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
    
    /**
     * Generate a JWT token for authentication
     */
    private function generateJWT($user) {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_EXPIRATION;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
        
        // In a real application, you would use a proper JWT library
        // For this example, we'll just simulate the process
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Handle GET requests to the auth endpoint
     */
    public function getAll() {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
    /**
     * Handle GET requests to the auth endpoint with an ID
     */
    public function get($id) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
    /**
     * Handle POST requests to the auth endpoint
     */
    public function create() {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $this->login();
                break;
            case 'register':
                $this->register();
                break;
            case 'reset-password':
                $this->requestPasswordReset();
                break;
            case 'logout':
                $this->logout();
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
    }
    
    /**
     * Handle PUT requests to the auth endpoint
     */
    public function update($id) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
    /**
     * Handle DELETE requests to the auth endpoint
     */
    public function delete($id) {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

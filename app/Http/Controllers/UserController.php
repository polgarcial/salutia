php -S localhost:8000<?php
/**
 * User Controller
 * 
 * Handles all user-related operations including CRUD operations
 * for patients, doctors, and administrators.
 */

class UserController {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Get all users with optional filtering
     */
    public function getAll() {
        try {
            $role = isset($_GET['role']) ? $_GET['role'] : null;
            $query = "SELECT id, email, first_name, last_name, role, specialty, phone FROM users";
            
            if ($role) {
                $query .= " WHERE role = :role";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':role', $role);
            } else {
                $stmt = $this->db->prepare($query);
            }
            
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $users]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve users: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get a specific user by ID
     */
    public function get($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, first_name, last_name, date_of_birth, phone, 
                       address, role, specialty, license_number, created_at 
                FROM users 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            echo json_encode(['success' => true, 'data' => $user]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to retrieve user: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Create a new user
     */
    public function create() {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['email']) || !isset($data['password']) || 
                !isset($data['first_name']) || !isset($data['last_name']) || 
                !isset($data['role'])) {
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
            
            // Prepare insert query
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    email, password, first_name, last_name, date_of_birth, 
                    phone, address, role, specialty, license_number
                ) VALUES (
                    :email, :password, :first_name, :last_name, :date_of_birth, 
                    :phone, :address, :role, :specialty, :license_number
                )
            ");
            
            // Bind parameters
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            $stmt->bindParam(':date_of_birth', $data['date_of_birth'] ?? null);
            $stmt->bindParam(':phone', $data['phone'] ?? null);
            $stmt->bindParam(':address', $data['address'] ?? null);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':specialty', $data['specialty'] ?? null);
            $stmt->bindParam(':license_number', $data['license_number'] ?? null);
            
            $stmt->execute();
            $userId = $this->db->lastInsertId();
            
            // Return success response
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'User created successfully',
                'data' => ['id' => $userId]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update an existing user
     */
    public function update($id) {
        try {
            // Get request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            // Build update query
            $query = "UPDATE users SET ";
            $params = [];
            
            // Add fields to update
            if (isset($data['first_name'])) {
                $query .= "first_name = :first_name, ";
                $params[':first_name'] = $data['first_name'];
            }
            
            if (isset($data['last_name'])) {
                $query .= "last_name = :last_name, ";
                $params[':last_name'] = $data['last_name'];
            }
            
            if (isset($data['date_of_birth'])) {
                $query .= "date_of_birth = :date_of_birth, ";
                $params[':date_of_birth'] = $data['date_of_birth'];
            }
            
            if (isset($data['phone'])) {
                $query .= "phone = :phone, ";
                $params[':phone'] = $data['phone'];
            }
            
            if (isset($data['address'])) {
                $query .= "address = :address, ";
                $params[':address'] = $data['address'];
            }
            
            if (isset($data['specialty'])) {
                $query .= "specialty = :specialty, ";
                $params[':specialty'] = $data['specialty'];
            }
            
            if (isset($data['license_number'])) {
                $query .= "license_number = :license_number, ";
                $params[':license_number'] = $data['license_number'];
            }
            
            if (isset($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $query .= "password = :password, ";
                $params[':password'] = $hashedPassword;
            }
            
            // Remove trailing comma and space
            $query = rtrim($query, ", ");
            
            // Add WHERE clause
            $query .= " WHERE id = :id";
            $params[':id'] = $id;
            
            // Execute update if there are fields to update
            if (count($params) > 1) { // More than just the ID parameter
                $stmt = $this->db->prepare($query);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'User updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'No fields to update'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete a user
     */
    public function delete($id) {
        try {
            // Check if user exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            // Delete user
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true, 
                'message' => 'User deleted successfully'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
        }
    }
}

<?php
class User {
    private $conn;
    private $table_name = "users";
    
    // User properties
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $password;
    public $emergency_contact_name;
    public $emergency_contact_phone;
    public $is_admin;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        try {
            error_log("Creating user with email: " . $this->email);
            
            $query = "INSERT INTO " . $this->table_name . "
                    (username, first_name, last_name, email, phone, password, 
                     emergency_contact_name, emergency_contact_phone)
                    VALUES 
                    (:username, :first_name, :last_name, :email, :phone, :password,
                     :emergency_contact_name, :emergency_contact_phone)";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            // Sanitize input
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            
            // Bind values
            $stmt->bindParam(":username", $this->email); // Use email as username
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":emergency_contact_name", $this->emergency_contact_name);
            $stmt->bindParam(":emergency_contact_phone", $this->emergency_contact_phone);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                error_log("User created successfully with ID: " . $this->id);
                return true;
            }
            
            error_log("Failed to execute statement: " . print_r($stmt->errorInfo(), true));
            return false;
            
        } catch(PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function authenticate($password) {
        try {
            $query = "SELECT u.*, CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as is_admin
                    FROM " . $this->table_name . " u
                    LEFT JOIN admins a ON u.id = a.user_id
                    WHERE u.email = :email
                    LIMIT 0,1";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();
    
            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if(password_verify($password, $row['password'])) {
                    // Set user properties
                    $this->id = $row['id'];
                    $this->first_name = $row['first_name'];
                    $this->last_name = $row['last_name'];
                    $this->phone = $row['phone'];
                    $this->is_admin = (bool)$row['is_admin'];
                    $this->emergency_contact_name = $row['emergency_contact_name'];
                    $this->emergency_contact_phone = $row['emergency_contact_phone'];
                    return true;
                }
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function emailExists() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " 
                    WHERE email = :email 
                    LIMIT 0,1";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            $stmt->bindParam(":email", $this->email);
            $stmt->execute();
    
            return $stmt->rowCount() > 0;
            
        } catch(PDOException $e) {
            error_log("Email check error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET first_name = :first_name,
                        last_name = :last_name,
                        phone = :phone,
                        emergency_contact_name = :emergency_contact_name,
                        emergency_contact_phone = :emergency_contact_phone
                    WHERE id = :id";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            // Sanitize input
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            
            // Bind values
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":emergency_contact_name", $this->emergency_contact_name);
            $stmt->bindParam(":emergency_contact_phone", $this->emergency_contact_phone);
            $stmt->bindParam(":id", $this->id);
    
            if($stmt->execute()) {
                error_log("User updated successfully with ID: " . $this->id);
                return true;
            }
            
            error_log("Failed to execute statement: " . print_r($stmt->errorInfo(), true));
            return false;
            
        } catch(PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function updatePassword($new_password) {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET password = :password
                    WHERE id = :id";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":id", $this->id);
    
            if($stmt->execute()) {
                error_log("Password updated successfully for user with ID: " . $this->id);
                return true;
            }
            
            error_log("Failed to execute statement: " . print_r($stmt->errorInfo(), true));
            return false;
            
        } catch(PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT u.*, CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as is_admin
                    FROM " . $this->table_name . " u
                    LEFT JOIN admins a ON u.id = a.user_id
                    WHERE u.id = :id
                    LIMIT 0,1";
    
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement: " . print_r($this->conn->errorInfo(), true));
                return false;
            }
            
            $stmt->bindParam(":id", $id);
            $stmt->execute();
    
            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $row['id'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->is_admin = (bool)$row['is_admin'];
                $this->emergency_contact_name = $row['emergency_contact_name'];
                $this->emergency_contact_phone = $row['emergency_contact_phone'];
                $this->created_at = $row['created_at'];
                return true;
            }
            return false;
            
        } catch(PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
}
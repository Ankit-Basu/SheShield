<?php
class Incident {
    private $conn;
    private $table_name = "incidents";

    public $id;
    public $user_id;
    public $type;
    public $description;
    public $location;
    public $incident_date;
    public $is_anonymous;
    public $evidence_files;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new incident report
    public function create() {
        try {
            // Validate input
            $this->validateInput();

            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        user_id = :user_id,
                        incident_type = :type,
                        description = :description,
                        location = :location,
                        date_time = :incident_date,
                        evidence_file = :evidence_files,
                        status = :status,
                        created_at = :created_at";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->sanitizeInput();

            // Bind values
            $createdAt = date('Y-m-d H:i:s');
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":type", $this->type);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":location", $this->location);
            $stmt->bindParam(":incident_date", $this->incident_date);
            $stmt->bindParam(":evidence_files", $this->evidence_files);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":created_at", $createdAt);

            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Create incident error: " . $e->getMessage());
            throw new Exception("Failed to create incident report");
        }
    }

    // Get all incidents for a user with pagination
    public function getUserIncidents($user_id, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $query = "SELECT id, user_id, incident_type AS type, description, location,
                        date_time AS incident_date, 0 AS is_anonymous,
                        COALESCE(evidence_file, '') AS evidence_files, status, created_at
                    FROM " . $this->table_name . " 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Get user incidents error: " . $e->getMessage());
            throw new Exception("Failed to retrieve incidents");
        }
    }

    // Get all incidents with pagination and filtering
    public function getAllIncidents($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereClause = "";
            $params = [];

            if (!empty($filters)) {
                $conditions = [];
                if (!empty($filters['status'])) {
                    $conditions[] = "i.status = :status";
                    $params[':status'] = $filters['status'];
                }
                if (!empty($filters['type'])) {
                    $conditions[] = "i.incident_type = :type";
                    $params[':type'] = $filters['type'];
                }
                if (!empty($filters['date_from'])) {
                    $conditions[] = "i.date_time >= :date_from";
                    $params[':date_from'] = $filters['date_from'];
                }
                if (!empty($filters['date_to'])) {
                    $conditions[] = "i.date_time <= :date_to";
                    $params[':date_to'] = $filters['date_to'];
                }
                if (!empty($conditions)) {
                    $whereClause = "WHERE " . implode(" AND ", $conditions);
                }
            }

            $query = "SELECT 
                        i.*,
                        i.incident_type AS type,
                        i.date_time AS incident_date,
                        0 AS is_anonymous,
                        COALESCE(i.evidence_file, '') AS evidence_files,
                        CASE 
                            WHEN i.user_id IS NULL THEN 'Anonymous'
                            ELSE TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))
                        END as reporter_name
                    FROM " . $this->table_name . " i
                    LEFT JOIN users u ON i.user_id = u.id
                    $whereClause
                    ORDER BY i.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Get all incidents error: " . $e->getMessage());
            throw new Exception("Failed to retrieve incidents");
        }
    }

    // Update incident status with validation
    public function updateStatus($id, $status) {
        try {
            // Validate status
            $validStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status value");
            }

            $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update status error: " . $e->getMessage());
            throw new Exception("Failed to update incident status");
        }
    }

    // Handle file upload with improved security
    public function uploadEvidence($files) {
        try {
            $uploadDir = '../uploads/evidence/';
            $uploadedFiles = [];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            foreach($files['tmp_name'] as $key => $tmp_name) {
                $file_name = $files['name'][$key];
                $file_size = $files['size'][$key];
                $file_tmp = $files['tmp_name'][$key];
                $file_type = $files['type'][$key];
                
                // Validate file type and size
                if (!in_array($file_type, $allowedTypes)) {
                    throw new Exception("Invalid file type: " . $file_type);
                }
                if ($file_size > $maxFileSize) {
                    throw new Exception("File too large: " . $file_name);
                }
                
                // Generate unique filename with timestamp
                $file_name = time() . '_' . uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                
                // Ensure upload directory exists and is writable
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    throw new Exception("Upload directory is not writable");
                }
                
                // Move uploaded file
                if(move_uploaded_file($file_tmp, $uploadDir . $file_name)) {
                    $uploadedFiles[] = $file_name;
                } else {
                    throw new Exception("Failed to upload file: " . $file_name);
                }
            }
            
            return implode(',', $uploadedFiles);
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            throw new Exception("Failed to upload evidence files: " . $e->getMessage());
        }
    }

    private function validateInput() {
        if (empty($this->type)) {
            throw new Exception("Incident type is required");
        }
        if (empty($this->description)) {
            throw new Exception("Description is required");
        }
        if (empty($this->location)) {
            throw new Exception("Location is required");
        }
        if (empty($this->incident_date)) {
            throw new Exception("Incident date is required");
        }
        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $this->incident_date);
        if (!$date || $date->format('Y-m-d H:i:s') !== $this->incident_date) {
            throw new Exception("Invalid date format");
        }
        return true;
    }

    private function sanitizeInput() {
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->evidence_files = htmlspecialchars(strip_tags($this->evidence_files));
    }

    // Get incident details with additional security
    public function getById($id) {
        try {
            $query = "SELECT i.*,
                        i.incident_type AS type,
                        i.date_time AS incident_date,
                        0 AS is_anonymous,
                        COALESCE(i.evidence_file, '') AS evidence_files,
                        CASE 
                            WHEN i.user_id IS NULL THEN 'Anonymous'
                            ELSE TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))
                        END as reporter_name
                    FROM " . $this->table_name . " i
                    LEFT JOIN users u ON i.user_id = u.id
                    WHERE i.id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row) {
                foreach($row as $key => $value) {
                    if(property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Get incident error: " . $e->getMessage());
            throw new Exception("Failed to retrieve incident details");
        }
    }
}
?>

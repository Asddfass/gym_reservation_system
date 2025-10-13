<?php
include_once 'Database.php';

class Functions 
{
    protected $db;

    public function __construct() 
    {
        $this->db = new Database();
    }

    // Run a custom SQL query
    public function query($sql, $params = [], $types = "") 
    {
        $stmt = $this->db->conn->prepare($sql);
        if ($params && $types) 
        {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    // Fetch one row
    public function fetchOne($sql, $params = [], $types = "") 
    {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Fetch all rows
    public function fetchAll($sql, $params = [], $types = "") 
    {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Insert / Update / Delete
    public function execute($sql, $params = [], $types = "") 
    {
        $stmt = $this->query($sql, $params, $types);
        return $stmt->affected_rows > 0;
    }

    // Check login credentials
    public function checkLogin($email, $password) 
    {
        $sql = "SELECT * FROM user WHERE email = ?";
        $user = $this->fetchOne($sql, [$email], "s");

        if (!$user) return false;

        // Check both plain and hashed passwords
        if ($user['password'] === $password || password_verify($password, $user['password'])) 
        {
            return $user;
        }

        return false;
    }

    // Fetch all facilities
    public function getFacilities($status = null)
    {
        $sql = "SELECT * FROM facility";
        $params = [];
        $types = "";

        if ($status) 
        {
            $sql .= " WHERE availability_status = ?";
            $params[] = $status;
            $types = "s";
        }

        $sql .= " ORDER BY name ASC";
        return $this->fetchAll($sql, $params, $types);
    }

    // Fetch all reservations (with joined info)
    public function getReservations()
    {
        $sql = "SELECT r.reservation_id AS id,
                u.name AS user_name,
                f.name AS facility_name,
                r.date,
                r.start_time,
                r.end_time,
                r.status
                FROM reservation r
                JOIN user u ON r.user_id = u.id
                JOIN facility f ON r.facility_id = f.id
                ORDER BY r.date DESC";

        return $this->fetchAll($sql);
    }

    


}
?>

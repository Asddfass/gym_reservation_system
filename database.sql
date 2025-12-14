-- Create database
CREATE DATABASE IF NOT EXISTS gym_reservation;
USE gym_reservation;

-- Create USER table
CREATE TABLE user (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','faculty','guest','admin') DEFAULT 'student',
  CONSTRAINT uc_user_email UNIQUE (email),
  INDEX idx_user_role (role),
  INDEX idx_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create FACILITY table
CREATE TABLE facility (
  facility_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  capacity INT NOT NULL,
  availability_status VARCHAR(50) DEFAULT 'available',
  CONSTRAINT chk_capacity CHECK (capacity > 0),
  CONSTRAINT chk_status CHECK (availability_status IN ('available', 'unavailable')),
  INDEX idx_facility_name (name),
  INDEX idx_facility_status (availability_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create RESERVATION table
CREATE TABLE reservation (
  reservation_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  facility_id INT NOT NULL,
  date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  status ENUM('pending','approved','denied','cancelled') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_reservation_user 
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
  
  CONSTRAINT fk_reservation_facility 
    FOREIGN KEY (facility_id) REFERENCES facility(facility_id) ON DELETE CASCADE,
  
  CONSTRAINT chk_time_validity CHECK (start_time < end_time),
  
  CONSTRAINT chk_start_time CHECK (start_time >= '06:00:00' AND start_time <= '16:00:00'),
  
  CONSTRAINT chk_end_time CHECK (end_time >= '06:00:00' AND end_time <= '20:00:00'),
  
  INDEX idx_reservation_user (user_id),
  INDEX idx_reservation_facility (facility_id),
  INDEX idx_reservation_date (date),
  INDEX idx_reservation_status (status),
  INDEX idx_reservation_facility_date (facility_id, date),
  INDEX idx_reservation_facility_date_status (facility_id, date, status)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create NOTIFICATION table
CREATE TABLE notification (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('success','error','warning','info') DEFAULT 'info',
  link VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_notification_user 
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
  
  CONSTRAINT chk_is_read CHECK (is_read IN (0, 1)),
  
  INDEX idx_notification_user (user_id),
  INDEX idx_notification_is_read (is_read),
  INDEX idx_notification_created (created_at),
  INDEX idx_notification_user_read (user_id, is_read)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default administrator account
INSERT INTO user (name, email, password, role) VALUES (
  'Administrator',
  'admin@gym.com',
  SHA2('admin123', 256),
  'admin'
);

-- Insert sample data for testing
INSERT INTO facility (name, capacity, availability_status) VALUES 
('Main Gymnasium', 500, 'available'),
('Volleyball Court', 100, 'available'),
('Basketball Court A', 200, 'available'),
('Badminton Court', 50, 'available'),
('Tennis Court', 4, 'available');

INSERT INTO user (name, email, password, role) VALUES 
('John Doe', 'john@example.com', SHA2('password123', 256), 'faculty'),
('Jane Smith', 'jane@example.com', SHA2('password123', 256), 'student'),
('Mark Johnson', 'mark@example.com', SHA2('password123', 256), 'guest');
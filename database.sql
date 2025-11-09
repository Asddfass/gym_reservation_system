CREATE DATABASE gym_reservation;

USE gym_reservation;

CREATE TABLE user (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  role ENUM('student','faculty','guest','admin') DEFAULT 'student'
);

CREATE TABLE facility (
  facility_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  capacity INT,
  availability_status VARCHAR(50)
);

CREATE TABLE reservation (
  reservation_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  facility_id INT,
  date DATE,
  start_time TIME,
  end_time TIME,
  purpose VARCHAR(255),
  status ENUM('pending','approved','denied','cancelled') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(user_id),
  FOREIGN KEY (facility_id) REFERENCES facility(facility_id)
);

INSERT INTO user (name, email, password, role)
VALUES (
  'Administrator',
  'admin@gym.com',
  'admin123',
  'admin'
);

INSERT INTO user (name, email, password, role)
VALUES (
  'John Doe',
  'john@example.com',
  'john123',
  'guest'
);
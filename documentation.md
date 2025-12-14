---
title: "Gym Reservation System"
subtitle: "System Documentation"
author: "Development Team"
date: "December 2025"
---

# Gym Reservation System Documentation

---

## 1. Project Context

The **Gym Reservation System** is a web-based application designed to streamline the process of reserving gym facilities within an educational institution. The system addresses the challenges of manual facility booking, schedule conflicts, and lack of real-time availability information that often plague traditional reservation methods.

In many institutions, gym facilities such as gymnasiums, courts, and sports areas are shared resources that require proper scheduling and management. Without a centralized system, users face difficulties in:

- Knowing which facilities are available at specific times
- Avoiding double bookings and scheduling conflicts  
- Tracking the status of their reservation requests
- Receiving timely notifications about approval or denial of requests

This system provides a digital solution that allows students, faculty, and guests to browse available facilities, submit reservation requests, and track their booking status in real-time. Administrators can efficiently manage all reservations, approve or deny requests, and maintain oversight of facility usage through comprehensive analytics and reporting tools.

---

## 2. General Objective

To develop a comprehensive web-based Gym Reservation System that enables efficient management and booking of gym facilities, providing users with an intuitive interface for making reservations while giving administrators complete control over facility scheduling and approval workflows.

---

## 3. Specific Objectives

1. **User Authentication and Authorization**
   - Implement secure login functionality with role-based access control (Student, Faculty, Guest, Admin)
   - Ensure data privacy through password encryption using SHA-256 hashing

2. **Facility Management**
   - Allow administrators to add, edit, and manage gym facilities
   - Track facility capacity and availability status
   - Provide real-time availability information to users

3. **Reservation Management**
   - Enable users to submit reservation requests with date, time, and purpose
   - Implement time slot validation to prevent scheduling conflicts
   - Support reservation status workflow (Pending → Approved/Denied/Cancelled)

4. **Administrative Dashboard**
   - Provide comprehensive system overview with key metrics
   - Display reservation trends and analytics through visual charts
   - Enable quick approval/denial of pending reservations

5. **Notification System**
   - Send real-time notifications for reservation status updates
   - Implement email notifications for important events
   - Allow users to manage and track their notifications

6. **Reporting and Analytics**
   - Generate reservation reports with customizable filters
   - Visualize facility usage patterns and trends
   - Support report printing for documentation purposes

7. **Calendar Integration**
   - Display reservations in an interactive calendar view
   - Support monthly, weekly, and daily calendar perspectives
   - Enable quick navigation to specific reservations

---

## 4. Scope and Limitations

### Scope

The Gym Reservation System includes the following functionalities:

**User Management:**
- User registration and login with role-based permissions
- Profile management and password updates
- Support for four user roles: Student, Faculty, Guest, and Admin

**Facility Management:**
- CRUD operations for gym facilities
- Capacity and availability tracking
- Facility status management (Available/Unavailable)

**Reservation System:**
- Online reservation submission with date and time selection
- Operating hours enforcement (6:00 AM - 8:00 PM)
- Conflict detection to prevent double bookings
- Status management with approval workflow

**Administrative Features:**
- Centralized dashboard with statistics and charts
- Reservation approval/denial with notifications
- Comprehensive reporting with filtering options
- Interactive calendar view for schedule overview

**Communication:**
- In-app notification system
- Email notifications for reservation updates
- Welcome emails for new account creation

### Limitations

1. **Single Facility Booking:** Users can only reserve one facility per reservation request. Group bookings across multiple facilities require separate reservations.

2. **Fixed Operating Hours:** The system enforces fixed operating hours (6:00 AM - 8:00 PM) and does not support 24-hour facility access.

3. **No Payment Integration:** The system does not include payment processing for facility rental fees. Financial transactions must be handled separately.

4. **No Mobile Application:** The system is web-based only and does not have a dedicated mobile application, though it is responsive for mobile browsers.

5. **Single Institution:** The system is designed for a single institution and does not support multi-tenant deployment.

6. **No Equipment Booking:** The system focuses on facility reservation and does not manage equipment or inventory booking.

7. **Internet Dependency:** The system requires an active internet connection for all operations.

---

## 5. System Features

### 5.1 Login Page

The login page provides secure authentication for all users. Users enter their email address and password to access the system. The interface features:

- Email and password input fields with validation
- "Forgot Password" link for password recovery
- Responsive design with institutional branding
- Error messaging for invalid credentials

### 5.2 User Dashboard

The user dashboard provides an overview of the user's reservation activity:

- **Statistics Cards:** Display total reservations, approved, pending, and denied counts
- **Recent Reservations:** Table showing the latest booking activities
- **Available Facilities:** Quick view of facilities that can be reserved
- Clean, intuitive interface with easy navigation to other modules

### 5.3 Reserve Facility (User)

The reservation form allows users to book gym facilities:

- **Facility Selection:** Dropdown of available facilities with descriptions
- **Date Picker:** Calendar-based date selection with validation
- **Time Selection:** Start and end time selection within operating hours (6 AM - 8 PM)
- **Purpose Field:** Text area to describe the intended use of the facility
- **Conflict Detection:** Real-time validation to prevent double bookings
- Confirmation messages upon successful submission

### 5.4 My Reservations

Users can view and manage their reservation history:

- **Filter Options:** Search by facility, date range, and status
- **Reservation Table:** Displays facility name, date, time, purpose, and status
- **Status Badges:** Color-coded indicators (Pending-Yellow, Approved-Green, Denied-Red, Cancelled-Gray)
- **Cancel Option:** Users can cancel pending reservations
- Statistics summary at the top showing reservation counts by status

### 5.5 User Notifications

The notification center keeps users informed:

- **Notification Cards:** Display title, message, and timestamp
- **Category Icons:** Visual indicators for success, error, warning, and info notifications
- **Filter Tabs:** Quick filtering by All, Unread, Success, Errors, and Warnings
- **Mark as Read:** Individual and bulk read options
- **Statistics:** Count of total, unread, and read notifications

### 5.6 User Profile

Profile management page for personal information:

- **Personal Information Form:** Update name and email
- **Password Change:** Secure password update with confirmation
- **Form Validation:** Real-time input validation
- Success/error messaging for updates

### 5.7 Admin Dashboard (System Overview)

The administrative dashboard provides comprehensive system insights:

- **Statistics Cards:** Active users, facilities, total reservations, pending approvals
- **Reservation Trends Chart:** Line graph showing bookings over the last 7 days
- **Status Distribution:** Pie chart displaying reservation status breakdown
- **Top Facility Usage:** Bar chart showing most popular facilities
- **Recent Reservations Table:** Latest bookings with quick status view

### 5.8 Manage Reservations (Admin)

Central hub for reservation administration:

- **Advanced Filters:** Filter by user, facility, date range, and status
- **Bulk Actions:** Approve or deny multiple reservations at once
- **Action Buttons:** Individual approve, deny, or cancel options per reservation
- **Email Notifications:** Automatic email sent upon status change
- **Statistics Summary:** Quick view of pending, approved, denied counts
- Detailed table with all reservation information

### 5.9 Manage Facilities (Admin)

Facility management interface:

- **Add Facility Form:** Create new facilities with name, capacity, and status
- **Facilities Table:** View all facilities with their details
- **Edit Modal:** Update facility information inline
- **Delete Option:** Remove facilities with confirmation
- **Status Toggle:** Mark facilities as available or unavailable

### 5.10 Manage Accounts (Admin)

User account administration:

- **Add Account Form:** Create new users with name, email, password, and role
- **Role Assignment:** Dropdown selection for Student, Faculty, Guest, or Admin
- **Accounts Table:** View all system users
- **Inline Editing:** Edit user details and change passwords
- **Delete Protection:** Cannot delete currently logged-in admin account
- **Welcome Email:** Automatic email sent to new users

### 5.11 Calendar View (Admin)

Visual calendar interface for reservation overview:

- **Multiple Views:** Month, Week, and Day calendar perspectives
- **Color-Coded Events:** Status-based coloring (Pending-Yellow, Approved-Green, Denied-Red)
- **Event Details Modal:** Click events to view full reservation details
- **Time Slot Display:** Week/Day views show 6 AM - 8 PM operating hours
- **Quick Navigation:** Direct link to manage specific reservations
- **Legend:** Status color guide for easy interpretation

### 5.12 Reports & Analytics (Admin)

Comprehensive reporting module:

- **Date Range Filters:** Generate reports for specific periods
- **Status Filters:** Filter by reservation status
- **Facility Filters:** Focus on specific facilities
- **Summary Statistics:** Total reservations, hourly, daily averages
- **Detailed Table:** Exportable reservation data
- **Print Function:** Print-friendly report layout

### 5.13 Admin Notifications

Administrative notification center:

- **System Notifications:** Alerts for new reservations, status changes
- **Statistics Dashboard:** Visual count of notification categories
- **Filter Tabs:** Quick access to specific notification types
- **Action Links:** Direct navigation to related pages
- **Mark All Read:** Bulk read functionality

### 5.14 Admin Reserve (Admin Quick Booking)

Administrators can make reservations on behalf of users:

- **User Selection:** Dropdown to select the user for the booking
- **Same Reservation Form:** Facility, date, time, and purpose fields
- **Immediate Approval:** Admin reservations can bypass the pending status
- Useful for phone or in-person booking requests

---

## 6. System Analysis and Design

### 6.1 Entity-Relationship (ER) Model

The system is built on a relational database with four main entities:

```
┌─────────────────┐         ┌─────────────────────┐
│      USER       │         │      FACILITY       │
├─────────────────┤         ├─────────────────────┤
│ PK user_id      │         │ PK facility_id      │
│    name         │         │    name             │
│    email        │         │    capacity         │
│    password     │         │    availability_    │
│    role         │         │    status           │
└────────┬────────┘         └──────────┬──────────┘
         │                             │
         │ 1                           │ 1
         │                             │
         ▼ N                           ▼ N
┌─────────────────────────────────────────────────┐
│                  RESERVATION                     │
├─────────────────────────────────────────────────┤
│ PK reservation_id                                │
│ FK user_id                                       │
│ FK facility_id                                   │
│    date                                          │
│    start_time                                    │
│    end_time                                      │
│    purpose                                       │
│    status                                        │
│    created_at                                    │
└─────────────────────────────────────────────────┘
         │
         │ 1
         ▼ N
┌─────────────────────────────────────────────────┐
│                  NOTIFICATION                    │
├─────────────────────────────────────────────────┤
│ PK notification_id                               │
│ FK user_id                                       │
│    title                                         │
│    message                                       │
│    type                                          │
│    link                                          │
│    is_read                                       │
│    created_at                                    │
└─────────────────────────────────────────────────┘
```

**Relationships:**
- **USER to RESERVATION:** One-to-Many (A user can have multiple reservations)
- **FACILITY to RESERVATION:** One-to-Many (A facility can have multiple reservations)
- **USER to NOTIFICATION:** One-to-Many (A user can have multiple notifications)

### 6.2 Logical Data Model

#### USER Entity
| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| user_id | Integer | Primary Key, Auto Increment | Unique user identifier |
| name | String(100) | Not Null | User's full name |
| email | String(100) | Unique, Not Null | User's email address |
| password | String(255) | Not Null | Hashed password (SHA-256) |
| role | Enum | Default 'student' | User role (student/faculty/guest/admin) |

#### FACILITY Entity
| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| facility_id | Integer | Primary Key, Auto Increment | Unique facility identifier |
| name | String(100) | Not Null | Facility name |
| capacity | Integer | Not Null, > 0 | Maximum capacity |
| availability_status | String(50) | Default 'available' | Current availability |

#### RESERVATION Entity
| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| reservation_id | Integer | Primary Key, Auto Increment | Unique reservation identifier |
| user_id | Integer | Foreign Key | Reference to user |
| facility_id | Integer | Foreign Key | Reference to facility |
| date | Date | Not Null | Reservation date |
| start_time | Time | Not Null, >= 06:00 | Start time |
| end_time | Time | Not Null, <= 20:00 | End time |
| purpose | String(255) | Not Null | Purpose of reservation |
| status | Enum | Default 'pending' | Status (pending/approved/denied/cancelled) |
| created_at | DateTime | Default Current Timestamp | Creation timestamp |

#### NOTIFICATION Entity
| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| notification_id | Integer | Primary Key, Auto Increment | Unique notification identifier |
| user_id | Integer | Foreign Key | Reference to user |
| title | String(255) | Not Null | Notification title |
| message | Text | Not Null | Notification content |
| type | Enum | Default 'info' | Type (success/error/warning/info) |
| link | String(255) | Nullable | Related page link |
| is_read | Boolean | Default 0 | Read status |
| created_at | DateTime | Default Current Timestamp | Creation timestamp |

### 6.3 Physical Database (SQL Script)

```sql
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

-- Insert sample facilities
INSERT INTO facility (name, capacity, availability_status) VALUES 
('Main Gymnasium', 500, 'available'),
('Volleyball Court', 100, 'available'),
('Basketball Court A', 200, 'available'),
('Badminton Court', 50, 'available'),
('Tennis Court', 4, 'available');
```

---

## 7. Technologies Used

| Category | Technology |
|----------|------------|
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 5 |
| **Backend** | PHP 8.x |
| **Database** | MySQL / MariaDB |
| **Server** | Apache (XAMPP) |
| **Libraries** | Chart.js, FullCalendar, PHPMailer, Bootstrap Icons |
| **Development** | Visual Studio Code, Git |

---

## 8. Default Credentials

**Administrator Account:**
- Email: admin@gym.com
- Password: admin123

---

*Document generated for system defense presentation*

*Gym Reservation System © 2025*

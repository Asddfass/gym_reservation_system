<?php

class Config 
{
    // === ADMIN EMAIL CONFIGURATION ===
    // This is the email that will receive all admin notifications
    const ADMIN_EMAIL = 'alihassansali@gmail.com';
    const ADMIN_NAME = 'Gymnasium Administrator';
    
    // === SMTP EMAIL CONFIGURATION ===
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_USERNAME = 'alihassansali@gmail.com';
    const SMTP_PASSWORD = 'jpjbfruifpqifjbc';
    const SMTP_PORT = 587;
    const SMTP_FROM_EMAIL = 'alihassansali@gmail.com';
    const SMTP_FROM_NAME = 'Gymnasium Reservation System';
    
    // === DATABASE CONFIGURATION (Optional) ===
    const DB_HOST = 'localhost';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_NAME = 'gym_reservation';
    
    // === SYSTEM SETTINGS ===
    const SYSTEM_NAME = 'Gymnasium Reservation System';
    const SYSTEM_VERSION = '1.0.0';
}
?>
<?php
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mail;
    
    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com'; // Change to your SMTP host
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'alihassansali@gmail.com'; // Your email
            $this->mail->Password   = 'jpjbfruifpqifjbc'; // Your app password
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            
            // Sender info
            $this->mail->setFrom('alihassansali@gmail.com', 'Gymnasium Reservation System');
            $this->mail->isHTML(true);
        } catch (Exception $e) {
            error_log("Mailer Error: {$this->mail->ErrorInfo}");
        }
    }
    
    public function sendReservationEmail($to, $name, $reservationDetails, $status)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $name);
            
            $statusText = ucfirst($status);
            $statusColor = match($status) {
                'approved' => '#28a745',
                'denied' => '#dc3545',
                'cancelled' => '#6c757d',
                default => '#ffc107'
            };
            
            $this->mail->Subject = "Reservation {$statusText} - Gymnasium Reservation System";
            
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #a4161a, #dc143c); color: white; padding: 30px 20px; text-align: center; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .content { padding: 30px 20px; }
                    .status-badge { display: inline-block; padding: 8px 16px; background: {$statusColor}; color: white; border-radius: 4px; font-weight: bold; margin: 10px 0; }
                    .details { background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0; }
                    .detail-row { padding: 8px 0; border-bottom: 1px solid #dee2e6; }
                    .detail-row:last-child { border-bottom: none; }
                    .detail-label { font-weight: bold; color: #495057; }
                    .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
                    .button { display: inline-block; padding: 12px 24px; background: #dc143c; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🏋️ Gymnasium Reservation System</h1>
                    </div>
                    <div class='content'>
                        <p>Hello <strong>{$name}</strong>,</p>
                        <p>Your reservation has been <span class='status-badge'>{$statusText}</span></p>
                        
                        <div class='details'>
                            <div class='detail-row'>
                                <span class='detail-label'>Facility:</span> {$reservationDetails['facility']}
                            </div>
                            <div class='detail-row'>
                                <span class='detail-label'>Date:</span> {$reservationDetails['date']}
                            </div>
                            <div class='detail-row'>
                                <span class='detail-label'>Time:</span> {$reservationDetails['start_time']} - {$reservationDetails['end_time']}
                            </div>
                            <div class='detail-row'>
                                <span class='detail-label'>Purpose:</span> {$reservationDetails['purpose']}
                            </div>
                        </div>
                        
                        <p>Thank you for using our reservation system!</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 Gymnasium Reservation System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mail->Body = $body;
            $this->mail->AltBody = "Hello {$name}, Your reservation has been {$statusText}. Facility: {$reservationDetails['facility']}, Date: {$reservationDetails['date']}, Time: {$reservationDetails['start_time']} - {$reservationDetails['end_time']}";
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
    
    public function sendWelcomeEmail($to, $name, $role)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $name);
            
            $this->mail->Subject = "Welcome to Gymnasium Reservation System";
            
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #a4161a, #dc143c); color: white; padding: 30px 20px; text-align: center; }
                    .header h1 { margin: 0; font-size: 24px; }
                    .content { padding: 30px 20px; }
                    .welcome-box { background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0; text-align: center; }
                    .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🏋️ Welcome to Gymnasium Reservation System</h1>
                    </div>
                    <div class='content'>
                        <div class='welcome-box'>
                            <h2>Hello {$name}! 👋</h2>
                            <p>Your account has been successfully created.</p>
                            <p><strong>Role:</strong> " . ucfirst($role) . "</p>
                        </div>
                        <p>You can now log in and start making reservations for our gymnasium facilities.</p>
                        <p>If you have any questions, please contact the administrator.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 Gymnasium Reservation System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mail->Body = $body;
            $this->mail->AltBody = "Welcome {$name}! Your account has been created with the role: " . ucfirst($role);
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}
?>
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

class Mailer {
    public static function sendOtpEmail($toEmail, $otp) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mindconnect.helper@gmail.com';   
            $mail->Password   = 'igsp pdzl sbzn wdnb';        
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('mindconnect.helper@gmail.com', 'mindconnect');
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = 'MindConnect - Your OTP Code';

            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color:#333;'>
                <p>Dear User,</p>
                <p>We received a request to verify your identity on <b>MindConnect</b>. Please use the one-time password (OTP) below to proceed:</p>
                
                <div style='font-size:20px; font-weight:bold; color:#2c3e50; 
                            border:1px solid #ddd; padding:10px; 
                            width:fit-content; margin:20px auto; text-align:center;'>
                $otp
                </div>
                
                <p>This code is valid for <b>10 minutes</b>. If you did not request this, please ignore this email.</p>
                
                <p style='margin-top:30px;'>Best regards,<br>
                The MindConnect Team</p>
            </body>
            </html>";
            $mail->isHTML(true);


            $mail->send();
            return true;
        } catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
    return false;
}

    }
}

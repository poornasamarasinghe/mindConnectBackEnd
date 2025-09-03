<?php
require_once __DIR__ . '/../database/dbconnection.php';
require_once __DIR__ . '/../utilities/Mailer.php';

class PasswordResetService
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new DbConnection())->getConnection(); // mysqli connection
    }

    private function generateOtp()
    {
        return rand(100000, 999999); // 6-digit OTP
    }

    public function createOtp($email)
    {
        try {
            // check if email exists
            $stmt = $this->conn->prepare("SELECT user_id FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user) {
                return ["success" => false, "message" => "Email not found"];
            }

            // generate OTP
            $otp = $this->generateOtp();
            $otpHash = password_hash($otp, PASSWORD_ARGON2ID);
            date_default_timezone_set("Asia/Colombo");
            $expireAt = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            // insert hashed OTP into DB
            $insert = $this->conn->prepare("
                INSERT INTO password_resets (user_id, OTP, ExpireAt) 
                VALUES (?, ?, ?)
            ");
            $insert->bind_param("iss", $user['user_id'], $otpHash, $expireAt);
            $insert->execute();
            $insert->close();

            // send plain OTP via email
            if (!Mailer::sendOtpEmail($email, $otp)) {
                return ["success" => false, "message" => "Failed to send OTP"];
            }

            return ["success" => true, "message" => "OTP sent"];
        } catch (\Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function verifyOtp($email, $otp)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT pr.OTP, pr.ExpireAt 
                FROM password_resets pr
                JOIN user u ON pr.user_id = u.user_id
                WHERE u.email = ?
                ORDER BY pr.reset_id DESC 
                LIMIT 1
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            $stmt->close();

            if (!$record) {
                return ["success" => false, "message" => "No OTP request found"];
            }

            // verify OTP
            if (!password_verify($otp, $record['OTP'])) {
                return ["success" => false, "message" => "Invalid OTP"];
            }

            if (strtotime($record['ExpireAt']) < time()) {
                return ["success" => false, "message" => "OTP expired"];
            }

            return ["success" => true, "message" => "OTP verified"];
        } catch (\Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function resetPassword($email, $newPassword)
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // update password
            $stmt = $this->conn->prepare("UPDATE user SET password_hash = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $email);
            $stmt->execute();
            $stmt->close();

            // delete ALL OTPs for this user
            $delete = $this->conn->prepare("
                DELETE pr 
                FROM password_resets pr
                INNER JOIN user u ON pr.user_id = u.user_id
                WHERE u.email = ?
            ");
            $delete->bind_param("s", $email);
            $delete->execute();
            $delete->close();

            return ["success" => true, "message" => "Password updated and OTPs cleared"];
        } catch (\Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}

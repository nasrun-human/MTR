<?php
// ตั้งค่าให้ไฟล์นี้คืนค่ากลับไปเป็น JSON ให้ JavaScript ฝั่งหน้าเว็บอ่านได้
header('Content-Type: application/json; charset=utf-8');

// เรียกใช้ PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ดึงไฟล์ PHPMailer เข้ามาทำงาน
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// ตรวจสอบว่ามีการส่งฟอร์มมาจริงๆ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าจากฟอร์มสมัครงาน
    $name       = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email      = htmlspecialchars(trim($_POST['email'] ?? ''));
    $phone      = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $position   = htmlspecialchars(trim($_POST['position'] ?? ''));
    $experience = htmlspecialchars(trim($_POST['experience'] ?? ''));

    // ตรวจสอบว่ากรอกอีเมลมาหรือไม่
    if (empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // =====================================================================
        // ตั้งค่าเซิร์ฟเวอร์อีเมล (SMTP)
        // =====================================================================
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';            
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@mtr.co.th';         
        $mail->Password   = 'smsfvqvkmlswwohl';   
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; // หากใช้ ENCRYPTION_SMTPS มักจะเป็น 465 (ถ้าใช้ 587 ให้เปลี่ยนเป็น ENCRYPTION_STARTTLS)
        $mail->CharSet    = 'UTF-8';                     

        // =====================================================================
        // Step 1: ส่งอีเมลแจ้งเตือนหา "HR / Admin"
        // =====================================================================
        $mail->setFrom('noreply@mtr.co.th', 'MTR Careers');
        $mail->addAddress('info@mtr.co.th', 'MTR HR Department'); // เปลี่ยนอีเมลเป็นของ HR ได้
        $mail->addReplyTo($email, $name); 

        // จัดการไฟล์แนบ (Resume)
        $resume_status = "No file attached";
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
            $uploadfile = $_FILES['resume']['tmp_name'];
            $filename = $_FILES['resume']['name'];
            
            // แนบไฟล์เข้าอีเมล
            $mail->addAttachment($uploadfile, $filename);
            $resume_status = "Yes (Attached in this email)";
        }

        $mail->isHTML(true);
        $mail->Subject = 'New Job Application: ' . $position . ' - ' . $name;
        
        // เนื้อหาอีเมลสำหรับ HR
        $adminBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2 style='color: #a48135;'>New Job Application Received</h2>
            <p><strong>Applicant Name:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Phone:</strong> {$phone}</p>
            <p><strong>Position Applied:</strong> {$position}</p>
            <hr>
            <p><strong>Experience / Background:</strong></p>
            <p>" . nl2br($experience) . "</p>
            <hr>
            <p><strong>Resume:</strong> {$resume_status}</p>
        </div>";
        
        $mail->Body = $adminBody;

        // สั่งส่งเมลหา HR
        $mail->send();


        // =====================================================================
        // Step 2: ส่งอีเมล Auto-Reply กลับไปหา "ผู้สมัคร (Applicant)"
        // =====================================================================
        // ล้างข้อมูลผู้รับและไฟล์แนบเดิมออกก่อน
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->clearAttachments(); // สำคัญ! ป้องกันการส่งไฟล์ Resume กลับไปหาผู้สมัครเอง

        // ตั้งค่าผู้รับใหม่เป็น "อีเมลของผู้สมัคร"
        $mail->addAddress($email, $name); 
        
        // เปลี่ยนหัวข้ออีเมล
        $mail->Subject = 'Application Received - MTR Asset Manager Co., Ltd.';
        
        // เนื้อหาอีเมลสำหรับผู้สมัคร (Auto-Reply)
        $customerBody = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2 style='color: #a48135;'>Thank you for your application!</h2>
            <p>Dear {$name},</p>
            <p>We have successfully received your application for the <strong>{$position}</strong> position.</p>
            <p>Our Human Resources team is currently reviewing your profile. If your qualifications match our requirements, we will contact you shortly to schedule an interview.</p>
            <br>
            <hr style='border: 0; border-top: 1px solid #eee;'>
            <p style='font-size: 12px; color: #777;'>
                Best Regards,<br>
                <strong>Human Resources Department</strong><br>
                <strong>M.T.R Asset Managers Co., Ltd.</strong><br>
                Tel: 02-381-8188<br>
                Website: <a href='https://www.mtr.co.th' style='color: #a48135;'>www.mtr.co.th</a>
            </p>
        </div>";
        
        $mail->Body = $customerBody;

        // สั่งส่งเมลหาผู้สมัคร
        $mail->send();

        // =====================================================================
        // เสร็จสิ้นการทำงาน
        // =====================================================================
        echo json_encode(['status' => 'success']);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
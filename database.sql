-- ============================================================
-- MSU DevNote - ไฟล์ Schema สำหรับ Import ลง MySQL
-- วิชา 1201 215 การเขียนโปรแกรมเว็บ
-- วิธีใช้: นำไฟล์นี้ไป Import ผ่าน phpMyAdmin หรือคำสั่ง mysql
-- ============================================================

CREATE DATABASE IF NOT EXISTS msu_devnote CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE msu_devnote;

-- ลบตารางเดิมออกก่อน (ใช้ตอนทดสอบซ้ำ) ------------------------------------
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS users;

-- ตารางผู้ใช้งาน -------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางบันทึกสมุดโน้ต (Data Ownership Feature) -------------------------------
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางข้อความสอบถาม/ข้อเสนอแนะ (Form #2) ----------------------------------
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- ข้อมูลตัวอย่าง (Sample Data)
-- รหัสผ่านถูก Hash ด้วย password_hash() แล้ว:
--   admin@msu.ac.th   => Admin@2026
--   student1@msu.ac.th => DevNote@2026
--   student2@msu.ac.th => DevNote@2026
-- ==========================================================================

INSERT INTO users (fullname, email, password, role) VALUES
('ผู้ดูแลระบบ ทดสอบ', 'admin@msu.ac.th',
 '$2y$10$4JbTnKy13g.RvDrSkPYoYO1SeSbCU43J08DDyXsQ5Z7LiSQxmRe2C', 'admin'),
('นิสิต ทดสอบ คนแรก', 'student1@msu.ac.th',
 '$2y$10$Ylkvz/f2tGcbgRY2bL8Kf.833FZU8RqJiqwlOKD6ffBTsgcN5EJfu', 'user'),
('นิสิต ทดสอบ คนที่สอง', 'student2@msu.ac.th',
 '$2y$10$Ylkvz/f2tGcbgRY2bL8Kf.833FZU8RqJiqwlOKD6ffBTsgcN5EJfu', 'user');

INSERT INTO notes (user_id, title, category, content) VALUES
(2, 'สรุปบทที่ 1 ความเป็นมาของเว็บ', 'การเขียนโปรแกรมเว็บ',
 'เว็บเบราว์เซอร์ส่งคำขอผ่าน HTTP ไปยังเซิร์ฟเวอร์ แล้วได้รับไฟล์ HTML กลับมาแสดงผล'),
(2, 'PHP Prepared Statements', 'PHP',
 'ใช้ prepare() แล้ว execute() เพื่อป้องกัน SQL Injection ห้ามต่อสตริงกับค่าจากผู้ใช้เด็ดขาด'),
(3, 'บันทึกวิธีตั้งค่า Git', 'Version Control',
 'รัน git config --local user.email ให้เป็นอีเมลมหาวิทยาลัยก่อนเริ่ม commit แรก');

INSERT INTO feedback (user_id, subject, message) VALUES
(2, 'ขอเพิ่มหมวดหมู่โน้ต', 'อยากให้เพิ่มหมวดหมู่วิชาคณิตศาสตร์เพิ่มครับ'),
(3, 'ระบบใช้งานง่ายดี', 'หน้าอ่านโน้ตอ่านง่ายสบายตาครับ');

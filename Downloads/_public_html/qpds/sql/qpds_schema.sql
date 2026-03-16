-- ============================================================
-- QPDS - Question Paper Delivery System
-- VTU Rules Based | Hostinger MySQL Compatible
-- ============================================================

CREATE DATABASE IF NOT EXISTS qpds_vtu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qpds_vtu;

-- ─────────────────────────────────────────
-- DEPARTMENTS
-- ─────────────────────────────────────────
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (name, code) VALUES
('Computer Science & Engineering', 'CS'),
('Electronics & Communication Engineering', 'EC'),
('Mechanical Engineering', 'ME'),
('Civil Engineering', 'CE'),
('Information Science & Engineering', 'IS'),
('Electrical & Electronics Engineering', 'EE'),
('Artificial Intelligence & Machine Learning', 'AI'),
('Chemical Engineering', 'CH');

-- ─────────────────────────────────────────
-- USERS & ROLES
-- ─────────────────────────────────────────
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('admin','principal','hod','staff') NOT NULL,
  department_id INT NULL,
  is_active TINYINT(1) DEFAULT 1,
  last_login DATETIME NULL,
  profile_photo VARCHAR(255) DEFAULT NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Default admin user (password: Admin@123)
INSERT INTO users (username, email, password, full_name, role, department_id, created_by) VALUES
('admin', 'admin@college.edu', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', NULL, NULL);

-- ─────────────────────────────────────────
-- COURSES / SUBJECTS
-- ─────────────────────────────────────────
CREATE TABLE subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_code VARCHAR(20) NOT NULL UNIQUE,
  subject_name VARCHAR(150) NOT NULL,
  department_id INT NOT NULL,
  semester INT NOT NULL CHECK (semester BETWEEN 1 AND 8),
  credits INT NOT NULL DEFAULT 4,
  subject_type ENUM('theory','lab','elective') DEFAULT 'theory',
  total_units INT NOT NULL DEFAULT 5,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- ─────────────────────────────────────────
-- UNITS PER SUBJECT
-- ─────────────────────────────────────────
CREATE TABLE subject_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  unit_number INT NOT NULL,
  unit_title VARCHAR(200) NOT NULL,
  unit_description TEXT,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- COURSE OUTCOMES (CO)
-- ─────────────────────────────────────────
CREATE TABLE course_outcomes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  co_number INT NOT NULL,
  co_code VARCHAR(20) NOT NULL,
  co_description TEXT NOT NULL,
  bloom_level ENUM('L1-Remember','L2-Understand','L3-Apply','L4-Analyze','L5-Evaluate','L6-Create') NOT NULL DEFAULT 'L2-Understand',
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
-- PROGRAM OUTCOMES (PO) - VTU Standard
-- ─────────────────────────────────────────
CREATE TABLE program_outcomes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  po_number INT NOT NULL,
  po_code VARCHAR(10) NOT NULL,
  po_description TEXT NOT NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- ─────────────────────────────────────────
-- CO-PO MAPPING
-- ─────────────────────────────────────────
CREATE TABLE co_po_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  co_id INT NOT NULL,
  po_id INT NOT NULL,
  mapping_level ENUM('1','2','3') NOT NULL DEFAULT '2' COMMENT '1=Low, 2=Medium, 3=High',
  FOREIGN KEY (co_id) REFERENCES course_outcomes(id) ON DELETE CASCADE,
  FOREIGN KEY (po_id) REFERENCES program_outcomes(id) ON DELETE CASCADE,
  UNIQUE KEY unique_co_po (co_id, po_id)
);

-- ─────────────────────────────────────────
-- QUESTION BANK
-- ─────────────────────────────────────────
CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  unit_id INT NOT NULL,
  co_id INT NOT NULL,
  added_by INT NOT NULL,
  question_text TEXT NOT NULL,
  question_type ENUM('2mark','5mark','10mark','part_a','part_b','part_c') NOT NULL,
  marks INT NOT NULL,
  difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
  bloom_level ENUM('L1','L2','L3','L4','L5','L6') NOT NULL DEFAULT 'L2',
  is_approved TINYINT(1) DEFAULT 0,
  approved_by INT NULL,
  times_used INT DEFAULT 0,
  last_used DATE NULL,
  tags VARCHAR(255) DEFAULT NULL,
  diagram_required TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id),
  FOREIGN KEY (unit_id) REFERENCES subject_units(id),
  FOREIGN KEY (co_id) REFERENCES course_outcomes(id),
  FOREIGN KEY (added_by) REFERENCES users(id)
);

-- ─────────────────────────────────────────
-- VTU QUESTION PAPER TEMPLATES
-- ─────────────────────────────────────────
CREATE TABLE qp_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_name VARCHAR(100) NOT NULL,
  exam_type ENUM('CIE1','CIE2','CIE3','SEE','Assignment','Quiz') NOT NULL,
  total_marks INT NOT NULL,
  duration_minutes INT NOT NULL,
  vtu_rules JSON NOT NULL COMMENT 'JSON rules for paper structure',
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- VTU CIE Template (30 marks)
INSERT INTO qp_templates (template_name, exam_type, total_marks, duration_minutes, vtu_rules, is_default) VALUES
('VTU CIE Standard (30 Marks)', 'CIE1', 30, 90, '{
  "sections": [
    {
      "section": "A",
      "title": "Part A - Short Answer",
      "instruction": "Answer ALL questions",
      "questions_to_answer": 5,
      "questions_to_attempt": 5,
      "marks_per_question": 2,
      "question_type": "2mark",
      "units_covered": [1,2,3]
    },
    {
      "section": "B",
      "title": "Part B - Medium Answer",
      "instruction": "Answer any 3 out of 5 questions",
      "questions_to_answer": 3,
      "questions_to_attempt": 5,
      "marks_per_question": 5,
      "question_type": "5mark",
      "units_covered": [1,2,3]
    }
  ],
  "total_marks": 30,
  "note": "VTU CIE - 30 Marks, 1.5 Hours"
}', 1),

('VTU SEE Standard (100 Marks)', 'SEE', 100, 180, '{
  "sections": [
    {
      "section": "A",
      "title": "Module 1",
      "instruction": "Answer Q1 OR Q2",
      "questions_to_answer": 1,
      "questions_to_attempt": 2,
      "marks_per_question": 20,
      "question_type": "10mark",
      "units_covered": [1],
      "or_choice": true
    },
    {
      "section": "B",
      "title": "Module 2",
      "instruction": "Answer Q3 OR Q4",
      "questions_to_answer": 1,
      "questions_to_attempt": 2,
      "marks_per_question": 20,
      "question_type": "10mark",
      "units_covered": [2],
      "or_choice": true
    },
    {
      "section": "C",
      "title": "Module 3",
      "instruction": "Answer Q5 OR Q6",
      "questions_to_answer": 1,
      "questions_to_attempt": 2,
      "marks_per_question": 20,
      "question_type": "10mark",
      "units_covered": [3],
      "or_choice": true
    },
    {
      "section": "D",
      "title": "Module 4",
      "instruction": "Answer Q7 OR Q8",
      "questions_to_answer": 1,
      "questions_to_attempt": 2,
      "marks_per_question": 20,
      "question_type": "10mark",
      "units_covered": [4],
      "or_choice": true
    },
    {
      "section": "E",
      "title": "Module 5",
      "instruction": "Answer Q9 OR Q10",
      "questions_to_answer": 1,
      "questions_to_attempt": 2,
      "marks_per_question": 20,
      "question_type": "10mark",
      "units_covered": [5],
      "or_choice": true
    }
  ],
  "total_marks": 100,
  "note": "VTU SEE - 100 Marks, 3 Hours"
}', 1);

-- ─────────────────────────────────────────
-- GENERATED QUESTION PAPERS
-- ─────────────────────────────────────────
CREATE TABLE question_papers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paper_code VARCHAR(50) NOT NULL UNIQUE,
  subject_id INT NOT NULL,
  template_id INT NOT NULL,
  exam_type ENUM('CIE1','CIE2','CIE3','SEE','Assignment','Quiz') NOT NULL,
  academic_year VARCHAR(10) NOT NULL,
  semester INT NOT NULL,
  generated_by INT NOT NULL,
  approved_by INT NULL,
  status ENUM('draft','pending_approval','approved','printed','archived') DEFAULT 'draft',
  paper_data JSON NOT NULL COMMENT 'Full paper JSON with questions',
  set_number INT DEFAULT 1 COMMENT 'Set A=1, Set B=2 for shuffled variants',
  shuffled_from INT NULL COMMENT 'Parent paper ID if this is a shuffled version',
  total_marks INT NOT NULL,
  duration_minutes INT NOT NULL,
  instructions TEXT,
  watermark_text VARCHAR(100) DEFAULT 'CONFIDENTIAL',
  print_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id),
  FOREIGN KEY (template_id) REFERENCES qp_templates(id),
  FOREIGN KEY (generated_by) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- ─────────────────────────────────────────
-- QUESTION PAPER ITEMS (individual questions in paper)
-- ─────────────────────────────────────────
CREATE TABLE paper_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paper_id INT NOT NULL,
  question_id INT NOT NULL,
  section VARCHAR(5) NOT NULL,
  question_number INT NOT NULL,
  sub_part VARCHAR(5) DEFAULT NULL COMMENT 'a, b, c etc',
  marks INT NOT NULL,
  display_order INT NOT NULL,
  FOREIGN KEY (paper_id) REFERENCES question_papers(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- ─────────────────────────────────────────
-- ACTIVITY LOGS
-- ─────────────────────────────────────────
CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  module VARCHAR(50) NOT NULL,
  details TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ─────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info','success','warning','error') DEFAULT 'info',
  is_read TINYINT(1) DEFAULT 0,
  link VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ─────────────────────────────────────────
-- COLLEGE INFO (for paper header)
-- ─────────────────────────────────────────
CREATE TABLE college_info (
  id INT AUTO_INCREMENT PRIMARY KEY,
  college_name VARCHAR(200) NOT NULL,
  college_code VARCHAR(20),
  affiliated_to VARCHAR(200) DEFAULT 'Visvesvaraya Technological University, Belagavi',
  address TEXT,
  phone VARCHAR(20),
  email VARCHAR(100),
  website VARCHAR(100),
  logo_path VARCHAR(255),
  accreditation VARCHAR(100) DEFAULT 'NAAC Accredited',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO college_info (college_name, college_code, affiliated_to) VALUES
('Your College Name', 'VTU-CODE', 'Visvesvaraya Technological University, Belagavi');

-- ─────────────────────────────────────────
-- INDEXES FOR PERFORMANCE
-- ─────────────────────────────────────────
ALTER TABLE questions ADD INDEX idx_subject_unit (subject_id, unit_id);
ALTER TABLE questions ADD INDEX idx_co (co_id);
ALTER TABLE questions ADD INDEX idx_type_marks (question_type, marks);
ALTER TABLE question_papers ADD INDEX idx_subject_exam (subject_id, exam_type);
ALTER TABLE question_papers ADD INDEX idx_status (status);
ALTER TABLE users ADD INDEX idx_role_dept (role, department_id);

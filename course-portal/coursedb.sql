CREATE DATABASE IF NOT EXISTS course_portal;
USE course_portal;

CREATE TABLE IF NOT EXISTS students (
    id          INT           NOT NULL AUTO_INCREMENT,
    studentId   VARCHAR(20)   NOT NULL UNIQUE,         
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,                
    firstName   VARCHAR(80)   NOT NULL,
    lastName    VARCHAR(80)   NOT NULL,
    major       VARCHAR(100),
    phone       VARCHAR(20),
    role        VARCHAR(30)   NOT NULL DEFAULT 'student', 
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS courses (
    id          INT           NOT NULL AUTO_INCREMENT,
    code        VARCHAR(20)   NOT NULL UNIQUE,         
    title       VARCHAR(150)  NOT NULL,
    description TEXT,
    department  VARCHAR(80)   NOT NULL,
    instructor  VARCHAR(120)  NOT NULL,
    credits     INT           NOT NULL DEFAULT 3,
    capacity    INT           NOT NULL DEFAULT 30,
    schedule    VARCHAR(100),                            
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enrollments (
    id          INT       NOT NULL AUTO_INCREMENT,
    student_id  INT       NOT NULL,
    course_id   INT       NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_enrollment (student_id, course_id),
    CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_enroll_course  FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO students (studentId, email, password, firstName, lastName, major, phone, role) VALUES
('S00001', 'admin@campus.edu',   '$2y$10$nrpQVfTK5I98YpK7ixAWlO038xJ99dE/wcc4daut/JmFqPPqnsQc.', 'Alex',  'Reyes',  'Registrar Office', '555-0100', 'admin'),
('S00002', 'jane.doe@campus.edu','$2y$10$nrpQVfTK5I98YpK7ixAWlO038xJ99dE/wcc4daut/JmFqPPqnsQc.', 'Jane',  'Doe',    'Computer Science', '555-0101', 'student');

INSERT IGNORE INTO courses (code, title, description, department, instructor, credits, capacity, schedule) VALUES
('CS101', 'Introduction to Programming', 'Fundamentals of programming using Python: variables, control flow, functions, and basic data structures.', 'Computer Science', 'Dr. Maria Chen', 3, 4, 'MWF 9:00 - 9:50 AM'),
('CS210', 'Data Structures & Algorithms', 'Core data structures, algorithm design, and complexity analysis.', 'Computer Science', 'Dr. Omar Farouk', 4, 3, 'TTh 11:00 - 12:20 PM'),
('CS330', 'Database Systems', 'Relational database design, SQL, normalization, and transaction processing.', 'Computer Science', 'Prof. Linda Suarez', 3, 25, 'MWF 1:00 - 1:50 PM'),
('MATH201', 'Calculus II', 'Integration techniques, sequences and series, and applications.', 'Mathematics', 'Dr. Henry Park', 4, 30, 'TTh 9:00 - 10:20 AM'),
('MATH250', 'Linear Algebra', 'Vector spaces, matrices, eigenvalues, and linear transformations.', 'Mathematics', 'Dr. Priya Nair', 3, 2, 'MWF 11:00 - 11:50 AM'),
('ENG105', 'Academic Writing', 'Composition, rhetoric, and research writing for college-level work.', 'English', 'Prof. Sarah Whitfield', 3, 28, 'TTh 1:00 - 2:20 PM'),
('BIO110', 'General Biology', 'Cell biology, genetics, and the fundamentals of evolution.', 'Biology', 'Dr. Kevin O\'Brien', 4, 32, 'MWF 10:00 - 10:50 AM'),
('ART150', 'Introduction to Digital Art', 'Foundations of digital illustration, composition, and color theory.', 'Art', 'Prof. Naomi Ishikawa', 2, 20, 'TTh 3:00 - 4:15 PM'),
('BUS220', 'Principles of Marketing', 'Marketing strategy, consumer behavior, and branding fundamentals.', 'Business', 'Prof. Daniel Osei', 3, 35, 'MWF 2:00 - 2:50 PM'),
('PHYS201', 'Physics I: Mechanics', 'Kinematics, Newtonian mechanics, energy, and momentum.', 'Physics', 'Dr. Elena Volkov', 4, 24, 'TTh 8:00 - 9:20 AM');

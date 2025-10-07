CREATE DATABASE faculty_subject_management;

USE faculty_subject_management;

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE faculty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department_id INT,
    position VARCHAR(255),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT,
    subject_id INT,
    semester VARCHAR(50),
    academic_year VARCHAR(50),
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);
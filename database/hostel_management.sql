
-- Student Hostel Management System
-- Database: hostel_management
-- Normalized to 3NF

DROP DATABASE IF EXISTS hostel_management;
CREATE DATABASE hostel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_management;

-- TABLE: users  (Admin login accounts)

CREATE TABLE users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    FullName VARCHAR(100) NOT NULL,
    Role ENUM('Admin') NOT NULL DEFAULT 'Admin',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- TABLE: students

CREATE TABLE students (
    StudentID INT AUTO_INCREMENT PRIMARY KEY,
    RegistrationNo VARCHAR(20) NOT NULL UNIQUE,
    FullName VARCHAR(100) NOT NULL,
    Gender ENUM('Male','Female','Other') NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Faculty VARCHAR(100) NOT NULL,
    Department VARCHAR(100) NOT NULL,
    AcademicYear VARCHAR(10) NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_name (FullName),
    INDEX idx_student_regno (RegistrationNo)
) ENGINE=InnoDB;


-- TABLE: rooms

CREATE TABLE rooms (
    RoomID INT AUTO_INCREMENT PRIMARY KEY,
    RoomNumber VARCHAR(20) NOT NULL UNIQUE,
    Block VARCHAR(20) NOT NULL,
    Floor INT NOT NULL,
    Capacity INT NOT NULL CHECK (Capacity > 0),
    OccupiedBeds INT NOT NULL DEFAULT 0,
    MonthlyFee DECIMAL(10,2) NOT NULL CHECK (MonthlyFee > 0),
    Status ENUM('Available','Full','Maintenance') NOT NULL DEFAULT 'Available',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_number (RoomNumber),
    INDEX idx_room_block (Block)
) ENGINE=InnoDB;


-- TABLE: allocations

CREATE TABLE allocations (
    AllocationID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID INT NOT NULL,
    RoomID INT NOT NULL,
    CheckInDate DATE NOT NULL,
    ExpectedCheckout DATE NOT NULL,
    AllocationStatus ENUM('Active','Checked Out','Transferred') NOT NULL DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_alloc_student FOREIGN KEY (StudentID) REFERENCES students(StudentID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_alloc_room FOREIGN KEY (RoomID) REFERENCES rooms(RoomID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_alloc_status (AllocationStatus)
) ENGINE=InnoDB;


-- TABLE: payments

CREATE TABLE payments (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    AllocationID INT NOT NULL,
    PaymentMonth VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    Amount DECIMAL(10,2) NOT NULL CHECK (Amount > 0),
    PaymentDate DATE NULL,
    PaymentStatus ENUM('Paid','Pending','Overdue') NOT NULL DEFAULT 'Pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_allocation FOREIGN KEY (AllocationID) REFERENCES allocations(AllocationID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_payment_month (PaymentMonth),
    INDEX idx_payment_status (PaymentStatus)
) ENGINE=InnoDB;


-- DEFAULT ADMIN USER
-- Username: admin  |  Password: admin123

INSERT INTO users (Username, PasswordHash, FullName, Role) VALUES
('admin', '$2b$10$LSqZGA1TJRSK2cp/nMhRweB25NCQpq/izip7MWChRZlCevslgZG1W', 'System Administrator', 'Admin');
-- NOTE: hash above is a verified bcrypt hash for password "admin123"


-- SAMPLE DATA: rooms

INSERT INTO rooms (RoomNumber, Block, Floor, Capacity, OccupiedBeds, MonthlyFee, Status) VALUES
('A-101', 'A', 1, 2, 2, 8000.00, 'Full'),
('A-102', 'A', 1, 2, 1, 8000.00, 'Available'),
('A-103', 'A', 1, 3, 0, 7500.00, 'Available'),
('B-201', 'B', 2, 2, 1, 8500.00, 'Available'),
('B-202', 'B', 2, 2, 0, 8500.00, 'Available'),
('C-301', 'C', 3, 4, 0, 7000.00, 'Available'),
('C-302', 'C', 3, 1, 0, 12000.00, 'Maintenance');


-- SAMPLE DATA: students

INSERT INTO students (RegistrationNo, FullName, Gender, Phone, Email, Faculty, Department, AcademicYear) VALUES
('ICT/2021/001', 'Nimal Perera', 'Male', '0771234567', 'nimal.p@example.com', 'Faculty of Technology', 'ICT', '3rd Year'),
('ICT/2021/002', 'Kamala Silva', 'Female', '0772345678', 'kamala.s@example.com', 'Faculty of Technology', 'ICT', '3rd Year'),
('MED/2022/015', 'Sunil Fernando', 'Male', '0773456789', 'sunil.f@example.com', 'Faculty of Medicine', 'Nursing', '2nd Year'),
('AGR/2020/044', 'Dilani Rathnayake', 'Female', '0774567890', 'dilani.r@example.com', 'Faculty of Agriculture', 'Crop Science', '4th Year');


-- SAMPLE DATA: allocations

INSERT INTO allocations (StudentID, RoomID, CheckInDate, ExpectedCheckout, AllocationStatus) VALUES
(1, 1, '2026-01-10', '2026-12-10', 'Active'),
(2, 1, '2026-01-12', '2026-12-10', 'Active'),
(3, 2, '2026-02-01', '2026-11-30', 'Active'),
(4, 4, '2025-08-15', '2026-06-30', 'Active');


-- SAMPLE DATA: payments

INSERT INTO payments (AllocationID, PaymentMonth, Amount, PaymentDate, PaymentStatus) VALUES
(1, '2026-06', 8000.00, '2026-06-03', 'Paid'),
(1, '2026-07', 8000.00, '2026-07-02', 'Paid'),
(2, '2026-06', 8000.00, '2026-06-05', 'Paid'),
(2, '2026-07', 8000.00, NULL, 'Pending'),
(3, '2026-07', 8000.00, '2026-07-01', 'Paid'),
(4, '2026-07', 8500.00, NULL, 'Overdue');

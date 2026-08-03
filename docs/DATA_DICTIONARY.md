# Data Dictionary

## Table: students

| Column | Type | Constraints | Description |
|---|---|---|---|
| StudentID | INT | PK, AUTO_INCREMENT | Unique student identifier |
| RegistrationNo | VARCHAR(20) | NOT NULL, UNIQUE | University registration number |
| FullName | VARCHAR(100) | NOT NULL | Student's full name |
| Gender | ENUM('Male','Female','Other') | NOT NULL | Student's gender |
| Phone | VARCHAR(15) | NOT NULL | Digits only, 9–15 characters |
| Email | VARCHAR(100) | NOT NULL | Validated email address |
| Faculty | VARCHAR(100) | NOT NULL | Faculty name |
| Department | VARCHAR(100) | NOT NULL | Department name |
| AcademicYear | VARCHAR(10) | NOT NULL | e.g. "3rd Year" |
| CreatedAt | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| UpdatedAt | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last modification time |

## Table: rooms

| Column | Type | Constraints | Description |
|---|---|---|---|
| RoomID | INT | PK, AUTO_INCREMENT | Unique room identifier |
| RoomNumber | VARCHAR(20) | NOT NULL, UNIQUE | Room number, e.g. "A-101" |
| Block | VARCHAR(20) | NOT NULL | Hostel block name |
| Floor | INT | NOT NULL | Floor number |
| Capacity | INT | NOT NULL, CHECK > 0 | Total beds in the room |
| OccupiedBeds | INT | NOT NULL, DEFAULT 0 | Current number of occupied beds (system-maintained) |
| MonthlyFee | DECIMAL(10,2) | NOT NULL, CHECK > 0 | Monthly rent per bed |
| Status | ENUM('Available','Full','Maintenance') | NOT NULL | Current room status |
| CreatedAt / UpdatedAt | TIMESTAMP | — | Audit timestamps |

## Table: allocations

| Column | Type | Constraints | Description |
|---|---|---|---|
| AllocationID | INT | PK, AUTO_INCREMENT | Unique allocation identifier |
| StudentID | INT | FK → students.StudentID | Student being housed |
| RoomID | INT | FK → rooms.RoomID | Room assigned |
| CheckInDate | DATE | NOT NULL | Date student checked in |
| ExpectedCheckout | DATE | NOT NULL | Planned checkout date |
| AllocationStatus | ENUM('Active','Checked Out','Transferred') | NOT NULL | Current state of the allocation |
| CreatedAt / UpdatedAt | TIMESTAMP | — | Audit timestamps |

## Table: payments

| Column | Type | Constraints | Description |
|---|---|---|---|
| PaymentID | INT | PK, AUTO_INCREMENT | Unique payment identifier |
| AllocationID | INT | FK → allocations.AllocationID | Allocation the payment applies to |
| PaymentMonth | VARCHAR(7) | NOT NULL, format YYYY-MM | Billing month |
| Amount | DECIMAL(10,2) | NOT NULL, CHECK > 0 | Amount paid/due |
| PaymentDate | DATE | NULLABLE | Actual date paid (null if not yet paid) |
| PaymentStatus | ENUM('Paid','Pending','Overdue') | NOT NULL | Payment state |
| CreatedAt / UpdatedAt | TIMESTAMP | — | Audit timestamps |

## Table: users

| Column | Type | Constraints | Description |
|---|---|---|---|
| UserID | INT | PK, AUTO_INCREMENT | Unique admin account identifier |
| Username | VARCHAR(50) | NOT NULL, UNIQUE | Login username |
| PasswordHash | VARCHAR(255) | NOT NULL | Bcrypt password hash |
| FullName | VARCHAR(100) | NOT NULL | Administrator's display name |
| Role | ENUM('Admin') | NOT NULL DEFAULT 'Admin' | Account role |
| CreatedAt | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |

# Entity-Relationship Diagram

```mermaid
erDiagram
    STUDENTS ||--o{ ALLOCATIONS : "has"
    ROOMS ||--o{ ALLOCATIONS : "hosts"
    ALLOCATIONS ||--o{ PAYMENTS : "generates"

    STUDENTS {
        int StudentID PK
        varchar RegistrationNo UK
        varchar FullName
        enum Gender
        varchar Phone
        varchar Email
        varchar Faculty
        varchar Department
        varchar AcademicYear
    }

    ROOMS {
        int RoomID PK
        varchar RoomNumber UK
        varchar Block
        int Floor
        int Capacity
        int OccupiedBeds
        decimal MonthlyFee
        enum Status
    }

    ALLOCATIONS {
        int AllocationID PK
        int StudentID FK
        int RoomID FK
        date CheckInDate
        date ExpectedCheckout
        enum AllocationStatus
    }

    PAYMENTS {
        int PaymentID PK
        int AllocationID FK
        varchar PaymentMonth
        decimal Amount
        date PaymentDate
        enum PaymentStatus
    }

    USERS {
        int UserID PK
        varchar Username UK
        varchar PasswordHash
        varchar FullName
        enum Role
    }
```

## Relationship Summary

- **One Student → Many Allocations** (a student may have multiple allocation records over time — e.g. after a transfer or a re-checkin the following year — but only one *Active* allocation at once, enforced at the application layer).
- **One Room → Many Allocations** (a room can house multiple students over time, and multiple beds concurrently up to its `Capacity`).
- **One Allocation → Many Payments** (a single room allocation accumulates one payment record per month).
- **USERS** is an independent entity used solely for admin authentication and does not participate in the hostel data model's relationships.

# Relational Schema

Primary keys are **bold and underlined** conceptually (shown as `PK`); foreign keys are marked `FK`.

```
users (UserID PK, Username, PasswordHash, FullName, Role, CreatedAt)

students (StudentID PK, RegistrationNo, FullName, Gender, Phone, Email,
          Faculty, Department, AcademicYear, CreatedAt, UpdatedAt)

rooms (RoomID PK, RoomNumber, Block, Floor, Capacity, OccupiedBeds,
       MonthlyFee, Status, CreatedAt, UpdatedAt)

allocations (AllocationID PK, StudentID FK -> students.StudentID,
             RoomID FK -> rooms.RoomID, CheckInDate, ExpectedCheckout,
             AllocationStatus, CreatedAt, UpdatedAt)

payments (PaymentID PK, AllocationID FK -> allocations.AllocationID,
          PaymentMonth, Amount, PaymentDate, PaymentStatus,
          CreatedAt, UpdatedAt)
```

## Functional Dependencies

- `StudentID → RegistrationNo, FullName, Gender, Phone, Email, Faculty, Department, AcademicYear`
- `RoomID → RoomNumber, Block, Floor, Capacity, OccupiedBeds, MonthlyFee, Status`
- `AllocationID → StudentID, RoomID, CheckInDate, ExpectedCheckout, AllocationStatus`
- `PaymentID → AllocationID, PaymentMonth, Amount, PaymentDate, PaymentStatus`

## Referential Integrity Rules

| Foreign Key | References | On Delete | On Update |
|---|---|---|---|
| `allocations.StudentID` | `students.StudentID` | CASCADE | CASCADE |
| `allocations.RoomID` | `rooms.RoomID` | CASCADE | CASCADE |
| `payments.AllocationID` | `allocations.AllocationID` | CASCADE | CASCADE |

`CASCADE` on delete means removing a student or room also removes their allocation and payment history — the application additionally guards against accidental deletion by warning the admin via a confirmation modal before any delete action.

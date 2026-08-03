# Normalization (1NF → 3NF)

## Starting point: an unnormalized "flat" record

A naive single-table design (as might be captured on a paper hostel intake form) would look like:

```
HostelRecord(
  StudentID, RegistrationNo, FullName, Gender, Phone, Email, Faculty, Department, AcademicYear,
  RoomNumber, Block, Floor, Capacity, MonthlyFee, RoomStatus,
  CheckInDate, ExpectedCheckout, AllocationStatus,
  PaymentMonth1, Amount1, PaymentDate1, Status1,
  PaymentMonth2, Amount2, PaymentDate2, Status2, ...
)
```

This has repeating groups (multiple payment columns per student) and mixes multiple entities into one row.

## 1NF — First Normal Form

**Rule:** Eliminate repeating groups; every column must hold a single atomic value; each row must be unique.

**Applied:** The repeating `PaymentMonth1/Amount1/...`, `PaymentMonth2/Amount2/...` columns are split out into their own `payments` table, one row per payment, linked back by a foreign key. Every remaining column holds a single atomic value (no comma-separated lists, no multi-valued fields).

Result: `students`, `rooms`, `allocations`, and `payments` are separated into distinct row-sets, each with a primary key and atomic columns.

## 2NF — Second Normal Form

**Rule:** Must be in 1NF, and every non-key attribute must depend on the *whole* primary key (relevant when a table has a composite key — no partial dependencies).

**Applied:** All four tables use a single-column surrogate primary key (`StudentID`, `RoomID`, `AllocationID`, `PaymentID`), so there is no composite key and therefore no possibility of a partial dependency. For example, in `allocations`, `CheckInDate` and `AllocationStatus` depend on the entire key `AllocationID`, not on some subset of a composite key. 2NF is satisfied by construction.

## 3NF — Third Normal Form

**Rule:** Must be in 2NF, and no non-key attribute may depend on another non-key attribute (no transitive dependencies).

**Applied:**
- In `rooms`, `MonthlyFee` and `Status` depend only on `RoomID`, not on any other non-key column.
- In `students`, `Faculty` and `Department` depend only on `StudentID` — they are not derived from another non-key attribute in the table.
- Room details (`Block`, `Floor`, `Capacity`, `MonthlyFee`) are **not duplicated** inside `allocations`; instead `allocations` stores only `RoomID` and looks up room details via a join. This removes the transitive dependency `AllocationID → RoomID → MonthlyFee` that would otherwise exist if `MonthlyFee` were copied into the `allocations` table.
- Similarly, `payments` stores only `AllocationID`, not the student's or room's details directly — those are reached transitively through the `allocations` table, avoiding duplication and update anomalies.

**Conclusion:** All four transactional tables (`students`, `rooms`, `allocations`, `payments`) are in Third Normal Form (3NF): every non-key attribute depends on the key, the whole key, and nothing but the key.

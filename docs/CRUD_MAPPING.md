# CRUD Mapping Table

| Module | Entity | Operation | Page / File | Trigger |
|---|---|---|---|---|
| Auth | users | Read (login check) | `login.php` | Login form submit |
| Students | students | Create | `students.php` | "Add Student" modal → POST `action=create` |
| Students | students | Read (list) | `students.php` | Page load (GET) |
| Students | students | Read (search) | `students.php` | Search form (GET `?q=`) |
| Students | students | Update | `students.php` | "Edit" button → modal → POST `action=update` |
| Students | students | Delete | `students.php` | "Delete" button → confirm modal → POST `action=delete` |
| Rooms | rooms | Create | `rooms.php` | "Add Room" modal → POST `action=create` |
| Rooms | rooms | Read (list) | `rooms.php` | Page load (GET) |
| Rooms | rooms | Read (search) | `rooms.php` | Search form (GET `?q=`) |
| Rooms | rooms | Update | `rooms.php` | "Edit" button → modal → POST `action=update` |
| Rooms | rooms | Delete | `rooms.php` | "Delete" button → confirm modal → POST `action=delete` |
| Allocations | allocations | Create (Assign) | `allocations.php` | "Assign Student to Room" modal → POST `action=assign` |
| Allocations | allocations | Read (history) | `allocations.php` | Page load (GET) / search (GET `?q=`) |
| Allocations | allocations, rooms | Update (Transfer) | `allocations.php` | "Transfer" button → modal → POST `action=transfer` |
| Allocations | allocations, rooms | Update (Checkout) | `allocations.php` | "Checkout" button → modal → POST `action=checkout` |
| Payments | payments | Create | `payments.php` | "Add Payment" modal → POST `action=create` |
| Payments | payments | Read (list) | `payments.php` | Page load (GET) |
| Payments | payments | Read (search) | `payments.php` | Search form (GET `?q=`) |
| Payments | payments | Update | `payments.php` | "Edit" button → modal → POST `action=update` |
| Payments | payments | Delete | `payments.php` | "Delete" button → confirm modal → POST `action=delete` |
| Reports | students/rooms/allocations/payments | Read (aggregate) | `reports.php` | Tab navigation (GET `?type=`) |
| Dashboard | students/rooms/payments | Read (aggregate/KPI) | `dashboard.php` | Page load |

**Note:** Transfer and Checkout are modeled as controlled updates rather than raw SQL `UPDATE`/`DELETE` because they must atomically update **two related tables** (`allocations` and `rooms`) inside a single database transaction to preserve referential and occupancy integrity.

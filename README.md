# Campus Lost & Found Management System

A centralized web-based relational database platform to record, search, track, verify ownership claims, and manage the safe return of lost and found belongings across the university campus.

---

## 📌 Project Overview
The **Campus Lost and Found Management System** is a full-stack database-driven web application developed for Metropolitan University to manage, search, claim, and verify lost and found belongings on campus.

It replaces informal platforms (Facebook/WhatsApp groups, notice boards) with a centralized relational database ensuring data consistency, searchability, ownership verification, and status lifecycle tracking.

---

## 🗄️ Relational Database Design

The database `campus_lost_and_found` implements **7 normalized entities**:

1. **`users`**:
   - `user_id` (PK), `name`, `email` (UNIQUE), `phone`, `password` (bcrypt hash), `user_type` (`Student`, `Teacher`, `Staff`, `Admin`), `created_at`
2. **`categories`**:
   - `category_id` (PK), `category_name` (UNIQUE), `description`
3. **`locations`**:
   - `location_id` (PK), `location_name` (UNIQUE), `description`
4. **`items`**:
   - `item_id` (PK), `item_name`, `description`, `color`, `category_id` (FK), `status` (`Lost`, `Found`, `Claimed`, `Returned`), `created_at`
5. **`lost_reports`**:
   - `lost_report_id` (PK), `item_id` (FK, UNIQUE), `user_id` (FK), `location_id` (FK), `lost_date`, `report_date`, `additional_details`
6. **`found_reports`**:
   - `found_report_id` (PK), `item_id` (FK, UNIQUE), `user_id` (FK), `location_id` (FK), `found_date`, `report_date`, `additional_details`
7. **`claims`**:
   - `claim_id` (PK), `item_id` (FK), `user_id` (FK), `claim_date`, `identification_details`, `status` (`Pending`, `Approved`, `Rejected`), `verified_by` (FK -> `users.user_id`)

### Relationships and Cardinality
- `users (1) : (N) lost_reports`
- `users (1) : (N) found_reports`
- `users (1) : (N) claims`
- `categories (1) : (N) items`
- `locations (1) : (N) lost_reports`
- `locations (1) : (N) found_reports`
- `items (1) : (1) lost_reports`
- `items (1) : (1) found_reports`
- `items (1) : (N) claims`
- `users (1) [Admin] : (N) claims [verified_by]`

---

## 🚀 Setup & Installation Instructions

### Prerequisites
- **XAMPP** (Apache & MySQL)
- **PHP** 8.0+
- Web browser (Chrome, Firefox, Edge)

### Quick One-Click Setup
1. Ensure XAMPP **Apache** and **MySQL** services are running.
2. Clone or place this project into your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\Campus-Lost-and-Found
   ```
3. Open your browser and navigate to the automated database installer:
   ```
   http://localhost/Campus-Lost-and-Found/database/setup.php
   ```
4. This script automatically creates the database `campus_lost_and_found`, all 7 tables with foreign keys and constraints, and seeds default users, categories, locations, sample lost/found reports, and claims with secure bcrypt password hashing.
5. Once complete, visit the application home page:
   ```
   http://localhost/Campus-Lost-and-Found/index.php
   ```

*(Alternatively, you can manually import `database/schema.sql` via phpMyAdmin at `http://localhost/phpmyadmin/`)*.

---

## 🔑 Default Demo Accounts

All seeded accounts use password: `password123`

| Role | Name | Email | Password |
|---|---|---|---|
| **Administrator** | Administrator | `admin@example.com` | `password123` |
| **Student** | Student User | `student@example.com` | `password123` |
| **Teacher** | Rahim Ahmed | `rahim@example.com` | `password123` |
| **Staff** | Karim Ullah | `karim@example.com` | `password123` |

---

## 📋 Functional Requirements (FR) Coverage

| ID | Requirement Description | Implementation in Project |
|---|---|---|
| **FR-01** | Store user info & identify by user type (`Student`, `Teacher`, `Staff`, `Admin`) | `auth/register.php`, `auth/login.php`, `users` table |
| **FR-02** | Report lost and found items with relevant item info | `user/report-lost.php`, `user/report-found.php` |
| **FR-03** | Store item name, category, color, description, location, status | `items`, `lost_reports`, `found_reports` tables |
| **FR-04** | Live keyword search across item name, description & details | `search.php`, SQL `LIKE` query |
| **FR-05** | Filter records by category, color, campus location, and status | `search.php` multi-criteria filter panel |
| **FR-06** | "Recently Found" items section displayed by report date | `index.php` (Dedicated section) & `search.php` |
| **FR-07** | Users submit ownership claims with identification details | `user/submit-claim.php`, `item-details.php`, `claims` table |
| **FR-08** | Administrator verifies claims (1-click Approve / Reject) | `admin/dashboard.php` (Claim Verification Tab) |
| **FR-09** | Track item lifecycle (`Lost` &rarr; `Found` &rarr; `Claimed` &rarr; `Returned`) | `items.status`, inline updater in `admin/dashboard.php` and `item-details.php` |
| **FR-10** | Administrator management of users, items, categories, locations, stats | `admin/dashboard.php` complete tabs |

---

## 🔍 Sample DBMS Queries (Demonstrated in Admin Dashboard)

Accessible in **Admin Dashboard &rarr; DBMS Lab Queries Tab**:

1. **Search for an item by name:**
   ```sql
   SELECT items.item_name, items.color, items.status, categories.category_name, users.name AS reported_by 
   FROM items 
   INNER JOIN categories ON items.category_id = categories.category_id 
   LEFT JOIN lost_reports ON items.item_id = lost_reports.item_id 
   LEFT JOIN found_reports ON items.item_id = found_reports.item_id 
   LEFT JOIN users ON users.user_id = COALESCE(lost_reports.user_id, found_reports.user_id) 
   WHERE items.item_name LIKE '%Phone%' OR items.item_name LIKE '%Watch%';
   ```

2. **Filter items by category and color:**
   ```sql
   SELECT items.item_id, items.item_name, items.color, items.status, categories.category_name 
   FROM items 
   INNER JOIN categories ON items.category_id = categories.category_id 
   WHERE categories.category_name = 'Electronics' AND items.color = 'Black';
   ```

3. **Find recently reported found items (FR-06):**
   ```sql
   SELECT found_reports.found_report_id, items.item_name, items.color, categories.category_name, locations.location_name, found_reports.found_date, found_reports.report_date 
   FROM found_reports 
   INNER JOIN items ON found_reports.item_id = items.item_id 
   INNER JOIN categories ON items.category_id = categories.category_id 
   INNER JOIN locations ON found_reports.location_id = locations.location_id 
   ORDER BY found_reports.report_date DESC LIMIT 5;
   ```

4. **Find claims awaiting verification (FR-08):**
   ```sql
   SELECT claims.claim_id, items.item_name, users.name AS claimant_name, users.email, claims.claim_date, claims.identification_details, claims.status 
   FROM claims 
   INNER JOIN items ON claims.item_id = items.item_id 
   INNER JOIN users ON claims.user_id = users.user_id 
   WHERE claims.status = 'Pending' 
   ORDER BY claims.claim_date ASC;
   ```

5. **Find all items reported at a particular campus location:**
   ```sql
   SELECT locations.location_name, items.item_name, items.status, 'Lost' AS report_type, lost_reports.lost_date AS event_date 
   FROM lost_reports 
   INNER JOIN items ON lost_reports.item_id = items.item_id 
   INNER JOIN locations ON lost_reports.location_id = locations.location_id 
   UNION ALL 
   SELECT locations.location_name, items.item_name, items.status, 'Found' AS report_type, found_reports.found_date AS event_date 
   FROM found_reports 
   INNER JOIN items ON found_reports.item_id = items.item_id 
   INNER JOIN locations ON found_reports.location_id = locations.location_id 
   ORDER BY event_date DESC;
   ```

---

## 📁 File Structure
```
Campus-Lost-and-Found/
├── admin/
│   └── dashboard.php          # Admin portal (Claims, items, categories, users, DBMS lab)
├── assets/
│   ├── css/
│   │   └── style.css          # Responsive design, color tokens, cards, badges
│   └── js/
│       └── main.js            # Tabs, mobile navigation, toast auto-dismiss
├── auth/
│   ├── login.php              # Secure login with bcrypt authentication & demo helper
│   ├── logout.php             # Session destruction & cookie cleanup
│   └── register.php           # User registration (Student, Teacher, Staff)
├── config/
│   └── database.php           # PDO connection, dynamic BASE_URL & auth helpers
├── database/
│   ├── schema.sql             # SQL DDL & DML seed data
│   └── setup.php              # 1-click web installer & database migration
├── includes/
│   ├── header.php             # Unified navbar, university branding, session pills
│   └── footer.php             # Campus help desk, footer links, copyright
├── user/
│   ├── my-reports.php         # User activity (My Lost, My Found, My Claims)
│   ├── report-found.php       # Found item submission form
│   ├── report-lost.php        # Lost item submission form
│   └── submit-claim.php       # Ownership claim submission form
├── index.php                  # Homepage with live stats & Recently Found section
├── item-details.php           # Detailed item view, reporter info, admin controls
├── search.php                 # Advanced multi-attribute search & filter
└── README.md                  # Complete documentation
```


# Campus Lost & Found Management System

A web-based platform for university campuses that centralizes the reporting, tracking, claiming, and return of lost and found belongings.

---

## Features

- **Item Reporting**: Report missing or discovered belongings with category, color, campus location, and date.
- **Search & Filtering**: Search reports by keywords, category, campus location, and item status.
- **Recently Found Section**: Quick-access feed of newly reported found items on the homepage.
- **Ownership Claim Management**: Claim items by submitting identifying proof (distinguishing marks, serial numbers, descriptions).
- **Admin Dashboard**: Review submitted claims with one-click approval/rejection, update item lifecycles (`Lost` &rarr; `Found` &rarr; `Claimed` &rarr; `Returned`), and manage categories and locations.
- **Role-Based Access**: Multi-role support for Students, Faculty, Staff, and Administrators with bcrypt password hashing.

---

## Tech Stack

- **Backend**: PHP 8 (PDO)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3 (Flexbox & Grid), JavaScript
- **Server**: Apache (via XAMPP)

---

## Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache and MySQL)

### Installation
1. Clone or place this repository into your XAMPP `htdocs` directory:
   ```bash
   git clone https://github.com/ntnisa/Campus-Lost-and-Found.git C:/xampp/htdocs/Campus-Lost-and-Found
   ```
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open your browser and run the automatic database installer:
   ```
   http://localhost/Campus-Lost-and-Found/database/setup.php
   ```
4. Access the application:
   ```
   http://localhost/Campus-Lost-and-Found/
   ```

---

## Demo Accounts

All pre-seeded test accounts use the password: `password123`

| Role | Email | Password |
|---|---|---|
| **Admin** | `admin@example.com` | `password123` |
| **Student** | `student@example.com` | `password123` |
| **Teacher** | `rahim@example.com` | `password123` |
| **Staff** | `karim@example.com` | `password123` |

---

## Project Structure

```text
Campus-Lost-and-Found/
├── admin/          # Administrator dashboard & verification
├── assets/         # CSS styles and JavaScript
├── auth/           # Login, registration, and logout
├── config/         # Database connection & route helpers
├── database/       # SQL schema & setup installer
├── includes/       # Shared header and footer templates
├── user/           # Reporting & claim management forms
├── index.php       # Homepage with statistics and recent feed
├── item-details.php# Item profile & claim view
└── search.php      # Live search and multi-attribute filters
```

---

## License
Open source under the [MIT License](LICENSE).

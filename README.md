# WASLA – Connect. Anticipate. Protect.

A web platform to **monitor, anticipate and manage water and electricity outages in Tunisia**, developed at **Esprit School of Engineering** as part of the Web Technologies Project (2A6, 2026–2027).

![WASLA home page](docs/screenshots/home-1440-full.png)

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Security](#security)
- [Contributors](#contributors)
- [Academic Context](#academic-context)
- [Getting Started](#getting-started)
- [Usage](#usage)
- [Contributions](#contributions)
- [Acknowledgments](#acknowledgments)
- [License](#license)

## Overview

This project was developed as part of the **Web Technologies Project – 2nd Year Engineering Program** at **Esprit School of Engineering** (Academic Year 2026–2027).

**The problem.** In Tunisia, water (SONEDE) and electricity (STEG) outages are often poorly announced: citizens know neither when they start, nor when they end, nor where to find help.

**The solution.** WASLA brings together on a single platform outages and reports, alerts, bill and consumption analysis, Refuge Zones offered by companies, and a solidarity network (assistance, volunteers, solar installations, suppliers).

The site includes a **Front Office** (citizens, companies, suppliers, volunteers) and a **Back Office** (administrator and STEG / SONEDE sub-admins).

## Features

| Module | Owner | Branch | Content |
|---|---|---|---|
| Common | team | `main` | MVC architecture, PDO connection, sign-up / login / logout, sessions, roles, access control, CSRF, FO / BO layouts |
| 1 — Outages, reports & alerts | Houssem Belhaj | `module1-interruptions` | Outages, reports, alerts, zones — STEG / SONEDE sub-admin accounts |
| 2 — Bill & consumption analysis | Hiba Bouzid | `module2-factures` | Bills, consumption, analysis — citizen accounts |
| 3 — Refuge Zones | Louay Azzabi | `module3-refuges` | Refuge Zones, bookings — company accounts |
| 4 — Emergency assistance, solar & suppliers | Bechir Hellali | `module4-assistance` | Assistance requests, replies, suppliers — supplier, volunteer and owner accounts |

**Database.** A single script, `database/wasla.sql`, created by one member and shared by the whole team: 18 tables linked by 34 foreign keys.

| Part | Tables |
|---|---|
| Common | `zone`, `utilisateur`, `notification` |
| Module 1 | `interruption`, `signalement`, `confirmation`, `alerte`, `lieu_suivi`, `preference_alerte` |
| Module 2 | `facture`, `consommation`, `analyse_facture` |
| Module 3 | `refuge_zone`, `reservation_refuge` |
| Module 4 | `installation_solaire`, `demande_assistance`, `reponse`, `personne_prioritaire` |

**User management.** A single `utilisateur` table; the role is a column (`citoyen`, `admin`, `steg`, `sonede`, `entreprise`, `fournisseur`, `benevole`, `proprietaire`). **Company and supplier are roles, not tables**: their details (company name, tax ID, specialty…) are optional columns of `utilisateur`. Public sign-up always creates a `citoyen` account; other roles are assigned by the administrator. Each module manages the accounts of its own actors.

**Interface:** French / English / Arabic (RTL), light / dark theme, global search (`Ctrl+K`), responsive, keyboard accessibility.

## Tech Stack

### Frontend

- HTML5, CSS3, JavaScript (ES6, no framework)
- Input validation in JavaScript (`validation.js`), **without HTML5 validation**
- [Lucide](https://lucide.dev) icons, Inter and IBM Plex Sans Arabic fonts (self-hosted)

### Backend

- PHP 8 — **MVC** architecture without a framework
- MySQL via **PDO** (prepared statements)

## Architecture

```text
├── index.php                  Single entry point: index.php?action=<key>
├── config/
│   ├── database.php           Database class (Singleton) → Database::getConnexion()
│   └── helpers.php            Display helpers: e(), url(), asset(), csrfField()…
├── models/                    ONLY private attributes + getters/setters + fromArray()
│   ├── UserModel.php
│   └── ZoneModel.php
├── controllers/               ALL the logic: prepared SQL, validation, sessions, roles
│   ├── Controller.php         Base class: $this->db + validate()
│   ├── AuthController.php     Sign-up, login, logout, roles, CSRF
│   └── ZoneController.php
├── routes/                    One file per module: 'action' => function () { … }
│   ├── common.php             Home, sign-up, login, my account, dashboard
│   └── module1.php … module4.php
├── views/                     ONLY display (htmlspecialchars via e())
│   ├── frontoffice/           layout.php, partials/, pages/, assets/
│   └── backoffice/            layout.php, partials/, pages/, assets/
├── database/wasla.sql         Full database (18 tables, 4 modules) — created by one member, shared
└── docs/                      Team guide, mockups, screenshots
```

**Request flow:**

```text
index.php?action=connexion
  → index.php: secure session, headers, CSRF check (POST)
  → routes/common.php: 'connexion' => function () { … }
  → AuthController::login($_POST)  (validation + prepared statement + password_verify)
  → views/frontoffice/pages/auth/connexion.php  (ob_start → $content)
  → views/frontoffice/layout.php  (head → navbar → $content → footer → scripts)
```

## Security

Details, tests to demonstrate and answers to the workshop's review questions: [docs/SECURITE.md](docs/SECURITE.md).

**Sessions.** The session is created by `session_start()` and gets a **new ID** at login (`session_regenerate_id(true)`). Its data (`user_id`, `role`, login time…) stays on the server; the browser only keeps the ID, in the `PHPSESSID` cookie (HttpOnly). Front Office and Back Office share the same session; the Back Office is protected by `requireRole()`. None of this is displayed: session handling lives in the code (`index.php`, `AuthController`).

| Risk | Protection | Where |
|---|---|---|
| SQL injection | PDO prepared statements, `ATTR_EMULATE_PREPARES = false` | controllers, `config/database.php` |
| Plain-text passwords | `password_hash()` / `password_verify()` | `AuthController` |
| XSS | `e()` = `htmlspecialchars()` on every output, input validation, CSP | views, controllers, `index.php` |
| CSRF | Per-session token in every form, checked for **every** POST with `hash_equals()` | `index.php`, `csrfField()` |
| Session fixation | `session_regenerate_id(true)` after login | `AuthController::login()` |
| Session hijacking | `HttpOnly` cookie, `SameSite=Lax`, `use_strict_mode` | `index.php` |
| Clickjacking | `X-Frame-Options: DENY` | `index.php` |
| Privilege escalation | Role forced at sign-up, `requireRole()` on every sensitive action, role re-read from the database on every request | `AuthController`, `routes/` |
| Brute force | 60 s lockout after 5 failed logins | `AuthController::login()` |
| Abandoned sessions | Expiry after 30 min of inactivity | `index.php` |

## Contributors

| Member | Module | GitHub |
|---|---|---|
| Houssem Belhaj | Module 1 — Outages, reports & alerts | [@iForgotTheSemicolonAgainlol](https://github.com/iForgotTheSemicolonAgainlol) |
| Hiba Bouzid | Module 2 — Bill & consumption analysis | [@hibabouzid13](https://github.com/hibabouzid13) |
| Louay Azzabi | Module 3 — Refuge Zones | [@loueyazzabi9-ux](https://github.com/loueyazzabi9-ux) |
| Bechir Hellali | Module 4 — Emergency assistance, solar & suppliers | [@MohamedBechirHelali](https://github.com/MohamedBechirHelali) |

## Academic Context

Developed at **Esprit School of Engineering – Tunisia**
Web Technologies Project — 2A6 | 2026–2027

## Getting Started

### Prerequisites

- PHP 8.0+ and MySQL 5.7+ / MariaDB 10.3+, via [Laragon](https://laragon.org), [XAMPP](https://www.apachefriends.org/index.html) or [WampServer](https://www.wampserver.com)
- A recent web browser

### Installation

1. Clone the repository into the web folder (`C:\laragon\www`, `htdocs` or `www`):

   ```bash
   git clone https://github.com/hibabouzid13/Esprit-PW-2A6-2026-2027-Wasla.git
   ```

2. Start Apache and MySQL.
3. In **phpMyAdmin**, create the `wasla` database (`utf8mb4_unicode_ci`), then in the **SQL** tab paste the whole `database/wasla.sql` file and run it. This single file creates the 18 tables of the 4 modules and the demo data.
4. If your MySQL server has a password, set it in `config/database.php` (`DB_PASS`).
5. Open `http://localhost/Esprit-PW-2A6-2026-2027-Wasla/index.php`.

### Demo accounts

| Role | Email | Password |
|---|---|---|
| Super admin | `admin@wasla.tn` | `Admin@2026` |
| STEG sub-admin | `steg@wasla.tn` | `Steg@2026` |
| SONEDE sub-admin | `sonede@wasla.tn` | `Sonede@2026` |
| Citizen | `citoyen@wasla.tn` | `Citoyen@2026` |
| Company | `entreprise@wasla.tn` | `Demo@2026` |
| Supplier | `fournisseur@wasla.tn` | `Demo@2026` |

## Usage

| Page | URL | Access |
|---|---|---|
| Home | `index.php?action=accueil` | public |
| Create an account | `index.php?action=inscription` | visitor |
| Login | `index.php?action=connexion` | visitor |
| My space | `index.php?action=mon-compte` | logged in |
| Dashboard (Back Office) | `index.php?action=dashboard` | admin, STEG, SONEDE |

## Contributions

See [docs/GUIDE-EQUIPE.md](docs/GUIDE-EQUIPE.md): branches, files owned by each module, commit conventions, merging and GitHub Project.

## Acknowledgments

- Our Web Technologies Project tutors at **Esprit School of Engineering**
- [Lucide](https://lucide.dev) (icons, ISC license), [geoBoundaries](https://www.geoboundaries.org) (governorate boundaries), [Inter](https://rsms.me/inter/) and [IBM Plex Sans Arabic](https://github.com/IBM/plex) (SIL Open Font License)

The demo figures shown are **fictitious**.

## License

Distributed under the **MIT License**. See [LICENSE](LICENSE).

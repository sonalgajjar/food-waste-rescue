# FoodWasteRescue – Surplus Food Donation & Redistribution Platform

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Leaflet](https://img.shields.io/badge/Leaflet-1.9-199900?style=for-the-badge&logo=leaflet&logoColor=white)](https://leafletjs.com)
[![Chart.js](https://img.shields.io/badge/Chart.js-4.4-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://chartjs.org)
[![Tests](https://img.shields.io/badge/Tests-78%20Passing-success?style=for-the-badge&logo=phpunit)](tests/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

FoodWasteRescue is a production-ready, full-featured web platform engineered to bridge commercial food donors (restaurants, caterers, banquet halls, artisanal bakeries) and verified non-governmental organizations (NGOs) and food banks. By utilizing real-time geolocation matching, automated freshness lifecycle management, secure OTP/QR handover verification, in-app coordination chat, PDF tax acknowledgement certificates, and multi-channel notifications (In-App, Email, WhatsApp Cloud API), FoodWasteRescue diverts consumable food away from landfills directly into vulnerable communities.

---

## Table of Contents

1. [Problem Statement & Social Impact](#problem-statement--social-impact)
2. [Advanced Feature Highlights](#advanced-feature-highlights)
3. [Key Features by Role](#key-features-by-role)
4. [System Architecture](#system-architecture)
5. [Verified Pickup Workflow (State Machine)](#verified-pickup-workflow-state-machine)
6. [Database Schema & ER Design](#database-schema--er-design)
7. [Technology Stack](#technology-stack)
8. [Installation & Setup Guide](#installation--setup-guide)
9. [Configuration & Environment Variables](#configuration--environment-variables)
10. [Automated Scheduler & Cron Jobs](#automated-scheduler--cron-jobs)
11. [Testing & Quality Assurance (78 Tests)](#testing--quality-assurance-78-tests)
12. [Security & Optimization Measures](#security--optimization-measures)
13. [License](#license)

---

## Problem Statement & Social Impact

Globally, approximately one-third of all food produced for human consumption is wasted, contributing up to 10% of global greenhouse gas emissions. Concurrently, millions face food insecurity daily.

In urban centers, restaurants, bakeries, and events generate surplus edible food daily, but lack a systematic, hygienic, verifiable, and immediate logistics channel to connect with verified food distribution NGOs before food reaches its expiration threshold.

**FoodWasteRescue solves this challenge by providing:**
- **Zero-barrier Listing:** Donors can list surplus items in under 60 seconds with food safety declarations.
- **Proximity-First Discovery:** NGOs discover nearby available donations sorted by real-time GPS distance with interactive Leaflet map overlays.
- **Secure OTP/QR Verification:** Cryptographic tokens and 6-digit OTP verification guarantee physical handover without bypass.
- **Direct Pickup Chat:** Real-time in-app messaging keeps donors and volunteer drivers coordinated during transit.
- **Verifiable Tax/CSR Certificates:** Automated DomPDF certificate generation with authentic QR verification links.
- **Automated Expiry Safeguards:** Background scheduler prevents expired food from ever being claimed or consumed.

---

## Advanced Feature Highlights

### 1. Pickup OTP & QR Code Handover Verification
- Eliminates fake status transitions: donations cannot be marked completed without physical handover verification.
- **Dual-Factor Verification:** The restaurant displays a unique 6-digit OTP and dynamic SVG QR code; the assigned NGO volunteer enters the OTP or scans the QR code.
- **Security Protections:**
  - 10-minute expiry window with automatic invalidation.
  - 60-second resend cooldown with rate-limiting.
  - Max 5 failed attempts limit to prevent brute forcing.
  - Hashed OTP storage using `Hash::make()` with `otp_hash`.
  - Database row locking (`lockForUpdate()`) and transactions preventing simultaneous verification collisions.
  - Audited administrative override available for emergency logistics situations.

### 2. Real-Time In-App Pickup Chat
- Isolated, private communication room per active pickup request.
- Restaurant staff and assigned volunteer drivers communicate pickup nuances (e.g., *"Standing near loading dock gate 2"*).
- WhatsApp-style chat interface with responsive bubble design, timestamps, and unread badges.
- Real-time polling fallback ensuring zero lost messages without requiring paid third-party socket subscriptions.
- Automatic archiving into read-only history once handover completes or cancels.

### 3. Downloadable Donation Tax / CSR Certificate
- Instant PDF certificate generation upon successful handover verification.
- Features donor details, recipient NGO, food itemization, weight/servings, CO₂ reduction impact metric, and authorization signatures.
- Includes a verifiable QR code linking directly to a public verification endpoint (`/certificates/verify/{certificate_number}`).

### 4. Two-Way Ratings & Reviews
- Restaurant and NGO review each other after verified completion.
- Star ratings across criteria: Food Freshness, Packaging Quality, Pickup Punctuality, and Driver Politeness.
- Public display of ratings on partner profiles with administrative moderation for abusive comments.

### 5. Automated Recurring Donations
- Restaurants can set up recurring donation schedules (Daily, Weekdays, Weekends, Weekly).
- Background Artisan command (`donations:process-recurring`) scheduled every 15 minutes automatically generates fresh surplus listings with future expiry dates.

### 6. Food Safety & Verification Documents
- Food safety inputs: Preparation timestamp, Expiry countdown warning, Allergen labels, Storage instructions, Packaging condition, and FSSAI Hygiene declaration.
- Registration document upload: FSSAI License upload for restaurants, 12A/80G Registration Certificate & FCRA number for NGOs.
- Secure private document storage (`storage/app/documents/`) with admin preview route and rejection reason audits.

---

## Key Features by Role

### 1. System Administrator (`admin`)
- **Executive Governance Dashboard:** Real-time analytics of total users, verified donors, accredited NGOs, active listings, and completed rescue operations.
- **Impact & Sustainability Hub:** Tracks real-time CO₂ emissions diverted, total meals distributed, and pickup fulfillment rate.
- **Partner Verification & KYC:** Review FSSAI food business licenses and NGO registration certificates with one-click **Approve** or **Reject** with custom rejection reasons.
- **Category Mix Analytics:** Chart.js donut chart breaking down food categories rescued (Veg, Non-Veg, Bakery, Dairy, etc.).
- **Reporting Engine:** Generate and export comprehensive platform activity summaries to PDF (via DomPDF) and Microsoft Excel / CSV (via Maatwebsite Excel).
- **Reviews Moderation:** Audit, toggle visibility, or delete inappropriate feedback.

### 2. Donor Restaurant / Food Business (`restaurant`)
- **Surplus Food Posting:** Post detailed donations with food title, category, quantity, serving units, cooked time, expiry time, pickup address, GPS coordinates, allergens, storage rules, and images.
- **OTP & QR Generation:** View the dynamic 6-digit OTP and QR code once an NGO pickup request is approved.
- **Recurring Donations Management:** Configure recurring donation templates that auto-post surplus food on schedule.
- **Certificate Archive:** View and download historical tax-deductible PDF donation certificates with one click.
- **Pickup Stepper:** Visual 5-stage progress stepper tracking each collection.

### 3. Food Collector / NGO (`ngo`)
- **Proximity-Based Food Discovery:** Real-time search engine with:
  - Haversine distance calculations showing exact kilometers from NGO headquarters.
  - Radial distance filters (5 km, 10 km, 25 km, 50 km).
  - Category filters and text keyword search.
  - Sorting by nearest distance, newest listed, expiring soon, or largest quantity.
- **Interactive Redistribution Map:** Leaflet.js / OpenStreetMap visualizer displaying nearby donations with direct claim shortcuts.
- **OTP/QR Scanner & Verification:** Direct mobile-friendly interface for volunteers to submit pickup verification tokens at the donor venue.
- **Live Expiry Countdown:** Real-time countdown clock on active listings preventing expired claims.

---

## System Architecture

```
   +-----------------------------------------------------------------+
   |                       Client Browser / Device                   |
   |   (Responsive Bootstrap 5 UI + Leaflet Maps + Chart.js Graphs)  |
   +--------------------------------+--------------------------------+
                                    | HTTPS / JSON
                                    v
   +-----------------------------------------------------------------+
   |                      Laravel 12 Web Application                 |
   |                                                                 |
   |  [Web Routes] ----> [RoleMiddleware] ----> [Policy Authorization]|
   |                              |                                  |
   |                              v                                  |
   |  [Controllers & Services]                                       |
   |    - HandoverVerificationService   - CertificateService         |
   |    - FoodAlertService              - RecurringDonationService   |
   |    - WhatsAppService               - QRCodeService              |
   |    - ChatController                - ReviewController           |
   |    - AdminController               - NGOController              |
   |    - RestaurantController          - FrontendController         |
   |                              |                                  |
   |                              v                                  |
   |  [Scheduled Console Workers]                                    |
   |    - ExpireDonationsCommand (every minute)                      |
   |    - ProcessRecurringDonationsCommand (every 15 minutes)        |
   +--------------------------------+--------------------------------+
                                    | Eloquent ORM
                                    v
   +-----------------------------------------------------------------+
   |                   Database (MySQL / SQLite)                     |
   |  - users              - food_donations     - pickup_requests    |
   |  - restaurants        - n_g_o_s            - pickup_verifications
   |  - recurring_donations- donation_reviews   - pickup_messages    |
   |  - donation_certificates                   - notifications      |
   +-----------------------------------------------------------------+
```

---

## Verified Pickup Workflow (State Machine)

```
             [ NGO Requests Available Food ]
                           |
                           v
                     ( Pending )
                    /           \
     Restaurant    /             \    Restaurant Declines OR
     Accepts      /               \   NGO Cancels OR Food Expires
                 v                 v
            ( Accepted )      ( Rejected / Expired )
                 |
     Volunteer Dispatched
                 |
                 v
         ( Out for Pickup )
                 |
     Volunteer Arrives at Kitchen
     [ Scans QR Code OR Enters 6-Digit OTP ]
                 |
                 v
       ( Handover Confirmed )
                 |
     Food Delivered to Community
                 |
                 v
            ( Completed )
       [ PDF Certificate Generated ]
       [ Two-Way Reviews Unlocked ]
```

---

## Database Schema & ER Design

### Primary Tables

1. **`users`**: Authentication, roles (`admin`, `restaurant`, `ngo`), approval status (`pending`, `active`, `inactive`, `rejected`), GPS location.
2. **`restaurants`**: Profiles, FSSAI license numbers, operating hours, uploaded license document, license expiry date, rejection reason, verification notes.
3. **`n_g_o_s`**: Profiles, registration numbers, FCRA numbers, uploaded registration document, rejection reason, verification notes.
4. **`food_donations`**: Surplus food items, category, quantity, unit, preparation time, expiry time, allergen info, storage instructions, packaging condition, safety declaration, GPS coordinates.
5. **`pickup_requests`**: Connection between donor and collector, scheduled pickup time, volunteer vehicle remarks, status (`pending`, `accepted`, `out_for_pickup`, `handover_confirmed`, `completed`, `rejected`).
6. **`pickup_verifications`**: OTP/QR verification records, hashed OTP (`otp_hash`), verification token, attempts counter, expiry timestamp, verified timestamp, verified user ID.
7. **`pickup_messages`**: Chat messages per pickup request, sender ID, message body, read status, timestamps.
8. **`donation_certificates`**: PDF certificate tracking, unique certificate number, validation token, issued dates, meal/CO₂ statistics.
9. **`donation_reviews`**: Two-way feedback, star ratings, criteria ratings, feedback text, moderation status.
10. **`recurring_donations`**: Scheduled recurrence templates, frequency (`daily`, `weekdays`, `weekends`, `weekly`), active toggle, last generated timestamp.
11. **`notifications`**: Multi-channel in-app alerts, type tags (`food_alert`, `pickup_update`), read status.

---

## Technology Stack

- **Backend Framework:** [Laravel 12](https://laravel.com)
- **Programming Language:** PHP 8.2+
- **Database Engine:** MySQL 8.0+ (Production) / SQLite 3 (Testing)
- **Frontend Architecture:** Blade Templates, HTML5 Geolocation API, Modern Vanilla JavaScript (ES6)
- **CSS Framework:** Bootstrap 5.3.3 + Font Awesome 6.5
- **Interactive Mapping:** [Leaflet.js 1.9](https://leafletjs.com) + OpenStreetMap
- **Data Visualizations:** [Chart.js 4.4](https://chartjs.org)
- **Document Generation:** Barryvdh Laravel DomPDF (PDF Certificates & Reports) & Maatwebsite Excel
- **QR Code Engine:** Built-in pure PHP SVG/Data-URI generator (`QRCodeService`)
- **Notifications:** Meta WhatsApp Cloud API / Twilio WhatsApp + Laravel Mail (SMTP / Log)

---

## Installation & Setup Guide

### Prerequisites
- PHP >= 8.2 with `pdo`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `sqlite3` extensions enabled.
- Composer 2.x
- MySQL 8.x or MariaDB (via XAMPP, WampServer, or standalone)
- Node.js & npm

### Step-by-Step Installation

1. **Navigate to the Project Directory:**
   ```bash
   cd c:\xampp\htdocs\FoodWasteRescue
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Install Frontend Dependencies:**
   ```bash
   npm install
   ```

4. **Environment Configuration:**
   Copy `.env.example` to `.env` if not already present:
   ```bash
   copy .env.example .env
   ```

5. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

6. **Configure Database Connection (`.env`):**
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=food_waste_rescue
   DB_USERNAME=root
   DB_PASSWORD=
   ```

7. **Execute Migrations:**
   ```bash
   php artisan migrate
   ```

8. **Create Public Storage Symlink:**
   ```bash
   php artisan storage:link
   ```

9. **Build Frontend Assets:**
   ```bash
   npm run build
   ```

10. **Start the Local Development Server:**
    ```bash
    php artisan serve --host=127.0.0.1 --port=8000
    ```
    The application will be accessible at: `http://127.0.0.1:8000`

---

## Default Demo Credentials

| Role | Email Address | Password | Account Status |
|---|---|---|---|
| **System Admin** | `admin@foodrescue.com` | `password` | `active` |
| **Restaurant Donor** | `marco@spicegarden.com` | `password` | `active` |
| **Restaurant Donor** | `sarah@greenleafbistro.com` | `password` | `active` |
| **NGO Partner** | `info@hopefoodbank.org` | `password` | `active` |
| **NGO Partner** | `contact@feedtheneed.org` | `password` | `active` |

---

## Automated Scheduler & Cron Jobs

FoodWasteRescue includes two automated scheduled console commands:

1. **Food Expiration (`donations:expire`):**
   - Runs every minute.
   - Updates expired donations to `expired` status and notifies active request parties.
   ```bash
   php artisan donations:expire
   ```

2. **Recurring Donations (`donations:process-recurring`):**
   - Runs every 15 minutes.
   - Automatically generates scheduled donations for active recurring donor profiles.
   ```bash
   php artisan donations:process-recurring
   ```

To run the scheduler continuously during development:
```bash
php artisan schedule:work
```

---

## Testing & Quality Assurance (78 Tests)

FoodWasteRescue includes an exhaustive automated PHPUnit feature test suite covering every critical workflow.

Run all tests:
```bash
php vendor/phpunit/phpunit/phpunit
```

### Test Suite Summary:
- **`VerificationAndSafetyTest`** (5 tests): Restaurant/NGO registration with document uploads, private document preview for admins, rejection with audited reasons, and food safety attribute persistence.
- **`AdvancedFeaturesTest`** (12 tests): OTP generation, rate limiting, expiry, hashed verification, QR verification, chat messaging, PDF certificate generation, two-way reviews, and recurring donation automation.
- **`NearbyNgoAlertTest`** (6 tests): Nearby geo-alert triggering, distance boundary filtering, and in-app notifications.
- **`AdminApprovalAndStatusTest`** (8 tests): User status management, pending/rejected access controls.
- **`AdminApprovalNotificationTest`** (11 tests): Email dispatches, fallback delivery handling.
- **`FoodDonationPolicyTest`** (6 tests): Authorization policies and IDOR prevention.
- **`PickupWorkflowComprehensiveTest`** (12 tests): Concurrency control (`lockForUpdate()`), status progression, and cancellation logic.
- **`ExpireDonationsCommandTest`** (4 tests): Scheduled expiration worker validation.
- **`LocationAndDistanceSearchTest`** (5 tests): Haversine distance calculations and location fallback.
- **`AuthenticationTest` & `ProfileTest`** (9 tests): Session lifecycle, profile modifications, and security controls.

**Result: 78 tests, 273 assertions passing.**

---

## Security & Optimization Measures

- **Role-Based Access Control (RBAC):** `RoleMiddleware` strictly enforces separation between `admin`, `restaurant`, and `ngo`.
- **Policy Enforcement:** `FoodDonationPolicy` and `PickupRequestPolicy` verify ownership on every action, eliminating Insecure Direct Object Reference (IDOR) vulnerabilities.
- **Cryptographic OTP Security:** 6-digit OTPs are hashed using `Hash::make()` with `otp_hash`, never stored or logged in plain text.
- **Concurrency & Race Condition Defense:** Database transactions with `lockForUpdate()` prevent duplicate claims or simultaneous verification collisions.
- **Private Document Isolation:** Partner legal certificates (FSSAI/NGO 12A) are stored in private storage (`storage/app/documents/`) accessible only to authorized administrators via authenticated controller streaming.
- **XSS & Injection Protection:** Blade automatic HTML escaping, parameterized SQL queries with boundary clamping (`LEAST(1.0, GREATEST(-1.0, ...))`), and strict form requests.

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details. Developed with care for community hunger alleviation and food waste reduction.

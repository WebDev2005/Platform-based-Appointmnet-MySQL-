# Platform-Based Appointment and Patient Management System

PHP + MySQL clinic system covering the full visit lifecycle: booking and registration,
arrival and pre-consultation, consultation and prescription, then checkout, billing,
follow-up, archiving and reporting.

## Roles and screens

| Role | Entry point | What it does |
| --- | --- | --- |
| Patient (`customer`) | `dashboard.php` | Self-service registration, slot booking, live status, queue ticket, medical records |
| Front desk (`staff`) | `staff-dashboard.php` | Confirms arrivals (issues the queue number), logs vitals, cancels bookings |
| Physician (`doctor`) | `doctor-queue.php` | Works the queue, writes diagnosis notes, orders prescriptions, completes visits |
| Administrator (`admin`) | `admin-dashboard.php` | Billing, missed/cancelled tracking, follow-ups, archive, summary reports |

### Visit lifecycle

`booked` → `arrived` → `vitals_done` → `in_consultation` → `done`,
with `cancelled` and `no_show` as terminal states. Bookings that pass their slot by
30 minutes without an arrival are flagged `no_show` automatically whenever a dashboard
is opened, so no cron job is needed.

Queue numbers are issued per doctor per day when the front desk confirms arrival.
Completing a visit creates the invoice (consultation fee plus a dispensing fee when
drugs were ordered); extra charges can be added at checkout.

## Configuration

Database credentials come from the environment — nothing secret is committed:

| Variable | Alias | Default |
| --- | --- | --- |
| `MYSQLHOST` | `DB_HOST` | `127.0.0.1` |
| `MYSQLUSER` | `DB_USER` | `root` |
| `MYSQLPASSWORD` | `DB_PASS` | *(empty)* |
| `MYSQLDATABASE` | `DB_NAME` | `railway` |
| `MYSQLPORT` | `DB_PORT` | `3306` |

Railway's MySQL plugin exports the `MYSQL*` names already.

## Running locally

```bash
sudo service mariadb start
mysql -e "CREATE DATABASE IF NOT EXISTS railway"

export DB_HOST=127.0.0.1 DB_USER=root DB_NAME=railway
php migrate.php     # creates/updates the schema
php seed.php        # optional demo data
php -S 127.0.0.1:8080
```

Open <http://127.0.0.1:8080/Userlogin.html>.

Demo accounts created by `seed.php` (password `password123`):
`patient@clinic.test`, `staff@clinic.test`, `doctor@clinic.test`, `admin@clinic.test`.

## Database

`database.sql` is the full schema. `migrate.php` applies it and adds any columns an
older database is missing, so it is safe to run against an existing deployment.

## Tests

`bash smoke.sh` drives the whole lifecycle over HTTP against a running server:
registration → slot booking → arrival → vitals → consultation → prescription →
checkout → payment → follow-up → archive → report export.

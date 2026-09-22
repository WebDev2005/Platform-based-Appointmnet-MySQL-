-- Platform-Based Appointment and Patient Management System
-- MySQL schema. Safe to run on an existing database: tables are created only
-- when missing and columns added by the ALTER statements at the bottom.

CREATE TABLE IF NOT EXISTS users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(120) NOT NULL,
    email       VARCHAR(160) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(40)  DEFAULT NULL,
    birth_date  DATE         DEFAULT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    role        VARCHAR(20)  NOT NULL DEFAULT 'customer',
    service_id  INT          DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    service_id   INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(120) NOT NULL,
    specialty    VARCHAR(120) DEFAULT NULL,
    fee          DECIMAL(10,2) NOT NULL DEFAULT 500.00,
    slot_minutes INT NOT NULL DEFAULT 30,
    open_time    TIME NOT NULL DEFAULT '09:00:00',
    close_time   TIME NOT NULL DEFAULT '17:00:00'
);

CREATE TABLE IF NOT EXISTS appointments (
    appointment_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    service_id       INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason           VARCHAR(255) DEFAULT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'booked',
    archived         TINYINT(1) NOT NULL DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_slot (service_id, appointment_date, appointment_time),
    KEY idx_user (user_id),
    KEY idx_date (appointment_date)
);

CREATE TABLE IF NOT EXISTS queue (
    queue_id       INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    user_id        INT NOT NULL,
    service_id     INT NOT NULL,
    queue_number   INT NOT NULL,
    queue_date     DATE NOT NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'waiting',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at   DATETIME DEFAULT NULL,
    KEY idx_queue_date (queue_date, service_id)
);

CREATE TABLE IF NOT EXISTS vitals (
    vital_id       INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    temperature    DECIMAL(5,2) DEFAULT NULL,
    blood_pressure VARCHAR(20)  DEFAULT NULL,
    pulse          INT DEFAULT NULL,
    respiratory    INT DEFAULT NULL,
    weight_kg      DECIMAL(6,2) DEFAULT NULL,
    height_cm      DECIMAL(6,2) DEFAULT NULL,
    notes          TEXT DEFAULT NULL,
    recorded_by    INT DEFAULT NULL,
    recorded_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS consultations (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT NOT NULL UNIQUE,
    chief_complaint VARCHAR(255) DEFAULT NULL,
    diagnosis       VARCHAR(255) DEFAULT NULL,
    notes           TEXT DEFAULT NULL,
    follow_up_date  DATE DEFAULT NULL,
    physician_id    INT DEFAULT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT NOT NULL,
    drug_name       VARCHAR(120) NOT NULL,
    dosage          VARCHAR(80)  DEFAULT NULL,
    frequency       VARCHAR(80)  DEFAULT NULL,
    duration        VARCHAR(80)  DEFAULT NULL,
    instructions    VARCHAR(255) DEFAULT NULL,
    prescribed_by   INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_rx_appointment (appointment_id)
);

CREATE TABLE IF NOT EXISTS invoices (
    invoice_id     INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    user_id        INT NOT NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'unpaid',
    payment_method VARCHAR(20) DEFAULT NULL,
    paid_at        DATETIME DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoice_items (
    item_id     INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id  INT NOT NULL,
    description VARCHAR(160) NOT NULL,
    amount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    KEY idx_item_invoice (invoice_id)
);

INSERT INTO services (service_name, specialty, fee)
SELECT * FROM (
    SELECT 'Dr. Reyes - General Medicine' AS a, 'General Medicine' AS b, 600.00 AS c
    UNION ALL SELECT 'Dr. Santos - Pediatrics', 'Pediatrics', 750.00
    UNION ALL SELECT 'Dr. Cruz - Internal Medicine', 'Internal Medicine', 850.00
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM services);

CREATE TABLE IF NOT EXISTS members (
    id INT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    contact_number VARCHAR(50) DEFAULT NULL,
    license_number VARCHAR(100) DEFAULT NULL,
    plate_number VARCHAR(100) DEFAULT NULL,
    id_doc_path VARCHAR(255) DEFAULT NULL,
    license_doc_path VARCHAR(255) DEFAULT NULL,
    orcr_doc_path VARCHAR(255) DEFAULT NULL,
    toda_id VARCHAR(100) DEFAULT NULL,
    body_number VARCHAR(100) DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING APPROVAL',
    created_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    member_id INT NOT NULL UNIQUE,
    role VARCHAR(80) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_users_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS violations (
    id INT PRIMARY KEY,
    reporter_user_id INT DEFAULT NULL,
    reporter_name VARCHAR(180) NOT NULL,
    reported_driver_id VARCHAR(120) DEFAULT NULL,
    reported_name VARCHAR(180) NOT NULL,
    reported_plate VARCHAR(100) DEFAULT NULL,
    actual_reported_plate VARCHAR(100) DEFAULT NULL,
    violation_type VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    incident_datetime DATETIME DEFAULT NULL,
    incident_location VARCHAR(255) DEFAULT NULL,
    evidence_path VARCHAR(255) DEFAULT NULL,
    review_notes VARCHAR(255) DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'SUBMITTED',
    created_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY,
    driver_name VARCHAR(180) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_to_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING VERIFICATION',
    due_date DATE DEFAULT NULL,
    reference_no VARCHAR(120) DEFAULT NULL,
    submitted_reference_no VARCHAR(120) DEFAULT NULL,
    proof_image_path VARCHAR(255) DEFAULT NULL,
    receipt_no VARCHAR(120) DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    is_flagged TINYINT(1) NOT NULL DEFAULT 0,
    flag_reason VARCHAR(255) DEFAULT NULL,
    flagged_by VARCHAR(180) DEFAULT NULL,
    flagged_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY,
    target_user_id INT DEFAULT NULL,
    target_role VARCHAR(80) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY,
    action VARCHAR(120) NOT NULL,
    details TEXT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    name VARCHAR(180) DEFAULT NULL,
    role VARCHAR(80) DEFAULT NULL,
    ip VARCHAR(60) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS fee_rules (
    id INT PRIMARY KEY,
    fee_key VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(180) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    period VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS penalty_rules (
    id INT PRIMARY KEY,
    penalty_key VARCHAR(120) NOT NULL UNIQUE,
    label VARCHAR(220) NOT NULL,
    min_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    max_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_range TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT NULL
);

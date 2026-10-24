CREATE TABLE employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    nid_number VARCHAR(30) NOT NULL UNIQUE,
    photo_path VARCHAR(255) DEFAULT NULL,
    designation VARCHAR(100) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    join_date DATE DEFAULT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payroll (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    salary_month DATE NOT NULL COMMENT 'মাসের প্রথম দিন, যেমন 2026-10-01',
    basic_salary DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    allowance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    overtime DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    deduction DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    advance_deduction DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    net_salary DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
    paid_at DATETIME DEFAULT NULL,
    remarks VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_employee_salary_month (employee_id, salary_month),
    CONSTRAINT fk_payroll_employee
        FOREIGN KEY (employee_id) REFERENCES employees(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

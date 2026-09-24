CREATE DATABASE IF NOT EXISTS food_waste_donation;

USE food_waste_donation;

-- =========================================
-- USERS TABLE
-- =========================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('donor', 'recipient', 'collector', 'ngo', 'admin') NOT NULL,
    address TEXT,
    city VARCHAR(100),
    area VARCHAR(100),
    pincode VARCHAR(10),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    profile_photo VARCHAR(255),
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================
-- FOOD DONATIONS TABLE
-- =========================================

CREATE TABLE food_donations (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,

    food_name VARCHAR(150) NOT NULL,
    food_category VARCHAR(100),
    description TEXT,

    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(30),

    food_type VARCHAR(50),
    people_served INT,

    food_photo VARCHAR(255),

    preparation_time DATETIME,
    best_before DATETIME,

    urgency ENUM('normal', 'urgent', 'very_urgent') DEFAULT 'normal',

    address TEXT,
    city VARCHAR(100),
    area VARCHAR(100),
    pincode VARCHAR(10),

    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),

    delivery_preference ENUM(
        'self_delivery',
        'collector',
        'ngo',
        'any'
    ) DEFAULT 'any',

    allow_partial_request BOOLEAN DEFAULT TRUE,

    status ENUM(
        'available',
        'requested',
        'accepted',
        'picked_up',
        'delivered',
        'completed',
        'cancelled',
        'expired'
    ) DEFAULT 'available',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (donor_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================
-- FOOD REQUESTS TABLE
-- =========================================

CREATE TABLE food_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,

    donation_id INT NOT NULL,
    recipient_id INT NOT NULL,

    quantity DECIMAL(10,2),
    message TEXT,

    status ENUM(
        'pending',
        'accepted',
        'rejected',
        'cancelled',
        'completed'
    ) DEFAULT 'pending',

    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,

    FOREIGN KEY (donation_id)
        REFERENCES food_donations(donation_id)
        ON DELETE CASCADE,

    FOREIGN KEY (recipient_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================
-- COLLECTOR TASKS TABLE
-- =========================================

CREATE TABLE collector_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,

    request_id INT,
    donation_id INT NOT NULL,
    collector_id INT NOT NULL,

    status ENUM(
        'available',
        'accepted',
        'pickup_started',
        'picked_up',
        'delivering',
        'delivered',
        'completed',
        'rejected',
        'cancelled'
    ) DEFAULT 'available',

    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pickup_time DATETIME NULL,
    delivery_time DATETIME NULL,
    completed_at DATETIME NULL,

    notes TEXT,

    FOREIGN KEY (request_id)
        REFERENCES food_requests(request_id)
        ON DELETE SET NULL,

    FOREIGN KEY (donation_id)
        REFERENCES food_donations(donation_id)
        ON DELETE CASCADE,

    FOREIGN KEY (collector_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================
-- NGO REQUESTS TABLE
-- =========================================

CREATE TABLE ngo_requests (
    ngo_request_id INT AUTO_INCREMENT PRIMARY KEY,

    donation_id INT NOT NULL,
    ngo_id INT NOT NULL,

    status ENUM(
        'pending',
        'accepted',
        'rejected',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',

    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,

    notes TEXT,

    FOREIGN KEY (donation_id)
        REFERENCES food_donations(donation_id)
        ON DELETE CASCADE,

    FOREIGN KEY (ngo_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================
-- PICKUP SCHEDULES TABLE
-- =========================================

CREATE TABLE pickup_schedules (
    pickup_id INT AUTO_INCREMENT PRIMARY KEY,

    donation_id INT NOT NULL,

    collector_id INT NULL,
    ngo_id INT NULL,

    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,

    notes TEXT,

    status ENUM(
        'scheduled',
        'confirmed',
        'picked_up',
        'cancelled',
        'completed'
    ) DEFAULT 'scheduled',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (donation_id)
        REFERENCES food_donations(donation_id)
        ON DELETE CASCADE,

    FOREIGN KEY (collector_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    FOREIGN KEY (ngo_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);


-- =========================================
-- DELIVERIES TABLE
-- =========================================

CREATE TABLE deliveries (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,

    donation_id INT NOT NULL,
    request_id INT NULL,

    method ENUM(
        'self',
        'collector',
        'ngo'
    ) NOT NULL,

    collector_id INT NULL,
    ngo_id INT NULL,

    status ENUM(
        'pending',
        'in_transit',
        'delivered',
        'confirmed',
        'cancelled'
    ) DEFAULT 'pending',

    delivered_at DATETIME NULL,
    confirmed_at DATETIME NULL,

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (donation_id)
        REFERENCES food_donations(donation_id)
        ON DELETE CASCADE,

    FOREIGN KEY (request_id)
        REFERENCES food_requests(request_id)
        ON DELETE SET NULL,

    FOREIGN KEY (collector_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    FOREIGN KEY (ngo_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);


-- =========================================
-- NOTIFICATIONS TABLE
-- =========================================

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,

    notification_type VARCHAR(50),

    related_id INT NULL,

    is_read BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================
-- DONATION HISTORY TABLE
-- =========================================

CREATE TABLE donation_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,

    donation_id INT NOT NULL,

    old_status VARCHAR(50),
    new_status VARCHAR(50),

    changed_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (donation_id)
        REFERENCES food_donations(donation_id)
        ON DELETE CASCADE,

    FOREIGN KEY (changed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);


-- =========================================
-- FEEDBACK TABLE
-- =========================================

CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,

    donation_id INT NOT NULL,

    from_user INT NOT NULL,
    to_user INT NOT NULL,

    rating INT,

    comment TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (donation_id)
        REFERENCES food_donations(donation_id)
        ON DELETE CASCADE,

    FOREIGN KEY (from_user)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (to_user)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);


-- =========================================
-- CONTACT MESSAGES TABLE
-- =========================================

CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP table IF EXISTS user_jobs;
DROP table IF EXISTS users;
DROP table IF EXISTS jobs;

CREATE TABLE IF NOT EXISTS users
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    validts    INT          NOT NULL,
    confirmed  TINYINT(1)   NOT NULL DEFAULT 0,
    checked    TINYINT(1)   NOT NULL DEFAULT 0,
    valid      TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP             DEFAULT CURRENT_TIMESTAMP,
    INDEX validts_index (validts),
    INDEX confirmed_index (confirmed),
    INDEX checked_index (checked),
    INDEX valid_index (valid)
    ) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS jobs
(
    id       INT AUTO_INCREMENT PRIMARY KEY,
    type     VARCHAR(16) NOT NULL,
    status   ENUM('ready', 'in progress', 'acknowledged', 'canceled') DEFAULT 'ready',
    params   TEXT DEFAULT '',
    INDEX type_index (type),
    INDEX status_index (status),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS user_jobs
(
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL,
    job_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (job_id) REFERENCES jobs(id)
) ENGINE = InnoDB;


DELIMITER //

CREATE PROCEDURE IF NOT EXISTS insert_users(IN total_users INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE CONFIRMED INT DEFAULT 0;
    DECLARE VALIDTS INT DEFAULT 0;

    WHILE i <= total_users
        DO
            SET CONFIRMED =  FLOOR(RAND() * 100) < 15;
            IF (FLOOR(RAND() * 100) > 80) = 1 THEN
                SET VALIDTS = UNIX_TIMESTAMP(NOW() + INTERVAL 1 DAY);
END IF;
INSERT INTO users (username, email, validts, confirmed)
VALUES (CONCAT('user_', i), CONCAT('user_', i, '@example.com'), VALIDTS, CONFIRMED);
SET i = i + 1;
            SET VALIDTS = 0;
            SET CONFIRMED = 0;
END WHILE;

    SET i = 1;
END //

DELIMITER ;

CALL insert_users(1000);

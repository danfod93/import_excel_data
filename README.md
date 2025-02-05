# Import Excel Data with SpreadsheetReader
-PDO connection
-Header Check included
-Bootstrap 5.3.3
-functions.lib.php: Contains my functions. (there will have more)

DB Table Used
CREATE TABLE asd (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        country VARCHAR(50) NOT NULL,
        age INT(3) NOT NULL,
        email VARCHAR(50),
        phone VARCHAR(20),
        account VARCHAR(12),
        account_date date ,
        balance DECIMAL(10.2),
        active enum('Yes', 'No'),
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    );

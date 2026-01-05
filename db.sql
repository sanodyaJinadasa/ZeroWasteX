CREATE DATABASE waste_management_db;
USE waste_management_db;

CREATE TABLE User (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(100) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Name VARCHAR(150),
    Role VARCHAR(100),
    Email VARCHAR(150),
    Age INT,
    Contact VARCHAR(20)
);

CREATE TABLE Waste_Category (
    Category_ID INT AUTO_INCREMENT PRIMARY KEY,
    Category_Name VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO Waste_Category (Category_Name) VALUES
('Organic'), ('Plastic'), ('Polythene'),
('Paper'), ('Ewaste'), ('Metal'), ('Glass');


CREATE TABLE Collection (
    Collection_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Collection_Date DATE DEFAULT (CURRENT_DATE),   -- auto date
    Collection_Time TIME DEFAULT (CURRENT_TIME),   -- auto time
    Bin_IDs TEXT,
    FOREIGN KEY (User_ID) REFERENCES User(User_ID)
        ON DELETE CASCADE ON UPDATE CASCADE
);



CREATE TABLE Bin (
    Bin_ID INT AUTO_INCREMENT PRIMARY KEY,
    Location VARCHAR(150) NOT NULL,
    Variety VARCHAR(100)
);


CREATE TABLE Bin_Status (
    Status_ID INT AUTO_INCREMENT PRIMARY KEY,
    Bin_ID INT NOT NULL,
    Sensor_ID VARCHAR(50),
    Status ENUM('empty', 'half', 'full') DEFAULT 'empty',
    Action VARCHAR(100),
    FOREIGN KEY (Bin_ID) REFERENCES Bin(Bin_ID)
        ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE User_Waste_Record (
    Record_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Category_ID INT NOT NULL,
    Amount_KG DECIMAL(10,2) NOT NULL,
    Updated_At DATETIME DEFAULT CURRENT_TIMESTAMP,
    Bin_ID INT NULL,
    Collection_ID INT NULL,
    FOREIGN KEY (User_ID) REFERENCES User(User_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Category_ID) REFERENCES Waste_Category(Category_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Bin_ID) REFERENCES Bin(Bin_ID)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (Collection_ID) REFERENCES Collection(Collection_ID)
        ON DELETE SET NULL ON UPDATE CASCADE
);


CREATE TABLE Notifications (
    Notification_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Message TEXT NOT NULL,
    Is_Read BOOLEAN DEFAULT FALSE,
    Created_Date DATE DEFAULT (CURRENT_DATE),
    Created_Time TIME DEFAULT (CURRENT_TIME),
    FOREIGN KEY (User_ID) REFERENCES User(User_ID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Locations (
    Location_ID INT AUTO_INCREMENT PRIMARY KEY,
    Location_Name VARCHAR(225) NOT NULL,
    Latitude DECIMAL(10, 7) NOT NULL,
    Longitude DECIMAL(10, 7) NOT NULL,
);



SHOW TABLES;

User
Waste_Category
Collection
Bin
Bin_Status
User_Waste_Record
Collection_BinStatus_Link
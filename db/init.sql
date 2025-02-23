-- noinspection SqlNoDataSourceInspectionForFile

-- Create the database (already set via ENV, but included for completeness)
CREATE DATABASE IF NOT EXISTS todo_db;
USE todo_db;

-- User table
CREATE TABLE User (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) UNIQUE NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('admin', 'user') DEFAULT 'user'
);

-- Location table
CREATE TABLE Location (
    LocationID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) UNIQUE NOT NULL,
    CreatedBy INT,
    Latitude FLOAT,
    Longitude FLOAT,
    FOREIGN KEY (CreatedBy) REFERENCES User(UserID) ON DELETE SET NULL
);

-- Task table
CREATE TABLE Task (
    TaskID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(100) NOT NULL,
    Description TEXT,
    EndDate DATETIME,
    Priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    Location VARCHAR(100),  -- Redundant text field
    UserID INT NOT NULL,
    LocationID INT,
    FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE,
    FOREIGN KEY (LocationID) REFERENCES Location(LocationID) ON DELETE SET NULL
);

-- Tag table
CREATE TABLE Tag (
    TagID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) UNIQUE NOT NULL,
    Priority ENUM('low', 'medium', 'high') DEFAULT 'medium'
);

-- TaskTag junction table
CREATE TABLE TaskTag (
    TaskID INT,
    TagID INT,
    PRIMARY KEY (TaskID, TagID),
    FOREIGN KEY (TaskID) REFERENCES Task(TaskID) ON DELETE CASCADE,
    FOREIGN KEY (TagID) REFERENCES Tag(TagID) ON DELETE CASCADE
);

-- TaskReminder table
CREATE TABLE TaskReminder (
    ReminderID INT AUTO_INCREMENT PRIMARY KEY,
    TaskID INT NOT NULL,
    ReminderTime DATETIME NOT NULL,
    IsSent BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (TaskID) REFERENCES Task(TaskID) ON DELETE CASCADE
);
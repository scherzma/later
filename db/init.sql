-- noinspection SqlNoDataSourceInspectionForFile

-- Create the database (already set via ENV, but included for completeness)
CREATE DATABASE IF NOT EXISTS todo_db;
USE todo_db;

-- User table with enhanced fields
CREATE TABLE User (
                      UserID INT AUTO_INCREMENT PRIMARY KEY,
                      Username VARCHAR(50) UNIQUE NOT NULL,
                      PasswordHash VARCHAR(255) NOT NULL,
                      Email VARCHAR(100) UNIQUE,
                      Role ENUM('admin', 'user') DEFAULT 'user',
                      CurrentStreak INT DEFAULT 0,
                      BestStreak INT DEFAULT 0,
                      LastCompletedDate DATE,
                      EmailNotifications BOOLEAN DEFAULT TRUE
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
                      Finished BOOLEAN DEFAULT FALSE,
                      DateCreated DATETIME DEFAULT CURRENT_TIMESTAMP,
                      DateFinished DATETIME,
                      FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE,
                      FOREIGN KEY (LocationID) REFERENCES Location(LocationID) ON DELETE SET NULL
);

-- Tag table (linked to User)
CREATE TABLE Tag (
                     TagID INT AUTO_INCREMENT PRIMARY KEY,
                     Name VARCHAR(50) NOT NULL,
                     Priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
                     UserID INT NOT NULL,
                     FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE,
                     UNIQUE (Name, UserID)  -- Ensures unique tag names per user
);

-- TaskTag junction table (links Tasks to Tags, allowing unlimited tags per task)
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

-- Add the TaskQueue table for tracking postponed tasks
CREATE TABLE TaskQueue (
                           QueueID INT AUTO_INCREMENT PRIMARY KEY,
                           TaskID INT NOT NULL,
                           UserID INT NOT NULL,
                           PostponedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
                           QueuePosition INT NOT NULL,
                           FOREIGN KEY (TaskID) REFERENCES Task(TaskID) ON DELETE CASCADE,
                           FOREIGN KEY (UserID) REFERENCES User(UserID) ON DELETE CASCADE
);
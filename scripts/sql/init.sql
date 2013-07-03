
DROP DATABASE IF EXISTS `zfshell`;
CREATE DATABASE `zfshell`
    CHARACTER SET utf8
    COLLATE utf8_general_ci;
USE `zfshell`;

/* Example base table structure containing common fields
CREATE TABLE `ExampleTableName` (
	exampleTableNameId INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	dateCreated TIMESTAMP NOT NULL DEFAULT 0,
	dateUpdated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	isEnabled BOOLEAN NOT NULL DEFAULT TRUE,
	isDeleted BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

/* Pre-constructed tables for ease of implementation
CREATE TABLE IF NOT EXISTS Config (
	configId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	dateCreated TIMESTAMP NOT NULL DEFAULT 0,
	dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	nameInternal VARCHAR(128) NOT NULL,
	nameExternal VARCHAR(128) NOT NULL,
	value TEXT NOT NULL,
	UNIQUE INDEX (nameInternal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `User` (
	userId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	dateCreated TIMESTAMP NOT NULL DEFAULT 0,
	dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	email VARCHAR(64) NOT NULL,
	role ENUM('guest') NOT NULL DEFAULT 'guest',
	isEnabled BOOLEAN NOT NULL DEFAULT TRUE,
	isDeleted BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `UserAuthLogin` (
	userAuthLoginId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	dateCreated TIMESTAMP NOT NULL DEFAULT 0,
	dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	userId INTEGER UNSIGNED NOT NULL,
	username VARCHAR(64) NOT NULL,
	password CHAR(64) NOT NULL,
	UNIQUE INDEX (userId),
	UNIQUE INDEX (username),
	FOREIGN KEY (userId)
		REFERENCES User (userId)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `UserAuthOpenId` (
	userAuthOpenIdId INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	dateCreated TIMESTAMP NOT NULL DEFAULT 0,
	dateUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	userId INTEGER UNSIGNED NOT NULL,
	openId VARCHAR(255) NOT NULL,
	server VARCHAR(255) NOT NULL,
	UNIQUE INDEX (openId, server),
	FOREIGN KEY (userId)
		REFERENCES User (userId)
		ON UPDATE CASCADE
		ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/



/* See application/plugins/RequestLog.php for usage */
CREATE TABLE RequestLog (
    requestLogId BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    server VARCHAR(64),
    module VARCHAR(128),
    controller VARCHAR(128),
    action VARCHAR(128),
    requestStart DECIMAL(17,6),
    requestEnd DECIMAL(17,6),
    requestTotal DECIMAL(11,6),
    systemTime INTEGER,
    userTime INTEGER,
    memoryPeak INTEGER UNSIGNED,
    memoryEnd INTEGER UNSIGNED,
    requestData BLOB
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* See application/modules/default/controllers/ErrorController.php for usage */
CREATE TABLE ExceptionLog (
    exceptionLogId BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dateCreated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    file TEXT NOT NULL,
    line INTEGER NOT NULL,
    message VARCHAR(255) NOT NULL,
    trace BLOB NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
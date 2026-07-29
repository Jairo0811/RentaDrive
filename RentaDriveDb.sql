USE [master];
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.server_principals
    WHERE name = N'rentadrive_app'
)
BEGIN
    CREATE LOGIN [rentadrive_app]
    WITH PASSWORD = 'RentaDrive_Local_2026!',
         CHECK_POLICY = ON,
         CHECK_EXPIRATION = OFF;
END
ELSE
BEGIN
    ALTER LOGIN [rentadrive_app]
    WITH PASSWORD = 'RentaDrive_Local_2026!';

    ALTER LOGIN [rentadrive_app] ENABLE;
END;
GO

IF DB_ID(N'RentaDriveDb') IS NULL
BEGIN
    CREATE DATABASE [RentaDriveDb];
END;
GO

USE [RentaDriveDb];
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.database_principals
    WHERE name = N'rentadrive_app'
)
BEGIN
    CREATE USER [rentadrive_app]
    FOR LOGIN [rentadrive_app];
END;
GO

ALTER ROLE [db_datareader] ADD MEMBER [rentadrive_app];
ALTER ROLE [db_datawriter] ADD MEMBER [rentadrive_app];
ALTER ROLE [db_ddladmin] ADD MEMBER [rentadrive_app];
GO
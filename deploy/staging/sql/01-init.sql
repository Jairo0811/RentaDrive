/*
  Base y logins de STAGING. Se ejecuta con sqlcmd; las variables se leen del entorno del proceso
  (no aparecen en la línea de comandos): STAGING_DB_NAME, DB_APP_PASSWORD, DB_MIGRATOR_PASSWORD.
  Nunca apuntar a RentaDriveDb.
*/
:on error exit
IF N'$(STAGING_DB_NAME)' = N'RentaDriveDb'
BEGIN
    RAISERROR(N'Este script no puede usarse con RentaDriveDb.', 20, 1) WITH LOG;
END;
GO
USE [master];
GO
IF DB_ID(N'$(STAGING_DB_NAME)') IS NULL CREATE DATABASE [$(STAGING_DB_NAME)];
GO
IF NOT EXISTS (SELECT 1 FROM sys.server_principals WHERE name = N'rentadrive_staging_app')
    CREATE LOGIN [rentadrive_staging_app] WITH PASSWORD = '$(DB_APP_PASSWORD)', CHECK_POLICY = ON, CHECK_EXPIRATION = OFF;
IF NOT EXISTS (SELECT 1 FROM sys.server_principals WHERE name = N'rentadrive_staging_migrator')
    CREATE LOGIN [rentadrive_staging_migrator] WITH PASSWORD = '$(DB_MIGRATOR_PASSWORD)', CHECK_POLICY = ON, CHECK_EXPIRATION = OFF;
GO
USE [$(STAGING_DB_NAME)];
GO
IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = N'rentadrive_staging_app')
    CREATE USER [rentadrive_staging_app] FOR LOGIN [rentadrive_staging_app];
IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = N'rentadrive_staging_migrator')
    CREATE USER [rentadrive_staging_migrator] FOR LOGIN [rentadrive_staging_migrator];
-- La aplicación solo lee y escribe datos; las migraciones usan la cuenta de migración.
ALTER ROLE [db_datareader] ADD MEMBER [rentadrive_staging_app];
ALTER ROLE [db_datawriter] ADD MEMBER [rentadrive_staging_app];
ALTER ROLE [db_owner] ADD MEMBER [rentadrive_staging_migrator];
GO

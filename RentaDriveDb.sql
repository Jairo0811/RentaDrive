/*
  Creación inicial de la base y del login de aplicación para un servidor SQL Server de DESARROLLO.

  Uso (la contraseña se entrega por variable; NO se guarda en el repositorio):
      sqlcmd -S <servidor> -E -i RentaDriveDb.sql -v RENTADRIVE_DB_NAME="RentaDriveDb" RENTADRIVE_APP_PASSWORD="<contraseña-larga-única>"

  Ambas variables son obligatorias (sin valores por defecto). Para staging usa RENTADRIVE_DB_NAME="RentaDriveStaging".

  Reglas:
  - No reutilices la contraseña entre entornos ni la subas a Git.
  - El script NO modifica la contraseña de un login existente (solo lo crea si falta).
  - db_ddladmin permite ejecutar migraciones. En staging/producción usa preferiblemente un login de migración
    separado y deja al login de la aplicación solo con db_datareader/db_datawriter (ver docs/staging-private.md).
*/
:on error exit
USE [master];
GO

IF DB_ID(N'$(RENTADRIVE_DB_NAME)') IS NULL
BEGIN
    CREATE DATABASE [$(RENTADRIVE_DB_NAME)];
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.server_principals
    WHERE name = N'rentadrive_app'
)
BEGIN
    CREATE LOGIN [rentadrive_app]
    WITH PASSWORD = '$(RENTADRIVE_APP_PASSWORD)',
         CHECK_POLICY = ON,
         CHECK_EXPIRATION = OFF;
END;
GO

USE [$(RENTADRIVE_DB_NAME)];
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

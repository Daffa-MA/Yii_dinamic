@echo off
echo ========================================
echo Dynamic Form Builder - Setup
echo ========================================
echo.

echo Step 1: Install Composer Dependencies
echo ----------------------------------------
call composer install
if %errorlevel% neq 0 (
    echo ERROR: Composer install failed!
    pause
    exit /b 1
)
echo.

echo Step 2: Database Setup
echo ----------------------------------------
echo Please create the database manually:
echo.
echo 1. Open phpMyAdmin or MySQL CLI
echo 2. Run the SQL script: database_setup.sql
echo.
echo OR run this command if PDO driver is available:
echo    php yii migrate
echo.
pause

echo.
echo Step 3: Configure Database Connection
echo ----------------------------------------
echo Edit file: config/db.php
echo Make sure the database name, username, and password are correct.
echo.

echo.
echo Step 4: Start the Application
echo ----------------------------------------
echo Run: php -S localhost:8080 -t web
echo Then open: http://localhost:8080
echo.
echo Default login: admin / admin123
echo.
pause

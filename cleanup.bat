@echo off
REM Script untuk membersihkan file sampah Yii
echo Membersihkan file sampah Yii...
echo.

REM Hapus cache files
echo [1/5] Membersihkan cache...
for /d %%I in (runtime\cache\*) do rd /s /q "%%I" 2>nul
del /q runtime\cache\* 2>nul

REM Hapus debug files
echo [2/5] Membersihkan debug files...
for /d %%I in (runtime\debug\*) do rd /s /q "%%I" 2>nul
del /q runtime\debug\* 2>nul

REM Hapus session files
echo [3/5] Membersihkan session files...
for /d %%I in (runtime\session\*) do rd /s /q "%%I" 2>nul
del /q runtime\session\* 2>nul

REM Hapus log lama (tapi keep .gitignore)
echo [4/5] Membersihkan log files...
for /d %%I in (runtime\logs\*) do rd /s /q "%%I" 2>nul
del /q runtime\logs\*.log 2>nul

REM Hapus php-server log
echo [5/5] Membersihkan server logs...
del /q runtime\php-server.log 2>nul
del /q runtime\*.db 2>nul

echo.
echo ✓ Pembersihan selesai!
echo.
echo Folder-folder runtime:
echo - Cache: %~dp0runtime\cache\
echo - Debug: %~dp0runtime\debug\
echo - Session: %~dp0runtime\session\
echo - Logs: %~dp0runtime\logs\
echo.
pause

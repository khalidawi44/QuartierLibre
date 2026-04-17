@echo off
REM Raccourci racine — double-clic = synchronisation complete.
REM Redirige vers deploy/deploy.bat.
cd /d "%~dp0"
call "%~dp0deploy\deploy.bat" %*

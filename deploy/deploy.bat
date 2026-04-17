@echo off
setlocal enabledelayedexpansion
REM ═══════════════════════════════════════════════════════════════════
REM  Quartier Libre — Synchronisation FTP en 1 clic (Windows)
REM ═══════════════════════════════════════════════════════════════════
REM
REM  UTILISATION : Double-cliquer sur ce fichier.
REM
REM  PREREQUIS :
REM    - WinSCP installe (https://winscp.net/eng/download.php)
REM      → lors de l'install, cocher "Add to PATH" pour que winscp.com
REM        soit accessible depuis n'importe ou.
REM    - OU le client FTP natif Windows (fallback, basique).
REM
REM  CE QUE FAIT CE SCRIPT :
REM    → "synchronize remote" = mirror du dossier local vers le serveur.
REM    → Les fichiers supprimes localement sont AUSSI supprimes a distance
REM      (option -delete). Ne garde donc en local que ce qui doit exister
REM      en ligne.
REM
REM ═══════════════════════════════════════════════════════════════════

REM ── CONFIGURATION ──────────────────────────────────────────────
REM  Les identifiants sont dans deploy.config.local.bat (gitignore).
REM  Pour changer d'identifiants : éditer deploy.config.local.bat
REM  (créer depuis deploy.config.local.bat.example si absent).
REM ───────────────────────────────────────────────────────────────

set CONFIG_FILE=%~dp0deploy.config.local.bat
if not exist "%CONFIG_FILE%" (
    echo.
    echo ERREUR : deploy.config.local.bat introuvable.
    echo.
    echo Creer le fichier depuis le modele :
    echo   copy "%~dp0deploy.config.local.bat.example" "%CONFIG_FILE%"
    echo Puis y renseigner vos identifiants FTP.
    echo.
    pause
    exit /b 1
)
call "%CONFIG_FILE%"

set THEME_DIR=%~dp0..\%THEME_NAME%

echo.
echo ===================================================
echo   Quartier Libre — Synchronisation FTP
echo ===================================================
echo.
echo Dossier source : %THEME_DIR%
echo Serveur FTP    : %FTP_HOST%:%FTP_PORT%
echo Destination    : %FTP_PATH%/%THEME_NAME%/
echo Mode           : %MODE%
echo.

if not exist "%THEME_DIR%" (
    echo ERREUR : Dossier du theme introuvable !
    echo Chemin attendu : %THEME_DIR%
    pause
    exit /b 1
)

if "%FTP_USER%"=="VOTRE_IDENTIFIANT_FTP" (
    echo.
    echo ATTENTION : identifiants FTP par defaut encore presents.
    echo  → Editer deploy.config.local.bat et y mettre vos vraies valeurs.
    echo.
    pause
    exit /b 1
)

set /p CONFIRM="Lancer la synchronisation ? (O/N) : "
if /i not "%CONFIRM%"=="O" (
    echo Deploiement annule.
    pause
    exit /b 0
)

echo.
echo Synchronisation en cours...
echo.

REM ── Methode 1 : WinSCP ─────────────────────────────────────────
set WINSCP_FOUND=
if exist "%WINSCP_EXE%" (
    set "WINSCP_FOUND=%WINSCP_EXE%"
) else (
    where winscp.com >nul 2>nul
    if %errorlevel% equ 0 set WINSCP_FOUND=winscp.com
)

if defined WINSCP_FOUND (
    if /i "%MODE%"=="mirror" (
        set SYNC_CMD=synchronize remote "%THEME_DIR%" "%FTP_PATH%/%THEME_NAME%/" -delete
    ) else (
        set SYNC_CMD=synchronize remote "%THEME_DIR%" "%FTP_PATH%/%THEME_NAME%/"
    )
    "!WINSCP_FOUND!" /log="%TEMP%\ql-deploy.log" /command ^
        "option batch abort" ^
        "option confirm off" ^
        "open ftp://%FTP_USER%:%FTP_PASS%@%FTP_HOST%:%FTP_PORT%/ -rawsettings ProxyPort=0" ^
        "!SYNC_CMD!" ^
        "exit"
    set STATUS=!errorlevel!
    goto :check_result
)

REM ── Methode 2 : FTP natif Windows (fallback basique) ──────────
echo WinSCP non detecte, utilisation du client FTP natif.
echo (recommande : installer WinSCP pour une vraie synchronisation)
echo.

set FTPSCRIPT=%TEMP%\ql-ftp-script.txt
echo open %FTP_HOST%> "%FTPSCRIPT%"
echo %FTP_USER%>> "%FTPSCRIPT%"
echo %FTP_PASS%>> "%FTPSCRIPT%"
echo binary>> "%FTPSCRIPT%"
echo cd %FTP_PATH%>> "%FTPSCRIPT%"
echo mkdir %THEME_NAME%>> "%FTPSCRIPT%"
echo cd %THEME_NAME%>> "%FTPSCRIPT%"
echo prompt>> "%FTPSCRIPT%"
echo lcd "%THEME_DIR%">> "%FTPSCRIPT%"
echo mput *.*>> "%FTPSCRIPT%"
echo quit>> "%FTPSCRIPT%"

ftp -s:"%FTPSCRIPT%"
set STATUS=%errorlevel%
del "%FTPSCRIPT%"

:check_result
echo.
if %STATUS% equ 0 (
    echo ===================================================
    echo   SYNCHRONISATION REUSSIE !
    echo ===================================================
    echo.
    echo Site a jour : https://quartierlibre.org/
    echo.
) else (
    echo ===================================================
    echo   ERREUR lors de la synchronisation ^(code: %STATUS%^)
    echo ===================================================
    echo.
    echo Verifications :
    echo   - FTP_HOST / FTP_USER / FTP_PASS corrects ?
    echo   - Le dossier %FTP_PATH% existe-t-il sur le serveur ?
    echo   - WinSCP installe ? ^(sinon : https://winscp.net/^)
    echo.
    if exist "%TEMP%\ql-deploy.log" (
        echo Log WinSCP : %TEMP%\ql-deploy.log
    )
)

echo.
pause
endlocal

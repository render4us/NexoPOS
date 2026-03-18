@echo off
setlocal EnableDelayedExpansion

REM ══════════════════════════════════════════════════════════════════════
REM  KIOSK LAUNCHER — Abre o autoatendimento em tela cheia
REM  Edite a linha KIOSK_URL abaixo com o endereco do seu servidor.
REM ══════════════════════════════════════════════════════════════════════

set KIOSK_URL=http://localhost/kiosk

REM ── Reinicia automaticamente se o navegador for fechado (1=sim / 0=nao)
set AUTO_RESTART=1

REM ── Caminhos dos navegadores suportados
set CHROME_PATH1=%ProgramFiles%\Google\Chrome\Application\chrome.exe
set CHROME_PATH2=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe
set CHROME_PATH3=%LocalAppData%\Google\Chrome\Application\chrome.exe
set EDGE_PATH1=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe
set EDGE_PATH2=%ProgramFiles%\Microsoft\Edge\Application\msedge.exe

REM ══════════════════════════════════════════════════════════════════════
:START

REM ── Oculta o cursor e limpa a tela
cls

REM ── Detecta e abre o navegador em modo kiosk
if exist "%CHROME_PATH1%" (
    set BROWSER="%CHROME_PATH1%"
    goto LAUNCH_CHROME
)
if exist "%CHROME_PATH2%" (
    set BROWSER="%CHROME_PATH2%"
    goto LAUNCH_CHROME
)
if exist "%CHROME_PATH3%" (
    set BROWSER="%CHROME_PATH3%"
    goto LAUNCH_CHROME
)
if exist "%EDGE_PATH1%" (
    set BROWSER="%EDGE_PATH1%"
    goto LAUNCH_EDGE
)
if exist "%EDGE_PATH2%" (
    set BROWSER="%EDGE_PATH2%"
    goto LAUNCH_EDGE
)

echo.
echo  [ERRO] Nenhum navegador suportado encontrado.
echo  Instale o Google Chrome ou Microsoft Edge e tente novamente.
echo.
pause
exit /b 1

REM ── Chrome: modo kiosk completo (sem barra, sem abas, tela cheia)
:LAUNCH_CHROME
%BROWSER% ^
  --kiosk ^
  --disable-infobars ^
  --disable-session-crashed-bubble ^
  --disable-restore-session-state ^
  --no-first-run ^
  --noerrdialogs ^
  --disable-translate ^
  --disable-extensions ^
  --overscroll-history-navigation=0 ^
  --disable-pinch ^
  --disable-features=TranslateUI ^
  --autoplay-policy=no-user-gesture-required ^
  "%KIOSK_URL%"
goto AFTER

REM ── Edge: modo kiosk
:LAUNCH_EDGE
%BROWSER% ^
  --kiosk "%KIOSK_URL%" ^
  --edge-kiosk-type=fullscreen ^
  --no-first-run ^
  --disable-infobars ^
  --noerrdialogs ^
  --disable-extensions
goto AFTER

:AFTER
if "%AUTO_RESTART%"=="1" (
    timeout /t 2 /nobreak >nul
    goto START
)

endlocal

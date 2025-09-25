@echo off
chcp 65001 > nul

echo.
echo ╔══════════════════════════════════════════════╗
echo ║              🚀 CAD-Share v1.0               ║
echo ║         Расшаривание CAD файлов             ║
echo ╚══════════════════════════════════════════════╝
echo.

:: Проверка Node.js
where node >nul 2>nul
if errorlevel 1 (
    echo ❌ Node.js не найден!
    echo.
    echo 📥 Пожалуйста, установите Node.js:
    echo    https://nodejs.org/ru/download/
    echo.
    pause
    exit /b 1
)

:: Проверка наличия CAD файлов
set "cadCount=0"
for %%f in (*.dwg *.dxf) do (
    set /a cadCount+=1
)

if %cadCount%==0 (
    echo ⚠️  CAD файлы не найдены в текущей папке
    echo.
    echo 📁 Поместите файлы .dwg или .dxf в папку с CAD-Share.bat
    echo    и перезапустите программу
    echo.
    pause
    exit /b 1
)

echo ✅ Найдено CAD файлов: %cadCount%
echo 📁 Папка: %cd%
echo.

:: Запуск CAD-Share
node "CAD-Share.exe.js"

pause
param (
    [string]$phpVersion = "8.1",
    [string]$project = "empin.loc"
)

# сгенерировать SSH ключ: ssh-keygen -t {указать любое название, типа: ed25519} -C "{директория проекта, из которой идет генерация например: empin.loc}"

# Запуск: .\share_local.ps1 -phpVersion "{версия PHP например 8.2}" -project {директория проекта, например: empin.loc}

$phpPath = "C:\OSPanel\modules\PHP-$phpVersion\PHP\php.exe"
$projectPath = "Z:\$project"
$tmpFile = "$env:TEMP\tunnel_output.txt"
$sshKeyPath = "$env:USERPROFILE\.ssh\id_ed25519"
$sshUser = "deathdrumer"

Write-Host "👉 PHP: $phpPath"
Write-Host "👉 Проект: $projectPath"

# Запускаем Laravel-сервер в фоне
Start-Process -WindowStyle Hidden -FilePath $phpPath -ArgumentList "-S 0.0.0.0:8080 -t $projectPath\public"

Start-Sleep -Seconds 2

# Флаг, чтобы ссылка показывалась один раз
$launched = $false

# Цикл авто-перезапуска туннеля
while ($true) {
    Remove-Item $tmpFile -Force -ErrorAction SilentlyContinue

    # Запускаем туннель
    Start-Process ssh -ArgumentList "-i $sshKeyPath -R 80:localhost:8080 $sshUser@localhost.run" `
        -RedirectStandardOutput $tmpFile `
        -WindowStyle Hidden

    Start-Sleep -Seconds 3

    # Считываем ссылку
    $content = Get-Content $tmpFile -Raw

    if (!$launched -and $content -match "https://[a-zA-Z0-9\-]+\.lhr\.life") {
        $url = $matches[0]
        Set-Clipboard -Value $url
        Write-Host "`👉 Ссылка: $url (скопирована в буфер обмена)"
        $launched = $true
    }

    Start-Sleep -Seconds 30
}


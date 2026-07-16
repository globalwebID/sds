$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$php = 'C:\xampp-8.2.4\php\php.exe'
$runner = Join-Path $root 'tools\scheduled-maintenance.php'
if (-not (Test-Path -LiteralPath $php)) { throw "PHP tidak ditemukan: $php" }
if (-not (Test-Path -LiteralPath $runner)) { throw "Runner tidak ditemukan: $runner" }
$action = New-ScheduledTaskAction -Execute $php -Argument ('"' + $runner + '"') -WorkingDirectory $root
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(2) -RepetitionInterval (New-TimeSpan -Hours 1)
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew
$task = Register-ScheduledTask -TaskName 'SDS Automated Backup' -Description 'Menjalankan kebijakan backup terpusat SDS setiap jam.' -Action $action -Trigger $trigger -Settings $settings -RunLevel Limited -Force -ErrorAction Stop
if (-not $task -or $task.TaskName -ne 'SDS Automated Backup') { throw 'Task gagal diverifikasi setelah registrasi.' }
Write-Host 'Task SDS Automated Backup berhasil dipasang dan diverifikasi.'

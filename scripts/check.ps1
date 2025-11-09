# Quick health check for Takemaster (PowerShell)
# Usage: From project root run: .\scripts\check.ps1

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Write-Host "Takemaster quick health check" -ForegroundColor Cyan
Write-Host "Project root: $projectRoot`n"

# 1) PHP syntax check (php -l) for all .php files
Write-Host "Running php -l on all .php files..." -ForegroundColor Yellow
$phpExe = "php"
$errors = @()
Get-ChildItem -Path $projectRoot -Recurse -Filter *.php | ForEach-Object {
    $file = $_.FullName
    Write-Host "Checking: $file"
    $proc = Start-Process -FilePath $phpExe -ArgumentList "-l`,""$file`""" -NoNewWindow -PassThru -RedirectStandardOutput tmp_out.txt -RedirectStandardError tmp_err.txt -Wait
    $out = Get-Content tmp_out.txt -Raw -ErrorAction SilentlyContinue
    $err = Get-Content tmp_err.txt -Raw -ErrorAction SilentlyContinue
    Remove-Item tmp_out.txt -ErrorAction SilentlyContinue
    Remove-Item tmp_err.txt -ErrorAction SilentlyContinue
    if ($out -match "Errors parsing" -or $out -match "Parse error" -or $err) {
        Write-Host "SYNTAX ERROR in $file" -ForegroundColor Red
        Write-Host $out
        Write-Host $err
        $errors += @{ file = $file; msg = $out + "\n" + $err }
    }
}
if ($errors.Count -eq 0) { Write-Host "PHP syntax: OK" -ForegroundColor Green } else { Write-Host "PHP syntax: FAIL ($($errors.Count) files)" -ForegroundColor Red }

# 2) Call diag.php if a local server is available
$diagUrl = "http://localhost/takemaster/diag.php"
Write-Host "\nAttempting to fetch diag.php from $diagUrl" -ForegroundColor Yellow
try {
    $resp = Invoke-WebRequest -Uri $diagUrl -UseBasicParsing -ErrorAction Stop
    Write-Host "diag.php response:" -ForegroundColor Green
    $resp.Content | Select-Object -First 200 | ForEach-Object { Write-Host $_ }
} catch {
    Write-Host "Could not fetch diag.php. Start a local server or adjust the URL in the script." -ForegroundColor Yellow
}

# 3) Summary file
$reportDir = Join-Path $projectRoot "build"
if (!(Test-Path $reportDir)) { New-Item -ItemType Directory -Path $reportDir | Out-Null }
$report = @()
$report += "Takemaster quick check - $(Get-Date)"
$report += "PHP syntax errors: $($errors.Count)"
$report += "See console output for details."
$reportFile = Join-Path $reportDir "health.txt"
$report | Out-File -FilePath $reportFile -Encoding utf8
Write-Host "\nReport written to: $reportFile" -ForegroundColor Cyan

if ($errors.Count -gt 0) { exit 1 } else { exit 0 }

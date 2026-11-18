#Requires -Version 5.1
<#
  Cài website bán nhôm định hình (WordPress + WooCommerce) lên Windows, KHÔNG cần Docker.
  Dùng PHP + MySQL/MariaDB + Apache của XAMPP hoặc Laragon có sẵn trên máy.

  Cách chạy: bấm đúp file  cai-dat-windows.bat  ở thư mục gốc repo.
  Chạy lại nhiều lần an toàn: bước nào đã xong sẽ được bỏ qua.
#>

# 'Continue': Windows PowerShell 5.1 coi stderr của lệnh ngoài là lỗi; ta tự kiểm tra $LASTEXITCODE.
$ErrorActionPreference = 'Continue'
try { [Console]::OutputEncoding = [Text.Encoding]::UTF8 } catch { }
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$Repo     = Split-Path -Parent $PSScriptRoot
$IsWin    = ($env:OS -eq 'Windows_NT')
$ExeSuffix = if ($IsWin) { '.exe' } else { '' }

function Step($msg) { Write-Host "`n==> $msg" -ForegroundColor Cyan }
function Info($msg) { Write-Host "    $msg" }
function Warn($msg) { Write-Host "    ! $msg" -ForegroundColor Yellow }
function Fail($msg) {
    Write-Host "`nLỖI: $msg" -ForegroundColor Red
    exit 1
}

# ------------------------------------------------------------------ Cấu hình (.env)

Step 'Đọc cấu hình .env'
$EnvFile = Join-Path $Repo '.env'
if (-not (Test-Path $EnvFile)) {
    Copy-Item (Join-Path $Repo '.env.example') $EnvFile -ErrorAction Stop
    Info 'Đã tạo .env từ .env.example. Hãy sửa mật khẩu quản trị, hotline, tài khoản ngân hàng rồi LƯU lại.'
    if ($IsWin) { Start-Process notepad.exe -ArgumentList "`"$EnvFile`"" -Wait }
    else { Read-Host 'Sửa .env xong nhấn Enter để tiếp tục' | Out-Null }
}
$cfg = @{}
foreach ($line in Get-Content -LiteralPath $EnvFile -Encoding UTF8) {
    $t = $line.Trim()
    if ($t -eq '' -or $t.StartsWith('#') -or -not $t.Contains('=')) { continue }
    $k, $v = $t.Split('=', 2)
    $k = $k.Trim(); $v = $v.Trim().Trim('"')
    $cfg[$k] = $v
    # seed.php đọc các giá trị này qua getenv().
    [Environment]::SetEnvironmentVariable($k, $v, 'Process')
}
function Cfg($key, $default = '') { if ($cfg.ContainsKey($key) -and $cfg[$key] -ne '') { $cfg[$key] } else { $default } }

$Folder = Cfg 'SITE_FOLDER' 'nhomdinhhinh'
if ($Folder -notmatch '^[a-z0-9_-]+$') { Fail "SITE_FOLDER chỉ được dùng chữ thường không dấu, số, - và _ (đang là '$Folder')." }

# ------------------------------------------------------------------ Tìm XAMPP / Laragon

Step 'Tìm XAMPP / Laragon'
$roots = @()
if (Cfg 'STACK_DIR') { $roots += (Cfg 'STACK_DIR') }
$roots += 'C:\laragon', 'C:\xampp', 'D:\laragon', 'D:\xampp'

$Php = $null; $Mysql = $null; $WebRoot = $null; $Stack = $null
foreach ($r in $roots) {
    if (-not (Test-Path $r)) { continue }
    if (Test-Path (Join-Path $r 'bin/php')) {
        # Laragon: bin\php\php-8.x..., bin\mysql\mysql-8.x...\bin
        $phpDir = Get-ChildItem (Join-Path $r 'bin/php') -Directory | Sort-Object Name -Descending | Select-Object -First 1
        $myDir  = Get-ChildItem (Join-Path $r 'bin/mysql') -Directory -ErrorAction SilentlyContinue | Sort-Object Name -Descending | Select-Object -First 1
        if ($phpDir -and $myDir) {
            $Php = Join-Path $phpDir.FullName "php$ExeSuffix"
            $Mysql = Join-Path $myDir.FullName "bin/mysql$ExeSuffix"
            $WebRoot = Join-Path $r 'www'
            $Stack = "Laragon ($r)"
            break
        }
    }
    if (Test-Path (Join-Path $r "php/php$ExeSuffix")) {
        $Php = Join-Path $r "php/php$ExeSuffix"
        $Mysql = Join-Path $r "mysql/bin/mysql$ExeSuffix"
        $WebRoot = Join-Path $r 'htdocs'
        $Stack = "XAMPP ($r)"
        break
    }
}
if (-not $Php) {
    Fail @'
Không tìm thấy XAMPP hoặc Laragon.
  - Cài XAMPP (miễn phí): https://www.apachefriends.org  (chọn bản PHP 8.2 trở lên, cài vào C:\xampp)
  - Hoặc Laragon: https://laragon.org
Nếu đã cài ở thư mục khác, thêm dòng STACK_DIR=D:\duong\dan vào file .env rồi chạy lại.
'@
}
Info "Dùng $Stack"
Info "PHP:   $Php"
Info "MySQL: $Mysql"

$phpVer = (& $Php -r 'echo PHP_VERSION;')
if ([version]($phpVer -replace '[^0-9.].*$', '') -lt [version]'7.4') { Fail "Cần PHP 7.4 trở lên (đang là $phpVer)." }
$mods = (& $Php -m) -join ' '
foreach ($m in 'mysqli', 'curl', 'openssl', 'mbstring') {
    if ($mods -notmatch "\b$m\b") { Fail "PHP thiếu extension '$m'. Mở php.ini, bỏ dấu ; trước dòng extension=$m rồi chạy lại." }
}
if ($mods -notmatch '\bgd\b') { Warn 'PHP chưa bật extension gd: sản phẩm mẫu sẽ không có ảnh minh hoạ (không ảnh hưởng chức năng).' }
Info "PHP $phpVer"

# ------------------------------------------------------------------ MySQL đang chạy?

function Test-Port([int]$port) {
    try { $c = New-Object Net.Sockets.TcpClient; $c.Connect('127.0.0.1', $port); $c.Close(); return $true } catch { return $false }
}
$DbPort = [int](Cfg 'DB_PORT' '3306')
while (-not (Test-Port $DbPort)) {
    Warn "MySQL chưa chạy (cổng $DbPort)."
    Read-Host '    Mở XAMPP Control Panel bấm Start ở Apache và MySQL (Laragon: bấm Start All), rồi nhấn Enter' | Out-Null
}
if (-not (Test-Port 80)) { Warn 'Apache chưa chạy (cổng 80) — nhớ bấm Start Apache để mở được website.' }

# ------------------------------------------------------------------ Database

Step 'Tạo database'
$DbName = Cfg 'DB_NAME' 'nhomdinhhinh'
$DbUser = Cfg 'DB_USER' 'root'
$DbPass = Cfg 'DB_PASSWORD' ''
if ($DbName -notmatch '^[A-Za-z0-9_]+$') { Fail "DB_NAME chỉ được dùng chữ, số và _ (đang là '$DbName')." }
$mysqlArgs = @('-h', '127.0.0.1', '-P', "$DbPort", '-u', $DbUser)
if ($DbPass -ne '') { $mysqlArgs += "-p$DbPass" }
& $Mysql @mysqlArgs -e "CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if ($LASTEXITCODE -ne 0) { Fail 'Không tạo được database. Kiểm tra DB_USER / DB_PASSWORD trong .env (XAMPP mặc định: root, mật khẩu trống).' }
Info "Database: $DbName"

# ------------------------------------------------------------------ WP-CLI

$Tools = Join-Path $Repo 'tools'
$WpCli = Join-Path $Tools 'wp-cli.phar'
if (-not (Test-Path $WpCli)) {
    Step 'Tải WP-CLI'
    New-Item -ItemType Directory -Force -Path $Tools -ErrorAction Stop | Out-Null
    Invoke-WebRequest -UseBasicParsing -Uri 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar' -OutFile $WpCli -ErrorAction Stop
}

$Site = Join-Path $WebRoot $Folder
$Url  = Cfg 'SITE_URL' "http://localhost/$Folder"

function wp {
    & $Php -d memory_limit=512M -d max_execution_time=0 $WpCli "--path=$Site" @args
}
function wp-must {
    wp @args
    if ($LASTEXITCODE -ne 0) { Fail "Lệnh WP-CLI thất bại: wp $($args -join ' ')" }
}

# ------------------------------------------------------------------ WordPress

Step "Cài WordPress vào $Site"
if (-not (Test-Path (Join-Path $Site 'wp-includes/version.php'))) {
    New-Item -ItemType Directory -Force -Path $Site -ErrorAction Stop | Out-Null
    wp-must core download --locale=vi
}
if (-not (Test-Path (Join-Path $Site 'wp-config.php'))) {
    wp-must config create "--dbname=$DbName" "--dbuser=$DbUser" "--dbpass=$DbPass" "--dbhost=127.0.0.1:$DbPort" --dbcharset=utf8mb4 --locale=vi
    wp-must config set WP_MEMORY_LIMIT 256M
    wp-must config set DISALLOW_FILE_EDIT true --raw
}
wp core is-installed 2>$null
if ($LASTEXITCODE -ne 0) {
    wp-must core install "--url=$Url" "--title=$(Cfg 'SITE_TITLE' 'Nhôm Định Hình')" `
        "--admin_user=$(Cfg 'ADMIN_USER' 'admin')" "--admin_password=$(Cfg 'ADMIN_PASSWORD')" `
        "--admin_email=$(Cfg 'ADMIN_EMAIL' 'admin@example.com')" --skip-email
}
wp language core install vi --activate 2>$null | Out-Null

# ------------------------------------------------------------------ Theme & plugin của repo

Step 'Gắn theme và plugin từ repo (liên kết, sửa code trong repo là site cập nhật ngay)'
foreach ($item in @('themes/nhom-dinh-hinh', 'plugins/nhom-gia-cong')) {
    $target = Join-Path $Repo "wp-content/$item"
    $link   = Join-Path $Site "wp-content/$item"
    if (Test-Path $link) { Info "Đã có: $item"; continue }
    if ($IsWin) { New-Item -ItemType Junction -Path $link -Target $target -ErrorAction Stop | Out-Null }
    else { New-Item -ItemType SymbolicLink -Path $link -Target $target -ErrorAction Stop | Out-Null }
    Info "Đã liên kết: $item"
}

Step 'Cài WooCommerce'
wp plugin is-installed woocommerce
if ($LASTEXITCODE -ne 0) { wp-must plugin install woocommerce }
wp-must plugin activate woocommerce
wp language plugin install woocommerce vi 2>$null | Out-Null
wp-must plugin activate nhom-gia-cong
wp plugin delete hello akismet 2>$null | Out-Null

Step 'Kích hoạt giao diện'
wp-must theme activate nhom-dinh-hinh
wp theme delete twentytwentythree twentytwentyfour 2>$null | Out-Null

# ------------------------------------------------------------------ Đường dẫn đẹp (.htaccess)

Step 'Bật đường dẫn đẹp'
wp-must option update permalink_structure '/%postname%/'
$base = ([Uri]$Url).AbsolutePath.TrimEnd('/') + '/'
$htaccess = @"
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase $base
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . ${base}index.php [L]
</IfModule>
# END WordPress
"@
Set-Content -LiteralPath (Join-Path $Site '.htaccess') -Value $htaccess -Encoding ASCII -ErrorAction Stop

# ------------------------------------------------------------------ Dữ liệu

Step 'Cấu hình cửa hàng & tạo dữ liệu mẫu'
wp-must eval-file (Join-Path $Repo 'scripts/seed.php')
wp-must rewrite flush
wp cache flush | Out-Null

Write-Host "`n============================================================" -ForegroundColor Green
Write-Host " XONG! Website:    $Url" -ForegroundColor Green
Write-Host "       Quản trị:   $Url/wp-admin" -ForegroundColor Green
Write-Host "       Tài khoản:  $(Cfg 'ADMIN_USER' 'admin') / (mật khẩu trong .env)" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
if ($IsWin -and -not $env:NDH_NO_BROWSER) { Start-Process $Url }

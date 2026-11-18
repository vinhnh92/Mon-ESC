#!/usr/bin/env bash
# Khởi tạo toàn bộ website: chạy 1 lần sau khi clone repo.
#   cp .env.example .env   (sửa mật khẩu)
#   ./scripts/setup.sh
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { echo "Chưa có .env — chạy: cp .env.example .env rồi sửa mật khẩu"; exit 1; }

echo "==> Khởi động database và WordPress"
docker compose up -d db wordpress

echo "==> Chờ WordPress chép mã nguồn vào volume"
for i in $(seq 1 60); do
  if docker compose exec -T wordpress test -f /var/www/html/wp-config.php 2>/dev/null; then break; fi
  sleep 2
done

echo "==> Cài đặt WordPress + WooCommerce"
docker compose run --rm wpcli sh /scripts/install.sh

echo
echo "Xong! Mở: $(grep ^SITE_URL .env | cut -d= -f2-)"
echo "Trang quản trị: $(grep ^SITE_URL .env | cut -d= -f2-)/wp-admin"

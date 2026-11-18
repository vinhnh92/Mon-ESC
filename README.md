# Mon ESC — Website bán hàng WordPress + WooCommerce

Shop bán ESC, motor và linh kiện FPV đã được tối ưu sẵn cho BLHeli / Bluejay / AM32.
Toàn bộ site dựng bằng Docker, cài đặt bằng **một lệnh**.

## Có gì trong repo

| Thư mục / file | Nội dung |
|---|---|
| `docker-compose.yml` | WordPress (PHP 8.3) + MariaDB 11.4 + WP-CLI |
| `wp-content/themes/mon-esc/` | Theme riêng: responsive, tiếng Việt, tối ưu cho WooCommerce |
| `wp-content/plugins/mon-esc-shop/` | Plugin tuỳ biến shop (xem bên dưới) |
| `scripts/setup.sh` | Cài đặt toàn bộ site tự động |
| `scripts/install.sh`, `scripts/seed.php` | Cấu hình WooCommerce + dữ liệu mẫu (chạy trong container) |

### Tính năng

- **Trang chủ**: banner, cam kết dịch vụ, danh mục, sản phẩm nổi bật / giảm giá / mới về, khối tư vấn.
- **WooCommerce cấu hình cho Việt Nam**: tiền VNĐ (`790.000 ₫`), giao hàng chỉ trong VN, múi giờ Asia/Ho_Chi_Minh, ngôn ngữ tiếng Việt.
- **Thanh toán**: COD + chuyển khoản ngân hàng (số tài khoản lấy từ `.env`).
- **Vận chuyển**: đồng giá 30.000đ, miễn phí cho đơn từ 1.000.000đ.
- **Checkout kiểu Việt**: một ô "Họ và tên", bắt buộc số điện thoại, email không bắt buộc, bỏ mã bưu chính / công ty.
- **Thông số ESC** cho từng sản phẩm (firmware, dòng, cell, MCU, kích thước): hiện thành chip trên trang sản phẩm, tab "Thông số kỹ thuật", nhãn firmware ngoài danh sách.
- **Lọc theo firmware** trên trang cửa hàng (`?firmware=Bluejay`).
- **Nút Zalo / Gọi điện nổi**, nhãn giảm giá theo `%`, giỏ hàng cập nhật AJAX.
- 12 sản phẩm mẫu, 6 danh mục, các trang Giới thiệu / Hướng dẫn BLHeli / Chính sách / Liên hệ, menu chính + chân trang.

## Cài đặt (máy local hoặc VPS)

Yêu cầu: Docker + Docker Compose.

```bash
cp .env.example .env      # rồi sửa mật khẩu, số điện thoại, tài khoản ngân hàng
./scripts/setup.sh
```

Mở http://localhost:8080 — quản trị tại http://localhost:8080/wp-admin (tài khoản trong `.env`).

Script có thể chạy lại nhiều lần an toàn (không tạo trùng sản phẩm, trang, menu).
Không muốn sản phẩm mẫu: đặt `SEED_DEMO=0` trước khi chạy.

## Vận hành hằng ngày

| Việc | Ở đâu |
|---|---|
| Thêm / sửa sản phẩm, nhập thông số ESC | *Sản phẩm → Thêm mới* (mục "Thông số kỹ thuật" trong tab Chung) |
| Xem & xử lý đơn hàng | *WooCommerce → Đơn hàng* |
| Đổi hotline / Zalo | *Cài đặt → Tổng quan* |
| Đổi logo | *Giao diện → Tuỳ biến → Nhận diện site* |
| Sửa menu | *Giao diện → Menu* |
| Phí ship, thanh toán | *WooCommerce → Cài đặt → Vận chuyển / Thanh toán* |
| Bộ lọc giá, danh mục ở cửa hàng | *Giao diện → Widget → Sidebar cửa hàng* |

Lệnh WP-CLI bất kỳ:

```bash
docker compose run --rm wpcli wp --path=/var/www/html plugin list
```

Sao lưu database:

```bash
mkdir -p backups && docker compose exec -T db sh -c 'mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' > backups/db-$(date +%F).sql
```

## Đưa lên Internet

1. Thuê VPS (Ubuntu), cài Docker, clone repo, tạo `.env` với `SITE_URL=https://ten-mien-cua-ban.vn`.
2. Chạy `./scripts/setup.sh`.
3. Đặt reverse proxy có HTTPS (Caddy / Nginx + Let's Encrypt) trỏ về cổng `SITE_PORT`.
4. Nếu đổi tên miền sau khi cài:
   `docker compose run --rm wpcli wp --path=/var/www/html search-replace 'http://localhost:8080' 'https://ten-mien-cua-ban.vn'`

## Gợi ý mở rộng

- Thanh toán online: plugin VNPay / MoMo / ZaloPay cho WooCommerce.
- Vận chuyển: plugin GHN / GHTK để tính phí theo địa chỉ.
- SEO: Yoast SEO hoặc Rank Math. Cache: LiteSpeed Cache / WP Super Cache.

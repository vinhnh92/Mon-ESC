# Website bán nhôm định hình — WordPress + WooCommerce

Shop bán **thanh nhôm định hình cắt theo chiều dài (mm), khoan lỗ, taro ren** và **phụ kiện** để dựng khung bàn, tủ, vỏ máy, cơ cấu cơ khí.
Cài thẳng lên Windows bằng XAMPP hoặc Laragon — **không cần Docker**.

## Tính năng chính

**Đặt gia công ngay trên trang sản phẩm**
- Khách nhập **chiều dài (mm)**, thêm **lỗ khoan** (vị trí tính từ đầu A, đường kính, mặt 1–4), chọn **taro ren đầu A / đầu B**, ghi chú.
- Nút **Chia đều…** chia nhanh nhiều lỗ cách đều.
- **Sơ đồ thanh nhôm** vẽ trực tiếp vị trí lỗ và đầu taro; báo đỏ lỗ đặt sai.
- **Giá tự tính** và hiện ngay:
  `giá 1 thanh = đơn giá/m × chiều dài + phí cắt + phí khoan × số lỗ + phí taro × số đầu` (làm tròn lên 100đ).
  Giá được tính lại trên server, khách không sửa được.
- Thông số gia công hiện trong giỏ hàng, email và **chi tiết đơn hàng** (để xưởng làm theo).

**Cửa hàng**
- Tiền VNĐ, chỉ giao trong Việt Nam, giao diện tiếng Việt, chạy tốt trên điện thoại.
- Thanh toán: COD, chuyển khoản (số tài khoản lấy từ `.env`).
- Vận chuyển: nhận tại xưởng (miễn phí), giao tận nơi 50.000đ, miễn phí từ 2.000.000đ.
- Checkout kiểu Việt: một ô "Họ và tên", bắt buộc số điện thoại, có ô công ty để xuất hoá đơn.
- Nút Zalo / Gọi điện nổi.
- Dữ liệu mẫu: nhôm 2020, 3030, 4040, 4080, 4545 (bạc/đen) và 12 phụ kiện (ke góc, ốc T, bản lề, tay nắm, chân tăng chỉnh, bánh xe, nắp bịt…), các trang hướng dẫn và chính sách.

## Cài đặt trên Windows

1. Cài **XAMPP** (miễn phí, https://www.apachefriends.org — bản PHP 8.2 trở lên, vào `C:\xampp`) hoặc **Laragon**.
2. Mở **XAMPP Control Panel** → bấm **Start** ở **Apache** và **MySQL** (Laragon: **Start All**).
3. Tải repo này về máy (Code → Download ZIP rồi giải nén, hoặc `git clone`).
4. Bấm đúp **`cai-dat-windows.bat`**.
   - Lần đầu, Notepad mở file `.env`: sửa mật khẩu quản trị, hotline, tài khoản ngân hàng → **Lưu** → đóng Notepad.
   - Script tự: tạo database, tải WordPress tiếng Việt + WooCommerce, gắn theme/plugin, cấu hình cửa hàng, tạo dữ liệu mẫu.
5. Xong, trình duyệt mở **http://localhost/nhomdinhhinh** — quản trị tại **/wp-admin**.

Chạy lại `cai-dat-windows.bat` bao nhiêu lần cũng được (không tạo trùng).
Không muốn sản phẩm mẫu: đặt `SEED_DEMO=0` trong `.env` trước khi chạy.
XAMPP/Laragon ở ổ khác: thêm `STACK_DIR=E:\xampp` vào `.env`.

Theme và plugin được **liên kết** (junction) từ thư mục repo vào WordPress, nên sửa code trong repo là site cập nhật ngay.

## Quản lý hằng ngày

| Việc | Ở đâu trong /wp-admin |
|---|---|
| Thêm thanh nhôm mới | *Sản phẩm → Thêm mới* → tab **Chung**: tick **Cắt theo chiều dài**, nhập đơn giá/m, phí cắt, phí khoan + đường kính, phí taro + cỡ ren, thông số kỹ thuật |
| Thêm phụ kiện | *Sản phẩm → Thêm mới* (sản phẩm thường, không tick "Cắt theo chiều dài") |
| Xem đơn & thông số gia công | *WooCommerce → Đơn hàng* → mở đơn: mỗi thanh ghi chiều dài, lỗ khoan, taro, ghi chú |
| Đổi hotline / Zalo | *Cài đặt → Tổng quan* |
| Phí ship, thanh toán | *WooCommerce → Cài đặt → Vận chuyển / Thanh toán* |
| Logo, menu | *Giao diện → Tuỳ biến* / *Giao diện → Menu* |

## Cấu trúc repo

| Đường dẫn | Nội dung |
|---|---|
| `cai-dat-windows.bat` | Bấm đúp để cài |
| `scripts/install-windows.ps1` | Trình cài (tìm XAMPP/Laragon, tạo DB, WP-CLI, WordPress, WooCommerce) |
| `scripts/seed.php` | Cấu hình WooCommerce + danh mục, sản phẩm mẫu, trang, menu |
| `wp-content/plugins/nhom-gia-cong/` | Plugin: cấu hình cắt/khoan/taro, tính giá, lưu thông số vào đơn, checkout VN |
| `wp-content/themes/nhom-dinh-hinh/` | Giao diện cửa hàng |

## Đưa lên Internet (sau này)

Thuê hosting/VPS có PHP 8.x + MySQL, cài WordPress + WooCommerce, chép thư mục `wp-content/themes/nhom-dinh-hinh` và `wp-content/plugins/nhom-gia-cong` lên, kích hoạt, rồi chạy `wp eval-file scripts/seed.php` (hoặc chuyển nguyên site bằng plugin All-in-One WP Migration).

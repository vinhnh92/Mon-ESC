#!/bin/sh
# Chạy bên trong container wpcli (xem scripts/setup.sh). Có thể chạy lại nhiều lần.
set -eu
wp() { command wp --path=/var/www/html "$@"; }

if ! wp core is-installed 2>/dev/null; then
  wp core install \
    --url="$SITE_URL" --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASSWORD" \
    --admin_email="$ADMIN_EMAIL" --skip-email
fi

echo "-- Ngôn ngữ & thời gian"
wp language core install vi --activate || true
wp option update timezone_string "Asia/Ho_Chi_Minh"
wp option update date_format "d/m/Y"
wp option update time_format "H:i"
wp option update start_of_week 1
wp option update blogdescription "$SITE_TAGLINE"
wp rewrite structure '/%postname%/' --hard

echo "-- Plugin"
wp plugin is-installed woocommerce || wp plugin install woocommerce
wp plugin activate woocommerce
wp language plugin install woocommerce vi || true
wp plugin activate mon-esc-shop
wp plugin delete hello akismet 2>/dev/null || true

echo "-- Giao diện"
wp theme activate mon-esc
wp theme delete twentytwentythree twentytwentyfour 2>/dev/null || true

echo "-- Cấu hình WooCommerce (VNĐ, Việt Nam)"
wp option update woocommerce_default_country "VN"
wp option update woocommerce_store_address "$STORE_ADDRESS"
wp option update woocommerce_store_city "$STORE_CITY"
wp option update woocommerce_currency "VND"
wp option update woocommerce_currency_pos "right_space"
wp option update woocommerce_price_thousand_sep "."
wp option update woocommerce_price_decimal_sep ","
wp option update woocommerce_price_num_decimals 0
wp option update woocommerce_weight_unit "g"
wp option update woocommerce_dimension_unit "mm"
wp option update woocommerce_allowed_countries "specific"
wp option update woocommerce_specific_allowed_countries '["VN"]' --format=json
wp option update woocommerce_ship_to_countries ""
wp option update woocommerce_calc_taxes "no"
wp option update woocommerce_enable_guest_checkout "yes"
wp option update woocommerce_checkout_phone_field "required"
wp option update woocommerce_checkout_company_field "hidden"
wp option update woocommerce_checkout_address_2_field "hidden"
wp option update woocommerce_enable_reviews "yes"
wp option update woocommerce_coming_soon "no"
wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json
wp option update woocommerce_task_list_hidden "yes" || true

wp wc tool run install_pages --user="$ADMIN_USER" >/dev/null

echo "-- Thông tin cửa hàng"
wp option update mon_esc_phone "$STORE_PHONE"
wp option update mon_esc_zalo "$STORE_ZALO"

echo "-- Dữ liệu: thanh toán, vận chuyển, trang, menu, sản phẩm mẫu"
wp eval-file /scripts/seed.php

wp rewrite flush --hard
wp cache flush
echo "-- Hoàn tất"

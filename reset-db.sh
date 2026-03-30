#!/bin/bash

# ============================================================
# V3 Veritabanını Sıfırla ve V1'den Yeniden Yükle
# ============================================================
# Kullanım: ./reset-db.sh
# Bu script:
#   1. V3 DB'yi migrate:fresh ile sıfırlar (tüm tablolar yeniden oluşturulur)
#   2. Tüm seeder'ları çalıştırır
#   3. V1'den verileri migre eder
# ============================================================

set -e

echo "🔄 V3 Veritabanını Sıfırla ve Yeniden Yükle"
echo "================================================"

# 1. Cache temizle
echo "📦 Cache temizleniyor..."
php artisan config:cache
php artisan cache:clear

# 2. Tüm migration'ları sıfırla ve yeniden çalıştır
echo "🗄️  Tüm tablolar sıfırlanıyor ve yeniden oluşturuluyor..."
php artisan migrate:fresh --seed

# 3. V1'den verileri migre et
echo "📤 V1'den veriler migre ediliyor..."
php artisan db:migrate-from-v1

echo ""
echo "✅ V3 Veritabanı Başarıyla Yüklendi!"
echo ""
echo "İstatistikler:"
mysql -h127.0.0.1 -uroot -ppassword hayvakfi_yonetim_v3 << 'EOF' 2>&1 | grep -v Warning
  SELECT CONCAT('  • Kullanıcılar: ', COUNT(*)) FROM users
  UNION ALL
  SELECT CONCAT('  • Kasalar: ', COUNT(*)) FROM safes
  UNION ALL
  SELECT CONCAT('  • İşlemler: ', COUNT(*)) FROM safe_transactions
  UNION ALL
  SELECT CONCAT('  • İşlem Satırları: ', COUNT(*)) FROM safe_transaction_items
  UNION ALL
  SELECT CONCAT('  • Kişiler: ', COUNT(*)) FROM contacts;
EOF

echo ""
echo "🎯 Giriş Yapabilecek Kullanıcılar:"
mysql -h127.0.0.1 -uroot -ppassword hayvakfi_yonetim_v3 << 'EOF' 2>&1 | grep -v Warning
  SELECT CONCAT('  • ', name, ' - ', phone) FROM users WHERE can_login = 1 AND is_active = 1 LIMIT 8;
EOF

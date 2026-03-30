# V1 → V3 Veri Migration Rehberi

## 🚀 Hızlı Start

### Seçenek 1: Script ile (Önerilen)
```bash
./reset-db.sh
```

Bu script otomatik olarak:
1. ✅ V3 veritabanını sıfırlar (tüm tablolar yeniden oluşturulur)
2. ✅ Seeder'ları çalıştırır (temel veriler: para birimleri, kategoriler, şirketi)
3. ✅ V1'den tüm verileri migre eder

### Seçenek 2: Adım adım manuel
```bash
# 1. V3 DB'yi sıfırla
php artisan migrate:fresh --seed

# 2. V1'den migre et
php artisan db:migrate-from-v1
```

---

## 📊 Migration Detayları

### Migre Edilen Tablolar (9 adım)

| Sıra | Tablo | V1 Sayı | V3 Sayı | Notlar |
|------|-------|---------|---------|--------|
| 1 | currencies | 3 | 3 | TRY, USD, EUR |
| 2 | users | 10 | 10 | Telefon format normalize edildi |
| 3 | company_user | 10 | 10 | Tüm kullanıcılar HAYVAKFI şirketine atandı |
| 4 | safe_groups | 5 | 5 | Kasa grupları |
| 5 | safes | 19 | 19 | Kasalar (bakiyeler korundu) |
| 6 | safe_transaction_categories | 30 | 43 | V1 flat kategorileri → V3 tree yapısı |
| 7 | contacts | 1 | 1 | Donörler contacts'a dönüştürüldü |
| 8 | safe_transactions | 4679 → 4589 | 4589 | Duplikeler filtreli (90 duplicate atlandı) |
| 9 | safe_transaction_items | 0 → 4589 | 4589 | Her transaction'ın 1+ item'ı var |

### Önemli Notlar

- **V1 hiç değiştirilmez** — sadece okunur
- **Duplicate transactions** — Aynı kasada aynı bank_payment_id olan işlemler skip edilir
- **Telefon normalize** — `05XX...` ve `+905XX...` formları otomatik eşleştirilir
- **Kategoriler tree yapısında** — V1 flat kategorileri V3'te parent_id ile bağlandı

---

## 🔐 Giriş Bilgileri

Migration sonrası giriş yapabileceğiniz kullanıcılar:

```
Telefon Numarası          | Şifre (V1'deki orijinal)
--------------------------|------------------------
05556260886               | ****
05372374634               | ****
05074080107               | ****
05334106992               | ****
05549296442               | ****
05071497855               | ****
05512093544               | ****
05066571194               | ****
```

> **Not**: V1 şifreleriniz olduğu gibi migre edilmiştir. Admin panel giriş sayfası otomatik olarak telefon numarasını normalize eder.

---

## 🛠️ Komutlar

### Dry-Run (test, veri yazılmaz)
```bash
php artisan db:migrate-from-v1 --dry-run
```
Çıkışta kaç kayıt migre edileceğini gösterir.

### Normal Migration
```bash
php artisan db:migrate-from-v1
```
Sadece eksik verileri ekler (idempotent).

### Fresh Migration (sıfırdan)
```bash
php artisan db:migrate-from-v1 --fresh
```
Önceki verileri siler, V1'den yeniden yükler.

---

## 📋 Checklist: Veriler Doğru Migre Edildi mi?

```bash
# 1. Tüm tabloları kontrol et
mysql -h127.0.0.1 -uroot -ppassword hayvakfi_yonetim_v3 << 'EOF'
SELECT 'Users' as table_name, COUNT(*) as count FROM users
UNION ALL SELECT 'Safes', COUNT(*) FROM safes
UNION ALL SELECT 'Transactions', COUNT(*) FROM safe_transactions
UNION ALL SELECT 'Transaction Items', COUNT(*) FROM safe_transaction_items;
EOF

# 2. Bakiyeleri kontrol et (örnek)
mysql -h127.0.0.1 -uroot -ppassword hayvakfi_yonetim_v3 << 'EOF'
SELECT name, balance FROM safes ORDER BY id LIMIT 5;
EOF

# 3. Admin panel'e gir ve doğrula
php artisan serve
# http://localhost:8000/admin
```

---

## ⚠️ Sorun Giderme

### "Telefon kullanıcısı bulunamadı"
- Telefonu kontrol edin: `05XX...` veya `+905XX...` formatı
- Boşluk veya özel karakter olmadığından emin olun
- `can_login` ve `is_active` true olmalı

### "Şirketi seçilmedi"
- Kullanıcı company_user tablosunda olmalı
- HAYVAKFI şirketi (id=1) varlığını kontrol et

### Migration yavaş
- İlk kez ~4700 işlem taşınması ~5-10 dakika alabilir
- `--dry-run` ile ön test yapın

---

## 🔄 Tekrar Çalıştırma

Eğer ileride:
- Tüm verileri sıfırlamak istiyorsanız: `./reset-db.sh`
- Sadece V1'den yeni verileri eklemek: `php artisan db:migrate-from-v1`
- Tüm migration komutlarını görmek: `php artisan help db:migrate-from-v1`

---

## 📁 İlgili Dosyalar

- Command: `app/Console/Commands/MigrateFromV1Command.php`
- Migrators: `app/Services/V1Migration/`
  - `CurrencyMigrator.php`
  - `UserMigrator.php`
  - `SafeGroupMigrator.php`
  - `SafeMigrator.php`
  - `CategoryMigrator.php`
  - `ContactMigrator.php`
  - `TransactionMigrator.php`
  - `TransactionItemMigrator.php`
- Config: `.env` (V1 connection: `DB_V1_*`)

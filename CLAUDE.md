# VAKIF YÖNETİM SİSTEMİ v3 — CLAUDE.md
# ================================================================
# Bu dosya projenin tüm geliştirme kurallarını, mimari kararlarını,
# veritabanı şemasını ve geliştirme fazlarını içerir.
# Tüm kod yazımı bu kurallara uygun olmalıdır.
# ================================================================

---

## KOMUTLAR

```bash
# Kurulum
composer run setup          # Bağımlılıkları kur, key üret, migrate, asset build

# Geliştirme
composer run dev            # Sunucu + queue + log + Vite HMR paralel başlatır
php artisan serve           # Sadece backend (localhost:8000)
npm run dev                 # Sadece Vite

# Build
npm run build

# Test
composer run test           # Config cache temizle + PHPUnit
php artisan test
php artisan test --filter TestName
php artisan test tests/Feature/ExampleTest.php

# Veritabanı
php artisan migrate
php artisan migrate:fresh --seed
```

---

## 1. TEKNOLOJİ VERSİYONLARI

| Paket | Versiyon | Not |
|-------|----------|-----|
| PHP | 8.2 | Kesinlikle bu versiyonda kalınır |
| Laravel | 12.x | PHP 8.2 uyumlu |
| Filament | 5.x | Admin panel |
| filament-shield | 4.x | Yetkilendirme |
| spatie/laravel-activitylog | 4.x | Loglama |
| MySQL | 8.0+ / MariaDB 10.6+ | |

---

## 2. MİMARİ YAPI

```
Controller / Filament Resource
        ↓
    Service Layer   (app/Services/)      — iş mantığı burada
        ↓
    Repository Layer (app/Repositories/) — DB sorguları burada
        ↓
    Model (app/Models/)                  — Eloquent
```

- **Controller / Resource**: İş mantığı YAZILMAZ, Service çağrılır.
- **Service**: Birbirini inject edebilir. Repository inject eder. Constructor injection.
- **Repository**: `BaseRepository`'den extend. Doğrudan Eloquent query BURAYA yazılır, başka yere değil.
- **Enum**: `app/Enums/` — PHP 8.1+ native backed enum. Magic string/number yasak. Her enum'da `label()` metodu.

### Dizin Yapısı

```
app/
├── Enums/
├── Models/
├── Repositories/
│   └── BaseRepository.php
├── Services/
├── Traits/
│   └── LogsActivity.php
├── Filament/
│   ├── Pages/Auth/Login.php   ← Custom login (telefon + şirket)
│   └── Resources/
└── Providers/Filament/AdminPanelProvider.php
```

---

## 3. KOD YAZIM KURALLARI

- `declare(strict_types=1)` her dosyada
- PSR-12
- Type declaration zorunlu (parametre + dönüş tipleri)
- Nullable açıkça belirtilir: `?string`
- Tüm property/method'larda visibility belirtilir
- Tüm tablolarda: soft delete + timestamps + foreign key constraint
- Raw SQL query **yasak** — Eloquent query builder kullanılır
- Mass assignment: `$fillable` kullanılır

### Naming

| Şey | Kural | Örnek |
|-----|-------|-------|
| Model | PascalCase, tekil | `SafeTransaction` |
| Migration | snake_case | `create_safe_transactions_table` |
| Service | PascalCase + Service | `SafeTransactionService` |
| Repository | PascalCase + Repository | `SafeTransactionRepository` |
| Enum | PascalCase, case UPPER_SNAKE | `TransactionType::INCOME` |
| Tablo | snake_case, çoğul | `safe_transactions` |
| Foreign key | snake_case + _id | `safe_id`, `company_id` |
| Kod dili | İngilizce | değişken, metod adları |
| UI dili | Türkçe | label, mesaj, navigasyon |

---

## 4. ÇOK ŞİRKETLİ YAPI

- Her şirket bazlı tablo `company_id` taşır.
- `CompanyScope` global scope tüm şirket bazlı modellere uygulanır.
- Aktif şirket `session('active_company_id')` ile belirlenir.
- **`contacts` ve `currencies` tabloları CompanyScope DIŞINDADIR** (global/ortak).
- Kullanıcılar birden fazla şirkete atanabilir (`company_user` pivot).

---

## 5. KİMLİK DOĞRULAMA

- **Email kullanılmaz.** Giriş: Telefon Numarası + Şifre + Şirket Seçimi
- `users` tablosunda email alanı **yoktur**
- `can_login = true` VE `is_active = true` — ikisi birden sağlanmalı
- Login ekranında yalnızca kullanıcının yetkili olduğu şirketler listelenir
- Telefon formatı: Türkiye (05XX XXX XX XX)
- Admin panel path: `/admin`

---

## 6. YETKİLENDİRME & LOGLAMA

- `filament-shield`: her Resource için otomatik permission
- `super_admin` rolü tüm yetkilere sahip
- Permission format: `view_safe`, `create_safe`, `update_safe`, `delete_safe`
- Her CRUD işlemi `spatie/laravel-activitylog` ile loglanır
- Log kaydında: kim, ne zaman, hangi şirkette, ne yaptı

---

## 7. VERİTABANI ŞEMASI

### 7.1 Temel Altyapı

```sql
currencies          -- Global, company_id YOK
  id, name, symbol, is_active, timestamps, soft_delete
  -- Seed: TRY, USD, EUR

companies
  id, name, tax_number, address, phone, is_active, timestamps, soft_delete

users               -- Email alanı YOK
  id, name, phone (unique), password, can_login, is_active,
  default_company_id (FK), timestamps, soft_delete

company_user        -- Pivot
  id, company_id (FK), user_id (FK), timestamps
```

### 7.2 Kişi Yönetimi — Global Ortak Havuz

```sql
contacts            -- company_id YOK, tüm şirketlerde ortak
  id, first_name, last_name, phone, national_id, birth_date,
  address, city,
  is_donor          (bool, default false),
  is_aid_recipient  (bool, default false),
  is_student        (bool, default false),
  notes, created_user_id (FK: users),
  timestamps, soft_delete
  -- INDEX: phone, national_id (arama için)
```

### 7.3 Kasa Grubu & Kasa

```sql
safe_groups
  id, company_id (FK),
  name, is_active,
  is_api_integration  (bool — true: banka API entegrasyonu),
  credentials         (JSON, encrypted — API erişim bilgileri),
  created_user_id (FK: users),
  timestamps, soft_delete

safes
  id, company_id (FK), safe_group_id (FK),
  name,
  iban                (nullable — banka hesabı için),
  currency_id         (FK: currencies),
  balance             (decimal 15,4 — lockForUpdate ile güncellenir),
  is_active, sort_order,
  last_processed_at   (nullable datetime — son API çekme),
  integration_id      (nullable — harici sistem ID),
  created_user_id (FK: users),
  timestamps, soft_delete
  -- NOTE: safe_group.is_api_integration = true ise, bu kasaya manuel işlem girilemez
```

### 7.4 İşlem Kategorileri

```sql
safe_transaction_categories   -- Self-referential hiyerarşi
  id, company_id              (nullable — null: sistem geneli, tüm şirketlerde görünür),
  name,
  type                        (ENUM: income/expense — nullable),
  parent_id                   (FK: safe_transaction_categories — nullable),
  sort_order, is_active,
  is_disable_in_report        (bool — transfer/döviz/sistem kategorilerini raporda gizler),
  is_sacrifice_type           (bool — kurban türü kategorileri için, yalnızca Vacip ve Nafile: true),
  contact_type                (ENUM: donor/aid_recipient/student — nullable),
  color                       (varchar 10),
  description                 (nullable),
  created_user_id (FK: users),
  timestamps, soft_delete

-- Sorgu paterni: WHERE company_id IS NULL OR company_id = :active_company_id

-- Sistem kategorileri (Seeder — sabit ID'ler, sıra bozulmaz):
--   ID 1 → Hesaplar Arası Para Transferleri  (is_disable_in_report: true)
--   ID 2 → Döviz İşlemleri                   (is_disable_in_report: true)
--   ID 3 → ATAMA BEKLİYOR                    (is_disable_in_report: false)
--   ID 4 → Açılış                            (income, is_active: false)
--   ID 5 → Bağış                             (income, parent: null)
--            └── Genel           (parent: 5)
--            └── Zekat           (parent: 5)
--            └── Fitre           (parent: 5)
--            └── Kumanya         (parent: 5)
--            └── Öğrenci İftarı  (parent: 5)
--            └── Hatim           (parent: 5)
--   ID 12 → Kurban                           (income, parent: null)
--            └── Vacip           (parent: 12, is_sacrifice_type: true)
--            └── Akika           (parent: 12, is_sacrifice_type: false)
--            └── Sadaka          (parent: 12, is_sacrifice_type: false)
--            └── Adak            (parent: 12, is_sacrifice_type: false)
--            └── Nafile          (parent: 12, is_sacrifice_type: true)
```

### 7.5 Kasa İşlemleri

```sql
safe_transactions
  id, company_id (FK), safe_id (FK),

  type              (ENUM: income / expense),
  operation_type    (ENUM: exchange / transfer — nullable, normal işlemde null),

  -- Tutar alanları
  total_amount      (decimal 15,4) — kasanın kendi para biriminde kesinleşmiş tutar (items toplamı)
  currency_id       (FK: currencies — nullable) — işlemin para birimi
  exchange_rate     (decimal 10,4 — nullable) — 1 yabancı = X kasa para birimi
                    -- Döviz işleminde: total_amount = base_amount × exchange_rate (servis hesaplar)
  item_rate         (decimal 10,4 — nullable) — birim fiyat (kurban: 3 adet × 5000 = 15000)

  -- Transfer / döviz eşi (ayrı tablo YOK, transaction üzerinde tutulur)
  target_safe_id          (FK: safes — nullable),
  target_transaction_id   (FK: safe_transactions — nullable),

  -- Kişi & kullanıcı
  contact_id        (FK: contacts — nullable),
  reference_user_id (FK: users — nullable),
  created_user_id   (FK: users),

  -- Zaman
  process_date      (date — resmi işlem tarihi),
  transaction_date  (datetime — nullable, API/banka zaman damgası),

  -- Audit & entegrasyon
  balance_after_created (decimal 15,4) — işlem anı bakiye snapshot, değişmez
  integration_id    (nullable) — banka benzersiz ID, duplicate koruması
  import_file       (nullable) — toplu import kaynak dosyası
  is_show           (bool, default true) — false: rapor dışı

  description (nullable), timestamps, soft_delete

  -- INDEX: UNIQUE(safe_id, integration_id) WHERE integration_id IS NOT NULL

safe_transaction_items    -- Split satırları
  id, company_id (FK),
  transaction_id          (FK: safe_transactions),
  transaction_category_id (FK: safe_transaction_categories),
  donation_category_id    (FK: safe_transaction_categories — nullable),
                          -- Bağış işleminde: üst kategori (Bağış-Kurban) + alt kategori (Akika)
  amount (decimal 15,4),
  timestamps, soft_delete
  -- KURAL: SUM(items.amount) = safe_transactions.total_amount
```

### 7.6 Eğitim

```sql
school_classes
  id, company_id (FK),
  name,
  teacher_id      (FK: users — nullable, şirkete atanmış kullanıcı),
  start_date, end_date,
  default_monthly_fee (decimal 10,2),
  capacity (nullable), is_active,
  timestamps, soft_delete

student_enrollments
  id, company_id (FK), class_id (FK), contact_id (FK: contacts),
  enrollment_date,
  monthly_fee     (decimal 10,2 — null ise class.default_monthly_fee kullanılır),
  is_active, timestamps, soft_delete
  UNIQUE: (class_id, contact_id)

student_fees
  id, company_id (FK), enrollment_id (FK),
  period          (date — ayın 1'i: 2026-03-01 = Mart 2026),
  amount (decimal 10,2), due_date,
  paid_at         (nullable datetime),
  payment_transaction_id (FK: safe_transactions — nullable),
  status          (ENUM: pending / paid / overdue / waived),
  timestamps, soft_delete
  UNIQUE: (enrollment_id, period)
```

### 7.7 Yardım Kayıtları

```sql
aid_records
  id, company_id (FK), contact_id (FK: contacts),
  transaction_id  (FK: safe_transactions — nullable),
  aid_type, description (nullable),
  amount (decimal 10,2), given_at (date),
  created_user_id (FK: users),
  timestamps, soft_delete
```

---

## 8. KRİTİK İŞ KURALLARI

### Kasa Bakiyesi
- `safes.balance` her işlemde `SafeService::updateBalance()` ile `lockForUpdate()` kullanılarak güncellenir.
- Bakiye < 0 olamaz — gider/transfer öncesi kontrol, yetersizse exception.
- `balance_after_created` işlem anında snapshot alınır, bir daha değişmez.

### Manuel İşlem Kontrolü
- `safe_group.is_api_integration = true` ise, panelden manuel işlem girilemez.
- `SafeTransactionService::create()` — manual giriş kontrolü: `safeGroup->is_api_integration = true` ise exception
- `SafeTransactionService::createFromApi()` — API işlemleri bu kontrolü bypass eder

### Split İşlem Kuralı
- `SUM(safe_transaction_items.amount)` = `safe_transactions.total_amount` zorunludur.
- `DB::transaction` içinde kontrol edilir; eşit değilse rollback.
- Tek kategori olsa bile `safe_transaction_items` kaydı oluşturulur.

### Döviz İşlemi
```
total_amount = amount × exchange_rate   (servis hesaplar, formdan alınmaz)
Örnek: 1000 USD × 32.0000 = 32.000 TRY
```

### Kasalar Arası Transfer
```
-- İki transaction atomik oluşturulur (DB::transaction):
[1] safe_id=A, type=expense, operation_type=transfer, target_safe_id=B, target_transaction_id=→[2]
[2] safe_id=B, type=income,  operation_type=transfer, target_safe_id=A, target_transaction_id=→[1]
-- Ayrı cashbox_transfers tablosu YOK.
```

### Duplicate API Koruması
- `UNIQUE(safe_id, integration_id)` — aynı banka işlemi iki kez kaydedilmez.

### Öğrenci Kaydı Yan Etkileri
- `student_enrollments` kaydı oluşturulunca → `contact.is_student = true` set edilir.
- `aid_records` kaydı oluşturulunca → `contact.is_aid_recipient = true` set edilir.

### Aidat Ödeme Akışı
```
StudentFeeService::markAsPaid(fee_id, safe_id):
  1. SafeTransactionService::create() → INCOME, kategori: Öğrenci Aidat
  2. student_fees.payment_transaction_id = yeni transaction.id
  3. student_fees.status = PAID, paid_at = now()
  Tümü DB::transaction içinde.
```

---

## 9. ENUM LİSTESİ

| Enum | Değerler |
|------|----------|
| `TransactionType` | `INCOME`, `EXPENSE` |
| `OperationType` | `EXCHANGE`, `TRANSFER` |
| `FeeStatus` | `PENDING`, `PAID`, `OVERDUE`, `WAIVED` |
| `ContactType` | `DONOR`, `AID_RECIPIENT`, `STUDENT` |

---

## 10. GELİŞTİRME FAZLARI

### FAZ 1 — Temel Altyapı ✦ İLK BAŞLANACAK
Currencies, Companies, Users, Authentication (telefon+şirket), CompanyScope, Shield, ActivityLog

### FAZ 2 — Kişi Yönetimi  *(FAZ 1 sonrası, FAZ 3 ile paralel)*
Contacts tablosu (is_donor / is_student / is_aid_recipient bayrakları), ContactResource

### FAZ 3 — Kasa & Finans  *(FAZ 1 sonrası, FAZ 2 ile paralel)*
SafeTransactionCategorySeeder → SafeGroup → Safe → SafeTransaction + SafeTransactionItem
(split kayıt, döviz, transfer, API entegrasyon altyapısı)

### FAZ 4 — Eğitim  *(FAZ 2 + FAZ 3 bittikten sonra)*
SchoolClass (teacher_id→users), StudentEnrollment, StudentFee (aidat oluşturma + ödeme)

### FAZ 5 — Yardım Takibi  *(FAZ 2 + FAZ 3 bittikten sonra)*
AidRecord (contact + opsiyonel safe transaction bağlantısı)

### FAZ 6 — Raporlama & Dashboard  *(Tüm fazlar bittikten sonra)*
Kasa özeti, kategori dağılımı, kurban raporu, bağışçı raporu, aidat raporu, dashboard widget'ları

---

## 11. FİLAMENT RESOURCE LİSTESİ

| Resource | Navigasyon Grubu |
|----------|-----------------|
| `CompanyResource` | Yönetim |
| `UserResource` | Yönetim |
| `CurrencyResource` | Yönetim > Tanımlar |
| `ContactResource` | Kişiler |
| `SafeGroupResource` | Kasa |
| `SafeResource` | Kasa |
| `SafeTransactionCategoryResource` | Kasa > Kategoriler |
| `SafeTransactionResource` | Kasa |
| `SchoolClassResource` | Eğitim |
| `StudentEnrollmentResource` | Eğitim |
| `StudentFeeResource` | Eğitim |
| `AidResource` | Yardım |

---

## 12. FİLAMENT HEADER ACTION BUTON RENK STANDARTLARI

Tüm Filament Resource sayfalarında header action butonları için tutarlı renk kullanımı:

| Aksiyon Tipi | Renk | Örnek |
|--------------|------|-------|
| **CreateAction** | (varsayılan) | `CreateAction::make()` — renk belirtilmez, Filament `primary` kullanır |
| **EditAction** | (varsayılan) | `EditAction::make()` — renk belirtilmez, Filament `primary` kullanır |
| **DeleteAction** | (varsayılan) | `DeleteAction::make()` — renk belirtilmez, Filament `danger` kullanır |
| **PDF İndir / Excel İndir / Export** | `success` (yeşil) | `Action::make('export')->color('success')` |
| **Yazdır / Download** | `success` (yeşil) | `Action::make('print')->color('success')` |
| **İptal / Geri / İkincil aksiyonlar** | `gray` | `Action::make('cancel')->color('gray')` |

**Kurallar:**
- Download/Export/PDF/Excel türü tüm butonlar → `color('success')`
- Create/Edit/Delete için Filament varsayılanlarını kullan (renk belirtme)
- İptal veya ikincil aksiyonlar → `color('gray')`
- Tehlikeli işlemler (geri alınamaz) → `color('danger')`

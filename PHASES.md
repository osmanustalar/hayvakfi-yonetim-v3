# Vakıf Yönetim Sistemi v3 — Geliştirme Fazları

Detaylı şema ve iş kuralları için: `CLAUDE.md`

---

## Faz Haritası

```
FAZ 1          FAZ 2         FAZ 3          FAZ 4         FAZ 5         FAZ 6
Altyapı   →   Kişiler   →   Kasa &    →   Eğitim    →   Yardım   →   Raporlar
Temel          (Contacts)    Finans                       Takibi        Dashboard
```

- FAZ 2 ve FAZ 3 **paralel** geliştirilebilir (birbirine bağımlı değil)
- FAZ 4 ve FAZ 5, FAZ 2 **ve** FAZ 3 bitmeden başlamaz
- FAZ 6, tüm fazlar bittikten sonra

---

## FAZ 1: Temel Altyapı

**Hedef:** Çalışan panel, çok şirketli oturum, yetkilendirme, temel tanımlar.

### Kurulum & Konfigürasyon
- [ ] Filament 5.x kur, Admin Panel yapılandır (`/admin` path, `tr` locale)
- [ ] `filament-shield` 4.x kur
- [ ] `spatie/laravel-activitylog` 4.x kur
- [ ] `declare(strict_types=1)` proje geneli yapılandır
- [ ] `BaseRepository` sınıfı oluştur (`app/Repositories/BaseRepository.php`)

### Para Birimi (Global)
- [ ] `currencies` migration: `id, name, symbol, is_active, timestamps, soft_delete`
- [ ] `Currency` model (CompanyScope YOK)
- [ ] `CurrencyRepository` + `CurrencyService`
- [ ] `CurrencyResource` (Filament — Yönetim > Tanımlar)
- [ ] Seed: TRY, USD, EUR

### Şirket Yönetimi
- [ ] `companies` migration: `id, name, tax_number, address, phone, is_active, timestamps, soft_delete`
- [ ] `Company` model
- [ ] `CompanyRepository` + `CompanyService`
- [ ] `CompanyResource` (Filament)

### Kullanıcı Yönetimi
- [ ] `users` migration: `id, name, phone (unique), password, can_login, is_active, default_company_id, timestamps, soft_delete` — **email alanı YOK**
- [ ] `company_user` pivot migration: `id, company_id, user_id, timestamps`
- [ ] `User` model
- [ ] `UserRepository` + `UserService`
- [ ] `UserResource` (Filament — şirket ataması RelationManager dahil)

### Custom Authentication (Telefon + Şirket)
- [ ] `AuthService::login(phone, password, company_id)`
  - `can_login = true` kontrolü
  - `is_active = true` kontrolü
  - Session'a `active_company_id` yaz
- [ ] Custom Filament Login sayfası (`app/Filament/Pages/Auth/Login.php`)
  - Alan 1: Telefon (Türkiye formatı 05XX XXX XX XX — validation)
  - Alan 2: Şifre
  - Alan 3: Şirket (kullanıcının yetkili olduğu şirketler dinamik yüklenir)
- [ ] Rate limiting: login denemelerinde

### Global Company Scope
- [ ] `CompanyScope` global scope sınıfı (`app/Models/Scopes/CompanyScope.php`)
- [ ] `HasCompanyScope` trait — şirket bazlı modellere uygulanır
- [ ] `contacts` ve `currencies` bu scope'un **dışında** kalır

### Activity Logging & Shield
- [ ] `LogsActivity` trait (`app/Traits/LogsActivity.php`) — Spatie wrapper
- [ ] `ActivityLogService` (kim, ne zaman, hangi şirket, ne yaptı)
- [ ] `super_admin` rolü seed
- [ ] FAZ 1 resource'ları için Shield permission generate

**✓ Tamamlanma Kriteri:**
- Telefon + şirket seçimi ile giriş yapılıyor
- Şirket ve kullanıcı CRUD çalışıyor
- TRY/USD/EUR tanımlı, yeni para birimi eklenebiliyor
- Activity log kaydediliyor

---

## FAZ 2: Kişi Yönetimi (Ortak Havuz)

**Hedef:** Tüm şirketlerde ortak contact kayıtları, rol bayrakları.

- [ ] `contacts` migration:
  ```
  id, first_name, last_name, phone, national_id, birth_date,
  address, city,
  is_donor          bool default false,
  is_aid_recipient  bool default false,
  is_student        bool default false,
  notes, created_user_id (FK: users),
  timestamps, soft_delete
  ```
  - INDEX: `phone`, `national_id` (hızlı arama için)
  - **company_id YOK**
- [ ] `Contact` model (CompanyScope YOK)
- [ ] `ContactRepository` + `ContactService`
  - `setDonorFlag(id)`, `setStudentFlag(id)`, `setAidRecipientFlag(id)`
- [ ] `ContactResource` (Filament — Kişiler grubu)
  - Tablo filtreleri: Bağışçılar / Öğrenciler / Yardım Alanlar
  - Arama: TC kimlik + telefon
  - Form: tüm alanlar, rol toggle'ları

**✓ Tamamlanma Kriteri:**
- Contact ekleme/düzenleme çalışıyor
- Aynı kişi hem `is_donor` hem `is_student` olabiliyor
- TC kimlik ve telefon ile hızlı arama çalışıyor

---

## FAZ 3: Kasa & Finansal Altyapı

**Hedef:** Kasa grubu, kasa, kategoriler, işlemler, split kayıt, transfer, döviz.

### 3.1 İşlem Kategorileri (önce seed gerekli)
- [ ] `safe_transaction_categories` migration:
  ```
  id, company_id (nullable — null: sistem geneli),
  name, type (income/expense/null), parent_id (self-FK, nullable),
  sort_order, is_active, is_disable_in_report,
  contact_type (nullable), color (varchar 10), description (nullable),
  created_user_id (FK: users),
  timestamps, soft_delete
  ```
- [ ] `SafeTransactionCategory` model
  - Constants: `TRANSFER_CATEGORY_ID=1`, `EXCHANGE_CATEGORY_ID=2`, `AWAITING_CATEGORY_ID=3`, `OPENING_CATEGORY_ID=4`, `DONATION_CATEGORY_ID=5`
  - Scope: `whereForCompany($id)` → `company_id IS NULL OR company_id = ?`
- [ ] `SafeTransactionCategoryRepository` + `SafeTransactionCategoryService`
- [ ] `SafeTransactionCategorySeeder` — **sabit sırayla, ID'ler kaymamalı**
- [ ] `SafeTransactionCategoryResource` (Filament — parent seçici, ağaç görünüm)

### 3.2 Kasa Grubu & Kasa
- [ ] `safe_groups` migration:
  ```
  id, company_id, name, is_active, is_api_integration,
  credentials (JSON encrypted), created_user_id, timestamps, soft_delete
  ```
- [ ] `safes` migration:
  ```
  id, company_id, safe_group_id, name, iban (nullable),
  currency_id (FK), balance (decimal 15,4),
  is_manual_transaction (bool), is_active, sort_order,
  last_processed_at (nullable), integration_id (nullable),
  created_user_id, timestamps, soft_delete
  ```
- [ ] `SafeGroup` model + Repository + Service
- [ ] `Safe` model + `SafeRepository`
- [ ] `SafeService`
  - `updateBalance(safe_id, amount, type)` → `lockForUpdate()`
  - `checkManualAllowed(safe_id)` → is_manual_transaction kontrolü
- [ ] `SafeGroupResource` + `SafeResource` (Filament)

### 3.3 İşlemler & Split
- [ ] `safe_transactions` migration:
  ```
  id, company_id, safe_id,
  type (income/expense), operation_type (exchange/transfer/null),
  total_amount (decimal 15,4), amount (decimal 15,4),
  currency_id (nullable), exchange_rate (decimal 10,4 nullable),
  item_rate (decimal 10,4 nullable),
  target_safe_id (nullable), target_transaction_id (nullable),
  contact_id (nullable), reference_user_id (nullable), created_user_id,
  process_date (date), transaction_date (datetime nullable),
  balance_after_created (decimal 15,4),
  integration_id (nullable), import_file (nullable),
  is_show (bool default true), description (nullable),
  timestamps, soft_delete
  ```
  - UNIQUE INDEX: `(safe_id, integration_id)` WHERE integration_id IS NOT NULL
- [ ] `safe_transaction_items` migration:
  ```
  id, company_id, transaction_id, transaction_category_id,
  donation_category_id (nullable), amount (decimal 15,4),
  timestamps, soft_delete
  ```
- [ ] `SafeTransaction` model + `SafeTransactionItem` model
- [ ] `SafeTransactionRepository` + `SafeTransactionItemRepository`
- [ ] `SafeTransactionService`
  - `create(data, items[])` — is_manual_transaction kontrolü, SUM assertion, balance update, balance_after_created snapshot
  - `createFromApi(data)` — is_manual_transaction bypass, integration_id duplicate skip
  - `createTransfer(from_safe, to_safe, amount, exchange_rate?)` — iki transaction atomik
  - `delete(id)` — soft delete + bakiye geri al
- [ ] `SafeTransactionResource` (Filament)
  - Repeater: split item'lar (kategori + bağış kategorisi + tutar)
  - operation_type=transfer → hedef kasa alanı görünür
  - currency ≠ safe.currency → exchange_rate alanı görünür
  - item_rate alanı (opsiyonel, kurban için)
  - is_manual_transaction=true kasada "Yeni Kayıt" butonu gizlenir

**✓ Tamamlanma Kriteri:**
- SafeGroup → Safe hiyerarşisi çalışıyor
- Manuel/API kasa ayrımı çalışıyor
- Split kayıt (birden fazla item) çalışıyor
- TL→USD kasa transferi atomik çalışıyor
- Döviz işlemi (amount × exchange_rate = total_amount) çalışıyor
- Bakiye lockForUpdate ile doğru güncelleniyor
- Duplicate API kaydı engelleniyor
- Kategori ağacı (sistem geneli + şirket özeli) çalışıyor

---

## FAZ 4: Eğitim Yönetimi

**Hedef:** Sınıf, öğrenci kaydı, aidat oluşturma ve ödeme.

- [ ] `school_classes` migration:
  ```
  id, company_id, name,
  teacher_id (FK: users — nullable, şirkete atanmış kullanıcı),
  start_date, end_date, default_monthly_fee (decimal 10,2),
  capacity (nullable), is_active, timestamps, soft_delete
  ```
- [ ] `student_enrollments` migration:
  ```
  id, company_id, class_id (FK), contact_id (FK: contacts),
  enrollment_date, monthly_fee (decimal 10,2 nullable),
  is_active, timestamps, soft_delete
  UNIQUE: (class_id, contact_id)
  ```
- [ ] `student_fees` migration:
  ```
  id, company_id, enrollment_id (FK),
  period (date — ayın 1'i), amount (decimal 10,2), due_date,
  paid_at (nullable), payment_transaction_id (FK: safe_transactions nullable),
  status (ENUM: pending/paid/overdue/waived),
  timestamps, soft_delete
  UNIQUE: (enrollment_id, period)
  ```
- [ ] `SchoolClass` model + Repository + Service
- [ ] `StudentEnrollment` model + `StudentEnrollmentService`
  - Kayıt sırasında `contact.is_student = true` otomatik set edilir
  - `monthly_fee` null ise `class.default_monthly_fee` devreye girer
- [ ] `StudentFee` model + `StudentFeeService`
  - `generateMonthlyFees(year, month)` — aktif kayıtlar için idempotent aidat oluşturma
  - `markAsPaid(fee_id, safe_id)` — SafeTransactionService çağırır (Öğrenci Aidat kategorisi)
  - `markAsOverdue()` — vadesi geçen pending → overdue
- [ ] `SchoolClassResource` (Filament)
  - teacher_id: şirkete ait users listesinden seçim
- [ ] `StudentEnrollmentResource` + `StudentFeeResource` (Filament)
  - Aidat ödeme: kasa seçim modal + toplu ödeme aksiyonu

**✓ Tamamlanma Kriteri:**
- Sınıf oluşturma ve öğretmen (user) atama çalışıyor
- Öğrenci kaydı → `is_student` bayrağı otomatik set
- Aylık aidat oluşturma (idempotent) çalışıyor
- Aidat ödeme → kasa INCOME işlemi oluşturuluyor ve bağlantı kuruluyoru

---

## FAZ 5: Yardım Takibi

**Hedef:** Yardım alan kişilere dağıtım kaydı.

- [ ] `aid_records` migration:
  ```
  id, company_id, contact_id (FK: contacts),
  transaction_id (FK: safe_transactions — nullable),
  aid_type (varchar), description (nullable),
  amount (decimal 10,2), given_at (date),
  created_user_id (FK: users),
  timestamps, soft_delete
  ```
- [ ] `AidRecord` model + `AidRepository` + `AidService`
  - Kayıt sırasında `contact.is_aid_recipient = true` otomatik set edilir
  - Opsiyonel: kasa EXPENSE işlemi bağlanabilir (SafeTransactionService çağırır)
- [ ] `AidResource` (Filament — Yardım grubu)

**✓ Tamamlanma Kriteri:**
- Yardım kaydı oluşturma çalışıyor
- `is_aid_recipient` bayrağı otomatik set ediliyor
- Opsiyonel kasa çıkış işlemi bağlantısı çalışıyor

---

## FAZ 6: Raporlama & Dashboard

**Hedef:** Karar desteği raporları ve özet dashboard.

### Raporlar
- [ ] Kasa Özet Raporu (tarih aralığı, kasa bazında — açılış/kapanış/giriş/çıkış)
- [ ] Kategori Dağılım Raporu (`is_disable_in_report=false` filtreyle, ağaç yapısında)
- [ ] Kurban Raporu (tip bazında adet + tutar — `item_rate × amount`)
- [ ] Bağışçı Raporu (contact bazında toplam bağış)
- [ ] Aidat Raporu (sınıf/dönem/durum — ödenen/bekleyen/geciken)
- [ ] Yardım Raporu (contact/tip bazında)
- [ ] Para Birimi Özeti (TRY/USD/EUR kasa toplamları)

### Dashboard
- [ ] Kasa bakiyesi widget'ları (şirket bazında, para birimi ile)
- [ ] Bu ay bağış toplamı
- [ ] Bekleyen aidat sayısı
- [ ] Son 10 işlem listesi

### Export
- [ ] Excel / PDF çıktısı (Laravel Excel veya DomPDF)

---

## Bağımlılık Şeması

```
FAZ 1 (zorunlu ilk)
├── FAZ 2 ──────────────────────────────┐
└── FAZ 3 ─────────────────────┐        │
                                ├─── FAZ 4
                                └─── FAZ 5
                                          └─── FAZ 6
```

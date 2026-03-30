# ATAMA BEKLİYOR İşlem Akışı Revizyon

## Tarih: 2026-03-30

## Özet

"ATAMA BEKLİYOR" kategorisindeki işlemler için atama akışı tamamen revize edildi. Önceki implementasyon sadece EXPENSE işlemlerini destekliyordu, yeni implementasyon hem INCOME hem EXPENSE işlemlerini destekliyor ve daha karmaşık senaryoları kapsıyor.

## Temel Değişiklikler

### 1. Tüm İşlemler ATAMA BEKLİYOR Olabilir
- API'den gelen **TÜM işlemler** (hem INCOME hem EXPENSE) ATAMA BEKLİYOR kategorisiyle gelir
- Her işlem Giriş (INCOME) VEYA Çıkış (EXPENSE) olabilir

### 2. Dört Senaryo
Atama işlemi `operation_choice` + `target_safe.is_api_integration` kombinasyonuna göre 4 senaryoya bölünür:

| Senaryo | Operation | Target Safe | Davranış |
|---------|-----------|-------------|----------|
| A1 | Transfer | API Kasası | Linking (tutar AYNI zorunlu) |
| A2 | Döviz | API Kasası | Linking (tutar farklı olabilir) |
| B1 | Transfer | Normal Kasa | Yeni kayıt oluştur (ters yön, aynı tutar) |
| B2 | Döviz | Normal Kasa | Yeni kayıt oluştur (manuel kur+tutar) |

## Değiştirilen Dosyalar

### 1. SafeTransactionRepository.php
**Yeni Metod:** `getEligibleTransactions()`

```php
public function getEligibleTransactions(
    SafeTransaction $source,
    Safe $targetSafe,
    string $operationType
): Collection
```

- `transaction_date` eşleştirmesi yapar (process_date değil)
- Transfer için: tutar aynı olanları filtreler
- Döviz için: tüm kayıtları döner
- `withoutGlobalScopes()` kullanır (farklı şirket/scope'ta olabilir)

### 2. SafeTransactionService.php
**Metod:** `assignTransaction()` — Completely Rewritten

**Önemli Değişiklikler:**
- `operation_type_choice` → `operation_choice` (parametre adı değişti)
- Kaynak işlem INCOME veya EXPENSE olabilir (sadece EXPENSE değil)
- Type tersi hesaplanır: EXPENSE → INCOME, INCOME → EXPENSE
- Hedef normal kasaysa bakiye kontrolü yapılır (EXPENSE durumunda)
- `transaction_date` kaydedilir (önceden kayboluyordu)
- `currency_id` doğru set edilir (transfer/exchange durumuna göre)

**API Kasası Senaryosu (A):**
- Mevcut kaydı bulup linking yapar
- Her iki kayıt da güncellenir (operation_type, target_safe_id, target_transaction_id)
- Items kategorileri güncellenir (ID 1 veya 2)

**Normal Kasa Senaryosu (B):**
- Yeni kayıt oluşturur (ters type ile)
- Bakiye güncellenir (increment/decrement)
- Items oluşturulur
- Kaynak kayıt güncellenir (linking)

### 3. AssignSafeTransaction.php (Filament Page)
**Completely Rewritten**

**Form Değişiklikleri:**
- Kaynak İşlem section'ı: `transaction_date` gösterir (banka zaman damgası)
- `operation_type_choice` → `operation_choice`
- `target_transaction_id` helperText eklendi
- `target_transaction_id` options: Repository'den `getEligibleTransactions()` çağırır
- `target_amount` label: "Hedef Kasaya Girecek Tutar (Yabancı Para)"
- Type label gösterimi: `type_label` (readonly)

**Form Data Pre-fill:**
```php
'operation_choice'        => null,
'target_safe_id'          => null,
'target_transaction_id'   => null,
'exchange_rate'           => null,
'target_amount'           => null,
```

**Success Notification:**
- İşlem tamamlandıktan sonra başarı mesajı gösterir

## Kritik Noktalar

### 1. transaction_date Kullanımı
- **TÜM SORGULARDA** `transaction_date` kullanılır (process_date değil)
- `transaction_date`: Banka işlem zaman damgası (API'den gelen)
- `process_date`: Türkiye saati (manuel giriş)

### 2. Scope Handling
- `target_transaction_id` fetch: **ALWAYS** `withoutGlobalScopes()`
- Farklı şirket/scope'ta olabilir (global transactions)

### 3. Tutar Kontrolü
- **Transfer:** `total_amount` eşit olmalı
- **Döviz:** Tutarlar farklı olabilir

### 4. Type Tersi
```php
$targetType = $source->type === TransactionType::EXPENSE
    ? TransactionType::INCOME
    : TransactionType::EXPENSE;
```

### 5. Bakiye Güncelleme
- INCOME: `increment()`
- EXPENSE: `checkBalance()` + `decrement()`

## Test Senaryoları

### Senaryo A1: Transfer + API Kasası
1. Kaynak: API Kasa A, EXPENSE, 1000 TRY
2. Hedef: API Kasa B seç (aynı currency)
3. Hedef işlem listesi: sadece 1000 TRY tutarında olanlar
4. Seç ve kaydet
5. Sonuç: Her iki kayıt da link edilir, kategori ID 1

### Senaryo A2: Döviz + API Kasası
1. Kaynak: API Kasa A, INCOME, 500 USD
2. Hedef: API Kasa B seç (farklı currency)
3. Hedef işlem listesi: tüm kayıtlar (tutar farklı olabilir)
4. 16000 TRY olan kaydı seç
5. Sonuç: Link edilir, kategori ID 2

### Senaryo B1: Transfer + Normal Kasa
1. Kaynak: API Kasa A, EXPENSE, 1000 TRY
2. Hedef: Normal Kasa B seç (aynı currency)
3. Hedef işlem input'u görünmez
4. Kaydet
5. Sonuç: Yeni INCOME kaydı oluşur, tutar 1000 TRY, link edilir

### Senaryo B2: Döviz + Normal Kasa
1. Kaynak: API Kasa A, INCOME, 500 USD
2. Hedef: Normal Kasa B seç (TRY)
3. Exchange rate: 32.0000 gir
4. Target amount: 16000 gir
5. Sonuç: Yeni EXPENSE kaydı oluşur (ters yön), tutar 16000 TRY, link edilir

## Backward Compatibility

❌ **Breaking Change**: Parametre adı değişti
- Eski: `operation_type_choice`
- Yeni: `operation_choice`

Eğer başka bir yerde bu metod çağrılıyorsa güncellenmeli.

## İlgili Dosyalar

```
app/Repositories/SafeTransactionRepository.php
app/Services/SafeTransactionService.php
app/Filament/Resources/SafeTransactionResource/Pages/AssignSafeTransaction.php
```

## SQL İzleme

Atama işlemi sırasında çalışan sorgular:

```sql
-- Eligible transactions fetch
SELECT * FROM safe_transactions
WHERE safe_id = ?
  AND transaction_date = ?
  AND target_transaction_id IS NULL
  AND EXISTS (SELECT 1 FROM safe_transaction_items WHERE transaction_category_id = 3)
  [AND total_amount = ?]  -- sadece transfer için

-- Update source
UPDATE safe_transactions SET
  operation_type = ?,
  target_safe_id = ?,
  target_transaction_id = ?,
  exchange_rate = ?,
  item_rate = ?
WHERE id = ?

-- Update target (API case)
UPDATE safe_transactions SET
  operation_type = ?,
  target_safe_id = ?,
  target_transaction_id = ?
WHERE id = ?

-- Update items
UPDATE safe_transaction_items SET transaction_category_id = ? WHERE transaction_id = ?

-- Create target (Normal case)
INSERT INTO safe_transactions (...) VALUES (...)
INSERT INTO safe_transaction_items (...) VALUES (...)

-- Update balance
UPDATE safes SET balance = balance + ? WHERE id = ?  -- INCOME
UPDATE safes SET balance = balance - ? WHERE id = ?  -- EXPENSE
```

## Gelecek Geliştirmeler

1. **Bulk Assignment:** Birden fazla işlemi toplu atama
2. **Auto-matching:** Aynı transaction_date + tutar otomatik eşleştirme
3. **Conflict Resolution:** Çakışan atamalar için öneri sistemi
4. **History Tracking:** Atama geçmişi ve değişiklik logu
5. **Reversal:** Atama geri alma (undo) özelliği

## Notlar

- Bu revizyon FAZ 3'ün bir parçasıdır
- API entegrasyon altyapısı tamamlanmış durumda
- V1 migration'dan gelen veriler bu flow ile işlenecek

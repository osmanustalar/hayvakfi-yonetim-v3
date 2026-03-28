---
name: FAZ 3 completed
description: Kasa & Finans modülü fully implemented — SafeGroup, Safe, SafeTransaction, SafeTransactionItem
type: project
---

FAZ 3 (Kasa & Finans) is complete as of 2026-03-26.

## What was implemented

### Enums (app/Enums/)
- `TransactionType` (INCOME/EXPENSE)
- `OperationType` (EXCHANGE/TRANSFER)
- `ContactType` (DONOR/AID_RECIPIENT/STUDENT)

### Exceptions (app/Exceptions/)
- `ManualTransactionNotAllowedException`
- `InsufficientBalanceException`
- `SplitAmountMismatchException`

### Migrations (in order)
- `2026_03_26_100001_create_safe_transaction_categories_table`
- `2026_03_26_100002_create_safe_groups_table`
- `2026_03_26_100003_create_safes_table`
- `2026_03_26_100004_create_safe_transactions_table`
- `2026_03_26_100005_create_safe_transaction_items_table`

### Models
- `SafeTransactionCategory` — NO CompanyScope; uses `scopeForActiveCompany()` Eloquent scope; nullable casts for type/contact_type enums
- `SafeGroup`, `Safe`, `SafeTransaction`, `SafeTransactionItem` — all have CompanyScope

### Repositories
- `SafeTransactionCategoryRepository`, `SafeGroupRepository`, `SafeRepository`, `SafeTransactionRepository`, `SafeTransactionItemRepository`

### Services
- `SafeGroupService`, `SafeService`, `SafeTransactionCategoryService`, `SafeTransactionService`
- `SafeTransactionService::create()` — throws ManualTransactionNotAllowedException if safe.is_manual_transaction=true
- `SafeTransactionService::createFromApi()` — bypasses manual check, idempotent on integration_id
- `SafeTransactionService::createTransfer()` — atomic dual-transaction, uses lockForUpdate via SafeRepository::findWithLock()
- `SafeTransactionService::createExchange()` — computes total_amount = amount × exchange_rate
- All persist via DB::transaction, SplitAmountMismatchException if SUM(items) != total_amount

### Seeder
- `SafeTransactionCategorySeeder` — 15 records with fixed IDs 1-15; uses updateOrCreate(['id' => X])
- IDs 1-5 are system categories (protected from edit in UI); IDs 6-15 are subcategories under ID 5

### Filament Resources (all in 'Kasa' navigation group)
- `SafeGroupResource` (sort: 1)
- `SafeResource` (sort: 2)
- `SafeTransactionResource` (sort: 3) — no Edit page; CreateSafeTransaction::handleRecordCreation() dispatches to correct service method
- `SafeTransactionCategoryResource` (sort: 10) — overrides getEloquentQuery() with withoutGlobalScopes() + company_id IS NULL OR = active; system categories (ID ≤ 5) hidden from edit

**Why:** FAZ 3 depends on FAZ 1 (company/user/currency infrastructure). FAZ 4 (Education) and FAZ 5 (Aid) depend on FAZ 3 being complete.

**How to apply:** FAZ 4 and FAZ 5 can now be started. SafeTransactionService::create() is the correct entry point for student fee payments (markAsPaid flow).

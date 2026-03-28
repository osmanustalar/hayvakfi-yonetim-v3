---
name: SafeTransaction type-based pages implemented
description: 4 Create + 4 Edit pages for income/expense/transfer/exchange transactions built with V3 service layer
type: project
---

SafeTransaction type-based Filament pages have been implemented (2026-03-27).

**Pages created:**
- `CreateIncomeSafeTransaction` — safe_id from URL, TransactionType::INCOME, items repeater, dynamic contact field
- `CreateExpenseSafeTransaction` — same as income but TransactionType::EXPENSE
- `CreateTransferSafeTransaction` — calls SafeTransactionService::createTransfer(), same-currency check
- `CreateExchangeSafeTransaction` — uses DB::transaction directly with SafeTransactionRepository/ItemRepository, different-currency check, item_rate auto-calc
- `EditIncomeSafeTransaction` — handleRecordUpdate → SafeTransactionService::update()
- `EditExpenseSafeTransaction` — same as edit-income
- `EditTransferSafeTransaction` — handleRecordUpdate → SafeTransactionService::updateTransfer(), fills source/target amounts from record
- `EditExchangeSafeTransaction` — handleRecordUpdate → SafeTransactionService::updateExchange(), fills amounts from record and targetTransaction

**Service methods added to SafeTransactionService:**
- `update()` — income/expense update with balance diff correction
- `updateTransfer()` — atomically updates both source+target transfer transactions
- `updateExchange()` — atomically updates both source+target exchange transactions

**SafeResource table actions updated:**
- Gelir/Çıkış/Transfer/Döviz İşlemi buttons — only visible when `is_manual_transaction = false`
- Grouped secondary actions (Transfer, Döviz, Hareketler, Görüntüle, Düzenle)

**SafeTransactionResource:**
- No more generic create/view pages in getPages()
- 9 routes: index + 4 create-* + 4 edit-*
- EditAction replaced with custom Action that routes by type/operation_type
- DeleteAction with before() hook for balance correction + target transaction cleanup

**Why:** V2 reference showed type-specific pages are needed for safe form UX (different fields per transaction type).

**How to apply:** When adding new transaction types or modifying form layouts, each type has its own page file.

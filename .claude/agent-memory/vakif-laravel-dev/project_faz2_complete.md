---
name: FAZ 2 implementation status
description: Contacts (global pool) fully implemented as of 2026-03-26
type: project
---

FAZ 2 (Kişi Yönetimi) is complete as of 2026-03-26.

**Why:** Contacts are a global shared pool — no company_id, no CompanyScope. Required by FAZ 4 (student_enrollments → contact.is_student) and FAZ 5 (aid_records → contact.is_aid_recipient).

**How to apply:** FAZ 3 can now run in parallel. FAZ 4 and FAZ 5 depend on FAZ 2 being done.

Key architectural decision: `handleRecordCreation` is overridden in `CreateContact` page (not in the Resource) to inject `created_user_id = auth()->id()` via `ContactService::create()`. `handleRecordUpdate` is overridden in `EditContact` page to route through `ContactService::update()`.

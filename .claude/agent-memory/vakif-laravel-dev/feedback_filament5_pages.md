---
name: Filament 5.x Page Conventions
description: Key Filament 5.x conventions for Create/Edit page overrides: Schema not Form, app() not constructor injection, no fn() use syntax
type: feedback
---

Filament 5.x Create/Edit page overrides must follow these rules:

1. **form() signature uses Schema, not Form**
   - `public function form(Schema $form): Schema` — NOT `Form $form): Form`
   - Import: `use Filament\Schemas\Schema;` — NOT `use Filament\Forms\Form;`
   - `Forms\Components\*` components still work inside the schema

2. **No constructor injection in Livewire/Filament pages**
   - Livewire components are serialized/deserialized — constructor DI breaks
   - Use `app(MyService::class)->method()` inside handleRecordCreation/handleRecordUpdate
   - Existing V3 pattern: `app(SafeTransactionService::class)->create($payload)`

3. **Arrow functions cannot use `use` keyword**
   - `fn () use ($var)` is a PHP parse error
   - For closures needing outer variables: use `function() use ($var)` or restructure to use `$this`

4. **halt() works** — `$this->halt()` throws a `Halt` exception, so return type mismatch is safe.

**Why:** Discovered during SafeTransaction page rebuild — all three caused fatal errors that blocked route registration.

**How to apply:** Any time writing a new Create/Edit page that overrides form(), handleRecordCreation(), or handleRecordUpdate().

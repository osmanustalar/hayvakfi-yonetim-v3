---
name: Filament 5.x locale method removed
description: Panel::locale() does not exist in Filament 5.x — causes BadMethodCallException on every artisan command
type: feedback
---

`->locale('tr')` on the Filament `Panel` builder throws `BadMethodCallException: Method Filament\Panel::locale does not exist` in Filament 5.x (v5.4.1+).

**Why:** The `locale()` macro was removed from the Panel API in Filament 5. Locale is now controlled via Laravel's standard `config/app.php` `locale` key.

**How to apply:** Do not add `->locale()` to the Panel builder in `AdminPanelProvider.php`. If locale must be set at the panel level, use Laravel's `App::setLocale()` in a middleware or service provider instead.

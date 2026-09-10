# HealthyMe — Implementation Plan

Instructions for Cursor. Work through phases in order. Do not skip ahead.

---

## 1. Context

**What this app is:** a blood pressure and blood sugar logbook for elderly users, whose
output is a report handed to a doctor. Primary user is an adult child living abroad
monitoring a parent at home, plus their own readings.

**Stack:** Laravel 13, Inertia 3, Vue 3.5, Tailwind 4, PostgreSQL, Pest 5, PHPStan/Larastan,
Pint. Vite via `vite-plus`. Auth is Fortify + passwordless magic links.

**Operating base:** Portugal (EU). Users worldwide. GDPR is the baseline everywhere.

**Business model:** free. Every dependency must have a free tier that does not expire.

---

## 2. Hard rules — apply to every task

### 2.1 Never make medical claims
This app is wellness software, NOT a medical device under EU MDR. Crossing that line
requires CE marking we cannot obtain. Therefore:

- **Descriptive, never prescriptive.** Show facts and the user's own data. Never instruct.
  - Allowed: "180 mg sodium per 100 g", "GI 73 (high)", "your 2h average after rice: 210"
  - Forbidden: "avoid rice", "eat this to lower your blood pressure", "you have hypertension"
- Never use the words *diagnose*, *diagnosis*, *treat*, *treatment*, or *prescribe* in any
  user-facing string.
- Never generate free-form health advice with an LLM. Classifiers stay deterministic, like
  the existing `App\Support\BloodPressureColor` and `App\Support\GlucoseRange`.
- Keep the existing "Display only — never a diagnosis" doc comments. They are load-bearing.
- Safe phrasing for out-of-range values is the existing pattern: "Check with your clinic".

### 2.2 GDPR by default
- Health data is Art. 9 special category. Consent must be **explicit and separately recorded**,
  never bundled into terms acceptance.
- Data minimisation: never store what is not needed; delete as soon as purpose is served.
- Every new third-party call must be listed in the privacy policy and the Art. 30 record.
- Never send identifiers (name, email, DOB) to an external AI API. Images only.

### 2.3 Code conventions — match what is already there
- Business logic goes in `app/Support/` (stateless helpers) or `app/Actions/` (operations),
  not in controllers. Follow the existing shape of these directories.
- Controllers stay thin; validation lives in `app/Http/Requests/`.
- Use enums in `app/Enums/` for any fixed set of values.
- Money/decimals via string + `bccomp`, as `GlucoseRange` already does. Never float compare.
- Every reading timestamp is `timestampTz` and timezone-aware. Use `$profile->clockTimezone()`.
- Authorisation via policies in `app/Policies/`. Every new model needs one.

### 2.4 Testing
- Pest. Feature tests in `tests/Feature/`, unit tests for Support classes in `tests/Unit/`.
- Every task below must ship with tests. Do not mark a task done without them.
- Run before declaring done:
  ```
  ./vendor/bin/pint
  ./vendor/bin/phpstan analyse
  ./vendor/bin/pest
  npm run types:check
  ```

---

## 3. Phase 0 — Compliance and safety hygiene

**Blocks launch. Do this first.**

### 0.1 Delete scan photos after extraction
`App\Actions\Readings\ProcessScanPhoto` stores uploads to `storage/app/scans` and never
removes them. This breaches GDPR storage limitation (Art. 5(1)(e)).

- Delete the file in a `finally` block after extraction, on success and on failure alike.
- Add a `healthlog:prune-scans` command deleting anything in `scans/` older than 1 hour, as
  a safety net for crashed requests. Schedule it hourly.
- Test: upload a photo, assert the file no longer exists after the request, in both the
  success and the `CouldNotReadPhotoException` paths.

### 0.2 Fix the timezone default
`database/migrations/2026_09_08_190000_create_profiles_table.php` defaults
`profiles.timezone` to `Asia/Kolkata`. Wrong for a worldwide app.

- New migration: make the column nullable, drop the default.
- `Profile::clockTimezone()` already prefers `SystemTimezone::detected()`. Make it fall back
  to `config('app.timezone')` when both are null, never to a hardcoded region.
- Profile creation should take the detected timezone from `DetectSystemTimezone` middleware.
- Test: a profile created with no timezone resolves to the detected one.

### 0.3 Explicit consent for health data
- Migration: `consents` table — `user_id`, `type`, `granted_at`, `revoked_at`, `ip_address`,
  `policy_version`.
- Onboarding must capture a separate, unticked checkbox for health data processing, distinct
  from terms acceptance. Record it.
- Creating a share link records its own consent row with the date range shared.
- Test: onboarding without the health-data consent does not complete; share link creation
  writes a consent row.

### 0.4 Copy audit
- Grep all of `resources/js/` and `app/` for: `diagnos`, `treat`, `prescri`, `medical advice`,
  `should eat`, `avoid`.
- Rewrite any hit to descriptive phrasing per rule 2.1.
- The landing page (`resources/js/pages/Welcome.vue`) must state plainly: not a medical
  device, does not provide medical advice, does not replace a doctor.

### 0.5 Attribution page
- Add `/about/data` listing every data source and its licence. Required by ODbL for
  Open Food Facts later, and good practice now.

---

## 4. Phase 1 — PWA, offline logging, Web Push

**The highest-value phase. Without it the app never reaches a parent's phone.**

### 1.1 Make it an installable PWA
- `public/manifest.json`: name, short_name, icons (192/512 + maskable), `display: standalone`,
  `start_url: /`, theme colour matching the existing design.
- Service worker: app shell precache, network-first for Inertia pages, cache-first for assets.
- Install prompt component, shown once, dismissible, remembered in `localStorage`.
- Test manually on Android Chrome and iOS Safari (Add to Home Screen).

### 1.2 Offline logging queue
A user with no signal must still be able to log a reading.

- IndexedDB queue for pending readings.
- Background Sync where supported; retry on `online` event otherwise.
- Queued readings show in the UI as pending with a clear visual state.
- Server must be idempotent: add a client-generated `client_uuid` (unique, nullable) to
  `bp_readings` and `glucose_readings`; a repeated submission updates rather than duplicates.
- Test: POST the same `client_uuid` twice, assert one row.

### 1.3 Web Push
Replaces email reminders. Free and unlimited — no WhatsApp, no SMS.

- `push_subscriptions` table: `profile_id`, `endpoint`, `p256dh`, `auth`, `user_agent`,
  `last_used_at`.
- VAPID keys in config, generated once, never committed.
- Use `minishlink/web-push` (composer).
- Rewrite `App\Console\Commands\SendReadingReminders` to send push first and fall back to
  the existing `SlotReminderNotification` email only when no subscription exists.
- Keep the existing per-slot dedupe in `App\Support\ReadingReminders` — do not regress it.
- Test: due slot with a subscription sends push and no email; without one, sends email.

---

## 5. Phase 2 — Caregiver roles

**This is the actual product: an adult child watching a parent's readings.**

### 2.1 Membership model
Currently `profiles.user_id` means one owner. We need a parent who owns their data and a
child who can view and log for them.

- Migration: `profile_members` — `profile_id`, `user_id`, `role` (`owner` | `caregiver`),
  `invited_at`, `accepted_at`. Unique on (`profile_id`, `user_id`).
- Backfill: every existing `profiles.user_id` becomes an `owner` row. Keep the column for now;
  drop it in a later migration once all reads go through the pivot.
- New enum `App\Enums\ProfileRole`.
- Update `ProfilePolicy` and every reading policy to authorise via membership, not `user_id`.
- `App\Support\CurrentProfile` must resolve across all profiles the user is a member of.
- Test thoroughly: a caregiver can read and log; a caregiver cannot delete the profile or
  change consent; a non-member gets 403 on every route.

### 2.2 Invitations
- Owner or caregiver invites by email; invitee accepts via a signed link.
- **The parent must always be able to see who has access and revoke it.** Build the revoke
  screen in the same task, not later.
- Never allow silent monitoring. If a caregiver has access, the parent's home screen says so.

### 2.3 Inactivity and out-of-range alerts
The reason a caregiver keeps the app.

- New `App\Support\CareAlerts` producing:
  - no readings for N days (default 3, configurable per profile)
  - N consecutive readings above the profile's own thresholds (default 3)
- Delivered by Web Push to caregivers only, not to the parent.
- Rate limit: at most one alert of each type per profile per 24h. Reuse the cache-dedupe
  pattern already in `ReadingReminders`.
- Alert copy is descriptive: "No readings since Tuesday" / "3 readings above target".
  Never "your mother's blood pressure is dangerous".
- Test each rule and the dedupe.

### 2.4 Caregiver dashboard
- New page listing every profile the user belongs to: name, last reading with relative time,
  today's slot completion, any active alert.
- Sort by attention needed.
- One tap into the existing per-profile views.

---

## 6. Phase 3 — Accessibility for elderly users

Small effort, disproportionate impact. Reference: the parent view should be usable by
someone with poor eyesight and no smartphone confidence.

- `SpeechSynthesis` read-aloud on every saved reading and on the home summary. Toggle in
  settings, off by default.
- A "large text" mode in `settings/Appearance` scaling the base rem.
- Minimum 56px touch targets throughout the logging flow.
- Verify WCAG AA contrast on the BP colour tokens; fix any that fail.
- Full keyboard and screen-reader labels on the log forms.

---

## 7. Phase 4 — Food logging (free, no AI)

Build the zero-cost version first. Photo recognition comes later, if at all.

### 4.1 Schema
Keep sources in **separate tables**. Open Food Facts is ODbL with share-alike; merging it
into one table with other sources could oblige us to open the combined database.

- `off_products` — cached Open Food Facts lookups by barcode
- `usda_foods` — seeded from FoodData Central (public domain)
- `food_logs` — `profile_id`, `source`, `source_ref`, `label`, `portion_g`, `eaten_at`,
  nullable `glucose_reading_id`
- Join at query time. Never distribute a merged table.

### 4.2 Barcode scanning
- Native `BarcodeDetector` where available; `zxing-wasm` fallback.
- Look up Open Food Facts (`https://world.openfoodfacts.org/api/v2/product/{barcode}.json`),
  no API key required. Cache aggressively in the existing `cache` table — never hit the API
  twice for the same barcode.
- Show per-100g sodium, sugar, carbs, plus Nutri-Score if present.
- Visible "Data from Open Food Facts (ODbL)" attribution on screen. Required by the licence.

### 4.3 Manual food entry
- Search `usda_foods` seeded from FoodData Central.
- Portion presets rather than gram entry — most users cannot estimate grams.

### 4.4 Reference thresholds — descriptive only
- New `App\Support\NutritionFacts`, deterministic, same shape as `BloodPressureColor`.
- Compare against **published public-health references, cited on screen** (e.g. WHO: under
  5 g salt/day). Present as information, never as an instruction to the user.
- Return null when a value is unremarkable, exactly as `GlucoseRange::label()` does.

---

## 8. Phase 5 — Personal correlation

**The feature no competitor can copy, and the safest thing in the app — it is the user's own
measurement history, not advice.**

- Job linking each `GlucoseContext::PostMeal2h` reading to `food_logs` in the preceding 2h.
- `App\Support\FoodResponse`: per food, the mean post-meal glucose and sample count.
- **Suppress any result with fewer than 5 samples.** Do not show noise as insight.
- Present as plain comparison: "After rice: 210 mg/dL avg (7 readings). After roti: 155 (9)."
- Same idea for sodium intake against next-morning systolic.
- Unit-test the aggregation with fixed fixtures, including the n<5 suppression.

---

## 9. Phase 6 — Clinical depth in the report

Makes the doctor handout genuinely better than a competitor's chart dump. All of this is
arithmetic over data we already hold — extend `App\Support\ReadingInsights`.

- **BP variability**: standard deviation and coefficient of variation of systolic.
- **Morning surge**: morning slot average minus evening slot average.
- **Time in range**: percentage of glucose readings inside the profile's own targets.
- **Post-meal excursion**: mean 2h post-meal minus mean fasting.
- Add each to `App\Support\VisitReport` and the printed report, labelled plainly.
- Server-side PDF for the visit report (doctors print). Keep the existing print CSS as fallback.
- Extend `ReadingInsights::summaryText()` with the new figures, keeping its plain-language,
  non-interpretive tone.
- Unit tests with known inputs for every statistic.

---

## 10. Phase 7 — Structured, portable data

Free future-proofing. The European Health Data Space will let EU clinics ingest structured
patient data; being FHIR-shaped turns our share link into something a clinic system can consume.

- Map `BpReading` and `GlucoseReading` to FHIR `Observation`, LOINC-coded
  (systolic `8480-6`, diastolic `8462-4`, HbA1c `4548-4`).
- `Medication` maps to `MedicationStatement`.
- Add a FHIR JSON export alongside the existing CSV export.
- Normalise medication names via the free RxNorm API (`https://rxnav.nlm.nih.gov/REST/`,
  no key) for autocomplete and dedup only.

**Do NOT build drug interaction checking.** The free structured sources are gone (NLM's API
was discontinued Jan 2024; DrugBank's free checker retires March 2026), and warning users
about drug combinations is therapeutic — it would pull us under MDR. Names and official
label text only.

---

## 11. Phase 8 — Two-way doctor link

The moat. No competitor allows a clinician to act without an account.

- Extend `share_links` with a scope: `read` or `respond`.
- A `respond` link lets the clinician, still without an account: acknowledge review, leave one
  free-text instruction, and set target ranges.
- New `clinician_targets` table. Add `set_by` (`self` | `clinician`) to the profile's glucose
  thresholds so clinician-set targets are distinguishable and take precedence.
- The instruction appears as a card on the patient's home screen and in the next report.
- Rate limit and log every write. Expiry rules stay as they are.
- This also resolves the food-guidance question: when a clinician sets targets, we are
  tracking adherence to their instruction rather than advising anyone.

---

## 12. Do not build

- Drug interaction checking (see 10)
- CGM integration, coaching, meal plans, calorie targets, insulin dosing — all therapeutic
- Anything that outputs an interpretation of the user's condition
- WhatsApp Business API — costs money from 1 Oct 2026; Web Push is free
- A merged food database redistributed publicly (ODbL share-alike)
- Any paid API tier

---

## 13. Definition of done, per task

1. Tests written and passing
2. `pint`, `phpstan`, `pest`, `npm run types:check` all clean
3. No new user-facing string violates rule 2.1
4. Any new external call added to the privacy policy and Art. 30 record
5. Any new model has a policy, and the policy has tests

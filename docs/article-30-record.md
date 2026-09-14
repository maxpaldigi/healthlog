# Article 30 record of processing

Controller: HealthyMe (operating from Portugal / EU).
Purpose: personal blood pressure and blood sugar record-keeping, visit reports, login.

| Processing | Data | Recipients | Lawful basis | Retention |
| --- | --- | --- | --- | --- |
| Account | Name, email | Laravel Cloud hosting | Contract / terms | Until account deletion |
| Health readings | BP, glucose, optional height and weight, BMI shown from those numbers, HbA1c, medication names, notes | Laravel Cloud hosting | Explicit Art. 9 consent (`health_data`) | Until account deletion or consent withdrawal |
| Site visit counting | Page URL, browser/device signals Google Analytics collects (no health readings; no name or email sent by HealthyMe) | Google Analytics | Consent (cookie banner; Consent Mode v2) | Per Google Analytics retention settings |
| Meter or food photo extract | Image of device screen or food (no name, email, or date of birth) | Google Gemini | Explicit Art. 9 consent | Deleted immediately after extract (hourly prune as backup) |
| Login email | Email, name | Resend | Contract | Magic links expire in 15 minutes |
| Web Push reminders | Browser push endpoint and keys | Browser push services (Mozilla, Google, Apple) | Optional notification permission | Until unsubscribe, key rotation, or account deletion |
| Caregiver invite | Invitee email, profile name | Resend | Contract / the owner inviting a helper | Invite link expires in 7 days; access until revoked |
| Food lookup | Barcode or search words (no name, email, or date of birth) | Open Food Facts | Explicit Art. 9 consent (`health_data`) | Cached product facts; food log until account deletion |
| Medication name lookup | Search words (no name, email, or date of birth) | U.S. National Library of Medicine RxNorm | Explicit Art. 9 consent (`health_data`) | Cached names; medication list until account deletion |
| Share link | Readings in the chosen date range | Anyone with the link | Separate `share_link` consent | Link expiry (1–30 days) |
| Clinic reply on a share link | Note and optional sugar targets written by whoever has the reply link | Stored on the person’s account (visible to them and helpers) | Separate `share_link` consent | Writes stop at link expiry; note until account deletion; targets until the person changes them |
| Operator directory | Name, email, join date, last log date, entry counts, health-data consent yes/no | HealthyMe operator | Contract / running the service | Until account deletion |

No identifiers are sent to Gemini. No merged food database is distributed.

Policy version: 2026-09-14.

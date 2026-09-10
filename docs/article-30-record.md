# Article 30 record of processing

Controller: HealthyMe (operating from Portugal / EU).
Purpose: personal blood pressure and blood sugar record-keeping, visit reports, login.

| Processing | Data | Recipients | Lawful basis | Retention |
| --- | --- | --- | --- | --- |
| Account | Name, email | Laravel Cloud hosting | Contract / terms | Until account deletion |
| Health readings | BP, glucose, optional weight, HbA1c, medication names, notes | Laravel Cloud hosting | Explicit Art. 9 consent (`health_data`) | Until account deletion or consent withdrawal |
| Meter photo extract | Image of device screen (no name, email, or date of birth) | Google Gemini | Explicit Art. 9 consent | Deleted immediately after extract (hourly prune as backup) |
| Login email | Email, name | Resend | Contract | Magic links expire in 15 minutes |
| Web Push reminders | Browser push endpoint and keys | Browser push services (Mozilla, Google, Apple) | Optional notification permission | Until unsubscribe, key rotation, or account deletion |
| Share link | Readings in the chosen date range | Anyone with the link | Separate `share_link` consent | Link expiry (1–30 days) |

No identifiers are sent to Gemini. No merged food database is distributed.

Policy version: 2026-09-10.

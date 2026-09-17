# HealthyMe Flutter (Android)

Android client for [HealthyMe](https://healthyme.fit). The website stays Laravel + Vue; this app talks to the Laravel JSON API at `/api/v1`.

**Not a medical device. Not medical advice.** Descriptive logbook only.

## Run on iPhone Simulator

With the simulator already open (e.g. iPhone 17):

```bash
cd mobile
flutter pub get
flutter run -d iPhone --dart-define=API_BASE=https://healthme.test
```

Or production (after the API is deployed):

```bash
flutter run -d iPhone
```

Login: enter email → paste the magic link / local `login_url` → Open HealthyMe.

## Login flow

1. Enter email → `POST /api/v1/auth/magic-link`
2. Open the email link (or paste the local `login_url`) → `POST /api/v1/auth/token`
3. Bearer token stored on device; Home uses `GET /api/v1/home`

## Current MVP screens

- Magic-link login
- Home (today’s BP slots)
- Add blood pressure
- Health-data consent auto-prompt when API returns `consent_required`

Next: sugar log, scan, people, FCM reminders, Play Store listing.

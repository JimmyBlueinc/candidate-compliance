# UI Acceptance Suite (Playwright)

This suite validates role-based UI flows for:

- Recruiter
- Compliance
- Candidate
- Finance

It also includes a contrast guard for tinted/gradient cards to ensure no dark text classes are used on those cards.

## 1) Install

From `backend/`:

```bash
npm install -D @playwright/test
npx playwright install
```

## 2) Configure environment

Copy and fill:

```bash
cp tests/ui/.env.ui.example tests/ui/.env.ui
```

Then export values (or load with your shell tooling):

- `UI_BASE_URL`
- `API_BASE_URL`
- `UI_RECRUITER_EMAIL`, `UI_RECRUITER_PASSWORD`
- `UI_COMPLIANCE_EMAIL`, `UI_COMPLIANCE_PASSWORD`
- `UI_CANDIDATE_EMAIL`, `UI_CANDIDATE_PASSWORD`
- `UI_FINANCE_EMAIL`, `UI_FINANCE_PASSWORD`

## 3) Run

```bash
npx playwright test
```

Headed:

```bash
npx playwright test --headed
```

Open HTML report:

```bash
npx playwright show-report
```

## Notes

- Tests authenticate via API and seed browser localStorage session keys used by the app.
- Tests are read-only (no destructive actions).
- Compliance modal test only verifies opening and action controls visibility.


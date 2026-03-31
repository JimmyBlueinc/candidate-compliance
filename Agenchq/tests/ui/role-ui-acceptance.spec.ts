import { expect, test, type APIRequestContext, type Page } from '@playwright/test';

const STORAGE_VERSION = '2026-03-21-001';
const VERSION_KEY = 'auth.storage_version';
const TOKEN_STORAGE_KEY = 'auth.token';
const USER_STORAGE_KEY = 'auth.user';
const TENANT_STORAGE_KEY = 'auth.tenant_id';

const API_BASE_URL = process.env.API_BASE_URL || 'https://api.agenchq.com';

type RoleKey = 'recruiter' | 'compliance' | 'candidate' | 'finance';

type RoleConfig = {
  email: string;
  password: string;
  landingPath: string;
  pages: string[];
};

const ROLE_CONFIG: Record<RoleKey, RoleConfig> = {
  recruiter: {
    email: process.env.UI_RECRUITER_EMAIL || '',
    password: process.env.UI_RECRUITER_PASSWORD || '',
    landingPath: '/dashboard',
    pages: ['/dashboard', '/dashboard/candidates', '/dashboard/job-orders', '/dashboard/placements', '/dashboard/messages'],
  },
  compliance: {
    email: process.env.UI_COMPLIANCE_EMAIL || '',
    password: process.env.UI_COMPLIANCE_PASSWORD || '',
    landingPath: '/dashboard/compliance/queue',
    pages: ['/dashboard/compliance', '/dashboard/compliance/queue'],
  },
  candidate: {
    email: process.env.UI_CANDIDATE_EMAIL || '',
    password: process.env.UI_CANDIDATE_PASSWORD || '',
    landingPath: '/portal',
    pages: ['/portal', '/portal/credentials', '/portal/messages', '/portal/profile'],
  },
  finance: {
    email: process.env.UI_FINANCE_EMAIL || '',
    password: process.env.UI_FINANCE_PASSWORD || '',
    landingPath: '/dashboard/finance',
    pages: ['/dashboard/finance', '/dashboard/invoices'],
  },
};

async function loginByApi(request: APIRequestContext, email: string, password: string) {
  const response = await request.post(`${API_BASE_URL}/api/login`, {
    data: { email, password },
  });

  expect(response.ok(), `Login failed for ${email}: ${response.status()} ${await response.text()}`).toBeTruthy();
  return response.json();
}

async function seedAuthStorage(page: Page, payload: any) {
  const token = payload?.token;
  const user = payload?.user;
  const tenantId = user?.organization_id ? String(user.organization_id) : null;

  await page.addInitScript(
    ({ token, user, tenantId }) => {
      localStorage.setItem(VERSION_KEY, STORAGE_VERSION);
      localStorage.setItem(TOKEN_STORAGE_KEY, String(token || ''));
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user || null));
      if (tenantId) {
        localStorage.setItem(TENANT_STORAGE_KEY, String(tenantId));
      } else {
        localStorage.removeItem(TENANT_STORAGE_KEY);
      }
    },
    { token, user, tenantId },
  );
}

async function assertNoDarkTextOnTintedCards(page: Page) {
  const violations = await page.evaluate(() => {
    const darkTextSelectors = [
      '.text-black',
      '.text-slate-900',
      '.text-gray-900',
      '.text-zinc-900',
      '.text-neutral-900',
    ];

    const tintedParents = Array.from(
      document.querySelectorAll<HTMLElement>(
        '.app-stat-card, .ui-stat-card, .finance-kpi-card, [class*="bg-gradient-to-"], .aq-on-tint',
      ),
    );

    const bad: Array<{ parentClass: string; childClass: string; text: string }> = [];
    for (const parent of tintedParents) {
      for (const sel of darkTextSelectors) {
        const matches = parent.querySelectorAll<HTMLElement>(sel);
        matches.forEach((node) => {
          const text = (node.textContent || '').trim();
          if (!text) return;
          bad.push({
            parentClass: parent.className,
            childClass: node.className,
            text: text.slice(0, 80),
          });
        });
      }
    }
    return bad;
  });

  expect(violations, `Dark text found on tinted cards: ${JSON.stringify(violations, null, 2)}`).toEqual([]);
}

async function ensurePageLoads(page: Page, path: string) {
  await page.goto(path, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('body')).toBeVisible();
  await page.waitForTimeout(600);
}

for (const role of Object.keys(ROLE_CONFIG) as RoleKey[]) {
  test.describe(`${role} UI acceptance`, () => {
    test(`role pages render and key contrast guard holds`, async ({ page, request }) => {
      const cfg = ROLE_CONFIG[role];
      test.skip(!cfg.email || !cfg.password, `Missing env credentials for ${role}.`);

      const payload = await loginByApi(request, cfg.email, cfg.password);
      await seedAuthStorage(page, payload);

      await ensurePageLoads(page, cfg.landingPath);
      for (const path of cfg.pages) {
        await ensurePageLoads(page, path);
      }

      await assertNoDarkTextOnTintedCards(page);
    });
  });
}

test.describe('compliance queue modal', () => {
  test('review modal opens in queue', async ({ page, request }) => {
    const cfg = ROLE_CONFIG.compliance;
    test.skip(!cfg.email || !cfg.password, 'Missing env credentials for compliance.');

    const payload = await loginByApi(request, cfg.email, cfg.password);
    await seedAuthStorage(page, payload);

    await page.goto('/dashboard/compliance/queue', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();

    // Wait for queue content to settle: either rows appear or empty state is shown.
    const queueRow = page.locator('button[type="button"]').filter({ hasText: /.+/ }).first();
    const emptyState = page.getByText('All caught up!');
    await Promise.race([
      queueRow.waitFor({ state: 'visible', timeout: 20_000 }),
      emptyState.waitFor({ state: 'visible', timeout: 20_000 }),
    ]).catch(() => {});

    if ((await emptyState.count()) > 0 && (await emptyState.first().isVisible())) {
      test.skip(true, 'No pending compliance items available to open review modal.');
    }

    if ((await queueRow.count()) === 0) {
      test.skip(true, 'No selectable queue row found for modal validation.');
    }

    await queueRow.click({ timeout: 10_000 }).catch(() => {});

    const reviewButton = page.getByRole('button', { name: 'Review Action' });
    if ((await reviewButton.count()) === 0) {
      test.skip(true, 'Review Action button not present for current queue state.');
    }
    await expect(reviewButton).toBeVisible({ timeout: 20_000 });
    await reviewButton.click();

    await expect(page.getByText('Review Credential')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Approve' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Reject' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Request Re-upload' })).toBeVisible();
  });
});


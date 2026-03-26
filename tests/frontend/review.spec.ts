import { test, expect } from '@playwright/test';
import { findPostInAdmin, collectConsoleErrors, collectFailedRequests } from '../helpers/wp-admin';
import { CASINOS } from '../helpers/data';

const BASE_URL = process.env.BASE_URL || 'http://casino-compare.local';

/** Derive the casino review URL slug from its title. */
function casinoSlug(title: string): string {
  return title.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
}

test.describe('Frontend — Casino Review', () => {
  test('PW Lunara Casino opens at /avis/{slug}/', async ({ page }) => {
    const casinoTitle = CASINOS[0].title;
    // Find the published casino to get its slug
    const id = await findPostInAdmin(page, 'casino', casinoTitle);
    expect(id, 'Casino must exist — run qa:seed first').toBeTruthy();

    // Navigate to the frontend using the WP permalink
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a, a.view-post-btn').first();

    if (await viewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      const href = await viewLink.getAttribute('href');
      if (href) {
        await page.goto(href);
        await expect(page).toHaveURL(/\/avis\//);
      }
    } else {
      // Derive URL from slug
      await page.goto(`/avis/pw-lunara-casino/`);
    }

    await expect(page.locator('h1')).toContainText('PW Lunara Casino');
  });

  test('Casino review page contains intro text', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    await expect(page.locator('body')).toContainText('Lunara Casino est');
  });

  test('Casino review page has CTA button', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    const cta = page.locator('a.ccc-cta, a[href*="example.com/go"], .cta-block a').first();
    await expect(cta).toBeVisible();
  });

  test('Casino review shows rating block', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    // Rating value should appear somewhere on the page
    await expect(page.locator('body')).toContainText(CASINOS[0].overallRating);
  });

  test('Casino review shows pros and cons', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    await expect(page.locator('body')).toContainText(CASINOS[0].pros[0]);
    await expect(page.locator('body')).toContainText(CASINOS[0].cons[0]);
  });

  test('Casino review shows summary section', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    await expect(page.locator('body')).toContainText(CASINOS[0].summary1Title);
  });

  test('Casino review page has compare button', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    const compareBtn = page.locator('[data-ccc-compare-id]').first();
    await expect(compareBtn).toBeVisible();
  });

  test('Casino review has no critical console errors', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/avis/pw-lunara-casino/');

    const critical = errors.filter(
      (e) => !e.includes('favicon') && !e.includes('mixed content') && e.trim() !== '',
    );
    expect(critical, `Console errors: ${critical.join('\n')}`).toHaveLength(0);
  });

  test('Casino review has no failed network requests', async ({ page }) => {
    const failed = collectFailedRequests(page);
    await page.goto('/avis/pw-lunara-casino/');
    await page.waitForLoadState('networkidle');

    const serious = failed.filter((f) => !f.includes('favicon'));
    expect(serious, `Failed requests: ${serious.join('\n')}`).toHaveLength(0);
  });

  test('All 3 PW casinos have accessible review pages', async ({ page }) => {
    const slugs = ['pw-lunara-casino', 'pw-novajackpot', 'pw-hexaspin'];
    for (const slug of slugs) {
      const response = await page.goto(`/avis/${slug}/`);
      expect(response?.status(), `${slug} should return 200`).toBe(200);
      await expect(page.locator('h1')).toBeVisible();
    }
  });
});

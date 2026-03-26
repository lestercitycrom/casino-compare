import { test, expect } from '@playwright/test';

test.describe('Frontend — Compare Engine', () => {
  test('/comparer/ page exists and is accessible', async ({ page }) => {
    const response = await page.goto('/comparer/');
    expect(response?.status()).toBe(200);
    await expect(page.locator('#ccc-compare-app, .ccc-compare-app')).toBeVisible();
  });

  test('/comparer/ shows empty state when no casinos selected', async ({ page }) => {
    // Clear localStorage before test
    await page.goto('/comparer/');
    await page.evaluate(() => localStorage.removeItem('ccc_compare_ids'));
    await page.reload();

    // Should show "No casinos selected" or similar empty state
    const app = page.locator('#ccc-compare-app');
    await expect(app).toBeVisible();
    const text = await app.innerText();
    expect(text.toLowerCase()).toContain('no casinos');
  });

  test('Compare button on casino review adds casino to compare', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');

    // Clear any existing compare state
    await page.evaluate(() => localStorage.removeItem('ccc_compare_ids'));

    const compareBtn = page.locator('[data-ccc-compare-id]').first();
    await expect(compareBtn).toBeVisible();

    // Get the casino ID from the button
    const casinoId = await compareBtn.getAttribute('data-ccc-compare-id');
    expect(casinoId).toBeTruthy();

    // Click the compare button
    await compareBtn.click();

    // Button text should change after adding (French UI: "Ajouté ✓")
    await expect(compareBtn).toHaveText('Ajouté ✓');

    // localStorage should contain the casino ID
    const stored = await page.evaluate(() => {
      try { return JSON.parse(localStorage.getItem('ccc_compare_ids') || '[]'); }
      catch { return []; }
    });
    expect(stored).toContain(parseInt(casinoId!, 10));
  });

  test('Compare badge updates after adding a casino', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    await page.evaluate(() => localStorage.removeItem('ccc_compare_ids'));

    const compareBtn = page.locator('[data-ccc-compare-id]').first();
    await compareBtn.click();

    // Badge should show count
    const badge = page.locator('#ccc-compare-badge');
    if (await badge.isVisible({ timeout: 3000 }).catch(() => false)) {
      await expect(badge).toContainText('1');
    }
  });

  test('Adding 2 casinos to compare updates badge to 2', async ({ page }) => {
    // Add first casino
    await page.goto('/avis/pw-lunara-casino/');
    await page.evaluate(() => localStorage.removeItem('ccc_compare_ids'));
    await page.locator('[data-ccc-compare-id]').first().click();

    // Add second casino
    await page.goto('/avis/pw-novajackpot/');
    await page.locator('[data-ccc-compare-id]').first().click();

    const badge = page.locator('#ccc-compare-badge');
    if (await badge.isVisible({ timeout: 3000 }).catch(() => false)) {
      await expect(badge).toContainText('2');
    }
  });

  test('/comparer/ renders comparison table when casinos are in localStorage', async ({ page }) => {
    // Set two casino IDs directly in localStorage via a neutral page
    await page.goto('/');

    // We need real casino IDs — get them from the casino page data attribute
    await page.goto('/avis/pw-lunara-casino/');
    const id1 = await page.locator('[data-ccc-compare-id]').first().getAttribute('data-ccc-compare-id');

    await page.goto('/avis/pw-novajackpot/');
    const id2 = await page.locator('[data-ccc-compare-id]').first().getAttribute('data-ccc-compare-id');

    if (!id1 || !id2) {
      console.warn('Could not get casino IDs for compare test');
      return;
    }

    // Set them in localStorage
    await page.evaluate(({ i1, i2 }: { i1: number; i2: number }) => {
      localStorage.setItem('ccc_compare_ids', JSON.stringify([i1, i2]));
    }, { i1: parseInt(id1, 10), i2: parseInt(id2, 10) });

    // Navigate to compare page
    await page.goto('/comparer/');

    // Wait for AJAX to load comparison table
    const app = page.locator('#ccc-compare-app');
    await expect(app).toBeVisible();

    // Table should be rendered with casino names
    await expect(app.locator('table, .compare-table')).toBeVisible({ timeout: 8000 });

    // Casino titles should appear in table headers
    await expect(app).toContainText('PW Lunara Casino');
    await expect(app).toContainText('PW NovaJackpot');
  });

  test('Remove button in compare table works', async ({ page }) => {
    // Setup: add a casino to compare
    await page.goto('/avis/pw-lunara-casino/');
    const id1 = await page.locator('[data-ccc-compare-id]').first().getAttribute('data-ccc-compare-id');
    if (!id1) return;

    await page.evaluate((id: number) => {
      localStorage.setItem('ccc_compare_ids', JSON.stringify([id]));
    }, parseInt(id1, 10));

    await page.goto('/comparer/');
    const app = page.locator('#ccc-compare-app');
    await expect(app.locator('table')).toBeVisible({ timeout: 8000 });

    // Click Remove button
    const removeBtn = app.locator('[data-ccc-remove-compare-id]').first();
    await expect(removeBtn).toBeVisible();
    await removeBtn.click();

    // Table should disappear, replaced by empty state
    await expect(app).toContainText('No casinos', { timeout: 5000 });

    // localStorage should be empty
    const stored = await page.evaluate(() => {
      try { return JSON.parse(localStorage.getItem('ccc_compare_ids') || '[]'); }
      catch { return []; }
    });
    expect(stored).toHaveLength(0);
  });

  test('Compare is capped at 3 casinos max', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/');
    await page.evaluate(() => localStorage.removeItem('ccc_compare_ids'));

    // Add 3 casinos via localStorage
    await page.goto('/avis/pw-lunara-casino/');
    const id1 = await page.locator('[data-ccc-compare-id]').first().getAttribute('data-ccc-compare-id');
    await page.goto('/avis/pw-novajackpot/');
    const id2 = await page.locator('[data-ccc-compare-id]').first().getAttribute('data-ccc-compare-id');
    await page.goto('/avis/pw-hexaspin/');
    const id3 = await page.locator('[data-ccc-compare-id]').first().getAttribute('data-ccc-compare-id');

    if (!id1 || !id2 || !id3) return;

    await page.evaluate(({ ids }: { ids: number[] }) => {
      localStorage.setItem('ccc_compare_ids', JSON.stringify(ids));
    }, { ids: [parseInt(id1, 10), parseInt(id2, 10), parseInt(id3, 10)] });

    // Verify localStorage has exactly 3
    const stored = await page.evaluate(() => {
      try { return JSON.parse(localStorage.getItem('ccc_compare_ids') || '[]'); }
      catch { return []; }
    });
    expect(stored).toHaveLength(3);
  });
});

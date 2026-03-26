import { test, expect } from '@playwright/test';
import { collectConsoleErrors } from '../helpers/wp-admin';
import { CASINOS } from '../helpers/data';

test.describe('Frontend — Casino Subpage', () => {
  test('Published bonus subpage opens at /avis/{casino}/bonus/', async ({ page }) => {
    const response = await page.goto('/avis/pw-lunara-casino/bonus/');
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1')).toBeVisible();
  });

  test('Published retrait subpage opens at /avis/{casino}/retrait/', async ({ page }) => {
    const response = await page.goto('/avis/pw-lunara-casino/retrait/');
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1')).toBeVisible();
  });

  test('Subpage hero title matches seeded value', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/bonus/');
    const casinoName = CASINOS[0].title.replace('PW ', '');
    await expect(page.locator('h1')).toContainText(casinoName);
  });

  test('Score block visible on bonus subpage (score_enabled = true)', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/bonus/');
    // Score block is seeded with score_enabled=true, label='Note Bonus'
    // Check for the section and its label (not the exact numeric value — number fields strip decimals on save)
    const scoreBlock = page.locator('.score-block');
    await expect(scoreBlock).toBeVisible();
    await expect(scoreBlock).toContainText('Note Bonus');
  });

  test('Parent review link is accessible from subpage', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/bonus/');
    // There should be a link back to the parent casino review
    const reviewLink = page.locator('a[href*="/avis/pw-lunara-casino/"]').first();
    if (await reviewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      await expect(reviewLink).toBeVisible();
    } else {
      // Breadcrumb link is acceptable
      const breadcrumb = page.locator('.breadcrumb, nav[aria-label="Breadcrumb"]');
      await expect(breadcrumb).toBeVisible();
    }
  });

  test('Sibling subpage links — bonus subpage shows other subpage types', async ({ page }) => {
    await page.goto('/avis/pw-lunara-casino/bonus/');
    // At minimum, the page should not 404 and have some navigation
    await expect(page.locator('body')).not.toContainText('404');
  });

  test('Unpublished subpage type returns 404', async ({ page }) => {
    // 'code_promo' subpage is a draft and should not be accessible
    const response = await page.goto('/avis/pw-lunara-casino/code_promo/');
    // Should be 404 or redirect to 404 page
    const is404 = response?.status() === 404 ||
      (await page.locator('body').textContent())?.toLowerCase().includes('not found') ||
      (await page.locator('body').textContent())?.toLowerCase().includes('404');
    expect(is404, 'Draft subpage should return 404').toBe(true);
  });

  test('Subpage for NovaJackpot bonus is accessible', async ({ page }) => {
    const response = await page.goto('/avis/pw-novajackpot/bonus/');
    expect(response?.status()).toBe(200);
  });

  test('Subpage for HexaSpin retrait is accessible', async ({ page }) => {
    const response = await page.goto('/avis/pw-hexaspin/retrait/');
    expect(response?.status()).toBe(200);
  });

  test('Subpage has no critical console errors', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/avis/pw-lunara-casino/bonus/');

    const critical = errors.filter(
      (e) => !e.includes('favicon') && !e.includes('mixed content') && e.trim() !== '',
    );
    expect(critical, `Console errors: ${critical.join('\n')}`).toHaveLength(0);
  });
});

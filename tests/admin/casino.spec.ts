import { test, expect } from '@playwright/test';
import { findPostInAdmin, gotoEditPost, getPostIdFromUrl, hasPhpErrors } from '../helpers/wp-admin';
import { CASINOS } from '../helpers/data';

test.describe('Admin — Casino CPT', () => {
  test('admin menu has Casinos link', async ({ page }) => {
    await page.goto('/wp-admin/');
    const casinosLink = page.locator('#menu-posts-casino a').filter({ hasText: 'Casinos' }).first();
    await expect(casinosLink).toBeVisible();
  });

  test('Casino list page loads without errors', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=casino');
    await expect(page.locator('#wpbody-content')).toBeVisible();
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('New casino edit screen opens without PHP errors', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=casino');
    await page.waitForSelector('#title');
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('PW casino edit screen has all meta box sections', async ({ page }) => {
    const casinoId = await findPostInAdmin(page, 'casino', CASINOS[0].title);
    expect(casinoId, `Casino "${CASINOS[0].title}" must exist — run qa:seed first`).toBeTruthy();

    await gotoEditPost(page, casinoId!);
    expect(await hasPhpErrors(page)).toBe(false);

    // Check all meta box headings are present
    await expect(page.locator('#ccc_casino_brand')).toBeVisible();
    await expect(page.locator('#ccc_casino_rating')).toBeVisible();
    await expect(page.locator('#ccc_casino_bonus')).toBeVisible();
    await expect(page.locator('#ccc_casino_technical')).toBeVisible();
    await expect(page.locator('#ccc_casino_content')).toBeVisible();
    await expect(page.locator('#ccc_casino_relations_seo')).toBeVisible();
  });

  test('PW casino field values persist after reload', async ({ page }) => {
    const casinoId = await findPostInAdmin(page, 'casino', CASINOS[0].title);
    expect(casinoId).toBeTruthy();

    await gotoEditPost(page, casinoId!);

    // Verify key text fields
    await expect(page.locator('input[name="affiliate_link"]')).toHaveValue(CASINOS[0].affiliateLink);
    await expect(page.locator('input[name="license"]')).toHaveValue(CASINOS[0].license);
    await expect(page.locator('input[name="overall_rating"]')).toHaveValue(CASINOS[0].overallRating);
    await expect(page.locator('input[name="wagering"]')).toHaveValue(CASINOS[0].wagering);
  });

  test('PW casino taxonomy terms are assigned', async ({ page }) => {
    const casinoId = await findPostInAdmin(page, 'casino', CASINOS[0].title);
    expect(casinoId).toBeTruthy();

    // casino_license is non-hierarchical — verify via REST API (more reliable than admin UI).
    await page.goto('/wp-admin/');
    const nonce: string = await page.evaluate(() => (window as any).wpApiSettings?.nonce ?? '');
    const res = await page.request.get(
      `/wp-json/wp/v2/casino/${casinoId}?context=edit`,
      { headers: nonce ? { 'X-WP-Nonce': nonce } : {} },
    );
    expect(res.ok()).toBeTruthy();
    const data = await res.json() as { casino_license?: number[] };
    expect(data.casino_license?.length).toBeGreaterThan(0);
  });

  test('All 3 PW casinos exist in admin list', async ({ page }) => {
    for (const casino of CASINOS) {
      const id = await findPostInAdmin(page, 'casino', casino.title);
      expect(id, `Casino "${casino.title}" should exist`).toBeTruthy();
    }
  });

  test('admin console has no critical errors on casino edit screen', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') errors.push(msg.text());
    });

    const casinoId = await findPostInAdmin(page, 'casino', CASINOS[0].title);
    expect(casinoId).toBeTruthy();
    await gotoEditPost(page, casinoId!);

    // Filter out known benign WP admin noise
    const criticalErrors = errors.filter(
      (e) => !e.includes('favicon') && !e.includes('mixed content') && e.trim() !== '',
    );
    expect(criticalErrors, `Critical console errors: ${criticalErrors.join('\n')}`).toHaveLength(0);
  });
});

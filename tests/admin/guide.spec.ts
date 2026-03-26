import { test, expect } from '@playwright/test';
import { findPostInAdmin, gotoEditPost, hasPhpErrors } from '../helpers/wp-admin';
import { GUIDE } from '../helpers/data';

test.describe('Admin — Guide CPT', () => {
  test('admin menu has Guides link', async ({ page }) => {
    await page.goto('/wp-admin/');
    const link = page.locator('#menu-posts-guide a').filter({ hasText: 'Guides' }).first();
    await expect(link).toBeVisible();
  });

  test('Guide list page loads without errors', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=guide');
    await expect(page.locator('#wpbody-content')).toBeVisible();
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('New guide edit screen opens without PHP errors', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=guide');
    await page.waitForSelector('#title');
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('PW guide edit screen opens and has correct field values', async ({ page }) => {
    const id = await findPostInAdmin(page, 'guide', GUIDE.title);
    expect(id, `Guide "${GUIDE.title}" must exist — run qa:seed first`).toBeTruthy();

    await gotoEditPost(page, id!);
    expect(await hasPhpErrors(page)).toBe(false);

    await expect(page.locator('input[name="category"]')).toHaveValue(GUIDE.category);
    await expect(page.locator('input[name="author_name"]')).toHaveValue(GUIDE.authorName);
    await expect(page.locator('input[name="reading_time"]')).toHaveValue(GUIDE.readingTime);
    await expect(page.locator('input[name="last_updated"]')).toHaveValue(GUIDE.lastUpdated);
  });

  test('PW guide meta boxes are present', async ({ page }) => {
    const id = await findPostInAdmin(page, 'guide', GUIDE.title);
    expect(id).toBeTruthy();

    await gotoEditPost(page, id!);

    await expect(page.locator('#ccc_guide_content')).toBeVisible();
    await expect(page.locator('#ccc_guide_sidebar')).toBeVisible();
  });

  test('PW guide sidebar casino list relation field is visible', async ({ page }) => {
    const id = await findPostInAdmin(page, 'guide', GUIDE.title);
    expect(id).toBeTruthy();

    await gotoEditPost(page, id!);

    const sidebarCasinoSelect = page.locator('select[name="sidebar_casino_list[]"]');
    await expect(sidebarCasinoSelect).toBeVisible();
  });

  test('PW guide callout_enabled field is present and visible', async ({ page }) => {
    const id = await findPostInAdmin(page, 'guide', GUIDE.title);
    expect(id).toBeTruthy();

    await gotoEditPost(page, id!);

    // Phase 1 gap closed by Chester — now a hard assertion
    await expect(page.locator('input[name="callout_enabled"]')).toBeVisible();
  });

  test('admin console has no critical errors on guide edit screen', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') errors.push(msg.text());
    });

    const id = await findPostInAdmin(page, 'guide', GUIDE.title);
    expect(id).toBeTruthy();
    await gotoEditPost(page, id!);

    const criticalErrors = errors.filter(
      (e) => !e.includes('favicon') && !e.includes('mixed content') && e.trim() !== '',
    );
    expect(criticalErrors, `Critical console errors: ${criticalErrors.join('\n')}`).toHaveLength(0);
  });
});

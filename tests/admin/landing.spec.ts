import { test, expect } from '@playwright/test';
import { findPostInAdmin, gotoEditPost, hasPhpErrors } from '../helpers/wp-admin';
import { HUB_LANDING, COMPARISON_LANDING, TRUST_LANDING } from '../helpers/data';

test.describe('Admin — Landing CPT', () => {
  test('admin menu has Landings link', async ({ page }) => {
    await page.goto('/wp-admin/');
    const link = page.locator('#menu-posts-landing a').filter({ hasText: 'Landings' }).first();
    await expect(link).toBeVisible();
  });

  test('Landing list page loads without errors', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=landing');
    await expect(page.locator('#wpbody-content')).toBeVisible();
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('New landing edit screen opens without PHP errors', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=landing');
    await page.waitForSelector('#title');
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('landing_type select triggers conditional visibility', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=landing');
    await page.waitForSelector('#title');

    // Select hub — hub block should be visible, comparison and trust hidden
    await page.selectOption('select[name="landing_type"]', 'hub');
    await page.dispatchEvent('select[name="landing_type"]', 'change');

    const hubBlock = page.locator('[data-ccc-condition-value="hub"]');
    const compBlock = page.locator('[data-ccc-condition-value="comparison"]');
    const trustBlock = page.locator('[data-ccc-condition-value="trust"]');

    await expect(hubBlock.first()).not.toHaveCSS('display', 'none');
    await expect(compBlock.first()).toHaveCSS('display', 'none');
    await expect(trustBlock.first()).toHaveCSS('display', 'none');

    // Switch to comparison
    await page.selectOption('select[name="landing_type"]', 'comparison');
    await page.dispatchEvent('select[name="landing_type"]', 'change');

    await expect(compBlock.first()).not.toHaveCSS('display', 'none');
    await expect(hubBlock.first()).toHaveCSS('display', 'none');
    await expect(trustBlock.first()).toHaveCSS('display', 'none');

    // Switch to trust
    await page.selectOption('select[name="landing_type"]', 'trust');
    await page.dispatchEvent('select[name="landing_type"]', 'change');

    await expect(trustBlock.first()).not.toHaveCSS('display', 'none');
    await expect(hubBlock.first()).toHaveCSS('display', 'none');
    await expect(compBlock.first()).toHaveCSS('display', 'none');
  });

  test('PW hub landing edit screen opens without errors', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', HUB_LANDING.title);
    expect(id, `Hub landing "${HUB_LANDING.title}" must exist — run qa:seed first`).toBeTruthy();

    await gotoEditPost(page, id!);
    expect(await hasPhpErrors(page)).toBe(false);

    await expect(page.locator('select[name="landing_type"]')).toHaveValue('hub');
  });

  test('PW comparison landing edit screen opens without errors', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
    expect(id, `Comparison landing must exist — run qa:seed first`).toBeTruthy();

    await gotoEditPost(page, id!);
    expect(await hasPhpErrors(page)).toBe(false);

    await expect(page.locator('select[name="landing_type"]')).toHaveValue('comparison');
    await expect(page.locator('input[name="author_name"]')).toHaveValue(COMPARISON_LANDING.authorName);
  });

  test('PW trust landing edit screen shows author and date fields', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
    expect(id, `Trust landing must exist — run qa:seed first`).toBeTruthy();

    await gotoEditPost(page, id!);
    expect(await hasPhpErrors(page)).toBe(false);

    await expect(page.locator('select[name="landing_type"]')).toHaveValue('trust');

    // Trust fields: show_author checkbox, trust_author_name, trust_last_updated
    await expect(page.locator('input[name="trust_author_name"]')).toHaveValue(TRUST_LANDING.authorName);
    await expect(page.locator('input[name="trust_last_updated"]')).toHaveValue(TRUST_LANDING.lastUpdated);
  });

  test('PW comparison landing is nested under hub landing', async ({ page }) => {
    const compId = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
    expect(compId).toBeTruthy();

    await gotoEditPost(page, compId!);

    // Parent page select should have a non-zero value
    const parentSelect = page.locator('select[name="parent_id"]');
    if (await parentSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
      const parentValue = await parentSelect.inputValue();
      expect(parseInt(parentValue, 10)).toBeGreaterThan(0);
    }
  });
});

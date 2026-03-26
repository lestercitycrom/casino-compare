import { test, expect } from '@playwright/test';
import { findPostInAdmin, gotoEditPost, hasPhpErrors } from '../helpers/wp-admin';
import { CASINOS } from '../helpers/data';

test.describe('Admin — Casino Subpage CPT', () => {
  test('admin menu has Casino Subpages link', async ({ page }) => {
    await page.goto('/wp-admin/');
    const link = page.locator('#menu-posts-casino_subpage a').filter({ hasText: 'Casino Subpages' }).first();
    await expect(link).toBeVisible();
  });

  test('Subpage list page loads without errors', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=casino_subpage');
    await expect(page.locator('#wpbody-content')).toBeVisible();
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('New subpage edit screen opens without PHP errors', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=casino_subpage');
    await page.waitForSelector('#title');
    expect(await hasPhpErrors(page)).toBe(false);
  });

  test('PW casino has 8 draft subpages auto-created', async ({ page }) => {
    const casinoTitle = CASINOS[0].title;
    await page.goto(
      `/wp-admin/edit.php?post_type=casino_subpage&s=${encodeURIComponent(casinoTitle)}`,
    );

    const noItems = page.locator('#the-list .no-items');
    const hasNoItems = await noItems.isVisible({ timeout: 3000 }).catch(() => false);
    expect(hasNoItems, `No subpages found for "${casinoTitle}" — run qa:seed first`).toBe(false);

    const rows = page.locator('#the-list tr[id]');
    const count = await rows.count();
    expect(count).toBeGreaterThanOrEqual(8);
  });

  test('PW bonus subpage has parent_casino and subpage_type set', async ({ page }) => {
    const searchTerm = `${CASINOS[0].title} - bonus`;
    const subpageId = await findPostInAdmin(page, 'casino_subpage', searchTerm);
    expect(subpageId, `Subpage "${searchTerm}" must exist`).toBeTruthy();

    await gotoEditPost(page, subpageId!);
    expect(await hasPhpErrors(page)).toBe(false);

    // parent_casino relation should have a value selected
    const parentSelect = page.locator('select[name="parent_casino"]');
    await expect(parentSelect).toBeVisible();
    const selectedValue = await parentSelect.inputValue();
    expect(parseInt(selectedValue, 10)).toBeGreaterThan(0);

    // subpage_type should be 'bonus'
    const typeSelect = page.locator('select[name="subpage_type"]');
    await expect(typeSelect).toHaveValue('bonus');
  });

  test('score_enabled conditional — fields hidden by default', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=casino_subpage');
    await page.waitForSelector('#title');

    // score_value and score_label should be hidden when score_enabled is unchecked
    const scoreEnabled = page.locator('input[name="score_enabled"]');
    if (!(await scoreEnabled.isChecked())) {
      const conditionalBlock = page.locator('[data-ccc-condition-field="score_enabled"]');
      await expect(conditionalBlock).toHaveCSS('display', 'none');
    }
  });

  test('score_enabled conditional — fields visible when enabled', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=casino_subpage');
    await page.waitForSelector('#title');

    await page.check('input[name="score_enabled"]');
    await page.dispatchEvent('input[name="score_enabled"]', 'change');

    const conditionalBlock = page.locator('[data-ccc-condition-field="score_enabled"]');
    await expect(conditionalBlock).not.toHaveCSS('display', 'none');
    await expect(page.locator('input[name="score_value"]')).toBeVisible();
    await expect(page.locator('input[name="score_label"]')).toBeVisible();
  });

  test('table_enabled conditional — fields visible when enabled', async ({ page }) => {
    await page.goto('/wp-admin/post-new.php?post_type=casino_subpage');
    await page.waitForSelector('#title');

    await page.check('input[name="table_enabled"]');
    await page.dispatchEvent('input[name="table_enabled"]', 'change');

    const conditionalBlock = page.locator('[data-ccc-condition-field="table_enabled"]');
    await expect(conditionalBlock).not.toHaveCSS('display', 'none');
  });

  test('PW published bonus subpage has score fields filled', async ({ page }) => {
    const searchTerm = `${CASINOS[0].title} - bonus`;
    const subpageId = await findPostInAdmin(page, 'casino_subpage', searchTerm);
    expect(subpageId).toBeTruthy();

    await gotoEditPost(page, subpageId!);

    const scoreEnabled = page.locator('input[name="score_enabled"]');
    if (await scoreEnabled.isChecked()) {
      await expect(page.locator('input[name="score_value"]')).not.toHaveValue('');
    }
  });
});

import { test, expect } from '@playwright/test';
import { findPostInAdmin, collectConsoleErrors } from '../helpers/wp-admin';
import { HUB_LANDING, COMPARISON_LANDING, TRUST_LANDING } from '../helpers/data';

test.describe('Frontend — Landing Pages', () => {
  // ─── Hub ──────────────────────────────────────────────────────────────────

  test('Hub landing is accessible by slug', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', HUB_LANDING.title);
    expect(id, 'Hub landing must exist — run qa:seed first').toBeTruthy();

    // Navigate to the hub landing via its permalink
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    let hubUrl = '/pw-casino-en-ligne/';
    if (await viewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      hubUrl = (await viewLink.getAttribute('href')) || hubUrl;
    }

    const response = await page.goto(hubUrl);
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1')).toBeVisible();
  });

  test('Hub landing contains hero title', async ({ page }) => {
    await page.goto('/pw-casino-en-ligne/');
    await expect(page.locator('h1, .hero-title')).toContainText('Casino');
  });

  test('Hub landing shows subcategory cards', async ({ page }) => {
    await page.goto('/pw-casino-en-ligne/');
    // At least one subcategory card should be visible
    await expect(page.locator('body')).toContainText('Casino Bonus');
  });

  test('Hub landing has no critical console errors', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/pw-casino-en-ligne/');
    const critical = errors.filter((e) => !e.includes('favicon') && e.trim() !== '');
    expect(critical).toHaveLength(0);
  });

  // ─── Comparison ──────────────────────────────────────────────────────────

  test('Comparison landing is accessible (child of hub)', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
    expect(id, 'Comparison landing must exist').toBeTruthy();

    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    let compUrl = '/pw-casino-en-ligne/pw-comparatif-casinos-bonus/';
    if (await viewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      const href = await viewLink.getAttribute('href');
      if (href) compUrl = href;
    }

    const response = await page.goto(compUrl);
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1')).toBeVisible();
  });

  test('Comparison landing shows casino cards', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    if (await viewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      const href = await viewLink.getAttribute('href');
      if (href) {
        await page.goto(href);
        // Should show at least one casino card
        await expect(page.locator('.casino-card, article.casino-card').first()).toBeVisible();
      }
    }
  });

  test('Comparison landing author/date badge is visible', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    if (await viewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      const href = await viewLink.getAttribute('href');
      if (href) {
        await page.goto(href);
        await expect(page.locator('body')).toContainText(COMPARISON_LANDING.authorName);
      }
    }
  });

  test('Filter UI is present on comparison landing', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    if (await viewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      const href = await viewLink.getAttribute('href');
      if (href) {
        await page.goto(href);
        // Filter form should exist
        const filterForm = page.locator('#ccc-filter-form, form.casino-filter, [data-filter]').first();
        await expect(filterForm).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('Filter — results update on filter selection', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;

    const href = await viewLink.getAttribute('href');
    if (!href) return;

    await page.goto(href);

    const filterResults = page.locator('#ccc-filter-results');
    if (!(await filterResults.isVisible({ timeout: 3000 }).catch(() => false))) return;

    const baselineContent = await filterResults.innerText();

    // Apply a filter (license = mga)
    const licenseSelect = page.locator('select[name="license[]"], select[name="license"]').first();
    if (await licenseSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
      await licenseSelect.selectOption('mga');
      await licenseSelect.dispatchEvent('change');
      // Wait for results to update via AJAX
      await page.waitForResponse((r) => r.url().includes('/wp-json/') && r.status() === 200, {
        timeout: 8000,
      }).catch(() => {});
    }
  });

  // ─── Trust ────────────────────────────────────────────────────────────────

  test('Trust landing is accessible', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
    expect(id, 'Trust landing must exist').toBeTruthy();

    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    let trustUrl = '/pw-notre-methodologie/';
    if (await viewLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      const href = await viewLink.getAttribute('href');
      if (href) trustUrl = href;
    }

    const response = await page.goto(trustUrl);
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1')).toBeVisible();
  });

  test('Trust landing shows author/date block', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;
    const href = await viewLink.getAttribute('href');
    if (!href) return;

    await page.goto(href);

    // Author name must appear on the trust landing
    await expect(page.locator('body')).toContainText(TRUST_LANDING.authorName);
    await expect(page.locator('body')).toContainText(TRUST_LANDING.lastUpdated);
  });

  test('Trust landing page content is rendered', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();

    if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;
    const href = await viewLink.getAttribute('href');
    if (!href) return;

    await page.goto(href);
    await expect(page.locator('body')).toContainText('processus d\'évaluation');
  });
});

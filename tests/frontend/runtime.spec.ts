/**
 * Runtime checks — scenarios that smoke-test.php cannot cover.
 * These verify actual browser-level rendering and network behavior.
 */
import { test, expect } from '@playwright/test';
import { findPostInAdmin, collectConsoleErrors, collectFailedRequests } from '../helpers/wp-admin';
import { TRUST_LANDING, CASINOS } from '../helpers/data';

test.describe('Runtime Checks', () => {
  // ─── 404 behavior ──────────────────────────────────────────────────────────

  test('Non-existent landing URL returns 404', async ({ page }) => {
    const response = await page.goto('/this-landing-does-not-exist-at-all/');
    expect(response?.status()).toBe(404);
  });

  test('Non-existent nested landing URL returns 404', async ({ page }) => {
    const response = await page.goto('/parent-x/child-y-does-not-exist/');
    expect(response?.status()).toBe(404);
  });

  test('Non-existent casino review returns 404', async ({ page }) => {
    const response = await page.goto('/avis/this-casino-does-not-exist/');
    expect(response?.status()).toBe(404);
  });

  test('Non-existent subpage type returns 404', async ({ page }) => {
    // paris_sportifs is not published for our PW casinos
    const response = await page.goto('/avis/pw-lunara-casino/paris_sportifs/');
    const is404 = response?.status() === 404 ||
      (await page.locator('body').textContent())?.toLowerCase().includes('not found') ||
      (await page.locator('body').textContent())?.toLowerCase().includes('404');
    expect(is404, `Expected 404 for draft subpage type`).toBe(true);
  });

  // ─── Trust landing author/date ─────────────────────────────────────────────

  test('Trust landing ACTUALLY renders author/date block in browser', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
    expect(id, 'Trust landing must exist — run qa:seed first').toBeTruthy();

    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const viewLink = page.locator('#sample-permalink a').first();
    if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;

    const href = await viewLink.getAttribute('href');
    if (!href) {
      console.warn('Could not get permalink for trust landing');
      return;
    }

    await page.goto(href);

    // This is the key runtime check: author_name must visually appear in the browser
    await expect(page.locator('body')).toContainText(TRUST_LANDING.authorName, { timeout: 8000 });
    await expect(page.locator('body')).toContainText(TRUST_LANDING.lastUpdated);
  });

  // ─── Admin edit screens ───────────────────────────────────────────────────

  test('Casino edit screen opens without PHP fatal error', async ({ page }) => {
    const id = await findPostInAdmin(page, 'casino', CASINOS[0].title);
    expect(id).toBeTruthy();

    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    await page.waitForSelector('#wpbody-content');

    const content = await page.content();
    expect(content).not.toMatch(/Fatal error:/i);
    expect(content).not.toMatch(/Parse error:/i);
    expect(content).not.toMatch(/Uncaught Error:/i);
  });

  test('Subpage edit screen opens without PHP fatal error', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=casino_subpage');
    const firstRow = page.locator('#the-list tr[id]').first();
    if (!(await firstRow.isVisible({ timeout: 5000 }).catch(() => false))) return;

    const href = await firstRow.locator('a.row-title').getAttribute('href');
    if (!href) return;

    const postId = href.match(/post=(\d+)/)?.[1];
    if (!postId) return;

    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
    const content = await page.content();
    expect(content).not.toMatch(/Fatal error:/i);
    expect(content).not.toMatch(/Uncaught Error:/i);
  });

  test('Landing edit screen opens without PHP fatal error', async ({ page }) => {
    const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
    if (!id) return;

    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
    const content = await page.content();
    expect(content).not.toMatch(/Fatal error:/i);
    expect(content).not.toMatch(/Uncaught Error:/i);
  });

  test('Guide edit screen opens without PHP fatal error', async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=guide');
    const firstRow = page.locator('#the-list tr[id]').first();
    if (!(await firstRow.isVisible({ timeout: 5000 }).catch(() => false))) return;

    const href = await firstRow.locator('a.row-title').getAttribute('href');
    if (!href) return;

    const postId = href.match(/post=(\d+)/)?.[1];
    if (!postId) return;

    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
    const content = await page.content();
    expect(content).not.toMatch(/Fatal error:/i);
    expect(content).not.toMatch(/Uncaught Error:/i);
  });

  // ─── Console errors on key pages ──────────────────────────────────────────

  test('No critical console errors on casino review page', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/avis/pw-lunara-casino/');
    await page.waitForLoadState('networkidle');

    const critical = errors.filter(
      (e) => !e.includes('favicon') && !e.includes('mixed content') && e.trim() !== '',
    );
    expect(critical, `Console errors on review: ${critical.join('\n')}`).toHaveLength(0);
  });

  test('No critical console errors on /comparer/ page', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/comparer/');
    await page.waitForLoadState('networkidle');

    const critical = errors.filter(
      (e) => !e.includes('favicon') && !e.includes('mixed content') && e.trim() !== '',
    );
    expect(critical, `Console errors on /comparer/: ${critical.join('\n')}`).toHaveLength(0);
  });

  // ─── Network failures ──────────────────────────────────────────────────────

  test('No failed network requests on casino review page', async ({ page }) => {
    const failed = collectFailedRequests(page);
    await page.goto('/avis/pw-lunara-casino/');
    await page.waitForLoadState('networkidle');

    const serious = failed.filter((f) => !f.includes('favicon'));
    expect(serious, `Failed requests: ${serious.join('\n')}`).toHaveLength(0);
  });

  test('No failed network requests on subpage', async ({ page }) => {
    const failed = collectFailedRequests(page);
    await page.goto('/avis/pw-lunara-casino/bonus/');
    await page.waitForLoadState('networkidle');

    const serious = failed.filter((f) => !f.includes('favicon'));
    expect(serious, `Failed requests: ${serious.join('\n')}`).toHaveLength(0);
  });

  // ─── Nonce deduplication guard ────────────────────────────────────────────

  test('Casino admin page has no duplicate nonce field warnings', async ({ page }) => {
    const errors = collectConsoleErrors(page);

    const id = await findPostInAdmin(page, 'casino', CASINOS[0].title);
    if (!id) return;

    await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);

    // Check there's exactly one ccc_casino_nonce input
    const nonceInputs = await page.locator('input[name="ccc_casino_nonce"]').count();
    expect(nonceInputs, 'Nonce should appear exactly once').toBe(1);
  });
});

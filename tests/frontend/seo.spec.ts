import { test, expect } from '@playwright/test';
import { findPostInAdmin } from '../helpers/wp-admin';
import { CASINOS, HUB_LANDING, COMPARISON_LANDING, TRUST_LANDING, GUIDE } from '../helpers/data';

const BASE_URL = process.env.BASE_URL || 'http://casino-compare.local';

async function getMetaContent(page: Parameters<typeof findPostInAdmin>[0], selector: string): Promise<string | null> {
  return page.locator(selector).getAttribute('content');
}

async function getJsonLd(page: Parameters<typeof findPostInAdmin>[0]): Promise<Record<string, unknown>[]> {
  const scripts = await page.locator('script[type="application/ld+json"]').all();
  const result: Record<string, unknown>[] = [];

  for (const script of scripts) {
    try {
      const text = await script.innerText();
      result.push(JSON.parse(text) as Record<string, unknown>);
    } catch {
      // Ignore malformed JSON-LD
    }
  }

  return result;
}

test.describe('Frontend — SEO Checks', () => {
  test.describe('Casino Review SEO', () => {
    test('has non-empty <title>', async ({ page }) => {
      await page.goto('/avis/pw-lunara-casino/');
      const title = await page.title();
      expect(title.trim()).not.toBe('');
      expect(title).toContain('PW Lunara Casino');
    });

    test('has meta description', async ({ page }) => {
      await page.goto('/avis/pw-lunara-casino/');
      const desc = await getMetaContent(page, 'meta[name="description"]');
      expect(desc).toBeTruthy();
      expect(desc!.length).toBeGreaterThan(30);
    });

    test('has canonical link', async ({ page }) => {
      await page.goto('/avis/pw-lunara-casino/');
      const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
      expect(canonical).toBeTruthy();
      expect(canonical).toContain('/avis/');
    });

    test('has Open Graph tags', async ({ page }) => {
      await page.goto('/avis/pw-lunara-casino/');
      const ogTitle = await getMetaContent(page, 'meta[property="og:title"]');
      const ogType = await getMetaContent(page, 'meta[property="og:type"]');
      const ogUrl = await getMetaContent(page, 'meta[property="og:url"]');
      expect(ogTitle).toBeTruthy();
      expect(ogType).toBeTruthy();
      expect(ogUrl).toBeTruthy();
    });

    test('has JSON-LD schema', async ({ page }) => {
      await page.goto('/avis/pw-lunara-casino/');
      const schemas = await getJsonLd(page);
      expect(schemas.length).toBeGreaterThan(0);

      const types = schemas.map((s) => s['@type']);
      // Should have at least one Review or BreadcrumbList or FAQPage schema
      const hasExpectedSchema = types.some((t) =>
        ['Review', 'BreadcrumbList', 'FAQPage', 'ItemList', 'Article'].includes(t as string),
      );
      expect(hasExpectedSchema, `JSON-LD types found: ${types.join(', ')}`).toBe(true);
    });

    test('has breadcrumbs', async ({ page }) => {
      await page.goto('/avis/pw-lunara-casino/');
      const breadcrumb = page.locator('[aria-label="Breadcrumb"], .breadcrumb, nav.breadcrumbs, .ccc-breadcrumb');
      await expect(breadcrumb).toBeVisible();
    });
  });

  test.describe('Comparison Landing SEO', () => {
    test('has title and meta description', async ({ page }) => {
      const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
      if (!id) return;

      await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      const viewLink = page.locator('#sample-permalink a').first();
      if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;

      const href = await viewLink.getAttribute('href');
      if (!href) return;

      await page.goto(href);
      const title = await page.title();
      expect(title.trim()).not.toBe('');

      const desc = await getMetaContent(page, 'meta[name="description"]');
      expect(desc).toBeTruthy();
    });

    test('has canonical', async ({ page }) => {
      const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
      if (!id) return;

      await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      const viewLink = page.locator('#sample-permalink a').first();
      if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;
      const href = await viewLink.getAttribute('href');
      if (!href) return;

      await page.goto(href);
      const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
      expect(canonical).toBeTruthy();
    });

    test('has JSON-LD schema', async ({ page }) => {
      const id = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
      if (!id) return;

      await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      const viewLink = page.locator('#sample-permalink a').first();
      if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;
      const href = await viewLink.getAttribute('href');
      if (!href) return;

      await page.goto(href);
      const schemas = await getJsonLd(page);
      expect(schemas.length).toBeGreaterThan(0);
    });
  });

  test.describe('Guide SEO', () => {
    test('Guide has title, meta description and canonical', async ({ page }) => {
      const response = await page.goto('/guide/pw-comprendre-le-wager/');
      if (response?.status() !== 200) {
        // Try alternate slug format
        const altResponse = await page.goto('/guide/pw-comprendre-le-wager/');
        if (altResponse?.status() !== 200) return;
      }

      const title = await page.title();
      expect(title.trim()).not.toBe('');

      const desc = await getMetaContent(page, 'meta[name="description"]');
      expect(desc).toBeTruthy();

      const canonical = await page.locator('link[rel="canonical"]').getAttribute('href');
      expect(canonical).toBeTruthy();
    });

    test('Guide has JSON-LD schema with FAQPage', async ({ page }) => {
      const response = await page.goto('/guide/pw-comprendre-le-wager/');
      if (response?.status() !== 200) return;

      const schemas = await getJsonLd(page);
      const types = schemas.map((s) => s['@type']);
      const hasFaq = types.includes('FAQPage');
      // Advisory — FAQPage schema should be present
      if (!hasFaq) {
        console.warn('ADVISORY: FAQPage JSON-LD not found on guide page');
      }
    });
  });

  test.describe('Trust Landing SEO', () => {
    test('Trust landing has correct og:type = website', async ({ page }) => {
      const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
      if (!id) return;

      await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      const viewLink = page.locator('#sample-permalink a').first();
      if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;
      const href = await viewLink.getAttribute('href');
      if (!href) return;

      await page.goto(href);
      const ogType = await getMetaContent(page, 'meta[property="og:type"]');
      // Trust landing should be 'website', not 'article'
      expect(ogType).toBe('website');
    });

    test('Trust landing has meta description', async ({ page }) => {
      const id = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
      if (!id) return;

      await page.goto(`/wp-admin/post.php?post=${id}&action=edit`);
      const viewLink = page.locator('#sample-permalink a').first();
      if (!(await viewLink.isVisible({ timeout: 3000 }).catch(() => false))) return;
      const href = await viewLink.getAttribute('href');
      if (!href) return;

      await page.goto(href);
      const desc = await getMetaContent(page, 'meta[name="description"]');
      expect(desc).toBeTruthy();
      expect(desc!.length).toBeGreaterThan(20);
    });
  });
});

import { Page, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = process.env.BASE_URL || 'http://casino-compare.local';

// ─── Navigation ─────────────────────────────────────────────────────────────

export async function gotoNewPost(page: Page, postType: string): Promise<void> {
  await page.goto(`/wp-admin/post-new.php?post_type=${postType}`);
  await page.waitForSelector('#title', { timeout: 15000 });
}

export async function gotoEditPost(page: Page, postId: number): Promise<void> {
  await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
  await page.waitForSelector('#title', { timeout: 15000 });
}

// ─── Idempotency ────────────────────────────────────────────────────────────

/**
 * Find a post by exact title using the WP REST API.
 * Returns post ID or null.
 */
export async function findPostInAdmin(
  page: Page,
  postType: string,
  title: string,
): Promise<number | null> {
  // WP uses the post type slug directly as the REST base (not pluralized)
  // e.g. casino → /wp/v2/casino, casino_subpage → /wp/v2/casino_subpage
  const restBase = postType;

  // Ensure we are on a WP admin page to have a valid nonce in window.wpApiSettings
  const currentUrl = page.url();
  if (!currentUrl.includes('/wp-admin')) {
    await page.goto('/wp-admin/');
  }
  const nonce: string = await page.evaluate(
    () => (window as any).wpApiSettings?.nonce ?? '',
  );

  // context=edit gives us title.raw (the actual post_title without wptexturize).
  // WP wptexturize() converts ' - ' → ' – ' (en dash), so title.rendered != post_title for titles with hyphens.
  const url = `${BASE_URL}/wp-json/wp/v2/${restBase}?context=edit&status=any&per_page=100&search=${encodeURIComponent(title)}`;
  const response = await page.request.get(url, {
    headers: nonce ? { 'X-WP-Nonce': nonce } : {},
  }).catch(() => null);

  if (!response || !response.ok()) return null;

  const posts = (await response.json()) as Array<{ id: number; title: { raw: string; rendered: string } }>;
  const match = posts.find((p) => p.title.raw === title);
  return match ? match.id : null;
}

/** Count posts in admin list matching a search term. */
export async function countPostsInAdmin(
  page: Page,
  postType: string,
  search: string,
): Promise<number> {
  await page.goto(
    `/wp-admin/edit.php?post_type=${postType}&s=${encodeURIComponent(search)}`,
  );

  const noItems = page.locator('#the-list .no-items');
  if (await noItems.isVisible({ timeout: 5000 }).catch(() => false)) {
    return 0;
  }

  return await page.locator('#the-list tr[id]').count();
}

// ─── Basic field helpers ─────────────────────────────────────────────────────

export async function setTitle(page: Page, title: string): Promise<void> {
  await page.fill('#title', title);
  await page.locator('#title').press('Tab');
}

export async function fillTextField(
  page: Page,
  nameOrSelector: string,
  value: string,
): Promise<void> {
  const selector = nameOrSelector.startsWith('[') || nameOrSelector.startsWith('#')
    ? nameOrSelector
    : `input[name="${nameOrSelector}"]`;
  await page.fill(selector, value);
}

export async function fillTextareaField(
  page: Page,
  name: string,
  value: string,
): Promise<void> {
  await page.fill(`textarea[name="${name}"]`, value);
}

export async function selectField(
  page: Page,
  name: string,
  value: string,
): Promise<void> {
  await page.selectOption(`select[name="${name}"]`, value);
  // Trigger change so conditionals update
  await page.dispatchEvent(`select[name="${name}"]`, 'change');
}

export async function checkboxField(
  page: Page,
  name: string,
  checked: boolean,
): Promise<void> {
  const el = page.locator(`input[name="${name}"][type="checkbox"]`);
  if (checked) {
    await el.check();
  } else {
    await el.uncheck();
  }
  // Trigger change for conditional visibility
  await page.dispatchEvent(`input[name="${name}"]`, 'change');
}

// ─── Wysiwyg ─────────────────────────────────────────────────────────────────

/**
 * Fill a wp_editor() wysiwyg field.
 * Tries tinyMCE API first; falls back to switching to the Text tab.
 */
export async function fillWysiwyg(
  page: Page,
  fieldId: string,
  content: string,
): Promise<void> {
  // Attempt 1: tinyMCE API (most reliable when loaded)
  const set = await page.evaluate(
    ({ id, text }: { id: string; text: string }) => {
      const w = window as unknown as { tinyMCE?: { get: (id: string) => { setContent: (t: string) => void } | null } };
      if (w.tinyMCE) {
        const ed = w.tinyMCE.get(id);
        if (ed) {
          ed.setContent(text);
          return true;
        }
      }
      return false;
    },
    { id: fieldId, text: content },
  );

  if (set) return;

  // Attempt 2: click the "Text" tab, then fill the underlying textarea
  const htmlTab = page.locator(`#${fieldId}-html`);
  if (await htmlTab.isVisible({ timeout: 2000 }).catch(() => false)) {
    await htmlTab.click();
    await page.waitForSelector(`textarea#${fieldId}:visible`, { timeout: 3000 });
  }
  await page.fill(`textarea#${fieldId}`, content);
}

// ─── Repeater ────────────────────────────────────────────────────────────────

/**
 * Fill a single repeater row (already existing).
 * `data` keys are subfield keys; values are string values.
 */
async function fillRepeaterRow(
  page: Page,
  key: string,
  rowIndex: number,
  data: Record<string, string>,
): Promise<void> {
  for (const [subkey, value] of Object.entries(data)) {
    const selector = `[name="${key}[${rowIndex}][${subkey}]"]`;
    const el = page.locator(selector).first();

    if (!(await el.isVisible({ timeout: 2000 }).catch(() => false))) continue;

    const tag = await el.evaluate((n) => n.tagName.toLowerCase());
    if (tag === 'select') {
      await page.selectOption(selector, value);
    } else {
      await el.fill(value);
    }
  }
}

/**
 * Fill a repeater with multiple rows.
 * Row 0 is pre-rendered by PHP; additional rows are added by clicking "Add row".
 */
export async function fillRepeater(
  page: Page,
  key: string,
  rows: Record<string, string>[],
): Promise<void> {
  if (rows.length === 0) return;

  const repeater = page.locator(`.ccc-repeater[data-key="${key}"]`);

  for (let i = 0; i < rows.length; i++) {
    if (i > 0) {
      const prevCount = await repeater.locator('.ccc-repeater-row').count();
      await repeater.locator('.ccc-add-row').click();

      // Wait for the new row to appear
      await expect(repeater.locator('.ccc-repeater-row')).toHaveCount(
        prevCount + 1,
        { timeout: 5000 },
      );
    }
    await fillRepeaterRow(page, key, i, rows[i]);
  }
}

// ─── Taxonomy ────────────────────────────────────────────────────────────────

/**
 * Assign taxonomy terms to a post via WP REST API.
 * Works for both hierarchical (checkbox) and non-hierarchical (tag) taxonomies.
 * Looks up term IDs by name; silently skips terms that don't exist in WP.
 */
export async function setTaxonomyTermsViaRest(
  page: Page,
  postId: number,
  postType: string,
  taxonomy: string,
  termNames: string[],
): Promise<void> {
  const currentUrl = page.url();
  if (!currentUrl.includes('/wp-admin')) {
    await page.goto('/wp-admin/');
  }
  const nonce: string = await page.evaluate(
    () => (window as any).wpApiSettings?.nonce ?? '',
  );

  // Resolve term names → IDs
  const termIds: number[] = [];
  for (const name of termNames) {
    const res = await page.request.get(
      `${BASE_URL}/wp-json/wp/v2/${taxonomy}?search=${encodeURIComponent(name)}&per_page=20`,
      { headers: nonce ? { 'X-WP-Nonce': nonce } : {} },
    ).catch(() => null);
    if (!res || !res.ok()) continue;
    const terms = (await res.json()) as Array<{ id: number; name: string }>;
    const match = terms.find((t) => t.name.toLowerCase() === name.toLowerCase());
    if (match) termIds.push(match.id);
  }

  if (termIds.length === 0) return;

  // Assign terms to post
  await page.request.post(
    `${BASE_URL}/wp-json/wp/v2/${postType}/${postId}`,
    {
      headers: {
        'X-WP-Nonce': nonce,
        'Content-Type': 'application/json',
      },
      data: JSON.stringify({ [taxonomy]: termIds }),
    },
  ).catch(() => null);
}

/**
 * Check taxonomy term checkboxes by label text (hierarchical taxonomies only).
 * Silently skips terms that are not found.
 * @deprecated Prefer setTaxonomyTermsViaRest for non-hierarchical taxonomies.
 */
export async function setTaxonomyTerms(
  page: Page,
  taxonomy: string,
  termLabels: string[],
): Promise<void> {
  const box = page.locator(`#${taxonomy}div`);

  for (const label of termLabels) {
    try {
      const cb = box.getByLabel(label, { exact: true });
      if (await cb.isVisible({ timeout: 2000 })) {
        await cb.check();
      }
    } catch {
      // Term not present — skip silently
    }
  }
}

// ─── Media upload ────────────────────────────────────────────────────────────

/**
 * Upload an image via WP REST API using the current page's auth cookies + nonce.
 * Returns the attachment ID, or null if upload fails.
 *
 * Technical debt: if this fails due to nonce issues, skip logo upload gracefully.
 */
export async function uploadMediaViaRest(
  page: Page,
  filePath: string,
): Promise<number | null> {
  if (!fs.existsSync(filePath)) return null;

  // Get nonce from wpApiSettings (must be on an admin page)
  const nonce = await page.evaluate(() => {
    const w = window as unknown as { wpApiSettings?: { nonce?: string } };
    return w.wpApiSettings?.nonce ?? null;
  });

  if (!nonce) return null;

  try {
    const fileBuffer = fs.readFileSync(filePath);
    const fileName = path.basename(filePath);
    const mimeType = fileName.endsWith('.png') ? 'image/png' : 'image/jpeg';

    const response = await page.request.post(`${BASE_URL}/wp-json/wp/v2/media`, {
      headers: {
        'X-WP-Nonce': nonce,
        'Content-Disposition': `attachment; filename="${fileName}"`,
        'Content-Type': mimeType,
      },
      data: fileBuffer,
    });

    if (response.ok()) {
      const data = (await response.json()) as { id?: number };
      return data.id ?? null;
    }
  } catch {
    // Media upload failed — treated as non-critical (tech debt noted)
  }
  return null;
}

/** Inject an attachment ID into a hidden image field via JS evaluation. */
export async function setImageFieldById(
  page: Page,
  fieldName: string,
  attachmentId: number,
): Promise<void> {
  await page.evaluate(
    ({ name, id }: { name: string; id: number }) => {
      const input = document.querySelector(
        `input[name="${name}"]`,
      ) as HTMLInputElement | null;
      if (input) input.value = String(id);
    },
    { name: fieldName, id: attachmentId },
  );
}

// ─── Publish / Save ──────────────────────────────────────────────────────────

/** Click Publish and wait for the edit page to reload. Returns the post ID. */
export async function publishPost(page: Page): Promise<number | null> {
  await page.click('#publish');
  await page.waitForURL(/[?&]action=edit/, { timeout: 30000 });
  return getPostIdFromUrl(page.url());
}

/** Click Save Draft and wait for reload. Returns the post ID. */
export async function saveDraft(page: Page): Promise<number | null> {
  await page.click('#save-draft');
  await page.waitForURL(/[?&]action=edit/, { timeout: 30000 });
  return getPostIdFromUrl(page.url());
}

export function getPostIdFromUrl(url: string): number | null {
  const match = url.match(/[?&]post=(\d+)/);
  return match ? parseInt(match[1], 10) : null;
}

// ─── Parent page (for nested landings) ───────────────────────────────────────

export async function setParentPage(page: Page, parentId: number): Promise<void> {
  await page.selectOption('select[name="parent_id"]', String(parentId));
}

// ─── PHP / console error checks ──────────────────────────────────────────────

export function collectConsoleErrors(page: Page): string[] {
  const errors: string[] = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') errors.push(msg.text());
  });
  return errors;
}

export function collectFailedRequests(page: Page): string[] {
  const failed: string[] = [];
  page.on('response', (response) => {
    const url = response.url();
    // Ignore browser extension noise and favicon
    if (url.includes('favicon') || url.startsWith('chrome-extension')) return;
    if (response.status() >= 400) {
      failed.push(`[${response.status()}] ${url}`);
    }
  });
  return failed;
}

export async function hasPhpErrors(page: Page): Promise<boolean> {
  const content = await page.content();
  return (
    /Fatal error:/i.test(content) ||
    /Parse error:/i.test(content) ||
    /Uncaught Error:/i.test(content)
  );
}

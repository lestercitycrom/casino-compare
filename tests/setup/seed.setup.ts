/**
 * Seed setup — creates realistic test data via wp-admin UI.
 *
 * All test entities use the "PW " title prefix for idempotency.
 * Before creating any entity, we check if it already exists.
 * Safe to run multiple times without manual cleanup.
 */
import { test, expect } from '@playwright/test';
import {
  findPostInAdmin,
  gotoNewPost,
  gotoEditPost,
  setTitle,
  fillTextField,
  fillTextareaField,
  selectField,
  checkboxField,
  fillWysiwyg,
  fillRepeater,
  setTaxonomyTermsViaRest,
  uploadMediaViaRest,
  setImageFieldById,
  publishPost,
  setParentPage,
  countPostsInAdmin,
} from '../helpers/wp-admin';
import {
  CASINOS,
  getSubpagesToPublish,
  HUB_LANDING,
  COMPARISON_LANDING,
  TRUST_LANDING,
  GUIDE,
  CasinoData,
} from '../helpers/data';

// ─── Casinos ─────────────────────────────────────────────────────────────────

async function seedCasino(page: Parameters<typeof findPostInAdmin>[0], data: CasinoData): Promise<number> {
  const existing = await findPostInAdmin(page, 'casino', data.title);
  if (existing) {
    console.log(`  ↩ Casino "${data.title}" exists (ID ${existing}), ensuring taxonomy terms.`);
    await setTaxonomyTermsViaRest(page, existing, 'casino', 'casino_license', data.taxonomies.casino_license);
    await setTaxonomyTermsViaRest(page, existing, 'casino', 'casino_feature', data.taxonomies.casino_feature);
    await setTaxonomyTermsViaRest(page, existing, 'casino', 'payment_method', data.taxonomies.payment_method);
    await setTaxonomyTermsViaRest(page, existing, 'casino', 'game_type', data.taxonomies.game_type);
    return existing;
  }

  console.log(`  + Creating casino: ${data.title}`);
  await gotoNewPost(page, 'casino');

  await setTitle(page, data.title);

  // Brand
  await fillTextField(page, 'affiliate_link', data.affiliateLink);
  await fillTextField(page, 'year_founded', data.yearFounded);
  await fillTextField(page, 'trustpilot_score', data.trustpilotScore);

  // Try to upload logo; skip gracefully if unavailable
  const logoId = await uploadMediaViaRest(page, data.logoFixture);
  if (logoId) {
    await setImageFieldById(page, 'logo', logoId);
    console.log(`    Logo uploaded (ID ${logoId})`);
  } else {
    console.log('    Logo upload skipped (tech debt: no nonce or fixture missing)');
  }

  // Rating
  await fillTextField(page, 'overall_rating', data.overallRating);
  await fillTextField(page, 'rating_bonus', data.ratingBonus);
  await fillTextField(page, 'rating_games', data.ratingGames);
  await fillTextField(page, 'rating_payments', data.ratingPayments);
  await fillTextField(page, 'rating_support', data.ratingSupport);
  await fillTextField(page, 'rating_reliability', data.ratingReliability);

  // Bonus
  await fillTextField(page, 'welcome_bonus_text', data.welcomeBonusText);
  await fillTextField(page, 'wagering', data.wagering);
  await fillTextField(page, 'min_deposit', data.minDeposit);
  if (data.noDepositBonus) {
    await fillTextField(page, 'no_deposit_bonus', data.noDepositBonus);
  }
  await fillTextField(page, 'free_spins', data.freeSpins);
  if (data.promoCode) {
    await fillTextField(page, 'promo_code', data.promoCode);
  }

  // Technical
  await fillTextField(page, 'license', data.license);
  await fillTextField(page, 'license_number', data.licenseNumber);
  await fillTextField(page, 'games_count', data.gamesCount);
  await fillTextField(page, 'withdrawal_time_min', data.withdrawalTimeMin);
  await fillTextField(page, 'withdrawal_time_max', data.withdrawalTimeMax);

  await fillRepeater(page, 'providers', data.providers.map((name) => ({ name })));
  await fillRepeater(page, 'deposit_methods', data.depositMethods.map((name) => ({ name })));

  // Content
  await fillRepeater(page, 'pros', data.pros.map((text) => ({ text })));
  await fillRepeater(page, 'cons', data.cons.map((text) => ({ text })));
  await fillTextareaField(page, 'intro_text', data.introText);

  await fillTextField(page, 'summary_1_title', data.summary1Title);
  await fillWysiwyg(page, 'summary_1', data.summary1);
  await fillWysiwyg(page, 'final_verdict', data.finalVerdict);

  // SEO
  await fillTextField(page, 'seo_title', data.seoTitle);
  await fillTextareaField(page, 'meta_description', data.metaDescription);

  // FAQ
  await fillRepeater(page, 'faq', [
    {
      question: `Est-ce que ${data.title.replace('PW ', '')} est fiable ?`,
      answer: `Oui, ${data.title.replace('PW ', '')} est licencié par ${data.license} et considéré comme fiable.`,
    },
  ]);

  const postId = await publishPost(page);
  if (!postId) throw new Error(`Failed to publish casino "${data.title}"`);

  // Assign taxonomy terms via REST (non-hierarchical taxonomies render as tag input in admin UI)
  await setTaxonomyTermsViaRest(page, postId, 'casino', 'casino_license', data.taxonomies.casino_license);
  await setTaxonomyTermsViaRest(page, postId, 'casino', 'casino_feature', data.taxonomies.casino_feature);
  await setTaxonomyTermsViaRest(page, postId, 'casino', 'payment_method', data.taxonomies.payment_method);
  await setTaxonomyTermsViaRest(page, postId, 'casino', 'game_type', data.taxonomies.game_type);

  console.log(`    Published (ID ${postId})`);
  return postId;
}

// ─── Subpages ─────────────────────────────────────────────────────────────────

async function verifySubpagesCreated(
  page: Parameters<typeof findPostInAdmin>[0],
  casinoTitle: string,
): Promise<void> {
  // The casino CPT auto-creates 8 draft subpages on publish.
  // Allow up to 5s for them to appear in the admin list.
  await page.waitForTimeout(500); // Brief stabilization after publish redirect

  const count = await countPostsInAdmin(page, 'casino_subpage', casinoTitle);
  console.log(`    Subpages found for "${casinoTitle}": ${count}`);

  if (count < 8) {
    console.warn(
      `    ⚠ Expected 8 draft subpages for "${casinoTitle}", got ${count}. ` +
      'The seed_phase_one_subpages hook may not have fired. Continuing.',
    );
  }
}

async function publishSubpage(
  page: Parameters<typeof findPostInAdmin>[0],
  casinoTitle: string,
  subpageType: string,
): Promise<void> {
  const searchTerm = `${casinoTitle} - ${subpageType}`;
  const existing = await findPostInAdmin(page, 'casino_subpage', searchTerm);

  if (!existing) {
    console.warn(`    ⚠ Subpage "${searchTerm}" not found, skipping publish.`);
    return;
  }

  // Check if already published
  await page.goto(`/wp-admin/post.php?post=${existing}&action=edit`);
  await page.waitForSelector('#title', { timeout: 10000 });

  const currentStatus = await page.locator('#post-status-display').textContent();
  if (currentStatus?.toLowerCase().includes('published')) {
    console.log(`    ↩ Subpage "${searchTerm}" already published.`);
    return;
  }

  console.log(`    + Publishing subpage: ${searchTerm}`);

  // Fill subpage content
  const pubData = getSubpagesToPublish().find(
    (s) => s.casinoTitle === casinoTitle && s.subpageType === (subpageType as 'bonus' | 'retrait'),
  );

  if (pubData) {
    await fillTextField(page, 'hero_title', pubData.heroTitle);
    await fillTextareaField(page, 'intro_text', pubData.introText);
    await fillTextField(page, 'seo_title', pubData.seoTitle);
    await fillTextareaField(page, 'meta_description', pubData.metaDescription);

    if (pubData.scoreEnabled) {
      await checkboxField(page, 'score_enabled', true);
      // Wait for conditional fields to become visible
      await page.waitForSelector('[data-ccc-condition-field="score_enabled"]', { timeout: 3000 }).catch(() => {});
      if (pubData.scoreValue) {
        await fillTextField(page, 'score_value', pubData.scoreValue);
      }
      if (pubData.scoreLabel) {
        await fillTextField(page, 'score_label', pubData.scoreLabel);
      }
    }
  }

  await publishPost(page);
  console.log(`    Published.`);
}

// ─── Hub landing ─────────────────────────────────────────────────────────────

async function seedHubLanding(
  page: Parameters<typeof findPostInAdmin>[0],
): Promise<number> {
  const existing = await findPostInAdmin(page, 'landing', HUB_LANDING.title);
  if (existing) {
    console.log(`  ↩ Hub landing exists (ID ${existing}), skipping.`);
    return existing;
  }

  console.log(`  + Creating hub landing: ${HUB_LANDING.title}`);
  await gotoNewPost(page, 'landing');

  await setTitle(page, HUB_LANDING.title);

  // Common fields
  await selectField(page, 'landing_type', 'hub');
  await fillTextField(page, 'hero_title', HUB_LANDING.heroTitle);
  await fillTextareaField(page, 'intro_text', HUB_LANDING.introText);
  await fillTextField(page, 'seo_title', HUB_LANDING.seoTitle);
  await fillTextareaField(page, 'meta_description', HUB_LANDING.metaDescription);

  // Hub-specific fields (conditional group visible after selecting hub type)
  await page.waitForTimeout(500); // Allow conditional JS to run

  await fillRepeater(page, 'subcategory_cards', HUB_LANDING.subcategoryCards.map((c) => ({
    title: c.title,
    url: c.url,
    description: c.description,
    icon: c.icon,
  })));

  await fillWysiwyg(page, 'educational_content', HUB_LANDING.educationalContent);
  await fillWysiwyg(page, 'howto_content', HUB_LANDING.howtoContent);

  // FAQ
  await fillRepeater(page, 'faq', [
    { question: 'Quels casinos sont les meilleurs en 2024 ?', answer: 'HexaSpin, Lunara Casino et NovaJackpot figurent parmi nos tops picks.' },
    { question: 'Comment choisir un casino en ligne ?', answer: 'Vérifiez la licence, comparez les bonus et lisez les avis de joueurs.' },
  ]);

  const postId = await publishPost(page);
  if (!postId) throw new Error('Failed to publish hub landing');

  console.log(`    Published hub landing (ID ${postId})`);
  return postId;
}

// ─── Comparison landing ───────────────────────────────────────────────────────

async function seedComparisonLanding(
  page: Parameters<typeof findPostInAdmin>[0],
  hubId: number,
  casinoIds: number[],
): Promise<number> {
  const existing = await findPostInAdmin(page, 'landing', COMPARISON_LANDING.title);
  if (existing) {
    console.log(`  ↩ Comparison landing exists (ID ${existing}), skipping.`);
    return existing;
  }

  console.log(`  + Creating comparison landing: ${COMPARISON_LANDING.title}`);
  await gotoNewPost(page, 'landing');

  await setTitle(page, COMPARISON_LANDING.title);

  // Common
  await selectField(page, 'landing_type', 'comparison');
  await fillTextField(page, 'hero_title', COMPARISON_LANDING.heroTitle);
  await fillTextareaField(page, 'intro_text', COMPARISON_LANDING.introText);
  await fillTextField(page, 'seo_title', COMPARISON_LANDING.seoTitle);
  await fillTextareaField(page, 'meta_description', COMPARISON_LANDING.metaDescription);

  // Parent page (makes this a nested child of hub)
  await setParentPage(page, hubId);

  // Comparison-specific
  await page.waitForTimeout(500);
  await fillTextField(page, 'casinos_tested_count', COMPARISON_LANDING.casinosTested);
  await fillTextField(page, 'last_updated', COMPARISON_LANDING.lastUpdated);
  await fillTextField(page, 'author_name', COMPARISON_LANDING.authorName);

  // Casino cards (curated list)
  const casinoCardRows = casinoIds.slice(0, 3).map((id, index) => ({
    casino_id: String(id),
    rank: String(index + 1),
    short_review: `Casino #${index + 1} de notre sélection bonus.`,
  }));
  await fillRepeater(page, 'casino_cards', casinoCardRows);

  await fillWysiwyg(page, 'methodology_content', COMPARISON_LANDING.methodologyContent);
  await fillWysiwyg(page, 'bottom_content', COMPARISON_LANDING.bottomContent);

  // FAQ
  await fillRepeater(page, 'faq', [
    { question: 'Quel casino a le meilleur bonus ?', answer: 'HexaSpin propose le meilleur bonus avec 100% jusqu\'à 1000€ et x30 de mise.' },
  ]);

  const postId = await publishPost(page);
  if (!postId) throw new Error('Failed to publish comparison landing');

  console.log(`    Published comparison landing (ID ${postId})`);
  return postId;
}

// ─── Trust landing ────────────────────────────────────────────────────────────

async function seedTrustLanding(
  page: Parameters<typeof findPostInAdmin>[0],
): Promise<number> {
  const existing = await findPostInAdmin(page, 'landing', TRUST_LANDING.title);
  if (existing) {
    console.log(`  ↩ Trust landing exists (ID ${existing}), skipping.`);
    return existing;
  }

  console.log(`  + Creating trust landing: ${TRUST_LANDING.title}`);
  await gotoNewPost(page, 'landing');

  await setTitle(page, TRUST_LANDING.title);

  // Common
  await selectField(page, 'landing_type', 'trust');
  await fillTextField(page, 'hero_title', TRUST_LANDING.heroTitle);
  await fillTextareaField(page, 'intro_text', TRUST_LANDING.introText);
  await fillTextField(page, 'seo_title', TRUST_LANDING.seoTitle);
  await fillTextareaField(page, 'meta_description', TRUST_LANDING.metaDescription);

  // Trust-specific
  await page.waitForTimeout(500);
  await checkboxField(page, 'show_author', true);
  await fillTextField(page, 'trust_author_name', TRUST_LANDING.authorName);
  await fillTextField(page, 'trust_last_updated', TRUST_LANDING.lastUpdated);
  await fillWysiwyg(page, 'page_content', TRUST_LANDING.pageContent);

  const postId = await publishPost(page);
  if (!postId) throw new Error('Failed to publish trust landing');

  console.log(`    Published trust landing (ID ${postId})`);
  return postId;
}

// ─── Guide ────────────────────────────────────────────────────────────────────

async function seedGuide(
  page: Parameters<typeof findPostInAdmin>[0],
  casinoIds: number[],
): Promise<number> {
  const existing = await findPostInAdmin(page, 'guide', GUIDE.title);
  if (existing) {
    console.log(`  ↩ Guide exists (ID ${existing}), skipping.`);
    return existing;
  }

  console.log(`  + Creating guide: ${GUIDE.title}`);
  await gotoNewPost(page, 'guide');

  await setTitle(page, GUIDE.title);

  await fillTextField(page, 'category', GUIDE.category);
  await fillTextField(page, 'reading_time', GUIDE.readingTime);
  await fillTextField(page, 'last_updated', GUIDE.lastUpdated);
  await fillTextField(page, 'author_name', GUIDE.authorName);
  await fillTextareaField(page, 'intro_text', GUIDE.introText);
  await fillTextareaField(page, 'callout_text', GUIDE.calloutText);
  await fillWysiwyg(page, 'main_content', GUIDE.mainContent);
  await fillWysiwyg(page, 'sidebar_takeaway', GUIDE.sidebarTakeaway);

  // Phase 1 gap fields — now closed by Chester, fill normally
  await fillTextField(page, 'callout_title', 'À retenir avant de continuer');
  await checkboxField(page, 'callout_enabled', true);
  await fillTextField(page, 'sidebar_top_title', 'Nos casinos recommandés');
  await fillTextField(page, 'sidebar_comparison_link', '/comparatif-casinos/');

  // Sidebar casino list (relation field, multiple select)
  if (casinoIds.length > 0) {
    const sidebarSelect = page.locator('select[name="sidebar_casino_list[]"]');
    if (await sidebarSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
      for (const id of casinoIds.slice(0, 3)) {
        await sidebarSelect.selectOption({ value: String(id) });
      }
    }
  }

  // Money page links
  await fillRepeater(page, 'money_page_links', GUIDE.moneyPageLinks.map((l) => ({
    label: l.label,
    url: l.url,
  })));

  // FAQ
  await fillRepeater(page, 'faq', GUIDE.faq.map((f) => ({
    question: f.question,
    answer: f.answer,
  })));

  await fillTextField(page, 'seo_title', GUIDE.seoTitle);
  await fillTextareaField(page, 'meta_description', GUIDE.metaDescription);

  const postId = await publishPost(page);
  if (!postId) throw new Error('Failed to publish guide');

  console.log(`    Published guide (ID ${postId})`);
  return postId;
}

// ─── Optional field helpers ───────────────────────────────────────────────────

async function fillTextFieldOptional(
  page: Parameters<typeof findPostInAdmin>[0],
  name: string,
  value: string,
): Promise<void> {
  try {
    const el = page.locator(`input[name="${name}"]`);
    if (await el.isVisible({ timeout: 1000 })) {
      await el.fill(value);
    }
  } catch {
    // Field does not exist yet (Phase 1 gap) — skip silently
  }
}

async function checkboxFieldOptional(
  page: Parameters<typeof findPostInAdmin>[0],
  name: string,
  checked: boolean,
): Promise<void> {
  try {
    const el = page.locator(`input[name="${name}"][type="checkbox"]`);
    if (await el.isVisible({ timeout: 1000 })) {
      if (checked) await el.check();
    }
  } catch {
    // Field does not exist yet — skip silently
  }
}

// ─── Main seed test ───────────────────────────────────────────────────────────

test('seed: create all PW test data', async ({ page }) => {
  const casinoIds: number[] = [];

  // 1. Casinos
  console.log('\n=== Seeding casinos ===');
  for (const casinoData of CASINOS) {
    const id = await seedCasino(page, casinoData);
    casinoIds.push(id);

    // Verify 8 draft subpages exist (auto-created by plugin on first publish)
    await verifySubpagesCreated(page, casinoData.title);
  }

  expect(casinoIds.length).toBe(3);
  expect(casinoIds.every((id) => id > 0)).toBe(true);

  // 2. Publish 2 subpages per casino (bonus + retrait)
  console.log('\n=== Publishing subpages ===');
  for (const casinoData of CASINOS) {
    await publishSubpage(page, casinoData.title, 'bonus');
    await publishSubpage(page, casinoData.title, 'retrait');
  }

  // 3. Landings
  console.log('\n=== Seeding landings ===');
  const hubId = await seedHubLanding(page);
  expect(hubId).toBeGreaterThan(0);

  const comparisonId = await seedComparisonLanding(page, hubId, casinoIds);
  expect(comparisonId).toBeGreaterThan(0);

  const trustId = await seedTrustLanding(page);
  expect(trustId).toBeGreaterThan(0);

  // 4. Guide
  console.log('\n=== Seeding guide ===');
  const guideId = await seedGuide(page, casinoIds);
  expect(guideId).toBeGreaterThan(0);

  console.log('\n=== Seed complete ===');
  console.log(`Casinos: ${casinoIds.join(', ')}`);
  console.log(`Hub landing: ${hubId}`);
  console.log(`Comparison landing: ${comparisonId}`);
  console.log(`Trust landing: ${trustId}`);
  console.log(`Guide: ${guideId}`);
});

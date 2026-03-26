import { test } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { AUTH_FILE } from '../helpers/auth';

const WP_ADMIN_USER = process.env.WP_ADMIN_USER || 'admin@gmail.com';
const WP_ADMIN_PASS = process.env.WP_ADMIN_PASS || 'admin123';

test('auth: login to wp-admin and save storage state', async ({ page }) => {
  await page.goto('/wp-login.php');

  await page.fill('#user_login', WP_ADMIN_USER);
  await page.fill('#user_pass', WP_ADMIN_PASS);
  await page.click('#wp-submit');

  // Wait for successful redirect into admin
  await page.waitForURL(/wp-admin/, { timeout: 15000 });

  // Ensure .auth directory exists
  const authDir = path.dirname(AUTH_FILE);
  if (!fs.existsSync(authDir)) {
    fs.mkdirSync(authDir, { recursive: true });
  }

  await page.context().storageState({ path: AUTH_FILE });
  console.log(`Auth state saved to ${AUTH_FILE}`);
});

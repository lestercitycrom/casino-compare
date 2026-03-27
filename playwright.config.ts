import { defineConfig, devices } from '@playwright/test';
import * as dotenv from 'dotenv';
import * as path from 'path';

dotenv.config({ path: path.resolve(__dirname, '.env') });

const BASE_URL = process.env.BASE_URL || 'http://casino-compare.local';
const AUTH_FILE = path.join(__dirname, '.auth/user.json');

export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: 1,
  timeout: 120000,
  reporter: [
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
    ['list'],
  ],
  use: {
    baseURL: BASE_URL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'off',
  },
  projects: [
    {
      name: 'auth',
      testDir: './tests/setup',
      testMatch: 'auth.setup.ts',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'seed',
      testDir: './tests/setup',
      testMatch: 'seed.setup.ts',
      dependencies: ['auth'],
      timeout: 600000, // 10 min — logo uploads + admin UI fills are slow
      use: {
        ...devices['Desktop Chrome'],
        storageState: AUTH_FILE,
      },
    },
    {
      name: 'e2e',
      testDir: './tests',
      testIgnore: '**/setup/**',
      dependencies: ['auth'],
      use: {
        ...devices['Desktop Chrome'],
        storageState: AUTH_FILE,
      },
    },
  ],
});

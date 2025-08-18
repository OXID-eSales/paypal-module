const { defineConfig } = require('@playwright/test');

require('dotenv').config();

module.exports = defineConfig({
    testDir: './tests/e2e',
    outputDir: './_output',

    timeout: 60000, // 60 seconds per test

    // Run tests in files in parallel
    fullyParallel: true,

    // Retry on CI only
    retries: process.env.CI ? 2 : 0,

    // Reporter to use
    reporter: [
        ['html', { outputFolder: './_generated' }],
        ['json', { outputFile: './_generated/report.json' }]
    ],

    // Configure projects for browsers
    projects: [
        {
            name: 'chrome',
            use: {
                browserName: 'chromium',
                viewport: { width: 1280, height: 1000 },

                // Always take screenshots on failure
                screenshot: 'only-on-failure',

                video: 'on-first-retry',
                trace: 'on-first-retry',
                ignoreHTTPSErrors: true,
                launchOptions: {
                    args: [
                        '--ignore-certificate-errors',
                        '--ignore-certificate-errors-spki-list',
                        '--ignore-ssl-errors',
                        '--disable-web-security',
                        '--allow-insecure-localhost',
                        '--disable-features=IsolateOrigins,site-per-process',
                        '--remote-debugging-port=9222',
                    ],
                }
            },
        },
    ],

    // Configure testing environment
    use: {
        baseURL: process.env.BASE_URL || 'http://localhost.local',
        ignoreHTTPSErrors: true,
        actionTimeout: 40000,
        navigationTimeout: 40000,
        slowMo: 100,
    },
});
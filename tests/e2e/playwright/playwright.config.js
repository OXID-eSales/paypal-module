const { defineConfig } = require('@playwright/test');

require('dotenv').config();

const config = {
    use: {
        baseURL: process.env.BASE_URL || 'https://localhost.local',
        sandbox: process.env.SANDBOX_MODE || 'true', // Default value if undefined
    },
};

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

    projects: [
        {
            name: 'chrome',
            use: {
                browserName: 'chromium',
                viewport: { width: 1280, height: 1000 },
                video: 'on-first-retry',
                screenshot: 'only-on-failure',
                trace: 'on-first-retry',
                ignoreHTTPSErrors: true,
                launchOptions: {
                    args: [
                        '--ignore-certificate-errors',
                        '--ignore-certificate-errors-spki-list',
                        '--ignore-ssl-errors',
                        '--disable-web-security',
                        '--allow-insecure-localhost',
                        '--disable-features=IsolateOrigins,site-per-process', // Optional flag to make Chrome behave like a regular browser
                        '--remote-debugging-port=9222', // Optional: Enable debugging, useful for Chrome
                    ],
                }
            },
        },
    ],

    // Configure testing environment
    use: {
        // Base URL to use
        baseURL: process.env.BASE_URL || 'https://localhost.local',

        // Ignore HTTPS errors globally too
        ignoreHTTPSErrors: true,

        // Maximum time each action (like click) can take
        actionTimeout: 40000,
        navigationTimeout: 40000,

        // Slow down Playwright operations by ms
        slowMo: 100,
    },
});

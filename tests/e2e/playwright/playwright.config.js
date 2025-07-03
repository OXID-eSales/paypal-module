const { defineConfig } = require('@playwright/test');
const sharedData = require('./testData/test-data.json');

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

    // Configure projects for browsers
    projects: [
        {
            name: 'chrome', // Switch project name to 'chrome'
            use: {
                browserName: 'chromium', // 'chromium' is the default for Google Chrome as well
                // Configure viewport
                viewport: { width: 1280, height: 720 },
                // Record video and screenshots
                video: 'on-first-retry',
                screenshot: 'only-on-failure',
                // Enable trace for debugging
                trace: 'on-first-retry',
                // Ignore HTTPS errors - most important setting for self-signed certs
                ignoreHTTPSErrors: true,
                // Add browser flags to launch Google Chrome (if you prefer)
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
        actionTimeout: 10000,
        navigationTimeout: 15000,

        // Slow down Playwright operations by ms
        slowMo: 100,

        navigationTimeout: 5000, // Fail navigation after 5 seconds
    },
});

const { defineConfig } = require('cypress');

module.exports = defineConfig({
    e2e: {
        baseUrl: 'https://apache', // Replace with your actual base URL
        specPattern: 'cypress/frontend_tests/**/*.cy.js', // Test file pattern
        supportFile: false, // Disable the default support file
        video: true,
        chromeWebSecurity: false,
        defaultCommandTimeout: 10000, // 10 seconds
        pageLoadTimeout: 30000, // 30 seconds
        screenshotsFolder: 'cypress/_generated/screenshots', // Custom screenshots path
        videosFolder: 'cypress/_generated/videos',           // Custom videos path
        downloadsFolder: 'cypress/_generated/downloads',     // Custom downloads path
        trashAssetsBeforeRuns: true,
        failOnStatusCode: true,
    },
});

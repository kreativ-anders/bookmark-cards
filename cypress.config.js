const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    // Local Wharf host (site/config/config.bookmark-cards.localhost.php).
    // Override with: CYPRESS_BASE_URL=http://localhost:8000 npm run cy:test
    baseUrl: process.env.CYPRESS_BASE_URL || "http://bookmark-cards.localhost",
    // Registration/deletion call Stripe (test mode) via memberkit hooks
    defaultCommandTimeout: 10000,
    video: false,
  },
});

# Use the official Playwright image
FROM mcr.microsoft.com/playwright:v1.51.0-jammy

# Set working directory
WORKDIR /app

# Copy package.json and install dependencies
COPY package.json package-lock.json ./
RUN npm install

# Copy test files
COPY . .

# Ensure Playwright browsers are installed
RUN npx playwright install --with-deps

# Run tests
CMD ["npx", "playwright", "test"]

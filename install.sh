#!/usr/bin/env bash
set -euo pipefail

# ----------------------------------------------------------------------------
# 0. Project directory setup
# ----------------------------------------------------------------------------
read -r -p "Enter project directory name [StoreWizard]: " PROJECT_DIR
PROJECT_DIR=${PROJECT_DIR:-StoreWizard}
ROOT_DIR="$(pwd)"
APP_DIR="$ROOT_DIR/$PROJECT_DIR"

echo -e "\n📁 0. Setting up project directory '$APP_DIR'..."
if [ ! -d "$APP_DIR" ]; then
  mkdir -p "$APP_DIR"
else
  echo "Directory '$APP_DIR' already exists."
fi
cd "$APP_DIR"

# ----------------------------------------------------------------------------
# 1. Repository setup
# ----------------------------------------------------------------------------
REPO_URL="https://github.com/Sylius/DemoCreator.git"
BRANCH="main-v4"

echo -e "\n🔍 1. Cloning the repository into '$APP_DIR' and switching to branch '$BRANCH'..."
if [ ! -d ".git" ]; then
  git clone --depth 1 --branch "$BRANCH" "$REPO_URL" .
else
  git fetch origin
  git checkout "$BRANCH"
  git pull origin "$BRANCH"
fi

# ----------------------------------------------------------------------------
# 2. Environment file creation and configuration
# ----------------------------------------------------------------------------
if [ -f .env.local ]; then
  echo "Warning: .env.local already exists → backing up to .env.local.bak"
  cp .env.local .env.local.bak
fi
cp .env .env.local

echo -e "\n📝 2. Configuring sylius/store-creator settings..."

# Color definitions for smoke test output
RED=$'\033[0;31m'
GREEN=$'\033[0;32m'
RESET=$'\033[0m'

# 2.1 Choose deploy target (local/platformsh)
read -r -p "Select deploy target (local/platformsh) [local]: " DEPLOY_TARGET
DEPLOY_TARGET="$(printf '%s' "$DEPLOY_TARGET" | tr '[:upper:]' '[:lower:]')"
DEPLOY_TARGET="${DEPLOY_TARGET:-local}"
if [[ "$DEPLOY_TARGET" != "local" && "$DEPLOY_TARGET" != "platformsh" ]]; then
  echo "Invalid option, defaulting to 'local'."
  DEPLOY_TARGET="local"
fi

# 2.2 Ask for specific values based on target
if [ "$DEPLOY_TARGET" = "local" ]; then
  read -r -p "Enter the ABSOLUTE path to your local project (leave empty to skip): " LOCAL_PROJECT_PATH
  LOCAL_PROJECT_PATH="${LOCAL_PROJECT_PATH:-}"
  PLATFORMSH_CLI_TOKEN=""
  PLATFORMSH_PROJECT_ID=""
else
  read -rs -p "Enter your Platform.sh API token (press Enter to skip, input hidden): " PLATFORMSH_CLI_TOKEN
  PLATFORMSH_CLI_TOKEN="${PLATFORMSH_CLI_TOKEN:-}"
  read -rs -p "Enter your Platform.sh Project ID (press Enter to skip, input hidden): " PLATFORMSH_PROJECT_ID
  PLATFORMSH_PROJECT_ID="${PLATFORMSH_PROJECT_ID:-}"
  LOCAL_PROJECT_PATH=""
fi

# 2.3 Require OpenAI API key (hidden input)
echo -e "\n💡 2.3 Configuring OpenAI API key (required, input hidden)..."
while true; do
  read -rs -p "Enter your OpenAI API key: " OPENAI_API_KEY
  echo
  if [ -n "$OPENAI_API_KEY" ]; then
    break
  fi
  echo "OpenAI API key is required."
done

# 2.3.1 Smoke test OpenAI key
echo -e "\n🔎 2.3.1 Validating OpenAI API key against models endpoint..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  -H "Authorization: Bearer $OPENAI_API_KEY" https://api.openai.com/v1/models)
if [[ "$HTTP_STATUS" != "200" ]]; then
  echo -e "${RED}Error:${RESET} OpenAI API key validation failed (HTTP status $HTTP_STATUS)."
  exit 1
fi
echo -e "${GREEN}OpenAI API key validated successfully!${RESET}"

# 2.4 Update .env.local with provided values

echo -e "\nUpdating .env.local with your inputs..."
# Deployer and Platform.sh
if sed --version >/dev/null 2>&1; then
  # GNU sed
  sed -i -e "s|^STORE_DEPLOY_TARGET=.*|STORE_DEPLOY_TARGET=$DEPLOY_TARGET|" \
         -e "s|^STORE_DEPLOY_TARGET_LOCAL_PROJECT_PATH=.*|STORE_DEPLOY_TARGET_LOCAL_PROJECT_PATH=$LOCAL_PROJECT_PATH|" \
         -e "s|^PLATFORMSH_CLI_TOKEN=.*|PLATFORMSH_CLI_TOKEN=$PLATFORMSH_CLI_TOKEN|" \
         -e "s|^PLATFORMSH_PROJECT_ID=.*|PLATFORMSH_PROJECT_ID=$PLATFORMSH_PROJECT_ID|" \
         .env.local
else
  # BSD sed (macOS)
  sed -i '' -e "s|^STORE_DEPLOY_TARGET=.*|STORE_DEPLOY_TARGET=$DEPLOY_TARGET|" \
            -e "s|^STORE_DEPLOY_TARGET_LOCAL_PROJECT_PATH=.*|STORE_DEPLOY_TARGET_LOCAL_PROJECT_PATH=$LOCAL_PROJECT_PATH|" \
            -e "s|^PLATFORMSH_CLI_TOKEN=.*|PLATFORMSH_CLI_TOKEN=$PLATFORMSH_CLI_TOKEN|" \
            -e "s|^PLATFORMSH_PROJECT_ID=.*|PLATFORMSH_PROJECT_ID=$PLATFORMSH_PROJECT_ID|" \
            .env.local
fi
# OpenAI key: replace or append
if grep -q '^OPENAI_API_KEY=' .env.local; then
  sed -i'' -e "s|^OPENAI_API_KEY=.*|OPENAI_API_KEY=$OPENAI_API_KEY|" .env.local
else
  echo "OPENAI_API_KEY=$OPENAI_API_KEY" >> .env.local
fi

echo ".env.local is ready!"

# ----------------------------------------------------------------------------
# 3. Install PHP dependencies
# ----------------------------------------------------------------------------
echo -e "\n📦 3. Installing PHP dependencies via Composer..."
composer install --no-interaction --optimize-autoloader

# ----------------------------------------------------------------------------
# 4. Install JS dependencies
# ----------------------------------------------------------------------------
echo -e "\n📦 4. Installing JavaScript dependencies via NPM..."
npm install

# ----------------------------------------------------------------------------
# 5. Build assets
# ----------------------------------------------------------------------------
echo -e "\n🚧 5. Building frontend assets..."
npm run build

# ----------------------------------------------------------------------------
# 6. Start server and open browser dynamically
# ----------------------------------------------------------------------------
echo -e "\n🚀 6. Starting development server..."
if command -v symfony >/dev/null 2>&1; then
  symfony serve --allow-http --dir=public --daemon
  sleep 2
  URL=$(symfony server:list --no-ansi | grep -oE 'https?://[0-9\.]+:[0-9]+' | head -n1)
  if [[ -z "$URL" ]]; then
    echo "Could not detect server URL, defaulting to http://127.0.0.1:8000"
    URL="http://127.0.0.1:8000"
  fi
else
  (cd public && php -S 127.0.0.1:8000) &
  sleep 1
  URL="http://127.0.0.1:8000"
fi

echo "Opening $URL in browser..."
if command -v xdg-open >/dev/null 2>&1; then
  xdg-open "$URL"
elif command -v open >/dev/null 2>&1; then
  open "$URL"
else
  echo "Please open $URL in your browser."
fi

echo -e "\n✅ Setup complete! Access the app at $URL\n"

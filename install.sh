#!/usr/bin/env bash
set -euo pipefail

# 1. Repository setup
# Use current working directory instead of BASH_SOURCE (supports one-liner execution)
APP_DIR="$(pwd)"
REPO_URL="https://github.com/Sylius/DemoCreator.git"
BRANCH="main-v4"

echo -e "\n🔍 1. Cloning the repository into '$APP_DIR' and switching to branch '$BRANCH'..."
if [ ! -d "$APP_DIR/.git" ]; then
  git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
else
  git -C "$APP_DIR" fetch origin
  git -C "$APP_DIR" checkout "$BRANCH"
  git -C "$APP_DIR" pull origin "$BRANCH"
fi

# 2. Environment file creation and configuration
if [ -f .env.local ]; then
  echo "Warning: .env.local already exists → backing up to .env.local.bak"
  cp .env.local .env.local.bak
fi
cp .env .env.local

echo -e "\n📝 2. Configuring sylius/store-creator settings..."

# 2.1 Choose deploy target (local/platformsh)
read -r -p "Select deploy target (local/platformsh) [local]: " DEPLOY_TARGET
DEPLOY_TARGET=${DEPLOY_TARGET:-local}
DEPLOY_TARGET=${DEPLOY_TARGET,,}  # lowercase

if [[ "$DEPLOY_TARGET" != "local" && "$DEPLOY_TARGET" != "platformsh" ]]; then
  echo "Invalid option, defaulting to 'local'."
  DEPLOY_TARGET="local"
fi

# 2.2 Ask for specific values based on target
if [ "$DEPLOY_TARGET" = "local" ]; then
  read -r -p "Enter the ABSOLUTE path to your local project (edit if different, leave empty to skip): " LOCAL_PROJECT_PATH
  # must be an absolute path, e.g. /Users/yourname/sylius-standard_my_project
  LOCAL_PROJECT_PATH=${LOCAL_PROJECT_PATH:-}
  PLATFORMSH_CLI_TOKEN=""
  PLATFORMSH_PROJECT_ID=""
else
  read -r -p "Enter your Platform.sh API token (press Enter to skip): " PLATFORMSH_CLI_TOKEN
  PLATFORMSH_CLI_TOKEN=${PLATFORMSH_CLI_TOKEN:-}
  read -r -p "Enter your Platform.sh Project ID (press Enter to skip): " PLATFORMSH_PROJECT_ID
  PLATFORMSH_PROJECT_ID=${PLATFORMSH_PROJECT_ID:-}
  LOCAL_PROJECT_PATH=""
fi

# 2.3 Update .env.local with provided values
sed -i -e "s|^STORE_DEPLOYER_TARGET=.*|STORE_DEPLOYER_TARGET=$DEPLOY_TARGET|" \
       -e "s|^STORE_DEPLOYER_TARGET_LOCAL_PROJECT_PATH=.*|STORE_DEPLOYER_TARGET_LOCAL_PROJECT_PATH=$LOCAL_PROJECT_PATH|" \
       -e "s|^PLATFORMSH_CLI_TOKEN=.*|PLATFORMSH_CLI_TOKEN=$PLATFORMSH_CLI_TOKEN|" \
       -e "s|^PLATFORMSH_PROJECT_ID=.*|PLATFORMSH_PROJECT_ID=$PLATFORMSH_PROJECT_ID|" \
       .env.local

echo ".env.local is ready!"

# 3. Install PHP dependencies
echo -e "\n📦 3. Installing PHP dependencies via Composer..."
composer install --no-interaction --optimize-autoloader

# 4. Install JS dependencies
echo -e "\n📦 4. Installing JavaScript dependencies via NPM..."
npm install

# 5. Build assets
echo -e "\n🚧 5. Building frontend assets..."
npm run build

# 6. Start server and open browser dynamically
echo -e "\n🚀 6. Starting development server..."
if command -v symfony >/dev/null 2>&1; then
  symfony serve --allow-http --dir=public --daemon
  sleep 2
  URL=$(symfony server:list --no-ansi | grep -oE 'https?://[0-9\.]+:[0-9]+' | head -n1)
  if [ -z "$URL" ]; then
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

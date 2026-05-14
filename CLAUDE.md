# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sylius Demo Creator - An AI-powered web application that generates complete e-commerce store presets for Sylius through a conversational interface. Uses OpenAI GPT for store configuration generation and DALL-E for image creation.

## Tech Stack

- **Backend**: PHP 8.2+ with Symfony 7.2
- **Frontend**: React 18 with React Router, Tailwind CSS 4.x
- **Database**: PostgreSQL (Docker-based)
- **AI**: OpenAI API (GPT and DALL-E)
- **Build**: Webpack Encore

## Essential Commands

### Development
```bash
# Install dependencies
composer install
npm install

# Start development
npm run watch           # Watch mode for asset changes
symfony serve          # Start Symfony dev server
# OR
npm run dev-server     # Webpack dev server with hot reload

# Build for production
npm run build
```

### Store Preset Management
```bash
# Update store presets from external repository
composer demo-creator:update-store-presets
```

### Environment Setup
Required environment variables in `.env.local`:
```
OPENAI_API_KEY=your-api-key-here
STORE_DEPLOY_TARGET=local  # or 'platformsh'
WIZARD_IMAGE_QUALITY=high   # Image generation quality
```

## Architecture

### Core Modules

**StoreDesigner** (`src/StoreDesigner/`)
- Handles AI-powered store generation via OpenAI
- Controllers: Chat, StorePreset, BulkImageGeneration
- Services: OpenAiStoreGenerator, ImageGenerator, ThemeGenerator
- Validation: JSON schemas in `resources/schemas/`
- Prompts: AI prompt templates in `resources/prompts/`

**StoreDeployer** (`src/StoreDeployer/`)
- Manages store deployment to different targets
- Deployer pattern with tagged services (`app.deployer`)
- Supports local and Platform.sh deployments

**React Frontend** (`assets/react/`)
- Multi-step wizard interface for store creation
- Components organized by stage (Welcome, DescribeStore, ChoosePlugins, Summary)
- Chat interface with streaming OpenAI responses
- API client in `assets/react/api/`

### Key Patterns

1. **AI Integration Flow**:
   - User describes store → OpenAI generates JSON definition → Validation against schemas → Store preset creation → Asset generation

2. **Image Generation**:
   - Async processing via Symfony Messenger
   - Bulk generation endpoint for multiple images
   - Types: logos, banners, product images

3. **Theme Generation**:
   - SCSS generation based on brand colors and preferences
   - Dynamic stylesheet creation from store definition

4. **Deployment**:
   - Strategy pattern for different deployment targets
   - Tagged services auto-discovered via DI

### API Endpoints

- `POST /api/store-presets` - Create store preset
- `PATCH /api/store-presets/{id}` - Update plugins
- `POST /api/store-presets/{id}/generate-store` - Full generation
- `POST /api/chat` - Conversational interface
- `POST /api/bulk-image-generation` - Batch image creation

## Development Guidelines

### Working with OpenAI Integration
- Prompts are in `resources/prompts/` - modify these to change AI behavior
- Response validation uses JSON schemas in `resources/schemas/`
- OpenAiStoreGenerator handles the main generation logic

### Frontend Development
- React components use functional components with hooks
- API calls go through the centralized API client
- Tailwind for styling - avoid inline styles
- Form state managed via React Hook Form or local state

### Adding New Deployers
1. Create class implementing `DeployerInterface`
2. Tag with `app.deployer` and set `target` attribute
3. Service will be auto-discovered

### Modifying Store Generation
- Store definition schema: `resources/schemas/store-definition.json`
- Generation logic: `src/StoreDesigner/Generator/OpenAiStoreGenerator.php`
- Chat prompts: `resources/prompts/describe_store_chat_*.txt`
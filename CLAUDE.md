# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 9 application for contract management with CAD file processing, AI assistant integration, custom drawing tools, and business workflow automation.

## Environment & Setup

### PHP Commands
- **Run artisan commands**: `/c/laragon/bin/php/php.cmd artisan {command}`
- Example: `/c/laragon/bin/php/php.cmd artisan migrate`
- Example: `/c/laragon/bin/php/php.cmd artisan config:cache`

### Frontend Build (Laravel Mix)
- **Development**: `npm run dev`
- **Watch mode**: `npm run watch`
- **Production build**: `npm run prod`
- **Hot reload**: `npm run hot`

Note: BrowserSync runs on port from `.env` (APP_PORT), proxying APP_URL

### Testing
- **Run tests**: `vendor/bin/phpunit`
- Feature tests located in `tests/Feature/`
- Test environment configured in `phpunit.xml`

## Architecture

### Backend Structure

**Core Layers:**
- **Models** (`app/Models/`) - Eloquent models with relationships, notably Contract-related models (Contract, ContractData, ContractFile, ContractSelection, etc.)
- **Services** (`app/Services/`) - Business logic layer
  - `Business/` - Domain services (Contract, Department, User, VirtualVars)
  - `VisionAssistantService.php` - OpenAI vision integration
  - `Settings.php` - Application settings service
- **Controllers** (`app/Http/Controllers/`)
  - `crud/` - CRUD controllers (Contracts, Staff, Departments, Roles, Permissions, etc.)
  - `admin/` - Admin-specific controllers
  - `site/` - Site-facing controllers
- **Enums** (`app/Enums/`) - Type-safe enumerations (using bensampo/laravel-enum)
- **Jobs** (`app/Jobs/`) - Queue jobs for async processing
- **Helpers** (`app/Helpers/helpers.php`) - Global helper functions, auto-loaded via composer.json

**Key Models:**
- Contract management: Contract, ContractData, ContractInfo, ContractFile, ContractSelection
- CAD/Drawing: AssistentFile for AI processing
- User management: AdminUser, User, Department
- Permissions: Using spatie/laravel-permission package

### Routing

**Route Organization:**
- `routes/ajax.php` - AJAX endpoints (middleware: isajax:admin)
- `routes/web/admin.php` - Admin routes (auth, registration, sections)
- `routes/web/site.php` - Public site routes
- `routes/web/log.php` - Logging routes
- `routes/api/` - API routes

**Custom Middleware:**
- `isajax:admin` - Validates AJAX requests for admin context
- `lang` - Language/localization handling

### Frontend Architecture

**Entry Points:**
- `resources/js/admin.js` - Admin panel bundle
- `resources/js/site.js` - Site-facing bundle
- `resources/js/sections.js` - Section-specific code

**Plugin System:**
All plugins in `resources/js/plugins/` follow factory pattern, loaded via admin.js:
- `ddrCRUD` - CRUD operations UI
- `ddrDatepicker` - Custom date picker
- `ddrPopup` - Modal/popup system
- `ddrFormSubmit` - Form handling
- `ddrFiles` - File upload/management
- `blockTable` - Table components
- `card` - Card UI components
- `tooltip` - Tooltip system
- `ddrCalc` - Calculator utilities
- `ddrContextMenu` - Context menu system
- `ddrDrawing` - CAD/Drawing plugin (see dedicated section)
- `autoCAD` - CAD file conversion (see dedicated section)

**Webpack Aliases** (defined in webpack.mix.js):
- `@` → `resources/js`
- `@plugins` → `resources/js/plugins`
- `@sass` → `resources/sass`
- `@fonts` → `resources/fonts`

### Specialized Modules

#### ddrDrawing Plugin
**Purpose**: JointJS-based drawing/CAD tool for creating technical diagrams

**Key Files:**
- Code: `resources/js/plugins/ddrDrawing/index.js`
- View: `resources/views/admin/section/system/system.blade.php` (tab systemTab7)
- Styles: `resources/sass/admin.sass` (section: `//-- здесь стили дял плагина ddrDrawing`)
- Documentation: `resources/js/plugins/ddrDrawing/CLAUDE.md`

**Architecture:**
- Uses JointJS 4.1.3 (available as `window.joint` from bootstrap.js)
- Factory pattern: `ddrDrawing()` returns plugin API
- Modular structure in `src/`: core/, services/, tools/
- Legacy code in `legacy/` directory
- Metadata system for links (LinkMetadata class)
- Connection types: shape-to-shape, shape-to-callout, port-to-port, etc.
- Line styles: solid, dashed, dotted, dash-dot, etc.

**Important:**
- Canvas container: `#ddrDrawingCanvas`
- Deferred initialization for hidden containers (500ms retry)
- DO NOT re-import JointJS - use global `window.joint`
- See ROADMAP.md for development plans

#### autoCAD Plugin
**Purpose**: DWG/DXF file conversion to JSON for AI processing

**Key Files:**
- Code: `resources/js/plugins/autoCAD/index.js`
- View: `resources/views/admin/section/system/system.blade.php` (marked with `{{-- тут разметка для autoCAD --}}`)
- Styles: `resources/sass/admin.sass` (section: "Здесь стили для autoCAD")
- Documentation: `resources/js/plugins/autoCAD/CLAUDE.md`
- Controller: `app/Http/Controllers/AutoCADController.php`, `AutoCADControllerNew.php`

**Workflow:**
1. User uploads DWG or DXF files
2. DWG files converted to DXF via api2convert.com (API key: 89ac71613b8958fcb5a33d1052fd9a84)
3. DXF parsed to JSON using dxf-parser library
4. Full geometry data passed to AI assistant

**Dependencies:**
- `@asposecloud/aspose-cad-cloud`
- `dxf-parser`
- `@mlightcad/libredwg-web`

### Database

**Configuration**: `config/database.php`
**Migrations**: `database/migrations/`
**Seeders**: `database/seeders/`
**Factories**: `database/factories/`

Primary connection: MySQL (configured via .env)

### Key Dependencies

**Backend:**
- `laravel/framework: ^9.2`
- `spatie/laravel-permission: ^5.5` - Role/permission management
- `bensampo/laravel-enum: ^6.11` - Enumerations
- `openai-php/laravel: 0.11.0` - OpenAI integration
- `maatwebsite/excel: ^3.1` - Excel export
- `intervention/image-laravel: ^1.3` - Image processing
- `phpoffice/phpword: ^1.2` - Word document generation

**Frontend:**
- `@joint/core: ^4.1.3` - Diagramming library
- `dxf-parser: ^1.1.2` - CAD file parsing
- `jquery: ^3.6.0`
- `tippy.js: ^6.3.7` - Tooltips
- `morphdom: ^2.7.5` - DOM diffing
- `compressorjs: ^1.1.1` - Image compression

## Custom Helpers

**Global helpers** auto-loaded from `app/Helpers/helpers.php`:
- `toLog()` - Custom logger with Carbon date formatting
- `cleanResource()` - Recursively converts API resources to arrays
- Uses custom `DdrDateTime` class for date handling

## Views & Templates

**Structure:**
- `resources/views/admin/` - Admin panel views
- `resources/views/site/` - Public site views
- `resources/views/components/` - Blade components
- `resources/views/render/` - Rendering templates

**Template Engine**: Blade + `.tpl` files (loaded via raw-loader in webpack)

## Styles

**SASS Structure:**
- `resources/sass/admin.sass` - Admin panel styles
- `resources/sass/site.sass` - Site styles
- `resources/sass/common/app.scss` - Common/shared styles

**Output:**
- `public/assets/css/admin.css`
- `public/assets/css/site.css`
- `public/assets/css/app.css`

## Public Assets

**Structure:**
- `public/assets/css/` - Compiled CSS
- `public/assets/js/` - Compiled JS
- Symlinks for storage: `public/storage`, `public/assistent`, `public/prompts`

## AI Integration

**OpenAI Configuration**: `config/openai.php`
**Service**: `VisionAssistantService.php` - Vision API integration for CAD file analysis
**Storage**: AssistentFile model for tracking AI-processed files

## Important Development Notes

1. **Artisan commands must use full PHP path**: `/c/laragon/bin/php/php.cmd artisan`
2. **JointJS is global** - access via `window.joint`, do not re-import
3. **Plugin pattern** - All JS plugins use factory functions returning API objects
4. **Template files** - `.tpl` files processed by raw-loader
5. **Module-specific docs** - Check for CLAUDE.md in plugin directories for detailed context
6. **BrowserSync** - Auto-configured to watch Blade, JS, and CSS files
7. **CAD conversion** - Two-stage process (DWG→DXF→JSON) for AI compatibility
8. **Custom middleware** - `isajax:admin` for AJAX validation, `lang` for localization

## Localization

Language files in `lang/` directory. Translation helpers:
- `__()` - Standard translation
- `trans_choice()` - Pluralization
- AJAX endpoint: `/langline` for dynamic translations

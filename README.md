# Fiefdom Forge

A web-based medieval city simulation game where players manage citizens, economy, buildings, and city development.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite)
![Smarty](https://img.shields.io/badge/Smarty-5.x-F7DF1E)

## Overview

Fiefdom Forge is a browser-based strategy game that puts you in charge of a medieval fiefdom. Recruit citizens, construct buildings, manage your economy, and watch your realm grow from a small settlement into a thriving medieval city.

### Key Features

- **Citizen Management**: Recruit, house, and employ citizens with unique stats (health, happiness, wealth)
- **Economic Simulation**: Production chains, market trading, taxes, and treasury management
- **Building System**: Construct houses, businesses, farms, and public buildings
- **Time Progression**: Day-by-day simulation with seasonal effects
- **Life Events**: Citizens age, marry, have children, and eventually pass away
- **Achievements**: Track your progress with unlockable achievements
- **Statistics & Reports**: Comprehensive economic reports and historical data visualization

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.x |
| Database | SQLite |
| Templating | Smarty 5.x |
| Frontend | HTML5, CSS3, JavaScript |
| Package Manager | Composer |
| Random Data | Faker PHP |

## Quick Start

### Prerequisites

- PHP 8.0 or higher
- Composer
- Web server (or PHP built-in server for development)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/fiefdom-forge.git
   cd fiefdom-forge
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   ```

4. **Start the development server**
   ```bash
   php -S localhost:8000 -t public_html
   ```

5. **Open in browser**
   ```
   http://localhost:8000
   ```

The SQLite database is automatically created and initialized on first request. No manual database setup required!

## Directory Structure

```
fiefdom-forge/
├── public_html/          # Web root
│   ├── index.php         # Single entry point & router
│   └── assets/
│       └── css/
│           └── style.css # Main stylesheet
├── src/                  # PHP classes
│   ├── Config.php        # Environment configuration
│   ├── Database.php      # SQLite PDO wrapper
│   ├── Session.php       # Session management
│   ├── User.php          # Authentication
│   ├── View.php          # Smarty integration
│   ├── GameEngine.php    # Main simulation orchestrator
│   ├── GameState.php     # Game time & treasury
│   ├── Citizen.php       # Citizen entity
│   ├── CitizenSimulator.php  # Life events simulation
│   ├── Building.php      # Building management
│   ├── Business.php      # Business operations
│   ├── Good.php          # Goods & resources
│   ├── EconomySimulator.php  # Economic simulation
│   ├── Area.php          # City districts
│   ├── Role.php          # Citizen occupations
│   ├── Skill.php         # Citizen skills
│   ├── Inventory.php     # Resource inventory
│   ├── GameEvent.php     # Event logging
│   ├── Achievement.php   # Achievement system
│   ├── HistoricalStats.php   # Statistics tracking
│   └── RandomEventSystem.php # Random events
├── templates/            # Smarty templates
│   ├── layout.tpl        # Base layout
│   ├── dashboard.tpl     # Main dashboard
│   ├── citizens.tpl      # Citizens list
│   ├── buildings.tpl     # Buildings list
│   ├── economy.tpl       # Market & trade
│   ├── reports.tpl       # Economic reports
│   └── ...
├── templates_c/          # Compiled templates (auto-generated)
├── cache/                # Smarty cache
├── database/             # SQLite database file
├── sql/
│   └── schema.sql        # Database schema
├── vendor/               # Composer dependencies
├── .env.example          # Environment template
├── composer.json         # PHP dependencies
└── CLAUDE.md             # AI assistant instructions
```

## Configuration

### Environment Variables (.env)

```ini
# Database Configuration
DB_PATH=database/fiefdom_forge.sqlite

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Session Settings
SESSION_NAME=fiefdom_session
SESSION_LIFETIME=3600
```

## Game Mechanics

### Time System

- Game progresses day-by-day when you click "Advance Day" or "Advance Week"
- Each year has 360 days divided into 4 seasons (90 days each)
- Seasons affect production rates:
  - **Spring**: Farms +20%, Ranches +10%
  - **Summer**: Farms +50%, Ranches +20%, Lumber Mills +10%
  - **Autumn**: Farms +30%
  - **Winter**: Farms -70%, Ranches -30%, Mines +20%, All others -10%

### Citizens

Citizens have the following attributes:
- **Age**: 0-100+ (children < 18, adults 18-64, elders 65+)
- **Gender**: Male or Female
- **Health**: 0-100%
- **Happiness**: 0-100%
- **Wealth**: Personal gold
- **Skills**: Crafting, Gathering, Combat, Social

Life events include:
- Births (married couples can have children)
- Deaths (age and health-dependent)
- Marriages (single adults can marry)
- Aging (citizens age each day)

### Buildings

| Type | Purpose |
|------|---------|
| House | Provides housing for citizens |
| Business | Production and employment |
| Farm | Food production |
| Resource | Raw material extraction (mines, lumber mills) |
| Public | Community buildings |

Buildings have:
- Construction cost
- Upkeep cost (daily)
- Capacity (residents or workers)
- Condition (degrades over time, can be repaired)
- Grid coordinates for map placement

### Economy

**Income Sources:**
- Tax collection from citizens
- Selling goods at market

**Expenses:**
- Building upkeep
- Worker wages
- Production costs
- Citizen recruitment

**Production Chain:**
Resources → Manufacturing → Finished Goods → Market Sales

### Food System

- Citizens consume 1 food unit per day
- Bread is preferred, Wheat is a fallback (2x consumption)
- Food shortage affects citizen health and happiness

## Pages & Features

| Page | Route | Description |
|------|-------|-------------|
| Dashboard | `/dashboard` | Overview with stats, charts, and quick actions |
| Citizens | `/citizens` | List all citizens, recruit new ones (single or bulk) |
| Citizen Detail | `/citizen/{id}` | Individual citizen management |
| Buildings | `/buildings` | View and construct buildings |
| Building Detail | `/building/{id}` | Building management and repairs |
| Business Detail | `/business/{id}` | Business operations |
| Areas | `/areas` | District management and tax rates |
| Economy | `/economy` | Market trading (buy/sell goods) |
| Reports | `/reports` | Economic reports and analysis |
| Transactions | `/transactions` | Transaction history |
| Statistics | `/stats` | Historical charts and trends |
| Events | `/events` | Game event chronicle |
| Achievements | `/achievements` | Progress tracking |
| Map | `/map` | Visual city map |

## Database Schema

### Core Tables

```sql
users           -- Player accounts
citizens        -- City inhabitants
roles           -- Occupational roles
skills          -- Skill definitions
areas           -- City districts
buildings       -- Structures
businesses      -- Commercial entities
goods           -- Resources and products
inventory       -- Player's goods storage
transactions    -- Economic records
game_states     -- Per-user game progress
game_events     -- Event log
notifications   -- User notifications
historical_stats -- Statistics over time
```

### Key Relationships

- Citizens belong to Users
- Citizens can have a Home (Building) and Work (Business)
- Businesses are linked to Buildings
- Buildings are in Areas
- Goods can require other Goods for production

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/notifications` | Fetch user notifications |
| POST | `/api/notifications/read` | Mark notification as read |
| POST | `/api/notifications/read-all` | Mark all as read |
| GET | `/api/chart-data` | Historical statistics for charts |

## Development

### Adding New Features

1. **Database changes**: Update `sql/schema.sql`
2. **Backend logic**: Add/modify classes in `src/`
3. **Routes**: Add handlers in `public_html/index.php`
4. **Templates**: Create/edit `.tpl` files in `templates/`
5. **Styles**: Update `public_html/assets/css/style.css`

### Smarty Template Notes

- Use `{literal}...{/literal}` to wrap JavaScript with curly braces
- Variables: `{$variable}`, `{$array.key}`, `{$object->property}`
- Conditionals: `{if}...{elseif}...{else}...{/if}`
- Loops: `{foreach $items as $item}...{/foreach}`
- Modifiers: `{$var|default:'value'}`, `{$var|capitalize}`

### Code Style

- PSR-4 autoloading via Composer
- Namespace: `FiefdomForge`
- PDO with prepared statements for database queries
- CSRF protection on all POST requests

## Security Features

- Password hashing with `password_hash()`
- CSRF token validation
- Prepared statements (SQL injection prevention)
- Session-based authentication
- Input validation and sanitization
- XSS prevention in templates

## Roadmap

### Completed
- [x] User authentication
- [x] Dashboard with statistics
- [x] Citizen management
- [x] Building construction
- [x] Business operations
- [x] Economic simulation
- [x] Time progression
- [x] Life events (births, deaths, marriages)
- [x] Market trading
- [x] Transaction history
- [x] Notifications system
- [x] Economic reports
- [x] Search & filtering
- [x] Bulk citizen recruitment
- [x] Seasonal effects
- [x] Food consumption system
- [x] Building degradation & repair
- [x] Historical statistics & charts

### Planned
- [ ] Random events system expansion
- [ ] Military/defense mechanics
- [ ] Trade routes with other cities
- [ ] Technology/research tree
- [ ] Multiplayer features
- [ ] Mobile-responsive improvements

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Acknowledgments

- [Smarty Template Engine](https://www.smarty.net/)
- [Faker PHP](https://fakerphp.github.io/) for random data generation
- [Chart.js](https://www.chartjs.org/) for statistics visualization
- Medieval-themed design inspiration from classic city-building games

---

**Happy ruling, my liege!** 👑

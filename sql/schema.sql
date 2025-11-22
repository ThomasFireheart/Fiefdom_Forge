-- SQL Schema for Fiefdom Forge Medieval City Simulation Game
-- SQLite Version

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    role TEXT DEFAULT 'player' CHECK(role IN ('player', 'admin')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Table: skills
CREATE TABLE IF NOT EXISTS skills (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    type TEXT -- e.g., "crafting", "gathering", "combat", "social"
);

-- Table: roles
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    base_income INTEGER DEFAULT 0,
    prestige_bonus INTEGER DEFAULT 0
);

-- Table: areas
CREATE TABLE IF NOT EXISTS areas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    tax_rate REAL DEFAULT 0.05, -- e.g., 0.05 for 5%
    prestige INTEGER DEFAULT 0,
    capacity INTEGER DEFAULT 0 -- Total citizen capacity for the area
);

-- Table: goods (unified for both products and resources)
CREATE TABLE IF NOT EXISTS goods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    base_price INTEGER DEFAULT 10,
    is_resource INTEGER DEFAULT 0, -- 1 if it's a raw resource (e.g., Wood, Ore)
    resource_needed TEXT -- JSON object: {"good_id": quantity, ...}
);

-- Table: buildings
CREATE TABLE IF NOT EXISTS buildings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('house', 'business', 'public', 'farm', 'resource')),
    area_id INTEGER NOT NULL,
    owner_citizen_id INTEGER, -- Nullable if city-owned or unassigned
    capacity INTEGER DEFAULT 1, -- e.g., housing capacity, worker slots for businesses
    condition INTEGER DEFAULT 100,
    construction_cost INTEGER DEFAULT 0,
    upkeep_cost INTEGER DEFAULT 0,
    built_at TEXT DEFAULT CURRENT_TIMESTAMP,
    x_coord INTEGER, -- X-coordinate on a grid map
    y_coord INTEGER, -- Y-coordinate on a grid map
    FOREIGN KEY (area_id) REFERENCES areas(id)
);

-- Table: businesses
CREATE TABLE IF NOT EXISTS businesses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    building_id INTEGER UNIQUE NOT NULL,
    owner_citizen_id INTEGER, -- Nullable if city-owned
    type TEXT, -- e.g., "bakery", "blacksmith", "farm", "mine"
    products TEXT, -- JSON array of good_ids produced by this business
    employees_capacity INTEGER DEFAULT 1,
    current_employees INTEGER DEFAULT 0,
    treasury INTEGER DEFAULT 0,
    reputation INTEGER DEFAULT 50,
    FOREIGN KEY (building_id) REFERENCES buildings(id)
);

-- Table: citizens
CREATE TABLE IF NOT EXISTS citizens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name TEXT NOT NULL,
    age INTEGER DEFAULT 0,
    gender TEXT CHECK(gender IN ('male', 'female')),
    role_id INTEGER, -- Nullable for unemployed/children
    wealth INTEGER DEFAULT 0,
    health INTEGER DEFAULT 100,
    happiness INTEGER DEFAULT 100,
    skill_levels TEXT, -- JSON object: {"skill_id": level, ...}
    home_building_id INTEGER, -- Nullable if homeless
    work_business_id INTEGER, -- Nullable if unemployed
    spouse_id INTEGER, -- Nullable if unmarried
    is_alive INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (home_building_id) REFERENCES buildings(id),
    FOREIGN KEY (work_business_id) REFERENCES businesses(id),
    FOREIGN KEY (spouse_id) REFERENCES citizens(id)
);

-- Table: game_states (per-user game progress)
CREATE TABLE IF NOT EXISTS game_states (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    current_day INTEGER DEFAULT 1,
    current_year INTEGER DEFAULT 1,
    treasury INTEGER DEFAULT 1000,
    settings TEXT, -- JSON
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: game_events (event log)
CREATE TABLE IF NOT EXISTS game_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    message TEXT NOT NULL,
    related_citizen_id INTEGER,
    game_day INTEGER NOT NULL,
    game_year INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (related_citizen_id) REFERENCES citizens(id)
);

-- Table: transactions
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    seller_id INTEGER, -- Nullable if sold by city/market
    buyer_id INTEGER, -- Nullable if bought by city/market
    good_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    price_per_unit INTEGER NOT NULL,
    total_price INTEGER NOT NULL,
    transaction_type TEXT NOT NULL CHECK(transaction_type IN ('sale', 'tax', 'wages', 'production_cost', 'upkeep_cost')),
    transaction_date TEXT DEFAULT CURRENT_TIMESTAMP,
    location_area_id INTEGER, -- Nullable if transaction is not location-specific
    FOREIGN KEY (seller_id) REFERENCES citizens(id),
    FOREIGN KEY (buyer_id) REFERENCES citizens(id),
    FOREIGN KEY (good_id) REFERENCES goods(id),
    FOREIGN KEY (location_area_id) REFERENCES areas(id)
);

-- Table: inventory (city/player inventory of goods)
CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    good_id INTEGER NOT NULL,
    quantity INTEGER DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (good_id) REFERENCES goods(id),
    UNIQUE(user_id, good_id)
);

-- Table: historical_stats (for tracking stats over time)
CREATE TABLE IF NOT EXISTS historical_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_day INTEGER NOT NULL,
    game_year INTEGER NOT NULL,
    population INTEGER DEFAULT 0,
    treasury INTEGER DEFAULT 0,
    buildings INTEGER DEFAULT 0,
    avg_happiness INTEGER DEFAULT 0,
    avg_health INTEGER DEFAULT 0,
    recorded_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table: notifications (tracks read/unread status for events)
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    event_id INTEGER NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES game_events(id),
    UNIQUE(user_id, event_id)
);

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_citizens_user_id ON citizens(user_id);
CREATE INDEX IF NOT EXISTS idx_citizens_is_alive ON citizens(is_alive);
CREATE INDEX IF NOT EXISTS idx_buildings_area_id ON buildings(area_id);
CREATE INDEX IF NOT EXISTS idx_buildings_type ON buildings(type);
CREATE INDEX IF NOT EXISTS idx_businesses_building_id ON businesses(building_id);
CREATE INDEX IF NOT EXISTS idx_game_events_user_id ON game_events(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_seller_id ON transactions(seller_id);
CREATE INDEX IF NOT EXISTS idx_transactions_buyer_id ON transactions(buyer_id);
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);

<?php

/**
 * Fiefdom Forge - Medieval City Simulation Game
 * Main Entry Point
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

use FiefdomForge\Config;
use FiefdomForge\Database;
use FiefdomForge\Session;
use FiefdomForge\User;
use FiefdomForge\View;
use FiefdomForge\GameEngine;

// Initialize configuration
$config = Config::getInstance();

// Initialize database if needed
$db = Database::getInstance();
if (!$db->isInitialized()) {
    $db->initializeSchema();
}

// Get the request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Simple router
$routes = [
    'GET' => [
        '/' => 'handleHome',
        '/login' => 'handleLoginPage',
        '/register' => 'handleRegisterPage',
        '/dashboard' => 'handleDashboard',
        '/logout' => 'handleLogout',
        '/citizens' => 'handleCitizens',
        '/buildings' => 'handleBuildings',
        '/buildings/new' => 'handleNewBuilding',
        '/economy' => 'handleEconomy',
        '/transactions' => 'handleTransactions',
        '/reports' => 'handleEconomicReports',
        '/areas' => 'handleAreas',
        '/events' => 'handleEvents',
        '/map' => 'handleMap',
        '/stats' => 'handleStats',
        '/achievements' => 'handleAchievements',
        '/api/chart-data' => 'handleApiChartData',
        '/api/notifications' => 'handleApiNotifications',
    ],
    'POST' => [
        '/login' => 'handleLogin',
        '/register' => 'handleRegister',
        '/advance-day' => 'handleAdvanceDay',
        '/advance-week' => 'handleAdvanceWeek',
        '/advance-month' => 'handleAdvanceMonth',
        '/advance-season' => 'handleAdvanceSeason',
        '/citizens/assign-home' => 'handleAssignHome',
        '/citizens/assign-job' => 'handleAssignJob',
        '/citizens/assign-role' => 'handleAssignRole',
        '/citizens/create' => 'handleCreateCitizen',
        '/citizens/recruit-bulk' => 'handleBulkRecruitCitizens',
        '/buildings/create' => 'handleCreateBuilding',
        '/businesses/create' => 'handleCreateBusiness',
        '/areas/create' => 'handleCreateArea',
        '/areas/update-tax' => 'handleUpdateTaxRate',
        '/market/buy' => 'handleMarketBuy',
        '/market/sell' => 'handleMarketSell',
        '/events/trigger' => 'handleTriggerEvent',
        '/buildings/repair' => 'handleRepairBuilding',
        '/buildings/transfer-ownership' => 'handleTransferOwnership',
        '/api/notifications/read' => 'handleApiNotificationRead',
        '/api/notifications/read-all' => 'handleApiNotificationReadAll',
    ],
];

// Dynamic routes (with parameters)
$dynamicRoutes = [
    'GET' => [
        '#^/citizen/(\d+)$#' => 'handleCitizenDetail',
        '#^/building/(\d+)$#' => 'handleBuildingDetail',
        '#^/business/(\d+)$#' => 'handleBusinessDetail',
    ],
];

// CSRF validation for POST requests (except login/register which create the session)
// Also exempt API routes which use JSON
$csrfExemptRoutes = ['/login', '/register', '/api/notifications/read', '/api/notifications/read-all'];
if ($requestMethod === 'POST' && !in_array($requestUri, $csrfExemptRoutes)) {
    $token = $_POST['_csrf_token'] ?? null;
    if (!Session::validateCsrfToken($token)) {
        Session::flash('error', 'Invalid security token. Please try again.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/dashboard'));
        exit;
    }
}

// Route the request
if (isset($routes[$requestMethod][$requestUri])) {
    $handler = $routes[$requestMethod][$requestUri];
    $handler();
} elseif (isset($dynamicRoutes[$requestMethod])) {
    // Check dynamic routes
    $matched = false;
    foreach ($dynamicRoutes[$requestMethod] as $pattern => $handler) {
        if (preg_match($pattern, $requestUri, $matches)) {
            array_shift($matches); // Remove full match
            $handler(...$matches);
            $matched = true;
            break;
        }
    }
    if (!$matched) {
        http_response_code(404);
        echo "Page not found";
    }
} elseif (preg_match('#^/assets/(.+)$#', $requestUri, $matches)) {
    // Serve static assets
    serveStaticAsset($matches[1]);
} else {
    http_response_code(404);
    echo "Page not found";
}

// Route Handlers

function handleHome(): void
{
    if (User::isLoggedIn()) {
        header('Location: /dashboard');
        exit;
    }

    $view = View::getInstance();
    $view->display('home.tpl');
}

function handleLoginPage(): void
{
    if (User::isLoggedIn()) {
        header('Location: /dashboard');
        exit;
    }

    $view = View::getInstance();
    $view->display('login.tpl');
}

function handleLogin(): void
{
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = User::login($username, $password);

    if ($result['success']) {
        Session::flash('success', 'Welcome back to your fiefdom!');
        header('Location: /dashboard');
        exit;
    }

    $view = View::getInstance();
    $view->assign('error', $result['error']);
    $view->assign('username', $username);
    $view->display('login.tpl');
}

function handleRegisterPage(): void
{
    if (User::isLoggedIn()) {
        header('Location: /dashboard');
        exit;
    }

    $view = View::getInstance();
    $view->display('register.tpl');
}

function handleRegister(): void
{
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $view = View::getInstance();

    // Validate password confirmation
    if ($password !== $passwordConfirm) {
        $view->assign('error', 'Passwords do not match');
        $view->assign('username', $username);
        $view->assign('email', $email);
        $view->display('register.tpl');
        return;
    }

    $result = User::register($username, $email, $password);

    if ($result['success']) {
        // Auto-login after registration
        User::login($username, $password);
        Session::flash('success', 'Welcome to Fiefdom Forge! Your kingdom awaits.');
        header('Location: /dashboard');
        exit;
    }

    $view->assign('error', $result['error']);
    $view->assign('username', $username);
    $view->assign('email', $email);
    $view->display('register.tpl');
}

function handleDashboard(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    // Initialize game if new
    $gameEngine->initializeNewGame();

    $view = View::getInstance();
    $stats = $gameEngine->getDashboardStats();
    $recentEvents = $gameEngine->getRecentEvents(10);

    $view->assign('stats', $stats);
    $view->assign('recent_events', $recentEvents);
    $view->display('dashboard.tpl');
}

function handleAdvanceDay(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $events = $gameEngine->advanceDay();

    $eventCount = count($events);
    if ($eventCount > 0) {
        Session::flash('success', "Day advanced! {$eventCount} event(s) occurred.");
    } else {
        Session::flash('success', 'Day advanced. A quiet day in the realm.');
    }

    header('Location: /dashboard');
    exit;
}

function handleAdvanceWeek(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $events = $gameEngine->advanceDays(7);

    $eventCount = count($events);
    Session::flash('success', "Week advanced! {$eventCount} event(s) occurred over 7 days.");

    header('Location: /dashboard');
    exit;
}

function handleAdvanceMonth(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $events = $gameEngine->advanceDays(30);

    $eventCount = count($events);
    Session::flash('success', "Month advanced! {$eventCount} event(s) occurred over 30 days.");

    header('Location: /dashboard');
    exit;
}

function handleAdvanceSeason(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $events = $gameEngine->advanceDays(90);

    $eventCount = count($events);
    Session::flash('success', "Season advanced! {$eventCount} event(s) occurred over 90 days.");

    header('Location: /dashboard');
    exit;
}

function handleCitizens(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $view = View::getInstance();
    $stats = $gameEngine->getDashboardStats();

    // Get all citizens
    $db = \FiefdomForge\Database::getInstance();
    $citizens = $db->fetchAll(
        "SELECT c.*,
                bus.name as role_name,
                b.name as home_name
         FROM citizens c
         LEFT JOIN businesses bus ON c.work_business_id = bus.id
         LEFT JOIN buildings b ON c.home_building_id = b.id
         WHERE c.user_id = ? AND c.is_alive = 1
         ORDER BY c.name",
        [$userId]
    );

    $view->assign('stats', $stats);
    $view->assign('citizens', $citizens);
    $view->display('citizens.tpl');
}

function handleBuildings(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $view = View::getInstance();
    $stats = $gameEngine->getDashboardStats();

    // Get all buildings with area info
    $db = \FiefdomForge\Database::getInstance();
    $buildings = $db->fetchAll(
        "SELECT b.*, a.name as area_name,
                (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) as occupants
         FROM buildings b
         LEFT JOIN areas a ON b.area_id = a.id
         ORDER BY a.name, b.name"
    );

    // Calculate totals
    $totalOccupants = 0;
    $totalUpkeep = 0;
    foreach ($buildings as $building) {
        $totalOccupants += $building['occupants'];
        $totalUpkeep += $building['upkeep_cost'];
    }

    $areas = \FiefdomForge\Area::getAll();

    $view->assign('stats', $stats);
    $view->assign('buildings', $buildings);
    $view->assign('areas', $areas);
    $view->assign('total_occupants', $totalOccupants);
    $view->assign('total_upkeep', $totalUpkeep);
    $view->display('buildings.tpl');
}

function handleEconomy(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $view = View::getInstance();
    $stats = $gameEngine->getDashboardStats();

    // Get businesses
    $businesses = \FiefdomForge\Business::getAll();

    // Get goods
    $goods = \FiefdomForge\Good::getAll();

    // Get inventory
    $inventory = new \FiefdomForge\Inventory($userId);
    $inventoryItems = $inventory->getAllGoods();
    $inventoryValue = $inventory->getTotalValue();

    $view->assign('stats', $stats);
    $view->assign('businesses', $businesses);
    $view->assign('goods', $goods);
    $view->assign('inventory', $inventoryItems);
    $view->assign('inventory_value', $inventoryValue);
    $view->display('economy.tpl');
}

function handleTransactions(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $db = \FiefdomForge\Database::getInstance();

    // Get all transactions with related data
    $transactions = $db->fetchAll(
        "SELECT t.*,
                g.name as good_name,
                seller.name as seller_name,
                buyer.name as buyer_name,
                a.name as area_name
         FROM transactions t
         LEFT JOIN goods g ON t.good_id = g.id
         LEFT JOIN citizens seller ON t.seller_id = seller.id
         LEFT JOIN citizens buyer ON t.buyer_id = buyer.id
         LEFT JOIN areas a ON t.location_area_id = a.id
         ORDER BY t.transaction_date DESC
         LIMIT 500"
    );

    // Get transaction summary by type
    $summaryByType = $db->fetchAll(
        "SELECT transaction_type, COUNT(*) as count, SUM(total_price) as total_value
         FROM transactions
         GROUP BY transaction_type"
    );

    // Calculate totals
    $totalTransactions = count($transactions);
    $totalValue = 0;
    $typeCounts = [];
    foreach ($summaryByType as $row) {
        $typeCounts[$row['transaction_type']] = [
            'count' => $row['count'],
            'total_value' => $row['total_value']
        ];
        $totalValue += $row['total_value'];
    }

    $view = View::getInstance();
    $view->assign('transactions', $transactions);
    $view->assign('type_counts', $typeCounts);
    $view->assign('total_transactions', $totalTransactions);
    $view->assign('total_value', $totalValue);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->display('transactions.tpl');
}

function handleEconomicReports(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $db = \FiefdomForge\Database::getInstance();

    // Treasury report - income vs expenses
    $totalIncome = 0;
    $totalExpenses = 0;

    $incomeData = $db->fetch(
        "SELECT SUM(total_price) as total FROM transactions WHERE transaction_type IN ('sale', 'tax')"
    );
    $totalIncome = $incomeData['total'] ?? 0;

    $expenseData = $db->fetch(
        "SELECT SUM(total_price) as total FROM transactions WHERE transaction_type IN ('wages', 'production_cost', 'upkeep_cost')"
    );
    $totalExpenses = $expenseData['total'] ?? 0;

    // Business performance
    $businessStats = $db->fetchAll(
        "SELECT b.*, bld.name as building_name,
                (SELECT COUNT(*) FROM citizens c WHERE c.work_business_id = b.id AND c.is_alive = 1) as worker_count
         FROM businesses b
         LEFT JOIN buildings bld ON b.building_id = bld.id
         ORDER BY b.treasury DESC"
    );

    // Production by type
    $productionByType = $db->fetchAll(
        "SELECT b.type,
                COUNT(*) as business_count,
                SUM(b.treasury) as total_treasury,
                AVG(b.reputation) as avg_reputation
         FROM businesses b
         GROUP BY b.type
         ORDER BY total_treasury DESC"
    );

    // Inventory value
    $inventory = new \FiefdomForge\Inventory($userId);
    $inventoryItems = $inventory->getAllGoods();
    $inventoryValue = $inventory->getTotalValue();

    // Citizen wealth distribution
    $wealthDistribution = $db->fetchAll(
        "SELECT
            CASE
                WHEN wealth < 20 THEN 'Poor'
                WHEN wealth < 50 THEN 'Modest'
                WHEN wealth < 100 THEN 'Comfortable'
                ELSE 'Wealthy'
            END as wealth_class,
            COUNT(*) as citizen_count,
            SUM(wealth) as total_wealth,
            AVG(wealth) as avg_wealth
         FROM citizens
         WHERE user_id = ? AND is_alive = 1
         GROUP BY wealth_class",
        [$userId]
    );

    // Area economic performance
    $areaStats = $db->fetchAll(
        "SELECT a.id, a.name, a.tax_rate,
                (SELECT COUNT(*) FROM buildings b WHERE b.area_id = a.id) as building_count,
                (SELECT COUNT(*) FROM citizens c JOIN buildings b ON c.home_building_id = b.id WHERE b.area_id = a.id AND c.is_alive = 1 AND c.user_id = ?) as citizen_count,
                (SELECT SUM(c.wealth) FROM citizens c JOIN buildings b ON c.home_building_id = b.id WHERE b.area_id = a.id AND c.is_alive = 1 AND c.user_id = ?) as total_citizen_wealth
         FROM areas a
         ORDER BY total_citizen_wealth DESC",
        [$userId, $userId]
    );

    $view = View::getInstance();
    $view->assign('total_income', $totalIncome);
    $view->assign('total_expenses', $totalExpenses);
    $view->assign('net_income', $totalIncome - $totalExpenses);
    $view->assign('business_stats', $businessStats);
    $view->assign('production_by_type', $productionByType);
    $view->assign('inventory_items', $inventoryItems);
    $view->assign('inventory_value', $inventoryValue);
    $view->assign('wealth_distribution', $wealthDistribution);
    $view->assign('area_stats', $areaStats);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->display('reports.tpl');
}

function handleMarketBuy(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $gameState = $gameEngine->getGameState();

    $goodId = (int)($_POST['good_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($goodId === 0 || $quantity < 1) {
        Session::flash('error', 'Invalid purchase request.');
        header('Location: /economy');
        exit;
    }

    $good = \FiefdomForge\Good::loadById($goodId);
    if (!$good) {
        Session::flash('error', 'Good not found.');
        header('Location: /economy');
        exit;
    }

    $totalCost = $good->getBasePrice() * $quantity;

    if ($gameState->getTreasury() < $totalCost) {
        Session::flash('error', "Not enough gold! Need {$totalCost} gold.");
        header('Location: /economy');
        exit;
    }

    // Deduct cost and add to inventory
    $gameState->subtractTreasury($totalCost);
    $gameState->save();

    $inventory = new \FiefdomForge\Inventory($userId);
    $inventory->addGood($goodId, $quantity);

    Session::flash('success', "Purchased {$quantity}x {$good->getName()} for {$totalCost} gold.");
    header('Location: /economy');
    exit;
}

function handleMarketSell(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $gameState = $gameEngine->getGameState();

    $goodId = (int)($_POST['good_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($goodId === 0 || $quantity < 1) {
        Session::flash('error', 'Invalid sale request.');
        header('Location: /economy');
        exit;
    }

    $good = \FiefdomForge\Good::loadById($goodId);
    if (!$good) {
        Session::flash('error', 'Good not found.');
        header('Location: /economy');
        exit;
    }

    $inventory = new \FiefdomForge\Inventory($userId);

    if (!$inventory->removeGood($goodId, $quantity)) {
        Session::flash('error', 'Not enough goods in inventory.');
        header('Location: /economy');
        exit;
    }

    // Sell at 80% of base price
    $sellPrice = (int)($good->getBasePrice() * 0.8);
    $totalValue = $sellPrice * $quantity;

    $gameState->addTreasury($totalValue);
    $gameState->save();

    Session::flash('success', "Sold {$quantity}x {$good->getName()} for {$totalValue} gold.");
    header('Location: /economy');
    exit;
}

function handleEvents(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    // Get all events (more than recent)
    $events = \FiefdomForge\GameEvent::getRecent($userId, 100);
    $eventsArray = array_map(fn($e) => $e->toArray(), $events);

    // Count events by category
    $eventCounts = [
        'positive' => 0,
        'negative' => 0,
        'neutral' => 0,
        'special' => 0,
    ];

    foreach ($eventsArray as $event) {
        $category = $event['category'] ?? 'neutral';
        if (isset($eventCounts[$category])) {
            $eventCounts[$category]++;
        }
    }

    // Check if user is admin
    $currentUser = User::current();
    $isAdmin = $currentUser && $currentUser->isAdmin();

    // Get available events for admin trigger
    $availableEvents = [];
    if ($isAdmin) {
        $randomEventSystem = new \FiefdomForge\RandomEventSystem($userId, $gameEngine->getGameState());
        $availableEvents = $randomEventSystem->getAvailableEvents();
    }

    $view = View::getInstance();
    $view->assign('events', $eventsArray);
    $view->assign('event_counts', $eventCounts);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->assign('is_admin', $isAdmin);
    $view->assign('available_events', $availableEvents);
    $view->display('events.tpl');
}

function handleTriggerEvent(): void
{
    User::requireAuth();

    $currentUser = User::current();
    if (!$currentUser || !$currentUser->isAdmin()) {
        Session::flash('error', 'Only administrators can trigger events.');
        header('Location: /events');
        exit;
    }

    $userId = Session::get('user_id');
    $eventType = $_POST['event_type'] ?? '';

    if (empty($eventType)) {
        Session::flash('error', 'Please select an event type.');
        header('Location: /events');
        exit;
    }

    $gameEngine = new GameEngine($userId);
    $randomEventSystem = new \FiefdomForge\RandomEventSystem($userId, $gameEngine->getGameState());

    $result = $randomEventSystem->triggerEvent($eventType);

    if ($result) {
        // Log the event
        \FiefdomForge\GameEvent::create(
            $userId,
            $result['type'],
            $result['message'],
            $gameEngine->getGameState()->getCurrentDay(),
            $gameEngine->getGameState()->getCurrentYear(),
            $result['citizen_id'] ?? null
        );

        Session::flash('success', "Event triggered: {$result['message']}");
    } else {
        Session::flash('error', 'Failed to trigger event.');
    }

    header('Location: /events');
    exit;
}

function handleMap(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $db = \FiefdomForge\Database::getInstance();

    // Get all areas
    $areas = \FiefdomForge\Area::getAll();

    // Get all buildings with additional info
    $mapData = \FiefdomForge\Building::getMapData();

    // Organize buildings by area
    $buildingsByArea = [];
    foreach ($mapData as $building) {
        $areaId = $building['area_id'];
        if (!isset($buildingsByArea[$areaId])) {
            $buildingsByArea[$areaId] = [];
        }
        $buildingsByArea[$areaId][] = $building;
    }

    // Get area statistics
    $areaStats = [];
    foreach ($areas as $area) {
        $pop = $db->fetch(
            "SELECT COUNT(*) as count FROM citizens c
             JOIN buildings b ON c.home_building_id = b.id
             WHERE b.area_id = ? AND c.is_alive = 1",
            [$area->getId()]
        );
        $buildings = $db->fetch(
            "SELECT COUNT(*) as count FROM buildings WHERE area_id = ?",
            [$area->getId()]
        );
        $areaStats[$area->getId()] = [
            'population' => $pop['count'] ?? 0,
            'buildings' => $buildings['count'] ?? 0,
        ];
    }

    // Get building type counts
    $typeCounts = $db->fetchAll(
        "SELECT type, COUNT(*) as count FROM buildings GROUP BY type"
    );
    $buildingTypeCounts = [];
    foreach ($typeCounts as $row) {
        $buildingTypeCounts[$row['type']] = $row['count'];
    }

    $view = View::getInstance();
    $view->assign('areas', $areas);
    $view->assign('buildings_by_area', $buildingsByArea);
    $view->assign('area_stats', $areaStats);
    $view->assign('building_type_counts', $buildingTypeCounts);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->display('map.tpl');
}

function handleStats(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $db = \FiefdomForge\Database::getInstance();

    $stats = $gameEngine->getDashboardStats();

    // Calculate population percentages for chart
    $total = max(1, $stats['population']);
    $popPercentages = [
        'adults' => round(($stats['population_stats']['adults'] / $total) * 100),
        'children' => round(($stats['population_stats']['children'] / $total) * 100),
        'elders' => round(($stats['population_stats']['elders'] / $total) * 100),
    ];

    // Calculate employment rate (based on working-age adults)
    $workingAge = $stats['population_stats']['adults'];
    $employed = $stats['population_stats']['employed'];
    $employmentRate = $workingAge > 0 ? round(($employed / $workingAge) * 100) : 0;

    // Calculate housing rate
    $housed = $stats['population_stats']['housed'];
    $housingRate = $total > 0 ? round(($housed / $total) * 100) : 0;

    // Get building counts by type
    $buildingCounts = [];
    foreach (\FiefdomForge\Building::TYPES as $type) {
        $count = $db->fetch(
            "SELECT COUNT(*) as count FROM buildings WHERE type = ?",
            [$type]
        );
        $buildingCounts[$type] = $count['count'] ?? 0;
    }

    // Get area statistics
    $areas = \FiefdomForge\Area::getAll();
    $areaStats = [];
    foreach ($areas as $area) {
        $pop = $db->fetch(
            "SELECT COUNT(*) as count FROM citizens c
             JOIN buildings b ON c.home_building_id = b.id
             WHERE b.area_id = ? AND c.is_alive = 1",
            [$area->getId()]
        );
        $buildings = $db->fetch(
            "SELECT COUNT(*) as count FROM buildings WHERE area_id = ?",
            [$area->getId()]
        );
        $areaStats[] = [
            'name' => $area->getName(),
            'population' => $pop['count'] ?? 0,
            'buildings' => $buildings['count'] ?? 0,
            'tax_rate' => $area->getTaxRate(),
        ];
    }

    $view = View::getInstance();
    $view->assign('stats', $stats);
    $view->assign('pop_percentages', $popPercentages);
    $view->assign('employment_rate', $employmentRate);
    $view->assign('housing_rate', $housingRate);
    $view->assign('building_counts', $buildingCounts);
    $view->assign('area_stats', $areaStats);
    $view->display('stats.tpl');
}

function handleAchievements(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $stats = $gameEngine->getDashboardStats();

    $achievement = new \FiefdomForge\Achievement($userId);
    $achievements = $achievement->getAllAchievementsWithStatus($stats);

    // Count unlocked
    $unlockedCount = count(array_filter($achievements, fn($a) => $a['unlocked']));
    $totalCount = count($achievements);
    $completionPercent = $totalCount > 0 ? round(($unlockedCount / $totalCount) * 100) : 0;

    $view = View::getInstance();
    $view->assign('achievements', $achievements);
    $view->assign('categories', \FiefdomForge\Achievement::getCategories());
    $view->assign('unlocked_count', $unlockedCount);
    $view->assign('total_count', $totalCount);
    $view->assign('completion_percent', $completionPercent);
    $view->assign('stats', $stats);
    $view->display('achievements.tpl');
}

// ============ CITIZEN MANAGEMENT ============

function handleCitizenDetail(string $id): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $citizen = \FiefdomForge\Citizen::loadById((int)$id);

    if (!$citizen || $citizen->getUserId() !== $userId) {
        Session::flash('error', 'Citizen not found.');
        header('Location: /citizens');
        exit;
    }

    $db = \FiefdomForge\Database::getInstance();
    $gameEngine = new GameEngine($userId);

    // Get available homes with space
    $availableHomes = $db->fetchAll(
        "SELECT b.*,
                (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) as occupants
         FROM buildings b
         WHERE b.type = 'house'
           AND (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) < b.capacity
         ORDER BY b.name"
    );

    // Get available jobs
    $availableJobs = $db->fetchAll(
        "SELECT bus.*,
                (SELECT COUNT(*) FROM citizens c WHERE c.work_business_id = bus.id AND c.is_alive = 1) as workers
         FROM businesses bus
         WHERE (SELECT COUNT(*) FROM citizens c WHERE c.work_business_id = bus.id AND c.is_alive = 1) < bus.employees_capacity
         ORDER BY bus.name"
    );

    // Get spouse info if married
    $spouse = null;
    if ($citizen->getSpouseId()) {
        $spouse = \FiefdomForge\Citizen::loadById($citizen->getSpouseId());
    }

    // Get home info
    $home = null;
    if ($citizen->getHomeBuildingId()) {
        $home = \FiefdomForge\Building::loadById($citizen->getHomeBuildingId());
    }

    // Get job info
    $job = null;
    if ($citizen->getWorkBusinessId()) {
        $job = \FiefdomForge\Business::loadById($citizen->getWorkBusinessId());
    }

    $view = View::getInstance();
    $view->assign('citizen', $citizen);
    $view->assign('spouse', $spouse);
    $view->assign('home', $home);
    $view->assign('job', $job);
    // Get all skills with citizen's levels
    $allSkills = \FiefdomForge\Skill::getAll();
    $citizenSkillLevels = $citizen->getSkillLevels();
    $skillsData = [];
    foreach ($allSkills as $skill) {
        $skillsData[] = [
            'id' => $skill->getId(),
            'name' => $skill->getName(),
            'description' => $skill->getDescription(),
            'type' => $skill->getType(),
            'level' => $citizenSkillLevels[$skill->getId()] ?? 0,
        ];
    }

    // Get citizen's current role
    $currentRole = null;
    if ($citizen->getRoleId()) {
        $currentRole = \FiefdomForge\Role::loadById($citizen->getRoleId());
    }

    // Get all available roles
    $allRoles = \FiefdomForge\Role::getAll();
    $rolesData = [];
    foreach ($allRoles as $role) {
        $rolesData[] = $role->toArray();
    }

    $view->assign('available_homes', $availableHomes);
    $view->assign('available_jobs', $availableJobs);
    $view->assign('skills', $skillsData);
    $view->assign('current_role', $currentRole);
    $view->assign('available_roles', $rolesData);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->display('citizen_detail.tpl');
}

function handleAssignHome(): void
{
    User::requireAuth();

    $citizenId = (int)($_POST['citizen_id'] ?? 0);
    $buildingId = (int)($_POST['building_id'] ?? 0);

    $userId = Session::get('user_id');
    $citizen = \FiefdomForge\Citizen::loadById($citizenId);

    if (!$citizen || $citizen->getUserId() !== $userId) {
        Session::flash('error', 'Citizen not found.');
        header('Location: /citizens');
        exit;
    }

    if ($buildingId === 0) {
        // Remove from home
        $citizen->setHomeBuildingId(null);
        $citizen->save();
        Session::flash('success', "{$citizen->getName()} is now homeless.");
    } else {
        $building = \FiefdomForge\Building::loadById($buildingId);
        if ($building && $building->hasSpace()) {
            $citizen->setHomeBuildingId($buildingId);
            $citizen->save();
            Session::flash('success', "{$citizen->getName()} now lives in {$building->getName()}.");
        } else {
            Session::flash('error', 'That building is full or does not exist.');
        }
    }

    header("Location: /citizen/{$citizenId}");
    exit;
}

function handleAssignJob(): void
{
    User::requireAuth();

    $citizenId = (int)($_POST['citizen_id'] ?? 0);
    $businessId = (int)($_POST['business_id'] ?? 0);

    $userId = Session::get('user_id');
    $citizen = \FiefdomForge\Citizen::loadById($citizenId);

    if (!$citizen || $citizen->getUserId() !== $userId) {
        Session::flash('error', 'Citizen not found.');
        header('Location: /citizens');
        exit;
    }

    if (!$citizen->canWork()) {
        Session::flash('error', "{$citizen->getName()} cannot work (too young or too old).");
        header("Location: /citizen/{$citizenId}");
        exit;
    }

    if ($businessId === 0) {
        // Remove from job
        $citizen->setWorkBusinessId(null);
        $citizen->save();
        Session::flash('success', "{$citizen->getName()} is now unemployed.");
    } else {
        $business = \FiefdomForge\Business::loadById($businessId);
        if ($business && $business->canHire()) {
            $citizen->setWorkBusinessId($businessId);
            $citizen->save();
            $business->updateEmployeeCount();
            $business->save();
            Session::flash('success', "{$citizen->getName()} now works at {$business->getName()}.");
        } else {
            Session::flash('error', 'That business is full or does not exist.');
        }
    }

    header("Location: /citizen/{$citizenId}");
    exit;
}

function handleAssignRole(): void
{
    User::requireAuth();

    $citizenId = (int)($_POST['citizen_id'] ?? 0);
    $roleId = (int)($_POST['role_id'] ?? 0);

    $userId = Session::get('user_id');
    $citizen = \FiefdomForge\Citizen::loadById($citizenId);

    if (!$citizen || $citizen->getUserId() !== $userId) {
        Session::flash('error', 'Citizen not found.');
        header('Location: /citizens');
        exit;
    }

    if ($roleId === 0) {
        // Remove role
        $citizen->setRoleId(null);
        $citizen->save();
        Session::flash('success', "{$citizen->getName()} no longer has a designated role.");
    } else {
        $role = \FiefdomForge\Role::loadById($roleId);
        if ($role) {
            $citizen->setRoleId($roleId);
            $citizen->save();
            Session::flash('success', "{$citizen->getName()} is now a {$role->getName()}.");
        } else {
            Session::flash('error', 'Invalid role selected.');
        }
    }

    header("Location: /citizen/{$citizenId}");
    exit;
}

function handleCreateCitizen(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $gameState = $gameEngine->getGameState();

    $name = trim($_POST['name'] ?? '');
    $age = (int)($_POST['age'] ?? 25);
    $gender = $_POST['gender'] ?? 'male';

    // Validate inputs
    if (empty($name)) {
        Session::flash('error', 'Please provide a name for the citizen.');
        header('Location: /citizens');
        exit;
    }

    if ($age < 18 || $age > 50) {
        Session::flash('error', 'Age must be between 18 and 50.');
        header('Location: /citizens');
        exit;
    }

    if (!in_array($gender, ['male', 'female'])) {
        Session::flash('error', 'Invalid gender selected.');
        header('Location: /citizens');
        exit;
    }

    // Check treasury
    $cost = 50;
    if ($gameState->getTreasury() < $cost) {
        Session::flash('error', "Not enough gold! Need {$cost} gold to recruit a citizen.");
        header('Location: /citizens');
        exit;
    }

    // Deduct cost and create citizen
    $gameState->subtractTreasury($cost);
    $gameState->save();

    $citizen = \FiefdomForge\Citizen::create($userId, $name, $age, $gender);

    // Log the event
    \FiefdomForge\GameEvent::create(
        $userId,
        'citizen_recruited',
        "{$name} has been recruited to your fiefdom.",
        $gameState->getCurrentDay(),
        $gameState->getCurrentYear(),
        $citizen->getId()
    );

    Session::flash('success', "{$name} has joined your realm!");
    header('Location: /citizens');
    exit;
}

function handleBulkRecruitCitizens(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $gameState = $gameEngine->getGameState();
    $db = Database::getInstance();

    $count = (int)($_POST['count'] ?? 0);
    $validCounts = [5, 10, 25];

    if (!in_array($count, $validCounts)) {
        Session::flash('error', 'Invalid recruitment count.');
        header('Location: /citizens');
        exit;
    }

    // Cost per citizen (discounted for bulk)
    $costPerCitizen = 40; // 40 gold each for bulk (vs 50 for single)
    $totalCost = $count * $costPerCitizen;

    if ($gameState->getTreasury() < $totalCost) {
        Session::flash('error', "Not enough gold! Need {$totalCost} gold to recruit {$count} citizens.");
        header('Location: /citizens');
        exit;
    }

    // Initialize Faker
    $faker = \Faker\Factory::create();

    // Get available houses with capacity
    $availableHomes = $db->fetchAll("
        SELECT b.id, b.capacity,
               (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) as current_residents
        FROM buildings b
        WHERE b.type = 'house'
          AND (SELECT COUNT(*) FROM citizens c WHERE c.home_building_id = b.id AND c.is_alive = 1) < b.capacity
    ");

    // Get available jobs
    $availableJobs = $db->fetchAll("
        SELECT b.id as business_id, b.employees_capacity,
               (SELECT COUNT(*) FROM citizens c WHERE c.work_business_id = b.id AND c.is_alive = 1) as current_workers
        FROM businesses b
        WHERE (SELECT COUNT(*) FROM citizens c WHERE c.work_business_id = b.id AND c.is_alive = 1) < b.employees_capacity
    ");

    // Get available roles
    $roles = $db->fetchAll("SELECT id FROM roles");

    // Deduct cost
    $gameState->subtractTreasury($totalCost);
    $gameState->save();

    $recruited = 0;
    $housed = 0;
    $employed = 0;

    // Medieval-style first names
    $medievalMaleNames = ['Aldric', 'Baldwin', 'Conrad', 'Darius', 'Edmund', 'Frederick', 'Gerald', 'Harold', 'Ivan', 'Jasper', 'Kenneth', 'Leopold', 'Marcus', 'Nathaniel', 'Oswald', 'Percival', 'Quincy', 'Roland', 'Sebastian', 'Theodore', 'Ulric', 'Victor', 'Walter', 'Xavier', 'York', 'Zachary', 'Arthur', 'Bertram', 'Cedric', 'Duncan', 'Edgar', 'Felix', 'Godfrey', 'Hugh', 'Ignatius', 'Julian'];
    $medievalFemaleNames = ['Adelaide', 'Beatrice', 'Catherine', 'Dorothy', 'Eleanor', 'Florence', 'Genevieve', 'Helena', 'Isabelle', 'Juliana', 'Katherine', 'Lillian', 'Margaret', 'Nicolette', 'Ophelia', 'Prudence', 'Quinn', 'Rosalind', 'Sophia', 'Tabitha', 'Ursula', 'Victoria', 'Winifred', 'Yolanda', 'Zelda', 'Agnes', 'Blanche', 'Cecilia', 'Diana', 'Edith', 'Felicity', 'Gwendolyn', 'Harriet', 'Iris', 'Joan'];
    $medievalSurnames = ['Smith', 'Miller', 'Baker', 'Cooper', 'Fletcher', 'Thatcher', 'Mason', 'Carpenter', 'Weaver', 'Potter', 'Tanner', 'Shepherd', 'Hunter', 'Fisher', 'Farmer', 'Brewer', 'Cook', 'Taylor', 'Carter', 'Wright', 'Walker', 'Turner', 'Hill', 'Wood', 'Green', 'Stone', 'Brook', 'Field', 'Ford', 'Wells'];

    for ($i = 0; $i < $count; $i++) {
        // Random gender
        $gender = $faker->randomElement(['male', 'female']);

        // Random medieval name
        if ($gender === 'male') {
            $firstName = $faker->randomElement($medievalMaleNames);
        } else {
            $firstName = $faker->randomElement($medievalFemaleNames);
        }
        $lastName = $faker->randomElement($medievalSurnames);
        $name = "{$firstName} {$lastName}";

        // Random age between 18 and 45
        $age = $faker->numberBetween(18, 45);

        // Create citizen with random stats
        $health = $faker->numberBetween(60, 100);
        $happiness = $faker->numberBetween(50, 90);
        $wealth = $faker->numberBetween(10, 100);

        $citizenId = $db->insert('citizens', [
            'user_id' => $userId,
            'name' => $name,
            'age' => $age,
            'gender' => $gender,
            'health' => $health,
            'happiness' => $happiness,
            'wealth' => $wealth,
            'is_alive' => 1
        ]);

        $recruited++;

        // Try to assign a home
        if (!empty($availableHomes)) {
            foreach ($availableHomes as $key => $home) {
                if ($home['current_residents'] < $home['capacity']) {
                    $db->update('citizens', ['home_building_id' => $home['id']], 'id = ?', [$citizenId]);
                    $availableHomes[$key]['current_residents']++;
                    $housed++;
                    break;
                }
            }
        }

        // Try to assign a job (only for adults 18+)
        if ($age >= 18 && !empty($availableJobs)) {
            foreach ($availableJobs as $key => $job) {
                if ($job['current_workers'] < $job['employees_capacity']) {
                    // Assign random role if available
                    $roleId = !empty($roles) ? $faker->randomElement($roles)['id'] : null;

                    $db->update('citizens', [
                        'work_business_id' => $job['business_id'],
                        'role_id' => $roleId
                    ], 'id = ?', [$citizenId]);

                    // Update business current_employees
                    $db->query("UPDATE businesses SET current_employees = current_employees + 1 WHERE id = ?", [$job['business_id']]);

                    $availableJobs[$key]['current_workers']++;
                    $employed++;
                    break;
                }
            }
        }
    }

    // Log the event
    \FiefdomForge\GameEvent::create(
        $userId,
        'bulk_recruitment',
        "{$recruited} new citizens have been recruited to your fiefdom. {$housed} housed, {$employed} employed.",
        $gameState->getCurrentDay(),
        $gameState->getCurrentYear()
    );

    Session::flash('success', "Recruited {$recruited} citizens! ({$housed} housed, {$employed} employed) Cost: {$totalCost} gold.");
    header('Location: /citizens');
    exit;
}

// ============ BUILDING MANAGEMENT ============

function handleNewBuilding(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    // Get templates with unlock status
    $templatesWithStatus = \FiefdomForge\Building::getTemplatesWithUnlockStatus($userId);

    $view = View::getInstance();
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->assign('areas', \FiefdomForge\Area::getAll());
    $view->assign('templates', $templatesWithStatus);
    $view->display('building_new.tpl');
}

function handleCreateBuilding(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);
    $gameState = $gameEngine->getGameState();

    $name = trim($_POST['name'] ?? '');
    $template = $_POST['template'] ?? '';
    $areaId = (int)($_POST['area_id'] ?? 0);

    if (empty($name) || empty($template) || $areaId === 0) {
        Session::flash('error', 'Please fill in all fields.');
        header('Location: /buildings/new');
        exit;
    }

    if (!isset(\FiefdomForge\Building::TEMPLATES[$template])) {
        Session::flash('error', 'Invalid building type.');
        header('Location: /buildings/new');
        exit;
    }

    // Check if building is unlocked
    if (!\FiefdomForge\Building::isTemplateUnlocked($template, $userId)) {
        $requiredAchievement = \FiefdomForge\Building::TEMPLATES[$template]['unlock'];
        $achievementName = \FiefdomForge\Achievement::ACHIEVEMENTS[$requiredAchievement]['name'] ?? $requiredAchievement;
        Session::flash('error', "This building is locked! Unlock it by earning the '{$achievementName}' achievement.");
        header('Location: /buildings/new');
        exit;
    }

    $cost = \FiefdomForge\Building::TEMPLATES[$template]['cost'];

    if ($gameState->getTreasury() < $cost) {
        Session::flash('error', "Not enough gold! Need {$cost} gold to build.");
        header('Location: /buildings/new');
        exit;
    }

    // Deduct cost and create building
    $gameState->subtractTreasury($cost);
    $gameState->save();

    $building = \FiefdomForge\Building::createFromTemplate($template, $name, $areaId);

    if ($building) {
        Session::flash('success', "{$name} has been constructed for {$cost} gold!");
    } else {
        Session::flash('error', 'Failed to create building.');
    }

    header('Location: /buildings');
    exit;
}

function handleBuildingDetail(string $id): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $building = \FiefdomForge\Building::loadById((int)$id);

    if (!$building) {
        Session::flash('error', 'Building not found.');
        header('Location: /buildings');
        exit;
    }

    $gameEngine = new GameEngine($userId);
    $db = \FiefdomForge\Database::getInstance();

    // Get residents or workers
    $occupants = [];
    if ($building->getType() === 'house') {
        $occupants = $db->fetchAll(
            "SELECT * FROM citizens WHERE home_building_id = ? AND is_alive = 1",
            [$building->getId()]
        );
    }

    // Get associated business if any
    $business = $db->fetch(
        "SELECT * FROM businesses WHERE building_id = ?",
        [$building->getId()]
    );

    $area = \FiefdomForge\Area::loadById($building->getAreaId());

    // Get current owner
    $owner = null;
    if ($building->getOwnerCitizenId()) {
        $owner = \FiefdomForge\Citizen::loadById($building->getOwnerCitizenId());
    }

    // Get citizens available for ownership transfer (adults with wealth)
    $availableOwners = $db->fetchAll(
        "SELECT id, name, wealth FROM citizens
         WHERE user_id = ? AND is_alive = 1 AND age >= ?
         ORDER BY wealth DESC, name",
        [$userId, \FiefdomForge\Citizen::AGE_ADULT]
    );

    $view = View::getInstance();
    // Calculate repair cost for display
    $repairCost = max(10, (int)($building->getUpkeepCost() * 2));

    $view->assign('building', $building);
    $view->assign('area', $area);
    $view->assign('occupants', $occupants);
    $view->assign('business', $business);
    $view->assign('owner', $owner);
    $view->assign('available_owners', $availableOwners);
    $view->assign('repair_cost', $repairCost);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->display('building_detail.tpl');
}

function handleRepairBuilding(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $buildingId = (int)($_POST['building_id'] ?? 0);

    if ($buildingId === 0) {
        Session::flash('error', 'Invalid building.');
        header('Location: /buildings');
        exit;
    }

    $building = \FiefdomForge\Building::loadById($buildingId);
    if (!$building) {
        Session::flash('error', 'Building not found.');
        header('Location: /buildings');
        exit;
    }

    // Calculate repair cost (10 gold per 10% repair, scaled by building upkeep)
    $repairAmount = 10;
    $repairCost = max(10, (int)($building->getUpkeepCost() * 2));

    // Can't repair if already at 100%
    if ($building->getCondition() >= 100) {
        Session::flash('info', 'Building is already in perfect condition.');
        header('Location: /building/' . $buildingId);
        exit;
    }

    $gameEngine = new GameEngine($userId);
    $gameState = $gameEngine->getGameState();

    if ($gameState->getTreasury() < $repairCost) {
        Session::flash('error', "Not enough gold! Need {$repairCost} gold to repair.");
        header('Location: /building/' . $buildingId);
        exit;
    }

    // Deduct cost and repair
    $gameState->subtractTreasury($repairCost);
    $gameState->save();

    $building->repair($repairAmount);
    $building->save();

    $newCondition = $building->getCondition();
    Session::flash('success', "Building repaired to {$newCondition}% condition for {$repairCost} gold.");
    header('Location: /building/' . $buildingId);
    exit;
}

function handleTransferOwnership(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $buildingId = (int)($_POST['building_id'] ?? 0);
    $citizenId = (int)($_POST['citizen_id'] ?? 0);

    if ($buildingId === 0) {
        Session::flash('error', 'Invalid building.');
        header('Location: /buildings');
        exit;
    }

    $building = \FiefdomForge\Building::loadById($buildingId);
    if (!$building) {
        Session::flash('error', 'Building not found.');
        header('Location: /buildings');
        exit;
    }

    if ($citizenId === 0) {
        // Remove ownership
        $building->setOwnerCitizenId(null);
        $building->save();
        Session::flash('success', "{$building->getName()} is now unowned.");
    } else {
        $citizen = \FiefdomForge\Citizen::loadById($citizenId);
        if (!$citizen || $citizen->getUserId() !== $userId) {
            Session::flash('error', 'Invalid citizen selected.');
            header('Location: /building/' . $buildingId);
            exit;
        }

        $building->setOwnerCitizenId($citizenId);
        $building->save();
        Session::flash('success', "{$citizen->getName()} now owns {$building->getName()}.");
    }

    header('Location: /building/' . $buildingId);
    exit;
}

// ============ BUSINESS MANAGEMENT ============

function handleCreateBusiness(): void
{
    User::requireAuth();

    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $buildingId = (int)($_POST['building_id'] ?? 0);

    if (empty($name) || empty($type) || $buildingId === 0) {
        Session::flash('error', 'Please fill in all fields.');
        header('Location: /economy');
        exit;
    }

    // Check building exists and doesn't have a business
    $db = \FiefdomForge\Database::getInstance();
    $existing = $db->fetch("SELECT id FROM businesses WHERE building_id = ?", [$buildingId]);

    if ($existing) {
        Session::flash('error', 'This building already has a business.');
        header('Location: /economy');
        exit;
    }

    $business = \FiefdomForge\Business::create($name, $buildingId, $type);

    if ($business) {
        Session::flash('success', "{$name} business has been established!");
    } else {
        Session::flash('error', 'Failed to create business. Invalid type?');
    }

    header('Location: /economy');
    exit;
}

function handleBusinessDetail(string $id): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $business = \FiefdomForge\Business::loadById((int)$id);

    if (!$business) {
        Session::flash('error', 'Business not found.');
        header('Location: /economy');
        exit;
    }

    $gameEngine = new GameEngine($userId);

    $building = \FiefdomForge\Building::loadById($business->getBuildingId());
    $employees = $business->getEmployees();

    // Get products info
    $products = [];
    foreach ($business->getProducts() as $goodId) {
        $good = \FiefdomForge\Good::loadById($goodId);
        if ($good) {
            $products[] = $good;
        }
    }

    $view = View::getInstance();
    $view->assign('business', $business);
    $view->assign('building', $building);
    $view->assign('employees', $employees);
    $view->assign('products', $products);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->display('business_detail.tpl');
}

// ============ AREA MANAGEMENT ============

function handleAreas(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $gameEngine = new GameEngine($userId);

    $db = \FiefdomForge\Database::getInstance();
    $areas = \FiefdomForge\Area::getAll();

    // Get population per area and calculate totals
    $areaStats = [];
    $totalPopulation = 0;
    $totalBuildings = 0;
    $totalCapacity = 0;

    foreach ($areas as $area) {
        $pop = $db->fetch(
            "SELECT COUNT(*) as count FROM citizens c
             JOIN buildings b ON c.home_building_id = b.id
             WHERE b.area_id = ? AND c.is_alive = 1",
            [$area->getId()]
        );
        $buildings = $db->fetch(
            "SELECT COUNT(*) as count FROM buildings WHERE area_id = ?",
            [$area->getId()]
        );

        $popCount = $pop['count'] ?? 0;
        $buildingCount = $buildings['count'] ?? 0;

        $areaStats[$area->getId()] = [
            'population' => $popCount,
            'buildings' => $buildingCount,
        ];

        $totalPopulation += $popCount;
        $totalBuildings += $buildingCount;
        $totalCapacity += $area->getCapacity();
    }

    $view = View::getInstance();
    $view->assign('areas', $areas);
    $view->assign('area_stats', $areaStats);
    $view->assign('total_population', $totalPopulation);
    $view->assign('total_buildings', $totalBuildings);
    $view->assign('total_capacity', $totalCapacity);
    $view->assign('stats', $gameEngine->getDashboardStats());
    $view->display('areas.tpl');
}

function handleCreateArea(): void
{
    User::requireAuth();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 100);

    if (empty($name)) {
        Session::flash('error', 'Area name is required.');
        header('Location: /areas');
        exit;
    }

    \FiefdomForge\Area::create($name, $description, $capacity);
    Session::flash('success', "Area '{$name}' has been created!");

    header('Location: /areas');
    exit;
}

function handleUpdateTaxRate(): void
{
    User::requireAuth();

    $areaId = (int)($_POST['area_id'] ?? 0);
    $taxRate = (float)($_POST['tax_rate'] ?? 0.05);

    $area = \FiefdomForge\Area::loadById($areaId);

    if (!$area) {
        Session::flash('error', 'Area not found.');
        header('Location: /areas');
        exit;
    }

    $taxRate = max(0, min(0.5, $taxRate)); // 0-50%
    $area->setTaxRate($taxRate);
    $area->save();

    $percent = $taxRate * 100;
    Session::flash('success', "Tax rate for {$area->getName()} set to {$percent}%.");

    header('Location: /areas');
    exit;
}

// ============ AUTH ============

function handleLogout(): void
{
    User::logout();
    Session::flash('success', 'You have been logged out. Until next time!');
    header('Location: /login');
    exit;
}

function handleApiChartData(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $historicalStats = new \FiefdomForge\HistoricalStats($userId);

    $chartData = $historicalStats->getChartData(50);

    header('Content-Type: application/json');
    echo json_encode($chartData);
    exit;
}

function handleApiNotifications(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $db = Database::getInstance();

    // Ensure notifications table exists
    $db->ensureNotificationsTable();

    // Create notifications for any new events that don't have notification records
    $db->query("
        INSERT OR IGNORE INTO notifications (user_id, event_id, is_read)
        SELECT user_id, id, 0 FROM game_events WHERE user_id = ?
    ", [$userId]);

    // Get recent notifications with event data
    $notifications = $db->fetchAll("
        SELECT n.id, n.is_read, e.event_type, e.message, e.game_day, e.game_year, e.created_at
        FROM notifications n
        JOIN game_events e ON n.event_id = e.id
        WHERE n.user_id = ?
        ORDER BY e.created_at DESC
        LIMIT 20
    ", [$userId]);

    // Get unread count
    $unreadCount = $db->fetch("
        SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0
    ", [$userId])['count'] ?? 0;

    header('Content-Type: application/json');
    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    exit;
}

function handleApiNotificationRead(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $db = Database::getInstance();

    // Get JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $notificationId = $input['id'] ?? null;

    if ($notificationId) {
        $db->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

function handleApiNotificationReadAll(): void
{
    User::requireAuth();

    $userId = Session::get('user_id');
    $db = Database::getInstance();

    $db->update('notifications', ['is_read' => 1], 'user_id = ?', [$userId]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

function serveStaticAsset(string $path): void
{
    $basePath = dirname(__DIR__) . '/assets/';
    $fullPath = realpath($basePath . $path);

    // Security: ensure the path is within the assets directory
    if ($fullPath === false || strpos($fullPath, realpath($basePath)) !== 0) {
        http_response_code(404);
        return;
    }

    if (!file_exists($fullPath)) {
        http_response_code(404);
        return;
    }

    // Determine content type
    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
    $contentTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    $contentType = $contentTypes[$extension] ?? 'application/octet-stream';
    header('Content-Type: ' . $contentType);
    readfile($fullPath);
}

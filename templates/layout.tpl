<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{block name="title"}Fiefdom Forge{/block}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    {block name="head"}{/block}
</head>
<body class="{if $is_logged_in}logged-in{else}logged-out{/if}">
    <!-- Header -->
    <header class="site-header">
        <div class="header-inner">
            <a href="/" class="logo">
                <span class="logo-icon">&#9876;</span>
                <span class="logo-text">Fiefdom Forge</span>
            </a>

            <div class="header-right">
                {if $is_logged_in}
                    <div class="game-time">
                        <span class="time-label">Year</span>
                        <span class="time-value">{$stats.current_year|default:1}</span>
                        <span class="time-separator">|</span>
                        <span class="time-label">Day</span>
                        <span class="time-value">{$stats.current_day|default:1}</span>
                    </div>
                    <div class="treasury-display">
                        <span class="treasury-icon">&#9733;</span>
                        <span class="treasury-value">{$stats.treasury|default:0}</span>
                        <span class="treasury-label">Gold</span>
                    </div>
                    <div class="notification-bell" id="notification-bell">
                        <span class="bell-icon">&#128276;</span>
                        <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                        <div class="notification-dropdown" id="notification-dropdown">
                            <div class="notification-header">
                                <span>Notifications</span>
                                <button class="mark-all-read" id="mark-all-read">Mark all read</button>
                            </div>
                            <div class="notification-list" id="notification-list">
                                <div class="notification-empty">No notifications</div>
                            </div>
                            <a href="/events" class="notification-footer">View all events</a>
                        </div>
                    </div>
                    <div class="user-menu">
                        <span class="user-name">{$current_user.username}</span>
                        <a href="/logout" class="logout-btn">Logout</a>
                    </div>
                {else}
                    <nav class="auth-nav">
                        <a href="/login" class="btn btn-primary">Login</a>
                        <a href="/register" class="btn btn-outline">Register</a>
                    </nav>
                {/if}
            </div>

            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <div class="app-container">
        {if $is_logged_in}
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <h3 class="nav-heading">Overview</h3>
                    <a href="/dashboard" class="nav-link {if $smarty.server.REQUEST_URI == '/dashboard'}active{/if}">
                        <span class="nav-icon">&#127968;</span>
                        Dashboard
                    </a>
                    <a href="/map" class="nav-link {if $smarty.server.REQUEST_URI == '/map'}active{/if}">
                        <span class="nav-icon">&#128506;</span>
                        City Map
                    </a>
                    <a href="/stats" class="nav-link {if $smarty.server.REQUEST_URI == '/stats'}active{/if}">
                        <span class="nav-icon">&#128200;</span>
                        Statistics
                    </a>
                </div>

                <div class="nav-section">
                    <h3 class="nav-heading">Management</h3>
                    <a href="/citizens" class="nav-link {if $smarty.server.REQUEST_URI == '/citizens'}active{/if}">
                        <span class="nav-icon">&#128101;</span>
                        Citizens
                    </a>
                    <a href="/buildings" class="nav-link {if $smarty.server.REQUEST_URI == '/buildings'}active{/if}">
                        <span class="nav-icon">&#127984;</span>
                        Buildings
                    </a>
                    <a href="/areas" class="nav-link {if $smarty.server.REQUEST_URI == '/areas'}active{/if}">
                        <span class="nav-icon">&#127759;</span>
                        Areas
                    </a>
                </div>

                <div class="nav-section">
                    <h3 class="nav-heading">Economy</h3>
                    <a href="/economy" class="nav-link {if $smarty.server.REQUEST_URI == '/economy'}active{/if}">
                        <span class="nav-icon">&#128176;</span>
                        Market & Trade
                    </a>
                    <a href="/reports" class="nav-link {if $smarty.server.REQUEST_URI == '/reports'}active{/if}">
                        <span class="nav-icon">&#128202;</span>
                        Reports
                    </a>
                    <a href="/transactions" class="nav-link {if $smarty.server.REQUEST_URI == '/transactions'}active{/if}">
                        <span class="nav-icon">&#128203;</span>
                        Transactions
                    </a>
                </div>

                <div class="nav-section">
                    <h3 class="nav-heading">Progress</h3>
                    <a href="/achievements" class="nav-link {if $smarty.server.REQUEST_URI == '/achievements'}active{/if}">
                        <span class="nav-icon">&#127942;</span>
                        Achievements
                    </a>
                    <a href="/events" class="nav-link {if $smarty.server.REQUEST_URI == '/events'}active{/if}">
                        <span class="nav-icon">&#128220;</span>
                        Chronicle
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="quick-stats">
                    <div class="quick-stat">
                        <span class="qs-value">{$stats.population|default:0}</span>
                        <span class="qs-label">Population</span>
                    </div>
                    <div class="quick-stat">
                        <span class="qs-value">{$stats.buildings|default:0}</span>
                        <span class="qs-label">Buildings</span>
                    </div>
                </div>
            </div>
        </aside>
        {/if}

        <!-- Main Content -->
        <main class="main-content">
            {block name="hero"}{/block}

            <div class="content-wrapper">
                {if $flash_success}
                    <div class="alert alert-success">
                        <span class="alert-icon">&#10003;</span>
                        {$flash_success}
                    </div>
                {/if}
                {if $flash_error}
                    <div class="alert alert-error">
                        <span class="alert-icon">&#10007;</span>
                        {$flash_error}
                    </div>
                {/if}

                {block name="content"}{/block}
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <span class="footer-logo">&#9876;</span>
                <span>Fiefdom Forge</span>
            </div>
            <p class="footer-tagline">A Medieval City Simulation</p>
            <div class="footer-links">
                <span>Build your realm. Shape history.</span>
            </div>
            <p class="footer-copyright">&copy; {$smarty.now|date_format:"%Y"} Fiefdom Forge</p>
        </div>
    </footer>

    <script>
        {literal}
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar')?.classList.toggle('open');
            this.classList.toggle('active');
        });
        {/literal}

        // Notification system
        {if $is_logged_in}
        {literal}
        (function() {
            const bell = document.getElementById('notification-bell');
            const dropdown = document.getElementById('notification-dropdown');
            const badge = document.getElementById('notification-badge');
            const list = document.getElementById('notification-list');
            const markAllBtn = document.getElementById('mark-all-read');

            // Toggle dropdown
            bell?.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('open');
                if (dropdown.classList.contains('open')) {
                    loadNotifications();
                }
            });

            // Close on outside click
            document.addEventListener('click', function(e) {
                if (!bell?.contains(e.target)) {
                    dropdown?.classList.remove('open');
                }
            });

            // Load notifications
            async function loadNotifications() {
                try {
                    const response = await fetch('/api/notifications');
                    const data = await response.json();
                    renderNotifications(data.notifications || []);
                    updateBadge(data.unread_count || 0);
                } catch (error) {
                    console.error('Failed to load notifications:', error);
                }
            }

            // Render notification items
            function renderNotifications(notifications) {
                if (!notifications.length) {
                    list.innerHTML = '<div class="notification-empty">No notifications</div>';
                    return;
                }

                list.innerHTML = notifications.map(n => `
                    <div class="notification-item ${n.is_read ? 'read' : 'unread'}" data-id="${n.id}">
                        <span class="notification-icon">${getNotificationIcon(n.event_type)}</span>
                        <div class="notification-content">
                            <div class="notification-message">${escapeHtml(n.message)}</div>
                            <div class="notification-time">Year ${n.game_year}, Day ${n.game_day}</div>
                        </div>
                        ${!n.is_read ? '<button class="notification-dismiss" onclick="markAsRead(' + n.id + ')">×</button>' : ''}
                    </div>
                `).join('');
            }

            // Get icon for notification type
            function getNotificationIcon(type) {
                const icons = {
                    'birth': '&#128118;',
                    'death': '&#9760;',
                    'marriage': '&#128141;',
                    'production': '&#9881;',
                    'tax': '&#128176;',
                    'construction': '&#127975;',
                    'economy': '&#128200;',
                    'default': '&#128276;'
                };
                return icons[type] || icons.default;
            }

            // Escape HTML to prevent XSS
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Update badge count
            function updateBadge(count) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }

            // Mark single notification as read
            window.markAsRead = async function(id) {
                try {
                    await fetch('/api/notifications/read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    loadNotifications();
                } catch (error) {
                    console.error('Failed to mark as read:', error);
                }
            };

            // Mark all as read
            markAllBtn?.addEventListener('click', async function(e) {
                e.stopPropagation();
                try {
                    await fetch('/api/notifications/read-all', { method: 'POST' });
                    loadNotifications();
                } catch (error) {
                    console.error('Failed to mark all as read:', error);
                }
            });

            // Initial load and periodic refresh
            loadNotifications();
            setInterval(loadNotifications, 60000); // Refresh every minute
        })();
        {/literal}
        {/if}
    </script>
    {block name="scripts"}{/block}
</body>
</html>

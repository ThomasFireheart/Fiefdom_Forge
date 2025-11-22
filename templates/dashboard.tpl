{extends file="layout.tpl"}

{block name="title"}Dashboard - Fiefdom Forge{/block}

{block name="content"}
<div class="dashboard">
    <div class="dashboard-header">
        <h1>Welcome to Your Fiefdom, {$current_user.username}</h1>
        <div class="time-display">
            <span class="season">{$stats.season}</span>
            <span class="date">Year {$stats.current_year}, Day {$stats.current_day}</span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Population
                <span class="help-tip">
                    <span class="help-icon">?</span>
                    <span class="tooltip-content">Your realm's inhabitants. Citizens work, pay taxes, and contribute to your economy. Keep them happy and healthy!</span>
                </span>
            </h3>
            <div class="stat-value">{$stats.population|default:0}</div>
            <div class="stat-label">Citizens</div>
        </div>

        <div class="stat-card">
            <h3>Treasury
                <span class="help-tip">
                    <span class="help-icon">?</span>
                    <span class="tooltip-content">Gold collected from taxes and trade. Used to construct buildings and recruit citizens. In medieval times, a lord's wealth determined their power.</span>
                </span>
            </h3>
            <div class="stat-value">{$stats.treasury|default:0}</div>
            <div class="stat-label">Gold Coins</div>
        </div>

        <div class="stat-card">
            <h3>Buildings</h3>
            <div class="stat-value">{$stats.buildings|default:0}</div>
            <div class="stat-label">Structures</div>
        </div>

        <div class="stat-card">
            <h3>Businesses</h3>
            <div class="stat-value">{$stats.economy_stats.businesses_count|default:0}</div>
            <div class="stat-label">Active</div>
        </div>
    </div>

    {if $stats.population_stats}
    <div class="section-card">
        <h2>Population Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Adults (Working Age)</td>
                    <td>{$stats.population_stats.adults}</td>
                    <td>
                        {if $stats.population_stats.adults > 0}
                            <span class="status-good">Workforce Available</span>
                        {else}
                            <span class="status-bad">No Workers!</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Children</td>
                    <td>{$stats.population_stats.children}</td>
                    <td><span class="status-ok">Future Workers</span></td>
                </tr>
                <tr>
                    <td>Elders</td>
                    <td>{$stats.population_stats.elders}</td>
                    <td><span class="status-ok">Retired</span></td>
                </tr>
                <tr>
                    <td>Employed</td>
                    <td>{$stats.population_stats.employed}/{$stats.population_stats.adults}</td>
                    <td>
                        {if $stats.population_stats.adults > 0}
                            {assign var="emp_rate" value=$stats.population_stats.employed / $stats.population_stats.adults * 100}
                            {if $emp_rate >= 80}
                                <span class="status-good">Full Employment</span>
                            {elseif $emp_rate >= 50}
                                <span class="status-warning">Partial Employment</span>
                            {else}
                                <span class="status-bad">High Unemployment</span>
                            {/if}
                        {else}
                            -
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Housed</td>
                    <td>{$stats.population_stats.housed}/{$stats.population}</td>
                    <td>
                        {if $stats.population_stats.housed == $stats.population}
                            <span class="status-good">All Housed</span>
                        {elseif $stats.population_stats.housed > $stats.population / 2}
                            <span class="status-warning">Some Homeless</span>
                        {else}
                            <span class="status-bad">Housing Crisis</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Married</td>
                    <td>{$stats.population_stats.married}</td>
                    <td><span class="status-ok">In Families</span></td>
                </tr>
            </tbody>
        </table>

        <h3>Population Growth</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Eligible Mothers</td>
                    <td>{$stats.population_stats.eligible_mothers|default:0}</td>
                </tr>
                <tr>
                    <td>Birth Rate Modifier</td>
                    <td>
                        {if $stats.population_stats.birth_rate_modifier > 0}
                            <span class="status-good">+{$stats.population_stats.birth_rate_modifier}%</span>
                        {elseif $stats.population_stats.birth_rate_modifier < 0}
                            <span class="status-bad">{$stats.population_stats.birth_rate_modifier}%</span>
                        {else}
                            <span class="status-ok">Normal</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Growth Potential</td>
                    <td>
                        {if $stats.population_stats.growth_potential == 'high'}
                            <span class="status-good">High Growth</span>
                        {elseif $stats.population_stats.growth_potential == 'stable'}
                            <span class="status-ok">Stable</span>
                        {elseif $stats.population_stats.growth_potential == 'low'}
                            <span class="status-warning">Low Growth</span>
                        {else}
                            <span class="status-bad">No Growth (No Eligible Mothers)</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        <h3>Citizen Wellbeing</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Average Health</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {$stats.population_stats.avg_health}%"></div>
                        </div>
                        {$stats.population_stats.avg_health}%
                    </td>
                    <td>
                        {if $stats.population_stats.avg_health >= 70}
                            <span class="status-good">Healthy</span>
                        {elseif $stats.population_stats.avg_health >= 50}
                            <span class="status-ok">Fair</span>
                        {else}
                            <span class="status-bad">Poor</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Average Happiness</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {$stats.population_stats.avg_happiness}%"></div>
                        </div>
                        {$stats.population_stats.avg_happiness}%
                    </td>
                    <td>
                        {if $stats.population_stats.avg_happiness >= 70}
                            <span class="status-good">Content</span>
                        {elseif $stats.population_stats.avg_happiness >= 50}
                            <span class="status-ok">Fair</span>
                        {else}
                            <span class="status-bad">Unhappy</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    {/if}

    {if $stats.citizen_needs}
    <div class="section-card">
        <h2>Citizen Needs & Resources</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Need</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Food Supply</strong></td>
                    <td>
                        {if $stats.citizen_needs.food_status == 'good'}
                            <span class="status-good">Good</span>
                        {elseif $stats.citizen_needs.food_status == 'warning'}
                            <span class="status-warning">Low</span>
                        {else}
                            <span class="status-bad">Critical!</span>
                        {/if}
                    </td>
                    <td>
                        {$stats.citizen_needs.food_days_supply} days supply
                        ({$stats.citizen_needs.food_bread} bread, {$stats.citizen_needs.food_wheat} wheat)
                    </td>
                </tr>
                <tr>
                    <td><strong>Housing</strong></td>
                    <td>
                        {if $stats.citizen_needs.housing_status == 'good'}
                            <span class="status-good">All Housed</span>
                        {elseif $stats.citizen_needs.housing_status == 'warning'}
                            <span class="status-warning">Some Homeless</span>
                        {else}
                            <span class="status-bad">Housing Crisis!</span>
                        {/if}
                    </td>
                    <td>
                        {if $stats.citizen_needs.homeless_count > 0}
                            {$stats.citizen_needs.homeless_count} citizens without homes
                        {else}
                            All citizens housed
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td><strong>Health</strong></td>
                    <td>
                        {if $stats.citizen_needs.sick_count == 0}
                            <span class="status-good">Healthy</span>
                        {elseif $stats.citizen_needs.sick_count < 3}
                            <span class="status-warning">Some Illness</span>
                        {else}
                            <span class="status-bad">Health Crisis!</span>
                        {/if}
                    </td>
                    <td>
                        {if $stats.citizen_needs.sick_count > 0}
                            {$stats.citizen_needs.sick_count} citizens in poor health
                        {else}
                            All citizens healthy
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td><strong>Morale</strong></td>
                    <td>
                        {if $stats.citizen_needs.unhappy_count == 0}
                            <span class="status-good">High</span>
                        {elseif $stats.citizen_needs.unhappy_count < 3}
                            <span class="status-warning">Some Discontent</span>
                        {else}
                            <span class="status-bad">Low Morale!</span>
                        {/if}
                    </td>
                    <td>
                        {if $stats.citizen_needs.unhappy_count > 0}
                            {$stats.citizen_needs.unhappy_count} unhappy citizens
                        {else}
                            All citizens content
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        <h3>Seasonal Effects ({$stats.season})</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Effect</th>
                    <th>Modifier</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Farm Production</td>
                    <td>
                        {if $stats.citizen_needs.season_farm_modifier >= 100}
                            <span class="status-good">{$stats.citizen_needs.season_farm_modifier}%</span>
                        {elseif $stats.citizen_needs.season_farm_modifier >= 70}
                            <span class="status-warning">{$stats.citizen_needs.season_farm_modifier}%</span>
                        {else}
                            <span class="status-bad">{$stats.citizen_needs.season_farm_modifier}%</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Other Production</td>
                    <td>
                        {if $stats.citizen_needs.season_default_modifier >= 100}
                            <span class="status-good">{$stats.citizen_needs.season_default_modifier}%</span>
                        {elseif $stats.citizen_needs.season_default_modifier >= 80}
                            <span class="status-ok">{$stats.citizen_needs.season_default_modifier}%</span>
                        {else}
                            <span class="status-warning">{$stats.citizen_needs.season_default_modifier}%</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Food Consumption</td>
                    <td>
                        {if $stats.season == 'Winter'}
                            <span class="status-warning">150%</span> (cold weather)
                        {else}
                            <span class="status-ok">100%</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    {/if}

    <!-- Visual Charts Section -->
    <div class="section-card">
        <h2>Kingdom Statistics</h2>

        <!-- Population Distribution Chart -->
        <div class="chart-container">
            <h3 class="chart-title">Population Distribution</h3>
            <div class="bar-chart">
                {assign var="max_pop" value=1}
                {if $stats.population_stats.adults > $max_pop}{assign var="max_pop" value=$stats.population_stats.adults}{/if}
                {if $stats.population_stats.children > $max_pop}{assign var="max_pop" value=$stats.population_stats.children}{/if}
                {if $stats.population_stats.elders > $max_pop}{assign var="max_pop" value=$stats.population_stats.elders}{/if}
                <div class="bar-row">
                    <span class="bar-label">Adults</span>
                    <div class="bar-track">
                        <div class="bar-fill burgundy" style="width: {($stats.population_stats.adults / $max_pop * 100)|string_format:'%.0f'}%">
                            {$stats.population_stats.adults}
                        </div>
                    </div>
                    <span class="bar-value">{$stats.population_stats.adults}</span>
                </div>
                <div class="bar-row">
                    <span class="bar-label">Children</span>
                    <div class="bar-track">
                        <div class="bar-fill info" style="width: {($stats.population_stats.children / $max_pop * 100)|string_format:'%.0f'}%">
                            {$stats.population_stats.children}
                        </div>
                    </div>
                    <span class="bar-value">{$stats.population_stats.children}</span>
                </div>
                <div class="bar-row">
                    <span class="bar-label">Elders</span>
                    <div class="bar-track">
                        <div class="bar-fill brown" style="width: {($stats.population_stats.elders / $max_pop * 100)|string_format:'%.0f'}%">
                            {$stats.population_stats.elders}
                        </div>
                    </div>
                    <span class="bar-value">{$stats.population_stats.elders}</span>
                </div>
            </div>
        </div>

        <!-- Employment & Housing Chart -->
        <div class="chart-container">
            <h3 class="chart-title">Employment & Housing</h3>
            <div class="bar-chart">
                <div class="bar-row">
                    <span class="bar-label">Employed</span>
                    <div class="bar-track">
                        {assign var="emp_pct" value=0}
                        {if $stats.population_stats.adults > 0}
                            {assign var="emp_pct" value=$stats.population_stats.employed / $stats.population_stats.adults * 100}
                        {/if}
                        <div class="bar-fill success" style="width: {$emp_pct|string_format:'%.0f'}%"></div>
                    </div>
                    <span class="bar-value">{$emp_pct|string_format:'%.0f'}%</span>
                </div>
                <div class="bar-row">
                    <span class="bar-label">Housed</span>
                    <div class="bar-track">
                        {assign var="house_pct" value=0}
                        {if $stats.population > 0}
                            {assign var="house_pct" value=$stats.population_stats.housed / $stats.population * 100}
                        {/if}
                        <div class="bar-fill forest" style="width: {$house_pct|string_format:'%.0f'}%"></div>
                    </div>
                    <span class="bar-value">{$house_pct|string_format:'%.0f'}%</span>
                </div>
                <div class="bar-row">
                    <span class="bar-label">Married</span>
                    <div class="bar-track">
                        {assign var="marry_pct" value=0}
                        {if $stats.population > 0}
                            {assign var="marry_pct" value=$stats.population_stats.married / $stats.population * 100}
                        {/if}
                        <div class="bar-fill gold" style="width: {$marry_pct|string_format:'%.0f'}%"></div>
                    </div>
                    <span class="bar-value">{$marry_pct|string_format:'%.0f'}%</span>
                </div>
            </div>
        </div>

        <!-- Health & Happiness Chart -->
        <div class="chart-container">
            <h3 class="chart-title">Citizen Wellbeing</h3>
            <div class="bar-chart">
                <div class="bar-row">
                    <span class="bar-label">Avg Health</span>
                    <div class="bar-track">
                        <div class="bar-fill {if $stats.population_stats.avg_health >= 70}success{elseif $stats.population_stats.avg_health >= 40}warning{else}error{/if}" style="width: {$stats.population_stats.avg_health}%"></div>
                    </div>
                    <span class="bar-value">{$stats.population_stats.avg_health}%</span>
                </div>
                <div class="bar-row">
                    <span class="bar-label">Avg Happiness</span>
                    <div class="bar-track">
                        <div class="bar-fill {if $stats.population_stats.avg_happiness >= 70}success{elseif $stats.population_stats.avg_happiness >= 40}warning{else}error{/if}" style="width: {$stats.population_stats.avg_happiness}%"></div>
                    </div>
                    <span class="bar-value">{$stats.population_stats.avg_happiness}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-actions">
        <h2>Advance Time</h2>
        <div class="action-buttons">
            <form method="POST" action="/advance-day" style="display: inline;">
                {$csrf_field nofilter}
                <button type="submit" class="btn btn-primary">Advance Day</button>
            </form>
            <form method="POST" action="/advance-week" style="display: inline;">
                {$csrf_field nofilter}
                <button type="submit" class="btn btn-secondary">Advance Week</button>
            </form>
            <form method="POST" action="/advance-month" style="display: inline;">
                {$csrf_field nofilter}
                <button type="submit" class="btn btn-secondary">Advance Month</button>
            </form>
            <form method="POST" action="/advance-season" style="display: inline;">
                {$csrf_field nofilter}
                <button type="submit" class="btn btn-secondary">Advance Season</button>
            </form>
        </div>
    </div>

    <div class="dashboard-actions">
        <h2>Manage Your Realm</h2>
        <div class="action-buttons">
            <a href="/citizens" class="btn btn-secondary">Manage Citizens</a>
            <a href="/buildings" class="btn btn-secondary">Manage Buildings</a>
            <a href="/economy" class="btn btn-secondary">View Economy</a>
        </div>
    </div>

    {if $recent_events}
    <div class="recent-events">
        <div class="events-header">
            <h2>Recent Events</h2>
            <a href="/events" class="btn btn-small">View All</a>
        </div>
        <div class="events-list">
            {foreach $recent_events as $event}
                <div class="event-item event-{$event.category}">
                    <span class="event-indicator event-indicator-{$event.category}">
                        {if $event.category == 'positive'}+{/if}
                        {if $event.category == 'negative'}!{/if}
                        {if $event.category == 'neutral'}-{/if}
                        {if $event.category == 'special'}*{/if}
                    </span>
                    <div class="event-details">
                        <span class="event-text">{$event.message}</span>
                        <span class="event-day">Year {$event.year}, Day {$event.day}</span>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
    {/if}
</div>
{/block}

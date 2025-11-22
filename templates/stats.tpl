{extends file="layout.tpl"}

{block name="title"}Statistics - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Realm Statistics</h1>
    <p>Year {$stats.current_year}, Day {$stats.current_day} | {$stats.season}</p>
</div>

<div class="stats-dashboard">
    <!-- Key Metrics Table -->
    <div class="section-card">
        <h2>Kingdom Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Population</td>
                    <td><strong>{$stats.population}</strong></td>
                </tr>
                <tr>
                    <td>Treasury Gold</td>
                    <td><strong>{$stats.treasury}</strong> gold</td>
                </tr>
                <tr>
                    <td>Buildings</td>
                    <td><strong>{$stats.buildings}</strong></td>
                </tr>
                <tr>
                    <td>Businesses</td>
                    <td><strong>{$stats.economy_stats.businesses_count}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Population Distribution Table -->
    <div class="section-card">
        <h2>Population Distribution</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Age Group</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Children (0-17)</td>
                    <td>{$stats.population_stats.children}</td>
                    <td>{$pop_percentages.children}%</td>
                </tr>
                <tr>
                    <td>Adults (18-59)</td>
                    <td>{$stats.population_stats.adults}</td>
                    <td>{$pop_percentages.adults}%</td>
                </tr>
                <tr>
                    <td>Elders (60+)</td>
                    <td>{$stats.population_stats.elders}</td>
                    <td>{$pop_percentages.elders}%</td>
                </tr>
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td><strong>{$stats.population}</strong></td>
                    <td><strong>100%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Employment & Housing Table -->
    <div class="section-card">
        <h2>Employment & Housing</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Total</th>
                    <th>Rate</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Employed Citizens</td>
                    <td>{$stats.population_stats.employed}</td>
                    <td>{$stats.population_stats.adults} (working age)</td>
                    <td>{$employment_rate}%</td>
                </tr>
                <tr>
                    <td>Housed Citizens</td>
                    <td>{$stats.population_stats.housed}</td>
                    <td>{$stats.population} (total pop)</td>
                    <td>{$housing_rate}%</td>
                </tr>
                <tr>
                    <td>Married Citizens</td>
                    <td>{$stats.population_stats.married}</td>
                    <td>{$stats.population_stats.adults} (adults)</td>
                    <td>{if $stats.population_stats.adults > 0}{($stats.population_stats.married / $stats.population_stats.adults * 100)|round}{else}0{/if}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Citizen Wellbeing Table -->
    <div class="section-card">
        <h2>Citizen Wellbeing</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Average</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Health</td>
                    <td>{$stats.population_stats.avg_health}%</td>
                    <td>
                        {if $stats.population_stats.avg_health >= 80}
                            <span class="status-good">Excellent</span>
                        {elseif $stats.population_stats.avg_health >= 60}
                            <span class="status-ok">Good</span>
                        {elseif $stats.population_stats.avg_health >= 40}
                            <span class="status-warning">Fair</span>
                        {else}
                            <span class="status-bad">Poor</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Happiness</td>
                    <td>{$stats.population_stats.avg_happiness}%</td>
                    <td>
                        {if $stats.population_stats.avg_happiness >= 80}
                            <span class="status-good">Thriving</span>
                        {elseif $stats.population_stats.avg_happiness >= 60}
                            <span class="status-ok">Content</span>
                        {elseif $stats.population_stats.avg_happiness >= 40}
                            <span class="status-warning">Discontent</span>
                        {else}
                            <span class="status-bad">Miserable</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Building Types Table -->
    <div class="section-card">
        <h2>Building Types</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                {foreach $building_counts as $type => $count}
                <tr>
                    <td>{$type|capitalize}</td>
                    <td>{$count}</td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="2" class="empty-message">No buildings yet</td>
                </tr>
                {/foreach}
                {if $building_counts}
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td><strong>{$stats.buildings}</strong></td>
                </tr>
                {/if}
            </tbody>
        </table>
    </div>

    <!-- Economy Overview Table -->
    <div class="section-card">
        <h2>Economy Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Royal Treasury</td>
                    <td><strong>{$stats.treasury}</strong> gold</td>
                </tr>
                <tr>
                    <td>Total Citizen Wealth</td>
                    <td>{$stats.economy_stats.total_citizen_wealth} gold</td>
                </tr>
                <tr>
                    <td>Total Business Treasury</td>
                    <td>{$stats.economy_stats.total_business_treasury} gold</td>
                </tr>
                <tr class="table-total">
                    <td><strong>Total Economy</strong></td>
                    <td><strong>{$stats.treasury + $stats.economy_stats.total_citizen_wealth + $stats.economy_stats.total_business_treasury}</strong> gold</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Area Statistics -->
    {if $area_stats}
    <div class="section-card">
        <h2>Area Statistics</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Area</th>
                    <th>Population</th>
                    <th>Buildings</th>
                    <th>Tax Rate</th>
                </tr>
            </thead>
            <tbody>
                {foreach $area_stats as $area}
                <tr>
                    <td><strong>{$area.name}</strong></td>
                    <td>{$area.population}</td>
                    <td>{$area.buildings}</td>
                    <td>{$area.tax_rate * 100}%</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    {/if}

    <!-- Historical Charts -->
    <div class="section-card">
        <h2>Historical Trends</h2>
        <div class="charts-container">
            <div class="chart-wrapper">
                <h3>Population & Treasury</h3>
                <canvas id="populationChart"></canvas>
            </div>
            <div class="chart-wrapper">
                <h3>Citizen Wellbeing</h3>
                <canvas id="wellbeingChart"></canvas>
            </div>
        </div>
        <p class="chart-note">Charts update as you advance time in the game.</p>
    </div>
</div>

<div class="page-actions">
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

{block name="scripts"}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
{literal}
// Simple donut chart animation
document.querySelectorAll('.donut-chart').forEach(chart => {
    const value = parseInt(chart.dataset.value) || 0;
    const degrees = (value / 100) * 360;
    chart.style.background = `conic-gradient(var(--color-secondary) ${degrees}deg, var(--color-border) ${degrees}deg)`;
});

// Load and render historical charts
async function loadChartData() {
    try {
        const response = await fetch('/api/chart-data');
        const data = await response.json();

        if (data.labels && data.labels.length > 0) {
            renderPopulationChart(data);
            renderWellbeingChart(data);
        } else {
            document.querySelectorAll('.chart-wrapper').forEach(wrapper => {
                wrapper.innerHTML = '<p class="empty-chart">No historical data yet. Advance time to see trends!</p>';
            });
        }
    } catch (error) {
        console.error('Failed to load chart data:', error);
    }
}

function renderPopulationChart(data) {
    const ctx = document.getElementById('populationChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Population',
                    data: data.datasets.population,
                    borderColor: '#4a90a4',
                    backgroundColor: 'rgba(74, 144, 164, 0.1)',
                    tension: 0.3,
                    yAxisID: 'y',
                },
                {
                    label: 'Treasury',
                    data: data.datasets.treasury,
                    borderColor: '#d4a259',
                    backgroundColor: 'rgba(212, 162, 89, 0.1)',
                    tension: 0.3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Population' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Treasury (Gold)' },
                    grid: { drawOnChartArea: false },
                }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
}

function renderWellbeingChart(data) {
    const ctx = document.getElementById('wellbeingChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Happiness',
                    data: data.datasets.happiness,
                    borderColor: '#7cb342',
                    backgroundColor: 'rgba(124, 179, 66, 0.1)',
                    tension: 0.3,
                },
                {
                    label: 'Health',
                    data: data.datasets.health,
                    borderColor: '#e57373',
                    backgroundColor: 'rgba(229, 115, 115, 0.1)',
                    tension: 0.3,
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    title: { display: true, text: 'Percentage' }
                }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
}

// Load charts on page load
document.addEventListener('DOMContentLoaded', loadChartData);
{/literal}
</script>
{/block}

{extends file="layout.tpl"}

{block name="title"}Citizens - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Citizens of Your Realm</h1>
    <p>Population: {$stats.population} | Avg Health: {$stats.population_stats.avg_health}% | Avg Happiness: {$stats.population_stats.avg_happiness}%</p>
</div>

<!-- Search and Filter Section -->
<div class="section-card">
    <h2>Search & Filter</h2>
    <div class="filter-controls">
        <div class="form-row">
            <div class="form-group">
                <label for="search-name">Search by Name</label>
                <input type="text" id="search-name" placeholder="Enter name..." class="filter-input">
            </div>
            <div class="form-group">
                <label for="filter-gender">Gender</label>
                <select id="filter-gender" class="filter-select">
                    <option value="">All</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filter-employment">Employment</label>
                <select id="filter-employment" class="filter-select">
                    <option value="">All</option>
                    <option value="employed">Employed</option>
                    <option value="unemployed">Unemployed</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filter-housing">Housing</label>
                <select id="filter-housing" class="filter-select">
                    <option value="">All</option>
                    <option value="housed">Housed</option>
                    <option value="homeless">Homeless</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filter-health">Health</label>
                <select id="filter-health" class="filter-select">
                    <option value="">All</option>
                    <option value="good">Good (70%+)</option>
                    <option value="fair">Fair (40-69%)</option>
                    <option value="poor">Poor (&lt;40%)</option>
                </select>
            </div>
            <div class="form-group">
                <button type="button" id="clear-filters" class="btn btn-secondary">Clear Filters</button>
            </div>
        </div>
    </div>
    <p class="filter-results">Showing <span id="visible-count">{$citizens|count}</span> of {$citizens|count} citizens</p>
</div>

<div class="citizens-list">
    {if $citizens}
    <table class="data-table" id="citizens-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Role</th>
                <th>Home</th>
                <th>Health</th>
                <th>Happiness</th>
                <th>Wealth</th>
            </tr>
        </thead>
        <tbody>
            {foreach $citizens as $citizen}
            <tr class="clickable-row citizen-row"
                onclick="window.location='/citizen/{$citizen.id}'"
                data-name="{$citizen.name|lower}"
                data-gender="{$citizen.gender}"
                data-employment="{if $citizen.role_name}employed{else}unemployed{/if}"
                data-housing="{if $citizen.home_name}housed{else}homeless{/if}"
                data-health="{$citizen.health}">
                <td>
                    <a href="/citizen/{$citizen.id}"><strong>{$citizen.name}</strong></a>
                    {if $citizen.spouse_id}<span class="badge">Married</span>{/if}
                </td>
                <td>{$citizen.age}</td>
                <td>{$citizen.gender|capitalize}</td>
                <td>{$citizen.role_name|default:'<span class="warning">Unemployed</span>'}</td>
                <td>{$citizen.home_name|default:'<span class="warning">Homeless</span>'}</td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill health" style="width: {$citizen.health}%"></div>
                    </div>
                    <span class="progress-text">{$citizen.health}%</span>
                </td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill happiness" style="width: {$citizen.happiness}%"></div>
                    </div>
                    <span class="progress-text">{$citizen.happiness}%</span>
                </td>
                <td>{$citizen.wealth} gold</td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <div class="empty-state">
        <p>No citizens yet. Advance time to grow your population!</p>
    </div>
    {/if}
</div>

<div class="section-card">
    <h2>Recruit New Citizen</h2>
    <p>Attract a new citizen to your realm for 50 gold.</p>
    <form method="POST" action="/citizens/create" class="inline-form">
        {$csrf_field nofilter}
        <div class="form-row">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required maxlength="50" placeholder="Enter name">
            </div>
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" min="18" max="50" value="25" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Recruit (50g)</button>
            </div>
        </div>
    </form>
</div>

<div class="section-card">
    <h2>Bulk Recruitment</h2>
    <p>Recruit multiple random citizens at a discounted rate (40g each). They will be automatically assigned to available housing and jobs.</p>
    <div class="bulk-recruit-options">
        <form method="POST" action="/citizens/recruit-bulk" class="inline-form bulk-form">
            {$csrf_field nofilter}
            <input type="hidden" name="count" value="5">
            <button type="submit" class="btn btn-secondary bulk-btn">
                <span class="bulk-count">5</span>
                <span class="bulk-label">Citizens</span>
                <span class="bulk-cost">200g</span>
            </button>
        </form>
        <form method="POST" action="/citizens/recruit-bulk" class="inline-form bulk-form">
            {$csrf_field nofilter}
            <input type="hidden" name="count" value="10">
            <button type="submit" class="btn btn-secondary bulk-btn">
                <span class="bulk-count">10</span>
                <span class="bulk-label">Citizens</span>
                <span class="bulk-cost">400g</span>
            </button>
        </form>
        <form method="POST" action="/citizens/recruit-bulk" class="inline-form bulk-form">
            {$csrf_field nofilter}
            <input type="hidden" name="count" value="25">
            <button type="submit" class="btn btn-primary bulk-btn">
                <span class="bulk-count">25</span>
                <span class="bulk-label">Citizens</span>
                <span class="bulk-cost">1000g</span>
            </button>
        </form>
    </div>
    <p class="help-text">Random medieval names, ages (18-45), and stats will be generated. Citizens will be housed and employed based on availability.</p>
</div>

<div class="page-actions">
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

{block name="scripts"}
<script>
{literal}
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-name');
    const genderFilter = document.getElementById('filter-gender');
    const employmentFilter = document.getElementById('filter-employment');
    const housingFilter = document.getElementById('filter-housing');
    const healthFilter = document.getElementById('filter-health');
    const clearBtn = document.getElementById('clear-filters');
    const visibleCount = document.getElementById('visible-count');
    const rows = document.querySelectorAll('.citizen-row');

    function filterRows() {
        const searchTerm = searchInput.value.toLowerCase();
        const gender = genderFilter.value;
        const employment = employmentFilter.value;
        const housing = housingFilter.value;
        const health = healthFilter.value;

        let visible = 0;

        rows.forEach(row => {
            let show = true;

            // Name search
            if (searchTerm && !row.dataset.name.includes(searchTerm)) {
                show = false;
            }

            // Gender filter
            if (gender && row.dataset.gender !== gender) {
                show = false;
            }

            // Employment filter
            if (employment && row.dataset.employment !== employment) {
                show = false;
            }

            // Housing filter
            if (housing && row.dataset.housing !== housing) {
                show = false;
            }

            // Health filter
            if (health) {
                const healthVal = parseInt(row.dataset.health);
                if (health === 'good' && healthVal < 70) show = false;
                if (health === 'fair' && (healthVal < 40 || healthVal >= 70)) show = false;
                if (health === 'poor' && healthVal >= 40) show = false;
            }

            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        visibleCount.textContent = visible;
    }

    // Add event listeners
    searchInput.addEventListener('input', filterRows);
    genderFilter.addEventListener('change', filterRows);
    employmentFilter.addEventListener('change', filterRows);
    housingFilter.addEventListener('change', filterRows);
    healthFilter.addEventListener('change', filterRows);

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        genderFilter.value = '';
        employmentFilter.value = '';
        housingFilter.value = '';
        healthFilter.value = '';
        filterRows();
    });
});
{/literal}
</script>
{/block}

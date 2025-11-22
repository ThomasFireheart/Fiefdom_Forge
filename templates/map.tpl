{extends file="layout.tpl"}

{block name="title"}City Map - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>City Map</h1>
    <p>Overview of all areas and buildings in your fiefdom</p>
</div>

<div class="stats-dashboard">
    <!-- City Overview Table -->
    <div class="section-card">
        <h2>City Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Areas</td>
                    <td><strong>{$areas|count}</strong></td>
                </tr>
                <tr>
                    <td>Total Buildings</td>
                    <td><strong>{$stats.buildings}</strong></td>
                </tr>
                <tr>
                    <td>Total Population</td>
                    <td><strong>{$stats.population}</strong></td>
                </tr>
                <tr>
                    <td>Treasury</td>
                    <td><strong>{$stats.treasury}</strong> gold</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Areas Summary Table -->
    {if $areas}
    <div class="section-card">
        <h2>Areas Summary</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Area Name</th>
                    <th>Description</th>
                    <th>Buildings</th>
                    <th>Population</th>
                    <th>Tax Rate</th>
                </tr>
            </thead>
            <tbody>
                {foreach $areas as $area}
                <tr>
                    <td><strong>{$area->getName()}</strong></td>
                    <td>{$area->getDescription()|default:'-'}</td>
                    <td>{$area_stats[$area->getId()].buildings}</td>
                    <td>{$area_stats[$area->getId()].population}</td>
                    <td>{$area->getTaxRate() * 100}%</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    <!-- Buildings by Area -->
    {foreach $areas as $area}
    <div class="section-card">
        <h2>{$area->getName()}</h2>
        <p class="section-description">{$area->getDescription()|default:'No description'}</p>

        {assign var="area_buildings" value=$buildings_by_area[$area->getId()]|default:[]}
        {if $area_buildings}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Building Name</th>
                    <th>Type</th>
                    <th>Occupancy</th>
                    <th>Condition</th>
                    <th>Business</th>
                </tr>
            </thead>
            <tbody>
                {foreach $area_buildings as $building}
                <tr class="clickable-row" onclick="window.location='/building/{$building.id}'">
                    <td><a href="/building/{$building.id}"><strong>{$building.name}</strong></a></td>
                    <td><span class="badge badge-{$building.type}">{$building.type|capitalize}</span></td>
                    <td>
                        {if $building.type == 'house'}
                            {$building.residents}/{$building.capacity} residents
                        {else}
                            {$building.capacity} capacity
                        {/if}
                    </td>
                    <td>
                        {if $building.condition >= 80}
                            <span class="status-good">{$building.condition}%</span>
                        {elseif $building.condition >= 50}
                            <span class="status-ok">{$building.condition}%</span>
                        {elseif $building.condition >= 25}
                            <span class="status-warning">{$building.condition}%</span>
                        {else}
                            <span class="status-bad">{$building.condition}%</span>
                        {/if}
                    </td>
                    <td>{$building.business_name|default:'-'}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p class="empty-message">No buildings in this area yet.</p>
        {/if}
    </div>
    {/foreach}

    <!-- Building Types Summary -->
    <div class="section-card">
        <h2>Building Types Summary</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge badge-house">House</span></td>
                    <td>{$building_type_counts.house|default:0}</td>
                    <td>Residential buildings for citizens</td>
                </tr>
                <tr>
                    <td><span class="badge badge-business">Business</span></td>
                    <td>{$building_type_counts.business|default:0}</td>
                    <td>Commercial workshops and shops</td>
                </tr>
                <tr>
                    <td><span class="badge badge-farm">Farm</span></td>
                    <td>{$building_type_counts.farm|default:0}</td>
                    <td>Agricultural production facilities</td>
                </tr>
                <tr>
                    <td><span class="badge badge-resource">Resource</span></td>
                    <td>{$building_type_counts.resource|default:0}</td>
                    <td>Mines, lumber mills, and extraction sites</td>
                </tr>
                <tr>
                    <td><span class="badge badge-public">Public</span></td>
                    <td>{$building_type_counts.public|default:0}</td>
                    <td>Churches, taverns, markets, and civic buildings</td>
                </tr>
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td><strong>{$stats.buildings}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    {else}
    <div class="section-card">
        <h2>No Areas</h2>
        <p class="empty-message">No areas defined. Create some areas first!</p>
        <a href="/areas" class="btn btn-primary">Manage Areas</a>
    </div>
    {/if}
</div>

<div class="page-actions">
    <a href="/buildings/new" class="btn btn-primary">Construct Building</a>
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

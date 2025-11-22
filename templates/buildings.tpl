{extends file="layout.tpl"}

{block name="title"}Buildings - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Buildings in Your Realm</h1>
    <p>Total Buildings: {$stats.buildings} | Treasury: {$stats.treasury} gold</p>
    <a href="/buildings/new" class="btn btn-primary">Construct New Building</a>
</div>

<div class="stats-dashboard">
    <!-- Buildings Overview Table -->
    <div class="section-card">
        <h2>Buildings Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Buildings</td>
                    <td><strong>{$stats.buildings}</strong></td>
                </tr>
                <tr>
                    <td>Total Areas</td>
                    <td><strong>{$areas|count}</strong></td>
                </tr>
                <tr>
                    <td>Treasury</td>
                    <td><strong>{$stats.treasury}</strong> gold</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Areas Table -->
    {if $areas}
    <div class="section-card">
        <h2>Areas</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Area Name</th>
                    <th>Description</th>
                    <th>Tax Rate</th>
                    <th>Capacity</th>
                </tr>
            </thead>
            <tbody>
                {foreach $areas as $area}
                <tr>
                    <td><strong>{$area->getName()}</strong></td>
                    <td>{$area->getDescription()|default:'-'}</td>
                    <td>{$area->getTaxRate() * 100}%</td>
                    <td>{$area->getCapacity()}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    {/if}

    <!-- All Buildings Table -->
    <div class="section-card">
        <h2>All Buildings</h2>
        {if $buildings}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Area</th>
                    <th>Capacity</th>
                    <th>Occupants</th>
                    <th>Condition</th>
                    <th>Upkeep</th>
                </tr>
            </thead>
            <tbody>
                {foreach $buildings as $building}
                <tr class="clickable-row" onclick="window.location='/building/{$building.id}'">
                    <td><a href="/building/{$building.id}"><strong>{$building.name}</strong></a></td>
                    <td><span class="badge badge-{$building.type}">{$building.type|capitalize}</span></td>
                    <td>{$building.area_name}</td>
                    <td>{$building.capacity}</td>
                    <td>{$building.occupants}</td>
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
                    <td>{$building.upkeep_cost} gold/month</td>
                </tr>
                {/foreach}
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td colspan="3"></td>
                    <td><strong>{$total_occupants|default:0}</strong></td>
                    <td></td>
                    <td><strong>{$total_upkeep|default:0}</strong> gold/month</td>
                </tr>
            </tbody>
        </table>
        {else}
        <p class="empty-message">No buildings yet. Start building your realm!</p>
        {/if}
    </div>
</div>

<div class="page-actions">
    <a href="/buildings/new" class="btn btn-primary">Construct New Building</a>
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

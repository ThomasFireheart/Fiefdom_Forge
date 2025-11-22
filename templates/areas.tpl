{extends file="layout.tpl"}

{block name="title"}Areas - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Manage Areas</h1>
    <p>Define the districts of your realm and set their tax rates</p>
</div>

<div class="stats-dashboard">
    <!-- Areas Overview Table -->
    <div class="section-card">
        <h2>Areas Overview</h2>
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
                    <td>Total Population</td>
                    <td><strong>{$total_population|default:0}</strong></td>
                </tr>
                <tr>
                    <td>Total Buildings</td>
                    <td><strong>{$total_buildings|default:0}</strong></td>
                </tr>
                <tr>
                    <td>Total Capacity</td>
                    <td><strong>{$total_capacity|default:0}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- All Areas Table -->
    <div class="section-card">
        <h2>All Areas</h2>
        {if $areas}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Area Name</th>
                    <th>Description</th>
                    <th>Population</th>
                    <th>Buildings</th>
                    <th>Capacity</th>
                    <th>Tax Rate</th>
                    <th>Update Tax</th>
                </tr>
            </thead>
            <tbody>
                {foreach $areas as $area}
                <tr>
                    <td><strong>{$area->getName()}</strong></td>
                    <td>{$area->getDescription()|default:'-'}</td>
                    <td>{$area_stats[$area->getId()].population}</td>
                    <td>{$area_stats[$area->getId()].buildings}</td>
                    <td>{$area->getCapacity()}</td>
                    <td>{($area->getTaxRate() * 100)|number_format:0}%</td>
                    <td>
                        <form method="POST" action="/areas/update-tax" class="inline-form-small">
                            {$csrf_field nofilter}
                            <input type="hidden" name="area_id" value="{$area->getId()}">
                            <input type="number"
                                   name="tax_rate"
                                   value="{$area->getTaxRate()}"
                                   min="0"
                                   max="0.5"
                                   step="0.01"
                                   class="input-small">
                            <button type="submit" class="btn btn-small btn-primary">Set</button>
                        </form>
                    </td>
                </tr>
                {/foreach}
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td></td>
                    <td><strong>{$total_population|default:0}</strong></td>
                    <td><strong>{$total_buildings|default:0}</strong></td>
                    <td><strong>{$total_capacity|default:0}</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        {else}
        <p class="empty-message">No areas defined yet. Create your first area below!</p>
        {/if}
    </div>

    <!-- Area Details Tables -->
    {if $areas}
    {foreach $areas as $area}
    <div class="section-card">
        <h2>{$area->getName()}</h2>
        <p class="section-description">{$area->getDescription()|default:'No description'}</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Population</td>
                    <td>{$area_stats[$area->getId()].population}</td>
                </tr>
                <tr>
                    <td>Buildings</td>
                    <td>{$area_stats[$area->getId()].buildings}</td>
                </tr>
                <tr>
                    <td>Capacity</td>
                    <td>{$area->getCapacity()}</td>
                </tr>
                <tr>
                    <td>Current Tax Rate</td>
                    <td>{($area->getTaxRate() * 100)|number_format:0}%</td>
                </tr>
                <tr>
                    <td>Occupancy</td>
                    <td>
                        {if $area->getCapacity() > 0}
                            {assign var="occupancy" value=($area_stats[$area->getId()].population / $area->getCapacity() * 100)|round}
                            {if $occupancy >= 90}
                                <span class="status-bad">{$occupancy}% (Full)</span>
                            {elseif $occupancy >= 70}
                                <span class="status-warning">{$occupancy}% (High)</span>
                            {elseif $occupancy >= 40}
                                <span class="status-ok">{$occupancy}% (Moderate)</span>
                            {else}
                                <span class="status-good">{$occupancy}% (Low)</span>
                            {/if}
                        {else}
                            0%
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    {/foreach}
    {/if}

    <!-- Create New Area -->
    <div class="section-card">
        <h2>Create New Area</h2>
        <form method="POST" action="/areas/create" class="create-form">
            {$csrf_field nofilter}
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Area Name</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Noble Quarter">
                </div>
                <div class="form-group">
                    <label for="capacity">Capacity</label>
                    <input type="number" id="capacity" name="capacity" value="100" min="10" max="1000">
                </div>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" placeholder="Brief description of this area">
            </div>
            <button type="submit" class="btn btn-primary">Create Area</button>
        </form>
    </div>
</div>

<div class="page-actions">
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

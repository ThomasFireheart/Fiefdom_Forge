{extends file="layout.tpl"}

{block name="title"}{$building->getName()} - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>{$building->getName()}</h1>
    <p>
        <span class="badge badge-{$building->getType()}">{$building->getType()|capitalize}</span>
        in {$area->getName()}
    </p>
</div>

<div class="stats-dashboard">
    <!-- Building Overview Table -->
    <div class="section-card">
        <h2>Building Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Attribute</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Type</td>
                    <td><span class="badge badge-{$building->getType()}">{$building->getType()|capitalize}</span></td>
                </tr>
                <tr>
                    <td>Area</td>
                    <td><a href="/areas">{$area->getName()}</a></td>
                </tr>
                <tr>
                    <td>Capacity</td>
                    <td>{$building->getCurrentOccupancy()}/{$building->getCapacity()}</td>
                </tr>
                <tr>
                    <td>Monthly Upkeep</td>
                    <td>{$building->getUpkeepCost()} gold</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Building Condition Table -->
    <div class="section-card">
        <h2>Building Condition</h2>
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
                    <td>Condition</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill condition" style="width: {$building->getCondition()}%"></div>
                        </div>
                        {$building->getCondition()}%
                    </td>
                    <td>
                        {if $building->getCondition() >= 80}
                            <span class="status-good">Excellent</span>
                        {elseif $building->getCondition() >= 60}
                            <span class="status-ok">Good</span>
                        {elseif $building->getCondition() >= 40}
                            <span class="status-warning">Fair</span>
                        {elseif $building->getCondition() > 20}
                            <span class="status-bad">Poor</span>
                        {else}
                            <span class="status-bad">Critical - Not Operational!</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Operational</td>
                    <td colspan="2">
                        {if $building->isOperational()}
                            <span class="status-good">Yes</span> - Building is functional
                        {else}
                            <span class="status-bad">No</span> - Repair needed to restore function
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        {if $building->getCondition() < 100}
        <form method="POST" action="/buildings/repair" class="management-form">
            {$csrf_field nofilter}
            <input type="hidden" name="building_id" value="{$building->getId()}">
            <div class="form-row">
                <div class="form-group">
                    <p>Repair building by 10% for <strong>{$repair_cost} gold</strong></p>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Repair Building</button>
                </div>
            </div>
        </form>
        {else}
        <p class="status-good">Building is in perfect condition!</p>
        {/if}
    </div>

    <!-- Ownership Table -->
    <div class="section-card">
        <h2>Building Ownership</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Current Owner</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        {if $owner}
                            <strong><a href="/citizen/{$owner->getId()}">{$owner->getName()}</a></strong>
                        {else}
                            <em>Unowned (Public Property)</em>
                        {/if}
                    </td>
                    <td>
                        {if $owner}
                            <span class="status-good">Private Property</span>
                        {else}
                            <span class="status-ok">Public Property</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        <form method="POST" action="/buildings/transfer-ownership" class="management-form">
            {$csrf_field nofilter}
            <input type="hidden" name="building_id" value="{$building->getId()}">
            <div class="form-row">
                <div class="form-group">
                    <label for="citizen_id">Transfer Ownership</label>
                    <select name="citizen_id" id="citizen_id">
                        <option value="0">-- Public Property (No Owner) --</option>
                        {foreach $available_owners as $c}
                            <option value="{$c.id}" {if $building->getOwnerCitizenId() == $c.id}selected{/if}>
                                {$c.name} ({$c.wealth} gold)
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Transfer Ownership</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Residents Table (for houses) -->
    {if $building->getType() == 'house'}
    <div class="section-card">
        <h2>Residents ({$occupants|count}/{$building->getCapacity()})</h2>
        {if $occupants}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $occupants as $resident}
                <tr>
                    <td><strong>{$resident.name}</strong></td>
                    <td>{$resident.age} years</td>
                    <td>{$resident.gender|capitalize}</td>
                    <td><a href="/citizen/{$resident.id}" class="btn btn-small btn-secondary">View</a></td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p class="empty-message">No residents currently living here.</p>
        {/if}
    </div>
    {/if}

    <!-- Business Table -->
    {if $business}
    <div class="section-card">
        <h2>Associated Business</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Attribute</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Name</td>
                    <td><strong><a href="/business/{$business.id}">{$business.name}</a></strong></td>
                </tr>
                <tr>
                    <td>Type</td>
                    <td>{$business.type|capitalize}</td>
                </tr>
                <tr>
                    <td>Employees</td>
                    <td>{$business.current_employees}/{$business.employees_capacity}</td>
                </tr>
                <tr>
                    <td>Treasury</td>
                    <td><strong>{$business.treasury}</strong> gold</td>
                </tr>
            </tbody>
        </table>
    </div>
    {elseif $building->getType() == 'business' || $building->getType() == 'farm' || $building->getType() == 'resource'}
    <div class="section-card">
        <h2>Business</h2>
        <p class="empty-message">No business established here yet.</p>
        <a href="/economy" class="btn btn-secondary">Create Business</a>
    </div>
    {/if}
</div>

<div class="page-actions">
    <a href="/buildings" class="btn btn-secondary">Back to Buildings</a>
</div>
{/block}

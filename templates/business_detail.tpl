{extends file="layout.tpl"}

{block name="title"}{$business->getName()} - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>{$business->getName()}</h1>
    <p><span class="badge badge-business">{$business->getType()|capitalize}</span></p>
</div>

<div class="stats-dashboard">
    <!-- Business Overview Table -->
    <div class="section-card">
        <h2>Business Overview</h2>
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
                    <td><span class="badge badge-business">{$business->getType()|capitalize}</span></td>
                </tr>
                <tr>
                    <td>Location</td>
                    <td><a href="/building/{$building->getId()}">{$building->getName()}</a></td>
                </tr>
                <tr>
                    <td>Employees</td>
                    <td>{$business->getCurrentEmployees()}/{$business->getEmployeesCapacity()}</td>
                </tr>
                <tr>
                    <td>Treasury</td>
                    <td><strong>{$business->getTreasury()}</strong> gold</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Reputation Table -->
    <div class="section-card">
        <h2>Business Reputation</h2>
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
                    <td>Reputation</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill reputation" style="width: {$business->getReputation()}%"></div>
                        </div>
                        {$business->getReputation()}/100
                    </td>
                    <td>
                        {if $business->getReputation() >= 80}
                            <span class="status-good">Excellent</span>
                        {elseif $business->getReputation() >= 60}
                            <span class="status-ok">Good</span>
                        {elseif $business->getReputation() >= 40}
                            <span class="status-warning">Average</span>
                        {elseif $business->getReputation() >= 20}
                            <span class="status-bad">Poor</span>
                        {else}
                            <span class="status-bad">Terrible</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        <h3>Reputation Effects</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Effect</th>
                    <th>Current Bonus</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Production Efficiency</td>
                    <td>
                        {assign var="prod_bonus" value=50 + ($business->getReputation() / 2)}
                        <span class="{if $prod_bonus >= 75}status-good{elseif $prod_bonus >= 50}status-ok{else}status-warning{/if}">
                            {$prod_bonus|string_format:"%.0f"}%
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>Wage Bonus (paid to workers)</td>
                    <td>
                        {assign var="wage_bonus" value=$business->getReputation() / 20}
                        <span class="status-ok">+{$wage_bonus|string_format:"%.0f"} gold</span>
                    </td>
                </tr>
                <tr>
                    <td>Customer Attraction</td>
                    <td>
                        {if $business->getReputation() >= 80}
                            <span class="status-good">Very High</span>
                        {elseif $business->getReputation() >= 60}
                            <span class="status-ok">High</span>
                        {elseif $business->getReputation() >= 40}
                            <span class="status-warning">Normal</span>
                        {else}
                            <span class="status-bad">Low</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Products Table -->
    <div class="section-card">
        <h2>Products</h2>
        {if $products}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Base Price</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                {foreach $products as $product}
                <tr>
                    <td><strong>{$product->getName()}</strong></td>
                    <td>{$product->getBasePrice()} gold</td>
                    <td>
                        {if $product->isResource()}
                            <span class="badge badge-resource">Resource</span>
                        {else}
                            <span class="badge badge-business">Manufactured</span>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p class="empty-message">No products configured for this business.</p>
        {/if}
    </div>

    <!-- Employees Table -->
    <div class="section-card">
        <h2>Employees ({$employees|count}/{$business->getEmployeesCapacity()})</h2>
        {if $employees}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Health</th>
                    <th>Happiness</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $employees as $employee}
                <tr>
                    <td><strong>{$employee->getName()}</strong></td>
                    <td>{$employee->getAge()} years</td>
                    <td>
                        {if $employee->getHealth() >= 80}
                            <span class="status-good">{$employee->getHealth()}%</span>
                        {elseif $employee->getHealth() >= 50}
                            <span class="status-ok">{$employee->getHealth()}%</span>
                        {else}
                            <span class="status-bad">{$employee->getHealth()}%</span>
                        {/if}
                    </td>
                    <td>
                        {if $employee->getHappiness() >= 80}
                            <span class="status-good">{$employee->getHappiness()}%</span>
                        {elseif $employee->getHappiness() >= 50}
                            <span class="status-ok">{$employee->getHappiness()}%</span>
                        {else}
                            <span class="status-bad">{$employee->getHappiness()}%</span>
                        {/if}
                    </td>
                    <td><a href="/citizen/{$employee->getId()}" class="btn btn-small btn-secondary">View</a></td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p class="empty-message">No employees currently working here.</p>
        {/if}
    </div>
</div>

<div class="page-actions">
    <a href="/economy" class="btn btn-secondary">Back to Economy</a>
</div>
{/block}

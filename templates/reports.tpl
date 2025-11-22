{extends file="layout.tpl"}

{block name="title"}Economic Reports - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Economic Reports</h1>
    <p>Comprehensive financial analysis of your realm</p>
</div>

<div class="stats-dashboard">
    <!-- Treasury Overview -->
    <div class="section-card">
        <h2>Treasury Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Income</td>
                    <td><span class="status-good">+{$total_income|default:0}</span> gold</td>
                    <td>From sales and taxes</td>
                </tr>
                <tr>
                    <td>Total Expenses</td>
                    <td><span class="status-bad">-{$total_expenses|default:0}</span> gold</td>
                    <td>Wages, upkeep, production</td>
                </tr>
                <tr class="table-total">
                    <td><strong>Net Income</strong></td>
                    <td>
                        {if $net_income >= 0}
                            <span class="status-good"><strong>+{$net_income}</strong></span> gold
                        {else}
                            <span class="status-bad"><strong>{$net_income}</strong></span> gold
                        {/if}
                    </td>
                    <td>
                        {if $net_income >= 0}
                            <span class="status-good">Profitable</span>
                        {else}
                            <span class="status-bad">Operating at Loss</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Current Treasury</td>
                    <td><strong>{$stats.treasury}</strong> gold</td>
                    <td>Available funds</td>
                </tr>
            </tbody>
        </table>

        <!-- Income vs Expenses Chart -->
        <div class="chart-container">
            <h3 class="chart-title">Income vs Expenses</h3>
            <div class="bar-chart">
                {assign var="max_val" value=1}
                {if $total_income > $max_val}{assign var="max_val" value=$total_income}{/if}
                {if $total_expenses > $max_val}{assign var="max_val" value=$total_expenses}{/if}
                <div class="bar-row">
                    <span class="bar-label">Income</span>
                    <div class="bar-track">
                        <div class="bar-fill success" style="width: {($total_income / $max_val * 100)|string_format:'%.0f'}%"></div>
                    </div>
                    <span class="bar-value">{$total_income} g</span>
                </div>
                <div class="bar-row">
                    <span class="bar-label">Expenses</span>
                    <div class="bar-track">
                        <div class="bar-fill error" style="width: {($total_expenses / $max_val * 100)|string_format:'%.0f'}%"></div>
                    </div>
                    <span class="bar-value">{$total_expenses} g</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Report -->
    <div class="section-card">
        <h2>Inventory Report</h2>
        <p>Total Inventory Value: <strong>{$inventory_value}</strong> gold</p>
        {if $inventory_items}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Value</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                {foreach $inventory_items as $item}
                <tr>
                    <td><strong>{$item.good_name}</strong></td>
                    <td>{$item.quantity}</td>
                    <td>{$item.base_price} gold</td>
                    <td>{$item.quantity * $item.base_price} gold</td>
                    <td>
                        {if $item.is_resource}
                            <span class="badge badge-resource">Resource</span>
                        {else}
                            <span class="badge badge-business">Product</span>
                        {/if}
                    </td>
                </tr>
                {/foreach}
                <tr class="table-total">
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>{$inventory_value}</strong> gold</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        {else}
        <p class="empty-message">No items in inventory.</p>
        {/if}
    </div>

    <!-- Business Performance -->
    <div class="section-card">
        <h2>Business Performance</h2>
        {if $business_stats}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Type</th>
                    <th>Workers</th>
                    <th>Treasury</th>
                    <th>Reputation</th>
                </tr>
            </thead>
            <tbody>
                {foreach $business_stats as $biz}
                <tr>
                    <td>
                        <a href="/business/{$biz.id}"><strong>{$biz.name}</strong></a>
                        <br><small>{$biz.building_name}</small>
                    </td>
                    <td>{$biz.type|capitalize}</td>
                    <td>{$biz.worker_count}/{$biz.employees_capacity}</td>
                    <td><strong>{$biz.treasury}</strong> gold</td>
                    <td>
                        {if $biz.reputation >= 70}
                            <span class="status-good">{$biz.reputation}%</span>
                        {elseif $biz.reputation >= 40}
                            <span class="status-ok">{$biz.reputation}%</span>
                        {else}
                            <span class="status-bad">{$biz.reputation}%</span>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p class="empty-message">No businesses established yet.</p>
        {/if}
    </div>

    <!-- Production by Type -->
    {if $production_by_type}
    <div class="section-card">
        <h2>Production Summary by Type</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Business Type</th>
                    <th>Count</th>
                    <th>Total Treasury</th>
                    <th>Avg Reputation</th>
                </tr>
            </thead>
            <tbody>
                {foreach $production_by_type as $prod}
                <tr>
                    <td><strong>{$prod.type|capitalize}</strong></td>
                    <td>{$prod.business_count}</td>
                    <td>{$prod.total_treasury|default:0} gold</td>
                    <td>{$prod.avg_reputation|string_format:'%.0f'}%</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    {/if}

    <!-- Wealth Distribution -->
    {if $wealth_distribution}
    <div class="section-card">
        <h2>Citizen Wealth Distribution</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Wealth Class</th>
                    <th>Citizens</th>
                    <th>Total Wealth</th>
                    <th>Average Wealth</th>
                </tr>
            </thead>
            <tbody>
                {foreach $wealth_distribution as $wealth}
                <tr>
                    <td>
                        {if $wealth.wealth_class == 'Wealthy'}
                            <span class="status-good">{$wealth.wealth_class}</span>
                        {elseif $wealth.wealth_class == 'Comfortable'}
                            <span class="status-ok">{$wealth.wealth_class}</span>
                        {elseif $wealth.wealth_class == 'Modest'}
                            <span class="status-warning">{$wealth.wealth_class}</span>
                        {else}
                            <span class="status-bad">{$wealth.wealth_class}</span>
                        {/if}
                    </td>
                    <td>{$wealth.citizen_count}</td>
                    <td>{$wealth.total_wealth|default:0} gold</td>
                    <td>{$wealth.avg_wealth|string_format:'%.0f'} gold</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    {/if}

    <!-- Area Economic Performance -->
    {if $area_stats}
    <div class="section-card">
        <h2>Area Economic Performance</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Area</th>
                    <th>Tax Rate</th>
                    <th>Buildings</th>
                    <th>Citizens</th>
                    <th>Total Wealth</th>
                </tr>
            </thead>
            <tbody>
                {foreach $area_stats as $area}
                <tr>
                    <td><strong>{$area.name}</strong></td>
                    <td>{($area.tax_rate * 100)|string_format:'%.0f'}%</td>
                    <td>{$area.building_count}</td>
                    <td>{$area.citizen_count}</td>
                    <td>{$area.total_citizen_wealth|default:0} gold</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    {/if}
</div>

<div class="page-actions">
    <a href="/transactions" class="btn btn-secondary">View Transactions</a>
    <a href="/economy" class="btn btn-secondary">View Market</a>
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

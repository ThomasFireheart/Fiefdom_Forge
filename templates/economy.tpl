{extends file="layout.tpl"}

{block name="title"}Economy & Market - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Economy & Market</h1>
    <p>Treasury: {$stats.treasury} gold | Inventory Value: {$inventory_value} gold</p>
</div>

<div class="economy-stats">
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Treasury</h3>
            <div class="stat-value">{$stats.treasury}</div>
            <div class="stat-label">Gold Coins</div>
        </div>
        <div class="stat-card">
            <h3>Business Treasury</h3>
            <div class="stat-value">{$stats.economy_stats.total_business_treasury}</div>
            <div class="stat-label">Gold Coins</div>
        </div>
        <div class="stat-card">
            <h3>Employed</h3>
            <div class="stat-value">{$stats.economy_stats.employed_count}</div>
            <div class="stat-label">Workers</div>
        </div>
        <div class="stat-card">
            <h3>Businesses</h3>
            <div class="stat-value">{$stats.economy_stats.businesses_count}</div>
            <div class="stat-label">Active</div>
        </div>
    </div>
</div>

<!-- City Inventory Section -->
<div class="section-card">
    <h2>City Inventory</h2>
    {if $inventory}
    <table class="data-table">
        <thead>
            <tr>
                <th>Good</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {foreach $inventory as $item}
            <tr>
                <td><strong>{$item.good_name}</strong></td>
                <td><span class="badge {if $item.is_resource}resource{else}manufactured{/if}">{if $item.is_resource}Resource{else}Crafted{/if}</span></td>
                <td>{$item.quantity}</td>
                <td>{$item.base_price} gold</td>
                <td>{$item.quantity * $item.base_price} gold</td>
                <td>
                    <form method="POST" action="/market/sell" class="inline-form-small">
                        {$csrf_field nofilter}
                        <input type="hidden" name="good_id" value="{$item.good_id}">
                        <input type="number" name="quantity" min="1" max="{$item.quantity}" value="1" class="input-small">
                        <button type="submit" class="btn btn-small btn-secondary">Sell</button>
                    </form>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <div class="empty-state">
        <p>Your city has no goods in inventory. Visit the market to purchase resources!</p>
    </div>
    {/if}
</div>

<!-- Market Section -->
<div class="section-card">
    <h2>Market - Buy Goods</h2>
    <p>Purchase goods from traveling merchants. Prices may vary!</p>
    {if $goods}
    <table class="data-table">
        <thead>
            <tr>
                <th>Good</th>
                <th>Type</th>
                <th>Price</th>
                <th>In Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {foreach $goods as $good}
            <tr>
                <td><strong>{$good->getName()}</strong></td>
                <td><span class="badge {if $good->isResource()}resource{else}manufactured{/if}">{if $good->isResource()}Resource{else}Crafted{/if}</span></td>
                <td>{$good->getBasePrice()} gold</td>
                <td class="text-muted">Unlimited</td>
                <td>
                    <form method="POST" action="/market/buy" class="inline-form-small">
                        {$csrf_field nofilter}
                        <input type="hidden" name="good_id" value="{$good->getId()}">
                        <input type="number" name="quantity" min="1" max="100" value="1" class="input-small">
                        <button type="submit" class="btn btn-small btn-primary">Buy</button>
                    </form>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <div class="empty-state">
        <p>No goods available in the market.</p>
    </div>
    {/if}
</div>

<!-- Businesses Section -->
<div class="section-card">
    <h2>Businesses</h2>
    {if $businesses}
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Employees</th>
                <th>Treasury</th>
                <th>Reputation</th>
            </tr>
        </thead>
        <tbody>
            {foreach $businesses as $business}
            <tr class="clickable-row" onclick="window.location='/business/{$business->getId()}'">
                <td><a href="/business/{$business->getId()}"><strong>{$business->getName()}</strong></a></td>
                <td><span class="badge">{$business->getType()|capitalize}</span></td>
                <td>{$business->getCurrentEmployees()} / {$business->getEmployeesCapacity()}</td>
                <td>{$business->getTreasury()} gold</td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-fill reputation" style="width: {$business->getReputation()}%"></div>
                    </div>
                    <span class="progress-text">{$business->getReputation()}%</span>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
    {else}
    <div class="empty-state">
        <p>No businesses yet. Build a workshop or farm to start production!</p>
    </div>
    {/if}
</div>

<div class="page-actions">
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

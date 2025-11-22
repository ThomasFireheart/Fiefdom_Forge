{extends file="layout.tpl"}

{block name="title"}Transaction History - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Transaction History</h1>
    <p>A record of all economic transactions in your realm</p>
</div>

<div class="stats-dashboard">
    <!-- Transactions Overview Table -->
    <div class="section-card">
        <h2>Transactions Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Transactions</td>
                    <td><strong>{$total_transactions}</strong></td>
                </tr>
                <tr>
                    <td>Total Value</td>
                    <td><strong>{$total_value}</strong> gold</td>
                </tr>
                <tr>
                    <td>Treasury</td>
                    <td><strong>{$stats.treasury}</strong> gold</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Transactions by Type Table -->
    <div class="section-card">
        <h2>Transactions by Type</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Total Value</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="status-good">Sale</span></td>
                    <td>{$type_counts.sale.count|default:0}</td>
                    <td>{$type_counts.sale.total_value|default:0} gold</td>
                    <td>Goods sold at market</td>
                </tr>
                <tr>
                    <td><span class="status-ok">Wages</span></td>
                    <td>{$type_counts.wages.count|default:0}</td>
                    <td>{$type_counts.wages.total_value|default:0} gold</td>
                    <td>Wages paid to workers</td>
                </tr>
                <tr>
                    <td><span class="status-warning">Tax</span></td>
                    <td>{$type_counts.tax.count|default:0}</td>
                    <td>{$type_counts.tax.total_value|default:0} gold</td>
                    <td>Taxes collected from citizens</td>
                </tr>
                <tr>
                    <td><span class="status-bad">Production Cost</span></td>
                    <td>{$type_counts.production_cost.count|default:0}</td>
                    <td>{$type_counts.production_cost.total_value|default:0} gold</td>
                    <td>Cost of producing goods</td>
                </tr>
                <tr>
                    <td><span class="status-bad">Upkeep Cost</span></td>
                    <td>{$type_counts.upkeep_cost.count|default:0}</td>
                    <td>{$type_counts.upkeep_cost.total_value|default:0} gold</td>
                    <td>Building maintenance costs</td>
                </tr>
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td><strong>{$total_transactions}</strong></td>
                    <td><strong>{$total_value}</strong> gold</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- All Transactions Table -->
    <div class="section-card">
        <h2>Recent Transactions</h2>
        {if $transactions}
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Good</th>
                    <th>Quantity</th>
                    <th>Price/Unit</th>
                    <th>Total</th>
                    <th>Seller</th>
                    <th>Buyer</th>
                </tr>
            </thead>
            <tbody>
                {foreach $transactions as $transaction}
                <tr>
                    <td>{$transaction.transaction_date|truncate:10:''}</td>
                    <td>
                        {if $transaction.transaction_type == 'sale'}
                            <span class="status-good">Sale</span>
                        {elseif $transaction.transaction_type == 'wages'}
                            <span class="status-ok">Wages</span>
                        {elseif $transaction.transaction_type == 'tax'}
                            <span class="status-warning">Tax</span>
                        {elseif $transaction.transaction_type == 'production_cost'}
                            <span class="status-bad">Production</span>
                        {else}
                            <span class="status-bad">Upkeep</span>
                        {/if}
                    </td>
                    <td>{$transaction.good_name|default:'-'}</td>
                    <td>{$transaction.quantity}</td>
                    <td>{$transaction.price_per_unit} gold</td>
                    <td><strong>{$transaction.total_price}</strong> gold</td>
                    <td>
                        {if $transaction.seller_name}
                            {$transaction.seller_name}
                        {else}
                            <em>Market</em>
                        {/if}
                    </td>
                    <td>
                        {if $transaction.buyer_name}
                            {$transaction.buyer_name}
                        {else}
                            <em>Treasury</em>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p class="empty-message">No transactions recorded yet. Buy or sell goods at the market to see transactions here.</p>
        {/if}
    </div>
</div>

<div class="page-actions">
    <a href="/economy" class="btn btn-primary">Go to Market</a>
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

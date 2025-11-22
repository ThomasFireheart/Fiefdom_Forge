{extends file="layout.tpl"}

{block name="title"}Achievements - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Achievements</h1>
    <p>Your progress and accomplishments in ruling the realm</p>
</div>

<div class="stats-dashboard">
    <!-- Achievements Overview Table -->
    <div class="section-card">
        <h2>Achievements Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Achievements Unlocked</td>
                    <td><strong>{$unlocked_count}</strong></td>
                </tr>
                <tr>
                    <td>Total Achievements</td>
                    <td><strong>{$total_count}</strong></td>
                </tr>
                <tr>
                    <td>Completion</td>
                    <td>
                        {if $completion_percent >= 100}
                            <span class="status-good">{$completion_percent}% (Complete!)</span>
                        {elseif $completion_percent >= 75}
                            <span class="status-good">{$completion_percent}%</span>
                        {elseif $completion_percent >= 50}
                            <span class="status-ok">{$completion_percent}%</span>
                        {elseif $completion_percent >= 25}
                            <span class="status-warning">{$completion_percent}%</span>
                        {else}
                            <span class="status-bad">{$completion_percent}%</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- All Achievements Summary Table -->
    <div class="section-card">
        <h2>All Achievements</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Achievement</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                {foreach $achievements as $achievement}
                <tr class="{if $achievement.unlocked}achievement-unlocked{/if}">
                    <td><strong>{$achievement.name}</strong></td>
                    <td>{$categories[$achievement.category]|default:$achievement.category}</td>
                    <td>{$achievement.description}</td>
                    <td>
                        {if $achievement.unlocked}
                            <span class="status-good">Unlocked</span>
                        {else}
                            <span class="status-warning">Locked</span>
                        {/if}
                    </td>
                    <td>
                        {if $achievement.unlocked}
                            <span class="status-good">100%</span>
                        {else}
                            {if $achievement.progress >= 75}
                                <span class="status-ok">{$achievement.progress}%</span>
                            {elseif $achievement.progress >= 50}
                                <span class="status-warning">{$achievement.progress}%</span>
                            {else}
                                <span class="status-bad">{$achievement.progress}%</span>
                            {/if}
                        {/if}
                    </td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="5" class="empty-message">No achievements available</td>
                </tr>
                {/foreach}
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td colspan="2"></td>
                    <td><strong>{$unlocked_count} / {$total_count}</strong></td>
                    <td><strong>{$completion_percent}%</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Category Tables -->
    {foreach $categories as $category_id => $category_name}
    <div class="section-card">
        <h2>{$category_name} Achievements</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Achievement</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                {assign var="category_count" value=0}
                {assign var="category_unlocked" value=0}
                {foreach $achievements as $achievement}
                    {if $achievement.category == $category_id}
                    {assign var="category_count" value=$category_count+1}
                    {if $achievement.unlocked}{assign var="category_unlocked" value=$category_unlocked+1}{/if}
                <tr class="{if $achievement.unlocked}achievement-unlocked{/if}">
                    <td><strong>{$achievement.name}</strong></td>
                    <td>{$achievement.description}</td>
                    <td>
                        {if $achievement.unlocked}
                            <span class="status-good">Unlocked</span>
                        {else}
                            <span class="status-warning">Locked</span>
                        {/if}
                    </td>
                    <td>
                        {if $achievement.unlocked}
                            <span class="status-good">100%</span>
                        {else}
                            {if $achievement.progress >= 75}
                                <span class="status-ok">{$achievement.progress}%</span>
                            {elseif $achievement.progress >= 50}
                                <span class="status-warning">{$achievement.progress}%</span>
                            {else}
                                <span class="status-bad">{$achievement.progress}%</span>
                            {/if}
                        {/if}
                    </td>
                </tr>
                    {/if}
                {/foreach}
            </tbody>
        </table>
    </div>
    {/foreach}
</div>

<div class="page-actions">
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

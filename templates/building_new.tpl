{extends file="layout.tpl"}

{block name="title"}Construct Building - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Construct New Building</h1>
    <p>Treasury: {$stats.treasury} gold</p>
</div>

<div class="form-container">
    <form method="POST" action="/buildings/create" class="main-form">
        {$csrf_field nofilter}
        <div class="form-group">
            <label for="name">Building Name</label>
            <input type="text" id="name" name="name" required placeholder="Enter building name">
        </div>

        <div class="form-group">
            <label for="area_id">Location (Area)</label>
            <select name="area_id" id="area_id" required>
                <option value="">-- Select Area --</option>
                {foreach $areas as $area}
                    <option value="{$area->getId()}">{$area->getName()}</option>
                {/foreach}
            </select>
        </div>

        <div class="form-group">
            <label>Building Type</label>
            <div class="template-grid">
                {foreach $templates as $key => $template}
                    <label class="template-option {if !$template.unlocked}locked{/if}">
                        <input type="radio" name="template" value="{$key}" required {if !$template.unlocked}disabled{/if}>
                        <div class="template-card {if !$template.unlocked}template-locked{/if}">
                            {if !$template.unlocked}
                                <div class="lock-overlay">
                                    <span class="lock-icon">Locked</span>
                                    <span class="unlock-hint">Requires: {$template.required_achievement_name}</span>
                                </div>
                            {/if}
                            <h3>{$template.name}</h3>
                            <p class="template-type">{$template.type|capitalize}</p>
                            <p class="template-details">
                                Capacity: {$template.capacity}<br>
                                Upkeep: {$template.upkeep} gold/month
                            </p>
                            {if $template.description}
                            <p class="template-desc">{$template.description|truncate:80:"..."}</p>
                            {/if}
                            <p class="template-cost">{$template.cost} gold</p>
                        </div>
                    </label>
                {/foreach}
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Construct Building</button>
            <a href="/buildings" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.template-option.locked {
    opacity: 0.6;
    cursor: not-allowed;
}
.template-card.template-locked {
    position: relative;
    background: var(--color-muted);
}
.lock-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: white;
    border-radius: 4px;
    padding: 1rem;
    text-align: center;
}
.lock-icon {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}
.unlock-hint {
    font-size: 0.85rem;
    opacity: 0.9;
}
</style>
{/block}

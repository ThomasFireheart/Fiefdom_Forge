{extends file="layout.tpl"}

{block name="title"}Chronicle - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>Chronicle of Events</h1>
    <p>A record of all happenings in your realm</p>
</div>

<div class="stats-dashboard">
    <!-- Events Overview Table -->
    <div class="section-card">
        <h2>Events Overview</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Events</td>
                    <td><strong>{$events|count}</strong></td>
                </tr>
                <tr>
                    <td>Positive Events</td>
                    <td><span class="status-good">{$event_counts.positive|default:0}</span></td>
                </tr>
                <tr>
                    <td>Negative Events</td>
                    <td><span class="status-bad">{$event_counts.negative|default:0}</span></td>
                </tr>
                <tr>
                    <td>Neutral Events</td>
                    <td><span class="status-ok">{$event_counts.neutral|default:0}</span></td>
                </tr>
                <tr>
                    <td>Special Events</td>
                    <td><span class="status-warning">{$event_counts.special|default:0}</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Events by Category Table -->
    <div class="section-card">
        <h2>Events by Category</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr class="event-row-positive">
                    <td><span class="status-good">Positive</span></td>
                    <td>{$event_counts.positive|default:0}</td>
                    <td>Good fortune, births, prosperity, discoveries</td>
                </tr>
                <tr class="event-row-negative">
                    <td><span class="status-bad">Negative</span></td>
                    <td>{$event_counts.negative|default:0}</td>
                    <td>Disasters, deaths, hardships, misfortune</td>
                </tr>
                <tr class="event-row-neutral">
                    <td><span class="status-ok">Neutral</span></td>
                    <td>{$event_counts.neutral|default:0}</td>
                    <td>Daily happenings, routine events</td>
                </tr>
                <tr class="event-row-special">
                    <td><span class="status-warning">Special</span></td>
                    <td>{$event_counts.special|default:0}</td>
                    <td>Unique events, achievements, milestones</td>
                </tr>
                <tr class="table-total">
                    <td><strong>Total</strong></td>
                    <td><strong>{$events|count}</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Admin Event Trigger -->
    {if $is_admin}
    <div class="section-card">
        <h2>Trigger Event (Admin)</h2>
        <p class="section-description">Manually trigger an event in your realm.</p>
        <form method="POST" action="/events/trigger" class="inline-form">
            {$csrf_field nofilter}
            <div class="form-row">
                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type" required>
                        <optgroup label="Positive Events">
                            {foreach $available_events as $evt}
                                {if $evt.category == 'positive'}
                                    <option value="{$evt.id}">{$evt.name}</option>
                                {/if}
                            {/foreach}
                        </optgroup>
                        <optgroup label="Negative Events">
                            {foreach $available_events as $evt}
                                {if $evt.category == 'negative'}
                                    <option value="{$evt.id}">{$evt.name}</option>
                                {/if}
                            {/foreach}
                        </optgroup>
                        <optgroup label="Neutral Events">
                            {foreach $available_events as $evt}
                                {if $evt.category == 'neutral'}
                                    <option value="{$evt.id}">{$evt.name}</option>
                                {/if}
                            {/foreach}
                        </optgroup>
                        <optgroup label="Special Events">
                            {foreach $available_events as $evt}
                                {if $evt.category == 'special'}
                                    <option value="{$evt.id}">{$evt.name}</option>
                                {/if}
                            {/foreach}
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Trigger Event</button>
                </div>
            </div>
        </form>
    </div>
    {/if}

    <!-- Filter Buttons -->
    <div class="section-card">
        <h2>Event Chronicle</h2>
        <div class="filter-buttons">
            <button class="btn btn-small filter-btn active" data-filter="all">All Events</button>
            <button class="btn btn-small filter-btn" data-filter="positive">Positive</button>
            <button class="btn btn-small filter-btn" data-filter="negative">Negative</button>
            <button class="btn btn-small filter-btn" data-filter="neutral">Neutral</button>
            <button class="btn btn-small filter-btn" data-filter="special">Special</button>
        </div>

        {if $events}
        <table class="data-table" id="events-table">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Day</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Message</th>
                    <th>Related</th>
                </tr>
            </thead>
            <tbody>
                {foreach $events as $event}
                <tr class="event-row" data-category="{$event.category}">
                    <td>Year {$event.year}</td>
                    <td>Day {$event.day}</td>
                    <td>{$event.type|replace:'_':' '|capitalize}</td>
                    <td>
                        {if $event.category == 'positive'}
                            <span class="status-good">Positive</span>
                        {elseif $event.category == 'negative'}
                            <span class="status-bad">Negative</span>
                        {elseif $event.category == 'neutral'}
                            <span class="status-ok">Neutral</span>
                        {else}
                            <span class="status-warning">Special</span>
                        {/if}
                    </td>
                    <td>{$event.message}</td>
                    <td>
                        {if $event.citizen_id}
                            <a href="/citizen/{$event.citizen_id}" class="btn btn-small btn-secondary">View Citizen</a>
                        {else}
                            -
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p class="empty-message">No events recorded yet. Advance time to see what happens in your realm!</p>
        {/if}
    </div>

    <!-- Events by Year -->
    {if $events}
    {assign var="years" value=[]}
    {foreach $events as $event}
        {if !in_array($event.year, $years)}
            {append var="years" value=$event.year}
        {/if}
    {/foreach}

    {foreach $years as $year}
    <div class="section-card">
        <h2>Year {$year}</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                {foreach $events as $event}
                    {if $event.year == $year}
                <tr>
                    <td>Day {$event.day}</td>
                    <td>{$event.type|replace:'_':' '|capitalize}</td>
                    <td>
                        {if $event.category == 'positive'}
                            <span class="status-good">Positive</span>
                        {elseif $event.category == 'negative'}
                            <span class="status-bad">Negative</span>
                        {elseif $event.category == 'neutral'}
                            <span class="status-ok">Neutral</span>
                        {else}
                            <span class="status-warning">Special</span>
                        {/if}
                    </td>
                    <td>{$event.message}</td>
                </tr>
                    {/if}
                {/foreach}
            </tbody>
        </table>
    </div>
    {/foreach}
    {/if}
</div>

<div class="page-actions">
    <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
{/block}

{block name="scripts"}
<script>
{literal}
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const eventRows = document.querySelectorAll('.event-row');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.dataset.filter;

            eventRows.forEach(row => {
                if (filter === 'all' || row.dataset.category === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
{/literal}
</script>
{/block}

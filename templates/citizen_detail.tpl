{extends file="layout.tpl"}

{block name="title"}{$citizen->getName()} - Fiefdom Forge{/block}

{block name="content"}
<div class="page-header">
    <h1>{$citizen->getName()}</h1>
    <p>{$citizen->getLifeStage()|capitalize} | Age {$citizen->getAge()} | {$citizen->getGender()|capitalize}</p>
</div>

<div class="stats-dashboard">
    <!-- Citizen Overview Table -->
    <div class="section-card">
        <h2>Citizen Overview</h2>
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
                    <td><strong>{$citizen->getName()}</strong></td>
                </tr>
                <tr>
                    <td>Age</td>
                    <td>{$citizen->getAge()} years old</td>
                </tr>
                <tr>
                    <td>Gender</td>
                    <td>{$citizen->getGender()|capitalize}</td>
                </tr>
                <tr>
                    <td>Life Stage</td>
                    <td>
                        {if $citizen->getLifeStage() == 'child'}
                            <span class="status-ok">Child</span>
                        {elseif $citizen->getLifeStage() == 'adult'}
                            <span class="status-good">Adult</span>
                        {else}
                            <span class="status-warning">Elder</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Status Table -->
    <div class="section-card">
        <h2>Status</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Attribute</th>
                    <th>Value</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Health</td>
                    <td>{$citizen->getHealth()}%</td>
                    <td>
                        {if $citizen->getHealth() >= 80}
                            <span class="status-good">Excellent</span>
                        {elseif $citizen->getHealth() >= 60}
                            <span class="status-ok">Good</span>
                        {elseif $citizen->getHealth() >= 40}
                            <span class="status-warning">Fair</span>
                        {else}
                            <span class="status-bad">Poor</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Happiness</td>
                    <td>{$citizen->getHappiness()}%</td>
                    <td>
                        {if $citizen->getHappiness() >= 80}
                            <span class="status-good">Thriving</span>
                        {elseif $citizen->getHappiness() >= 60}
                            <span class="status-ok">Content</span>
                        {elseif $citizen->getHappiness() >= 40}
                            <span class="status-warning">Discontent</span>
                        {else}
                            <span class="status-bad">Miserable</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Wealth</td>
                    <td><strong>{$citizen->getWealth()}</strong> gold</td>
                    <td>
                        {if $citizen->getWealth() >= 100}
                            <span class="status-good">Wealthy</span>
                        {elseif $citizen->getWealth() >= 50}
                            <span class="status-ok">Comfortable</span>
                        {elseif $citizen->getWealth() >= 20}
                            <span class="status-warning">Modest</span>
                        {else}
                            <span class="status-bad">Poor</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Personal Information Table -->
    <div class="section-card">
        <h2>Personal Information</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Attribute</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Marital Status</td>
                    <td>
                        {if $spouse}
                            <span class="status-good">Married</span> to <a href="/citizen/{$spouse->getId()}">{$spouse->getName()}</a>
                        {else}
                            Single
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Can Work</td>
                    <td>
                        {if $citizen->canWork()}
                            <span class="status-good">Yes</span>
                        {else}
                            <span class="status-warning">No</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Current Home</td>
                    <td>
                        {if $home}
                            <a href="/building/{$home->getId()}">{$home->getName()}</a>
                        {else}
                            <span class="status-bad">Homeless</span>
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>Current Job</td>
                    <td>
                        {if $job}
                            <a href="/business/{$job->getId()}">{$job->getName()}</a> ({$job->getType()})
                        {else}
                            <span class="status-warning">Unemployed</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Role Management -->
    <div class="section-card">
        <h2>Role Management</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Current Role</th>
                    <th>Income</th>
                    <th>Prestige</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        {if $current_role}
                            <strong>{$current_role->getName()}</strong>
                            <br><small>{$current_role->getDescription()}</small>
                        {else}
                            <span class="status-warning">No Role Assigned</span>
                        {/if}
                    </td>
                    <td>
                        {if $current_role}
                            +{$current_role->getBaseIncome()} gold
                        {else}
                            -
                        {/if}
                    </td>
                    <td>
                        {if $current_role}
                            +{$current_role->getPrestigeBonus()}
                        {else}
                            -
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        <form method="POST" action="/citizens/assign-role" class="management-form">
            {$csrf_field nofilter}
            <input type="hidden" name="citizen_id" value="{$citizen->getId()}">
            <div class="form-row">
                <div class="form-group">
                    <label for="role_id">Assign Role</label>
                    <select name="role_id" id="role_id">
                        <option value="0">-- No Role --</option>
                        {foreach $available_roles as $r}
                            <option value="{$r.id}" {if $citizen->getRoleId() == $r.id}selected{/if}>
                                {$r.name} (+{$r.base_income}g, +{$r.prestige_bonus}p)
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Housing Management -->
    <div class="section-card">
        <h2>Housing Management</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Current Home</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        {if $home}
                            <a href="/building/{$home->getId()}">{$home->getName()}</a>
                        {else}
                            None
                        {/if}
                    </td>
                    <td>
                        {if $home}
                            <span class="status-good">Housed</span>
                        {else}
                            <span class="status-bad">Homeless</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        <form method="POST" action="/citizens/assign-home" class="management-form">
            {$csrf_field nofilter}
            <input type="hidden" name="citizen_id" value="{$citizen->getId()}">
            <div class="form-row">
                <div class="form-group">
                    <label for="building_id">Assign to Home</label>
                    <select name="building_id" id="building_id">
                        <option value="0">-- Remove from Home --</option>
                        {foreach $available_homes as $h}
                            <option value="{$h.id}" {if $citizen->getHomeBuildingId() == $h.id}selected{/if}>
                                {$h.name} ({$h.occupants}/{$h.capacity} occupants)
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Housing</button>
                </div>
            </div>
        </form>

        {if $available_homes}
        <h3>Available Homes</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Building</th>
                    <th>Occupancy</th>
                    <th>Available Spots</th>
                </tr>
            </thead>
            <tbody>
                {foreach $available_homes as $h}
                <tr>
                    <td>{$h.name}</td>
                    <td>{$h.occupants}/{$h.capacity}</td>
                    <td>
                        {assign var="spots" value=$h.capacity - $h.occupants}
                        {if $spots > 3}
                            <span class="status-good">{$spots} spots</span>
                        {elseif $spots > 1}
                            <span class="status-ok">{$spots} spots</span>
                        {else}
                            <span class="status-warning">{$spots} spot</span>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {/if}
    </div>

    <!-- Employment Management -->
    {if $citizen->canWork()}
    <div class="section-card">
        <h2>Employment Management</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Current Job</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        {if $job}
                            <a href="/business/{$job->getId()}">{$job->getName()}</a>
                        {else}
                            None
                        {/if}
                    </td>
                    <td>
                        {if $job}
                            {$job->getType()|capitalize}
                        {else}
                            -
                        {/if}
                    </td>
                    <td>
                        {if $job}
                            <span class="status-good">Employed</span>
                        {else}
                            <span class="status-warning">Unemployed</span>
                        {/if}
                    </td>
                </tr>
            </tbody>
        </table>

        <form method="POST" action="/citizens/assign-job" class="management-form">
            {$csrf_field nofilter}
            <input type="hidden" name="citizen_id" value="{$citizen->getId()}">
            <div class="form-row">
                <div class="form-group">
                    <label for="business_id">Assign to Job</label>
                    <select name="business_id" id="business_id">
                        <option value="0">-- Remove from Job --</option>
                        {foreach $available_jobs as $j}
                            <option value="{$j.id}" {if $citizen->getWorkBusinessId() == $j.id}selected{/if}>
                                {$j.name} ({$j.type}) - {$j.workers}/{$j.employees_capacity} workers
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Employment</button>
                </div>
            </div>
        </form>

        {if $available_jobs}
        <h3>Available Jobs</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Business</th>
                    <th>Type</th>
                    <th>Workers</th>
                    <th>Available Positions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $available_jobs as $j}
                <tr>
                    <td>{$j.name}</td>
                    <td>{$j.type|capitalize}</td>
                    <td>{$j.workers}/{$j.employees_capacity}</td>
                    <td>
                        {assign var="positions" value=$j.employees_capacity - $j.workers}
                        {if $positions > 3}
                            <span class="status-good">{$positions} positions</span>
                        {elseif $positions > 1}
                            <span class="status-ok">{$positions} positions</span>
                        {else}
                            <span class="status-warning">{$positions} position</span>
                        {/if}
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {/if}
    </div>
    {/if}

    <!-- Skills Section -->
    <div class="section-card">
        <h2>Skills & Abilities</h2>
        {if $skills}
        {assign var="skill_types" value=['crafting' => 'Crafting', 'gathering' => 'Gathering', 'combat' => 'Combat', 'social' => 'Social']}
        {foreach $skill_types as $type_key => $type_name}
        <h3>{$type_name} Skills</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Skill</th>
                    <th>Level</th>
                    <th>Proficiency</th>
                </tr>
            </thead>
            <tbody>
                {foreach $skills as $skill}
                {if $skill.type == $type_key}
                <tr>
                    <td>
                        <strong>{$skill.name}</strong>
                        <br><small>{$skill.description}</small>
                    </td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {$skill.level}%"></div>
                        </div>
                        {$skill.level}/100
                    </td>
                    <td>
                        {if $skill.level == 0}
                            <span class="status-bad">Untrained</span>
                        {elseif $skill.level < 25}
                            <span class="status-warning">Novice</span>
                        {elseif $skill.level < 50}
                            <span class="status-ok">Apprentice</span>
                        {elseif $skill.level < 75}
                            <span class="status-good">Journeyman</span>
                        {elseif $skill.level < 100}
                            <span class="status-good">Expert</span>
                        {else}
                            <span class="status-good">Master</span>
                        {/if}
                    </td>
                </tr>
                {/if}
                {/foreach}
            </tbody>
        </table>
        {/foreach}
        {else}
        <p class="empty-message">No skills data available.</p>
        {/if}
    </div>
</div>

<div class="page-actions">
    <a href="/citizens" class="btn btn-secondary">Back to Citizens</a>
</div>
{/block}

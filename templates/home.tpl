{extends file="layout.tpl"}

{block name="title"}Fiefdom Forge - Medieval City Simulation{/block}

{block name="content"}
<div class="home-hero">
    <h1>Welcome to Fiefdom Forge</h1>
    <p class="hero-tagline">Build your medieval kingdom from the ground up</p>

    <div class="hero-description">
        <p>
            In Fiefdom Forge, you'll manage citizens, grow your economy, construct buildings,
            and guide your settlement through the ages. Watch your people live, work, and thrive
            as you make decisions that shape the future of your realm.
        </p>
    </div>

    <div class="hero-features">
        <div class="feature">
            <h3>Manage Citizens</h3>
            <p>Guide your people through births, marriages, careers, and life events.</p>
        </div>
        <div class="feature">
            <h3>Build Your City</h3>
            <p>Construct houses, businesses, farms, and public buildings.</p>
        </div>
        <div class="feature">
            <h3>Grow Your Economy</h3>
            <p>Trade goods, collect taxes, and manage your treasury.</p>
        </div>
    </div>

    <div class="hero-cta">
        <a href="/register" class="btn btn-primary btn-large">Start Your Kingdom</a>
        <a href="/login" class="btn btn-secondary btn-large">Continue Your Reign</a>
    </div>
</div>
{/block}

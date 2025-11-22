{extends file="layout.tpl"}

{block name="title"}Login - Fiefdom Forge{/block}

{block name="content"}
<div class="auth-container">
    <div class="auth-box">
        <h1>Login to Your Fiefdom</h1>

        {if $error}
            <div class="alert alert-error">{$error}</div>
        {/if}

        <form method="POST" action="/login" class="auth-form">
            {$csrf_field nofilter}
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required
                       value="{$username|default:''}" autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Enter Your Realm</button>
        </form>

        <p class="auth-link">
            Don't have an account? <a href="/register">Create one</a>
        </p>
    </div>
</div>
{/block}

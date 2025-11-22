{extends file="layout.tpl"}

{block name="title"}Register - Fiefdom Forge{/block}

{block name="content"}
<div class="auth-container">
    <div class="auth-box">
        <h1>Forge Your Fiefdom</h1>

        {if $error}
            <div class="alert alert-error">{$error}</div>
        {/if}

        <form method="POST" action="/register" class="auth-form">
            {$csrf_field nofilter}
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       minlength="3" maxlength="50" value="{$username|default:''}" autofocus>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="{$email|default:''}">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small>At least 6 characters</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Your Kingdom</button>
        </form>

        <p class="auth-link">
            Already have an account? <a href="/login">Login here</a>
        </p>
    </div>
</div>
{/block}

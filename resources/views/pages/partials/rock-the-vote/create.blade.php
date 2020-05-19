<h3>Import configuration</h3>
<h4>Users</h4>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">Email subscription topics</label>
    <div class="col-sm-9">
        <p class="form-control-static"><code>{{ $config['user']['email_subscription_topics'] }}</code></p>
        <small class="form-text text-muted">
          The email subscription topics to subscribe new users to, if they have opted-in to receive emails from DS.
        </small>
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">SMS subscription topics</label>
    <div class="col-sm-9">
        <p class="form-control-static"><code>{{ $config['user']['sms_subscription_topics'] }}</code></p>
        <small SMS="form-text text-muted">
          The SMS subscription topics to subscribe new users to, if they have opted-in to receive texts from DS.
        </small>
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">User source detail</label>
    <div class="col-sm-9">
        <p class="form-control-static"><code>{{ $config['user']['source_detail'] }}</code></p>
        <small class="form-text text-muted">
          The source details we will store on new users.
        </small>
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">Send activate account email</label>
    <div class="col-sm-9">
        <p class="form-control-static">{{ $config['reset']['enabled'] ? 'ON' : 'OFF' }}</p>
        <small class="form-text text-muted">
          Sending can be disabled via the <code>ROCK_THE_VOTE_RESET_ENABLED</code> config var.
        </small>
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">Activate account email template</label>
    <div class="col-sm-9">
        <p class="form-control-static"><code>{{ $config['reset']['type'] }}</code></p>
        <small class="form-text text-muted">
          The type of password reset email to send, if ON. See <a href="https://github.com/DoSomething/northstar/blob/master/documentation/endpoints/resets.md" target="_blank">Northstar documentation</a>.
        </small>
    </div>
</div>

<h4>Posts</h4>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">Action ID</label>
    <div class="col-sm-9">
        <p class="form-control-static">{{ $config['post']['action_id'] }}</p>
        <small class="form-text text-muted">
          The action ID to get/create a voter registration post for the user.
        </small>
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">Type</label>
    <div class="col-sm-9">
        <p class="form-control-static"><code>{{ $config['post']['type'] }}</code></p>
        <small class="form-text text-muted">
          The <code>type</code> parameter to use when checking existing post for User and Action ID's.
        </small>
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-form-label">Source</label>
    <div class="col-sm-9">
        <p class="form-control-static"><code>{{ $config['post']['source'] }}</code></p>
        <small class="form-text text-muted">
          The <code>source</code> to save for the post, if creating.
        </small>
    </div>
</div>

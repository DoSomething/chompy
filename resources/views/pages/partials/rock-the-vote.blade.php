<div>
    <h1>Rock The Vote</h1>
    <p>Creates/updates users and their voter registration post via CSV from Rock The Vote.</p>
    <h4>Users</h4>
    <dl class="dl-horizontal">
        <dt>Email subscriptions</dt><dd>{{ $config['user']['email_subscription_topics'] }}</dd>
        <dt>Reset email enabled</dt><dd>{{ $config['reset']['enabled'] ? 'true' : 'false' }}</dd>
        <dt>Reset email type</dt><dd>{{ $config['reset']['type'] }}</dd>
    </dl>
    <h4>Posts</h4>
    <dl class="dl-horizontal">
        <dt>Action ID</dt><dd>{{ $config['post']['action_id'] }}</dd>
        <dt>Type</dt><dd>{{ $config['post']['type'] }}</dd>
        <dt>Source</dt><dd>{{ $config['post']['source'] }}</dd>
    </dl>
</div>

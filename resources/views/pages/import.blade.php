@extends('layouts.master')

@section('main_content')

@if ($type === \Chompy\ImportType::$rockTheVote)
    <div>
        <h1>Rock The Vote</h1>
        <p>Creates/updates users and their voter registration post via CSV from Rock The Vote.</p>
        <h4>Users</h4>
        <dl class="dl-horizontal">
            <dt>Source</dt><dd>{{ get_user_source() }}</dd>
            <dt>Email subscriptions</dt><dd>{{ $userConfig['email_subscription_topics'] }}</dd>
            <dt>Reset email enabled</dt><dd>{{ $resetConfig['enabled'] ? 'true' : 'false'}}</dd>
            <dt>Reset email type</dt><dd>{{ $resetConfig['type'] }}</dd>
        </dl>
        <h4>Posts</h4>
        <dl class="dl-horizontal">
            <dt>Action ID</dt><dd>{{ $postConfig['action_id'] }}</dd>
            <dt>Type</dt><dd>{{ $postConfig['type'] }}</dd>
            <dt>Source</dt><dd>{{ $postConfig['source'] }}</dd>
        </dl>
        <form action={{url('/import')}} method="post" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="form-group">
                <div class="input-group">
                    <label class="input-group-btn">
                        <span class="btn btn-default">
                            Select CSV <input type="file" name="upload-file" style="display: none;" multiple>
                        </span>
                    </label>
                    <input type="hidden" name="import-type" value={{ app('request')->input('type') }}>
                    <input type="text" class="form-control" readonly>
                </div>
            </div>
            <div>
                <input type="submit" class="btn btn-primary" value="Import">
            </div>
        </form>
    </div>
    <h2>Progress Log</h2>
    <div id="messages">
        <!--Messages goes here-->
    </div>
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
    </div>
@endif

@endsection

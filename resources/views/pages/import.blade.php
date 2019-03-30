@extends('layouts.master')

@section('main_content')

<div>
    <form action={{ route('import.store', ['importType' => $importType]) }} method="post" enctype="multipart/form-data">
        {{ csrf_field() }}
        @if ($importType === \Chompy\ImportType::$rockTheVote)
            @include('pages.partials.rock-the-vote', ['config' => $config])
        @elseif ($importType === \Chompy\ImportType::$emailSubscription)
            @include('pages.partials.email-subscription')
        @endif
        <div class="form-group">
            <h3>Upload</h3>
            <div class="input-group">
                <label class="input-group-btn">
                    <span class="btn btn-default">
                        Select CSV <input type="file" name="upload-file" style="display: none;" multiple>
                    </span>
                </label>
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


@endsection

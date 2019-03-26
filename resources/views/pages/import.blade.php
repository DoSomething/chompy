@extends('layouts.master')

@section('main_content')

<div>
    <h1>{{ $title }}</h1>
    @if ($type === \Chompy\ImportType::$rockTheVote)
    @include('pages.partials.rock-the-vote', ['config' => $config])
    @endif
    <form action={{ app('request')->url() }} method="post" enctype="multipart/form-data">
        {{ csrf_field() }}
        <div class="form-group">
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

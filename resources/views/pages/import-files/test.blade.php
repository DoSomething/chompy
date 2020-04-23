@extends('layouts.master')

@section('title', 'Import test')

@section('main_content')

<div>
    <h1>Test Import</h1>
    <p>
        Use this form to test importing a <code>{{$importType}}</code> record.
    <p>
    @if (config('import.import_test_form_enabled') == 'true')
        <div>
            <form action={{ route('import.store', ['importType' => $importType]) }} method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                @if ($importType === \Chompy\ImportType::$rockTheVote)
                    @include('pages.partials.rock-the-vote.test')
                    <div>
                        <input type="submit" class="btn btn-primary btn-lg" value="Submit">
                    </div>
                    @include('pages.partials.rock-the-vote.create', ['config' => $config])
                @endif
            </form>
        </div>
    @else
        <p>This feature is currently disabled.</p>
    @endif

</div>


@endsection

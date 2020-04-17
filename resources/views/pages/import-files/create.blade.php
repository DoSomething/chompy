@extends('layouts.master')

@section('title', 'Import test')

@section('main_content')

<div>
    <h1>Test Import</h1>
    <p>
        Use this form to test importing a <code>{{$importType}}</code> record.
    <p>
    <div>
        <form action={{ route('import.store', ['importType' => $importType]) }} method="post" enctype="multipart/form-data">
            {{ csrf_field() }}
            @if ($importType === \Chompy\ImportType::$rockTheVote)
                @include('pages.partials.rock-the-vote.test')
            @endif
            <div>
                <input type="submit" class="btn btn-primary btn-lg" value="Submit">
            </div>
        </form>
    </div>
</div>


@endsection

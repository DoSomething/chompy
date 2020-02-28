@extends('layouts.master')

@section('main_content')

<div>
    <h1>
        {{$importFile->created_at}}
    </h1>
    <p>
        <strong>{{$importFile->import_type}}</strong> ({{$importFile->row_count}} rows)
    </p>
    @if ($importFile->import_type === \Chompy\ImportType::$rockTheVote)
        @include('pages.partials.rock-the-vote.logs', ['rows' => $rows, 'user_id' => null])
    @endif
</div>

@stop

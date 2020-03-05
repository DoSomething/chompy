@extends('layouts.master')

@section('main_content')

<div>
    <h1>
        Report #{{$id}}
    </h1>
    <p>
        Status: <strong>{{$report->status}}</strong>
    </p>
    <p>
        Total rows: <strong>{{$report->record_count}}</strong>
    </p>
</div>

@stop

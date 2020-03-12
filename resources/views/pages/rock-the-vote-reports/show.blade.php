@extends('layouts.master')

@section('title', 'Rock The Vote report')

@section('main_content')

<div>
    <h1>
        Report #{{$report->id}}
    </h1>
    <p>
        Since: <strong>{{$report->since}}</strong>
    </p>
    <p>
        Before: <strong>{{$report->before}}</strong>
    </p>
    <p>
        Status: <strong>{{$report->status}}</strong>
    </p>
    <p>
        Total rows: <strong>{{$report->row_count}}</strong>
    </p>
    @if ($report->status === 'building')
    <p>
        Progress: <strong>{{$report->percentage}}% </strong>(processed <strong>{{$report->current_index}}</strong> rows)
    </p>
    @endif
    <hr />
    <small>This report was created by {{$report->user_id}} on {{$report->created_at}}.</small>
</div>

@stop

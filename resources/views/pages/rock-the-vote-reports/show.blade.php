@extends('layouts.master')

@section('title', 'Rock The Vote report')

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
    @if ($report->status === 'building')
    <p>
        Progress: <strong>{{round(($report->current_index * 100) / $report->record_count)}}% </strong>(processed <strong>{{$report->current_index}}</strong> rows)
    </p>
    @endif
</div>

@stop

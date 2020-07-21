@extends('layouts.master')

@section('title', 'Rock The Vote report')

@section('main_content')

<div>
    <h1>
        Report #{{$report->id}} <small>{{$report->row_count}} rows</small>
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
    @if ($report->status === 'building')
        <p>
            Progress: <strong>{{$report->percentage}}% </strong>(processed <strong>{{$report->current_index}}</strong> rows)
        </p>
    @elseif ($report->status === 'failed')
        @if ($report->retry_report_id)
            <p>
                Retry Report: <a href="{{ route('rock-the-vote-reports.show', $report->retry_report_id) }}"><strong>#{{$report->retry_report_id}}</strong></a>.
            </p>
        @else
            <form method="POST" action="{{ route('rock-the-vote-reports.update', $report->id) }}">
                {{ csrf_field() }}
                {{ method_field('PATCH') }}
                <div>
                    <input type="submit" class="btn btn-primary" value="Retry">
                </div>
            </form>
        @endif
    @elseif ($report->status === 'complete')
        <p>
            Imported: <strong>{{$report->dispatched_at}}</strong>
        </p>
    @endif
    <hr />
    <small>This report was created by {{$report->user_id}} on {{$report->created_at}}.</small>
</div>

@stop

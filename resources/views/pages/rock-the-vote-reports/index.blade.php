@extends('layouts.master')

@section('title', 'Rock The Vote reports')

@section('main_content')

<div>
    <h1>Rock The Vote Reports <small><a class="pull-right" href="/rock-the-vote-reports/create">Create</a></small></h1>
    <table class="table">
        <thead>
          <tr class="row">
            <th class="col-md-2">ID</th>
            <th class="col-md-3">Since</th>
            <th class="col-md-3">Before</th>
            <th class="col-md-2">Status</th>
            <th class="col-md-2">Imported</th>
          </tr>
        </thead>
        @foreach($data as $key => $report)
            <tr class="row">
              <td class="col-md-2">
                <a href="{{ route('rock-the-vote-reports.show', $report) }}">
                  <strong>{{$report->id}}</strong>
                </a>
              </td>
              <td class="col-md-3">
                {{$report->since}}
              </td> 
              <td class="col-md-3">
                {{$report->before}}
              </td>
              <td class="col-md-2">
                @if ($report->retry_report_id)
                  retried <a href="{{ route('rock-the-vote-reports.show', $report->retry_report_id) }}">
                    #{{ $report->retry_report_id }}
                  </a>
                @else
                  {{$report->status}}
                @endif
              </td>
              <td class="col-md-2">
                {{$report->dispatched_at}}
              </td>    
            </tr>
        @endforeach
    </table>
    {{$data->links()}}
</div>

@stop

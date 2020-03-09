@extends('layouts.master')

@section('main_content')

<div>
    <table class="table">
        <thead>
          <tr class="row">
            <th class="col-md-2">ID</th>
            <th class="col-md-3">Since</th>
            <th class="col-md-3">Before</th>
            <th class="col-md-2">Status</th>
            <th class="col-md-2">Created</th>
          </tr>
        </thead>
        @foreach($data as $key => $report)
            <tr class="row">
              <td class="col-md-2">
                <a href="/rock-the-vote/reports/{{$report->id}}">
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
                {{$report->status}}
              </td>
              <td class="col-md-2">
                {{$report->created_at}}
              </td>    
            </tr>
        @endforeach
    </table>
    {{$data->links()}}
</div>

@stop

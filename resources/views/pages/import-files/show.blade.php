@extends('layouts.master')

@section('main_content')

<div>
  <h1>
    {{$importFile->filepath}}
  </h1>
    <table class="table">
        <thead>
          <tr class="row">
            <th class="col-md-2">Started Registration</th>
            <th class="col-md-2">User</th>
            <th class="col-md-1">Status</th>
            <th class="col-md-3">Tracking Code</th>
            <th class="col-md-2">Finish With State</th>
            <th class="col-md-2">Pre-Registered</th>

          </tr>
        </thead>
        @foreach($rows as $key => $row)
            <tr class="row">
              <td class="col-md-2">
                {{$row->started_registration}}
              </td>    
              <td class="col-md-2">
                {{$row->user_id}}
              </td> 
              <td class="col-md-1">
                {{$row->status}}
              </td>
              <td class="col-md-3">
                {{$row->tracking_source}}
              </td>
              <td class="col-md-2">
                {{$row->finish_with_state}}
              </td>
              <td class="col-md-2">
                {{$row->pre_registered}}
              </td> 
            </tr>
        @endforeach
    </table>
    {{$rows->links()}}
</div>

@stop

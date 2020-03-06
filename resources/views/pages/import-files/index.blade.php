@extends('layouts.master')

@section('main_content')

<div>
    <table class="table">
        <thead>
          <tr class="row">
            <th class="col-md-3">Created</th>
            <th class="col-md-3">Import type</th>
            <th class="col-md-2">Row count</th>
            <th class="col-md-4">Created by</th>
          </tr>
        </thead>
        @foreach($data as $key => $importFile)
            <tr class="row">
              <td class="col-md-3">
                <a href="/import-files/{{$importFile->id}}">
                  <strong>{{$importFile->created_at}}</strong>
                </a>
              </td>
              <td class="col-md-3">
                {{$importFile->import_type}}
              </td> 
              <td class="col-md-2">
                {{$importFile->row_count}}
              </td>
              <td class="col-md-4">
                {{$importFile->user_id ? $importFile->user_id : 'Console'}}
                @if ($importFile->options)
                  <ul>
                  @foreach (json_decode($importFile->options) as $key => $value)
                    <li>{{$key}}: <strong>{{$value}}</strong></li>
                  @endforeach
                  </ul>
                @endif
              </td>     
            </tr>
        @endforeach
    </table>
    {{$data->links()}}
</div>

@stop

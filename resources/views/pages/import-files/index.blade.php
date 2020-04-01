@extends('layouts.master')

@section('title', 'Imports')

@section('main_content')

<div>
    <table class="table">
        <thead>
          <tr class="row">
            <th class="col-md-3">Created</th>
            <th class="col-md-3">Import type</th>
            <th class="col-md-3">Import count</th>
            <th class="col-md-3">Created by</th>
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
                @if ($importFile->options)
                  <ul>
                  @foreach (json_decode($importFile->options) as $key => $value)
                    @if ($key === 'report_id')
                      <li>
                        <a href="/rock-the-vote-reports/{{$value}}">
                          <strong>#{{$value}}</strong>
                        </a>
                      </li>
                    @else
                      <li>{{$key}}: <strong>{{$value}}</strong></li>
                    @endif
                  @endforeach
                  </ul>
                @endif
              </td> 
              <td class="col-md-3">
                {{$importFile->import_count}}
              </td>
              <td class="col-md-3">
                {{$importFile->user_id ? $importFile->user_id : 'Console'}}
              </td>     
            </tr>
        @endforeach
    </table>
    {{$data->links()}}
</div>

@stop

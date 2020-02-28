@extends('layouts.master')

@section('main_content')

<div>
    <p><strong>Note:</strong> We didn't start saving this data locally until Feb 27, 2020.</p>
    <table class="table">
        <thead>
          <tr class="row">
            <th class="col-md-3">Created</th>
            <th class="col-md-3">Import type</th>
            <th class="col-md-3">Row count</th>
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
              </td> 
              <td class="col-md-3">
                {{$importFile->row_count}}
              </td>
              <td class="col-md-3">
                {{$importFile->user_id}}
              </td>     
            </tr>
        @endforeach
    </table>
    {{$data->links()}}
</div>

@stop

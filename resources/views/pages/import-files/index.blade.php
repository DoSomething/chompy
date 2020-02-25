@extends('layouts.master')

@section('main_content')

<div>
    <table class="table">
        <thead>
          <tr class="row">
            <th class="col-md-3">Uploaded at</th>
            <th class="col-md-3">Uploaded by</th>
            <th class="col-md-3">Import type</th>
            <th class="col-md-3">Row count</th>
          </tr>
        </thead>
        @foreach($data as $key => $importFile)
            <tr class="row">
              <td class="col-md-3">
                <strong>{{$importFile->created_at}}</strong>
              </td>
              <td class="col-md-3">
                {{$importFile->user_id}}
              </td>
              <td class="col-md-3">
                {{$importFile->import_type}}
              </td> 
              <td class="col-md-3">
                {{$importFile->row_count}}
              </td>     
            </tr>
        @endforeach
    </table>
    {{$data->links()}}
</div>

@stop

@extends('layouts.master')

@section('main_content')

<div>
    <table class="table">
    @foreach($data as $key => $row)
        <tr class="row">
          <td class="col-md-2">
            {{$row->id}}
          </td>
          <td class="col-md-4">
            <strong>{{$row->created_at}}</strong>
          </td>
          <td class="col-md-3">
            {{$row->import_type}}
          </td> 
          <td class="col-md-3">
            {{$row->row_count}}
          </td>     
        </tr>
    @endforeach
    </table>
</div>

@stop

@extends('layouts.master')

@section('main_content')

<div>
    @foreach($data as $key => $row)
        <div class="row">
          <div class="col-md-2">{{$key}}</div>
          <div class="col-md-2">{{$row->failed_at}}</div>
          <div class="col-md-8">
            {{print_r(json_decode($row->payload), true)}}
            <br />
            <code>{{substr($row->exception, 0, 255)}}...</code>
          </div>    
        </div>
    @endforeach
    {{$data->links()}}
</div>

@stop

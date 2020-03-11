@extends('layouts.master')

@section('title', 'Failed job')

@section('main_content')

<div>
  <form action={{ url()->current() }} method="post">
    {{csrf_field()}}
    @method('delete')
    <div class="form-group row">
      <label class="col-sm-3 col-form-label">Failed at</label>
      <div class="col-sm-9">{{$data->failed_at}}</div>
    </div>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label">Command</label>
      <div class="col-sm-9">
        <strong>{{$data->commandName}}</strong>
        @isset($data->parameters)
          <ul>
            @foreach ($data->parameters as $key => $value)
              <li><code>{{$key}}</code> {{$value}}</li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
    <div class="form-group row">
      <label class="col-sm-3 col-form-label">Exception</label>
      <div class="col-sm-9">{{$data->exception}}</div>
    </div>
     <div class="form-group row">
        <div class="col-sm-9 col-sm-offset-3">
          <input type="submit" class="btn btn-danger" value="Delete" onclick="return confirm('Are you sure you want to delete this job? This cannot be undone.')">
        </div>   
    </div>
  </form>
</div>

@stop

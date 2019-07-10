@extends('layouts.master')

@section('main_content')

<div>
  <form>
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
  </form>
</div>

@stop

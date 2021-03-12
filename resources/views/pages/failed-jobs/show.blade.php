@extends('layouts.master')

@section('title', 'Failed job')

@section('main_content')

<div>
  <form action={{ url()->current() }} method="post">
    {{csrf_field()}}

    @method('delete')

    <div class="form-group row">
      <label class="col-sm-3 col-form-label">Failed at</label>

      <div class="col-sm-9">{{$failedJob['failed_at']}}</div>
    </div>

    <div class="form-group row">
      <label class="col-sm-3 col-form-label">Command</label>

      <div class="col-sm-9">
        <strong>{{$failedJob['command_name']}}</strong>

        @isset($failedJob['parameters'])
          <code>
            {{json_encode($failedJob['parameters'], TRUE)}}
          </code>
        @endif
      </div>
    </div>

    <div class="form-group row">
      <label class="col-sm-3 col-form-label">Exception</label>

      <div class="col-sm-9">{{$failedJob['exception']}}</div>
    </div>

     <div class="form-group row">
        <div class="col-sm-9 col-sm-offset-3">
          <input type="submit" class="btn btn-danger" value="Delete" onclick="return confirm('Are you sure you want to delete this job? This cannot be undone.')">
        </div>   
    </div>
  </form>
</div>

@stop

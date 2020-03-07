@extends('layouts.master')

@section('main_content')

<div>
    <h1>
        Create Report
    </h1>
    <form method="POST" action="{{ route('reports.store') }}">
        {{ csrf_field()}}
        <div class="form-group row">
            <label for="id" class="col-sm-3 col-form-label" required>
                Report ID
            </label>
            <div class="col-sm-9">
              <input type="text" class="form-control" name="id">
            </div>
        </div>
        <div class="form-group row">
            <label for="id" class="col-sm-3 col-form-label" required>
                Status
            </label>
            <div class="col-sm-9">
              <input type="text" class="form-control" name="status">
            </div>
        </div>
        <div class="form-group row">
            <label for="since" class="col-sm-3 col-form-label" required>
                Since
            </label>
            <div class="col-sm-9">
              <input type="text" class="form-control" name="since">
            </div>
        </div>
        <div class="form-group row">
            <label for="before" class="col-sm-3 col-form-label" required>
                Before
            </label>
            <div class="col-sm-9">
              <input type="text" class="form-control" name="before">
            </div>
        </div>
        <div>
            <input type="submit" class="btn btn-primary btn-lg" value="Create">
        </div>
    </form>
</div>

@stop

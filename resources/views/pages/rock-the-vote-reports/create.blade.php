@extends('layouts.master')

@section('main_content')

<div>
    <h1>Create Report</h1>
    <p>
        Use this form to create a Rock The Vote CSV report of voter registrations.
    <p>
    <p>
        <strong>Note</strong>: It won't automatically be imported yet, you'll still need to find and download it from our Rock The Vote partner portal for now.
    </p> 
    <form method="POST" action="{{ route('reports.store') }}">
        {{ csrf_field()}}
        <div class="form-group row">
            <label for="since" class="col-sm-3 col-form-label" required>
                Since
            </label>
            <div class="col-sm-9">
              <input type="text" class="form-control" name="since">
              <small>e.g. 2020-02-28 12:00</small>
            </div>
        </div>
        <div class="form-group row">
            <label for="before" class="col-sm-3 col-form-label" required>
                Before
            </label>
            <div class="col-sm-9">
              <input type="text" class="form-control" name="before">
              <small>e.g. 2020-02-28 13:00</small>
            </div>
        </div>
        <div>
            <input type="submit" class="btn btn-primary btn-lg" value="Create">
        </div>
    </form>
</div>

@stop
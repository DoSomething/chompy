<div class="form-group row">
  <label for="email" class="col-sm-3 col-form-label" required>Email</label>
  <div class="col-sm-9">
    {!! Form::text('email', request()->get('email'), ['class' => 'form-control']) !!}
  </div>
</div>
<div class="form-group row">
  <label for="mobile" class="col-sm-3 col-form-label" required>Mobile</label>
  <div class="col-sm-9">
    <input type="text" class="form-control" name="mobile" value="{{ old('mobile') }}">
  </div>
</div>
<div class="form-group row">
  <label for="started-registration" class="col-sm-3 col-form-label" required>Started registration</label>
  <div class="col-sm-9">
    {!! Form::date('started-registration', \Carbon\Carbon::now()) !!}
  </div>
</div>
<div class="form-group row">
  <label for="status" class="col-sm-3 col-form-label" required>Status</label>
  <div class="col-sm-9">
      <div class="select">
          {!! Form::select('status', [
              'Rejected' => 'Rejected',
              'Under 18' => 'Under 18',
              'Step 1' => 'Step 1',
              'Step 2' => 'Step 2',
              'Step 3' => 'Step 3',
              'Step 4' => 'Step 4',
              'Complete' => 'Complete',
          ], null, ['placeholder' => '--']) !!}
      </div>
  </div>
</div>

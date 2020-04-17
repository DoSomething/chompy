<div class="form-group row">
  <label for="email" class="col-sm-3 col-form-label" required>Email</label>
  <div class="col-sm-9">
    {!! Form::text('email', $data['email'], ['class' => 'form-control']) !!}
  </div>
</div>
<div class="form-group row">
  <label for="mobile" class="col-sm-3 col-form-label" required>Mobile</label>
  <div class="col-sm-9">
    <input type="text" class="form-control" name="mobile" value="{{ old('mobile') }}">
  </div>
</div>
<div class="form-group row">
  <label for="referral" class="col-sm-3 col-form-label" required>Referral</label>
  <div class="col-sm-9">
    {!! Form::text('referral', $data['referral'], ['class' => 'form-control']) !!}
  </div>
</div>
<div class="form-group row">
  <label for="started-registration" class="col-sm-3 col-form-label" required>Started registration</label>
  <div class="col-sm-9">
    {!! Form::text('started-registration', $data['started-registration'], ['class' => 'form-control']) !!}
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
          ], 'Step 1', ['placeholder' => '--', 'class' => 'form-control']) !!}
      </div>
  </div>
</div>
<div class="form-group row">
  <label for="finish-with-state" class="col-sm-3 col-form-label" required>Finish with State</label>
  <div class="col-sm-9">
    {!! Form::checkbox('finish-with-state', 'Yes', false) !!}
  </div>
</div>
<div class="form-group row">
  <label for="email-opt-in" class="col-sm-3 col-form-label" required>Email Opt-in</label>
  <div class="col-sm-9">
    {!! Form::checkbox('email-opt-in', 'Yes', false) !!}
  </div>
</div>
<div class="form-group row">
  <label for="sms-opt-in" class="col-sm-3 col-form-label" required>SMS Opt-in</label>
  <div class="col-sm-9">
    {!! Form::checkbox('sms-opt-in', 'Yes', false) !!}
  </div>
</div>

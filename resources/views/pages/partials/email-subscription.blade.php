<div class="form-group">
    <h1>Email subscription</h1>
    <p class="lead">
      Creates/updates users from uploaded CSV, expecting an <code>email</code> column header.
    </p>
</div>
<h3>Users</h3>
<div class="form-group row">
  <label for="source-detail" class="col-sm-3 col-form-label" required>Source detail</label>
  <div class="col-sm-9">
    <input type="text" class="form-control" name="source-detail" placeholder="breakdown_opt_in" value="{{ old('source-detail') }}">
    <small class="form-text text-muted">
      Specify the <code>source_detail</code> for new users that will be created from this upload. 
    </small>
  </div>
</div>
<div class="form-group row">
  <label for="source-detail" class="col-sm-3 col-form-label" required>Subscription topics</label>
  <div class="col-sm-9">
    @foreach ($config['topics'] as $topic)
      <div class="form-check">
        <input class="form-check-input" name="topics[]" type="checkbox" value="{{ $topic }}" id="community">
        <label class="form-check-label" for="{{ $topic }}">
          {{ $topic }}
        </label>
      </div>
    @endforeach
    <small class="form-text text-muted">
      Select the email subscription topics to subscribe new or existing user to.<br />
      <strong>Note</strong> - This will append (not overwrite) subscriptions for existing users.
    </small>
  </div>
</div>

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
    <input type="text" class="form-control" name="source-detail" placeholder="breakdown_opt_in">
    <small class="form-text text-muted">
      Specify the <code>source_detail</code> for new users that will be created from this upload. 
    </small>
  </div>
</div>
<div class="form-group row">
  <label for="source-detail" class="col-sm-3 col-form-label" required>Subscription topic</label>
  <div class="col-sm-9">
    <select class="form-control" name="topic">
      <option value="">-- Select --</option>
      <option value="community">community</option>
      <option value="lifesetyle">lifestyle</option>
      <option value="news">news</option>
      <option value="scholarships">scholarships</option>
    </select>
    <small class="form-text text-muted">
      Select the email subscription topic to subscribe new or existing user to.<br />
      <strong>Note</strong> - This will append (not overwrite) the email subscripton topic for existing users.
    </small>
  </div>
</div>

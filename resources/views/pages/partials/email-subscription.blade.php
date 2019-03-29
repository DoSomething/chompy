<div class="form-group">
    <h1>Email subscription</h1>
    <p>Gets or creates a user and subscribes to selected email subscription topic. Expects an 'email' column header.</p>
    <p>Note: This import will append the selected email subscription topic for existing users (does not overwrite).</p> 
</div>
<div class="form-group row">
  <label for="source-detail" class="col-sm-2 col-form-label" required>Source detail</label>
  <div class="col-sm-10">
    <input type="text" class="form-control" name="source-detail" placeholder="breakdown_opt_in">
  </div>
</div>
<div class="form-group row">
  <label for="source-detail" class="col-sm-2 col-form-label" required>Subscription topic</label>
  <div class="col-sm-10">
    <select class="form-control" name="topic">
      <option value="">-- Select --</option>
      <option value="community">community</option>
      <option value="lifesetyle">lifestyle</option>
      <option value="news">news</option>
      <option value="scholarships">scholarships</option>
    </select>
  </div>
</div>

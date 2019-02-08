@extends('layouts.master')

@section('main_content')

<div>
    <form action={{url('/import')}} method="post" enctype="multipart/form-data">
        {{ csrf_field()}}
        <div class="form-group">
            <div class="input-group">
                <label class="input-group-btn">
                    <span class="btn btn-primary">
                        Browse <input type="file" name="upload-file" style="display: none;" multiple>
                    </span>
                </label>
                <input type="text" class="form-control" readonly>
            </div>
        </div>

        <div class="form-group">
            <label>Import Type</label>
            <div class="form-check">
                <label class="form-check-label">
                    <input name="import-type" class="form-check-input" type="radio" value="turbovote" {{ (isset($_GET['type']) && $_GET['type'] === 'turbovote') ? 'checked' : '' }} >
                    TurboVote
                    <br>
                    <input name="import-type" class="form-check-input" type="radio" value="rock-the-vote" {{ (isset($_GET['type']) && $_GET['type'] === 'rock-the-vote') ? 'checked' : '' }} >
                    Rock the Vote
                    <br>
                    <input name="import-type" class="form-check-input" type="radio" value="facebook" {{ (isset($_GET['type']) && $_GET['type'] === 'facebook') ? 'checked' : '' }} >
                    Facebook Share
                </label>
            </div>
        </div>
        <div>
            <input type="submit" class="btn btn-primary" value="Submit CSV">
        </div>
    </form>
</div>
<h2>Progress Log</h2>
<div id="messages">
    <!--Messages goes here-->
</div>
<div class="progress">
    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
</div>

@endsection

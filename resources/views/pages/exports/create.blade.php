@extends('layouts.master')

@section('title', 'Export')

@section('main_content')

<div>
    <h1>Create Export</h1>

    <p>
        Use this form to download Chompy data.
    <p>

    <form method="POST" action="{{ route('exports.store') }}">
        {{ csrf_field()}}

        <div class="form-check">
            <input class="form-check-input" name="topic" type="radio" value="MutePromotions" checked>

            <label class="form-check-label" for="MutePromotions">Failed jobs - Mute Promotions</label>
        </div>

        <div>
            <input type="submit" class="btn btn-primary btn-lg" value="Download">
        </div>
    </form>
</div>

@stop

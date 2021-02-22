@extends('layouts.master')

@section('main_content')

<div>
    <h1>{{$id}}</h1>

    <p>
        <a href="{{config('services.rogue.url') . '/users/' . $id}}">View user in Rogue</a>
    </p>

    <h3>Mute Promotions Imports</h3>
    @include('pages.partials.mute-promotions.logs', ['user_id' => $id, 'rows' => $mutePromotionsLogs])

    <h3>Rock The Vote Imports</h3>
    @include('pages.partials.rock-the-vote.logs', ['user_id' => $id, 'rows' => $rockTheVoteLogs])
</div>

@stop

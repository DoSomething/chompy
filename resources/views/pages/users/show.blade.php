@extends('layouts.master')

@section('main_content')

<div>
    <h1>{{$user_id}}</h1>
    <p><a href="#">View in Rogue</a></p>
    <h3>Rock The Vote</h3>
    @include('pages.partials.rock-the-vote.logs', ['user_id' => $user_id, 'rows' => $rows])
</div>

@stop

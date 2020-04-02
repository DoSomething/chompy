@extends('layouts.master')

@section('main_content')

<div>
    <h1>{{$id}}</h1>
    <p>
        <a href="{{config('services.rogue.url') . '/users/' . $id}}">View user in Rogue</a>
    </p>
    <h3>Rock The Vote</h3>
    @include('pages.partials.rock-the-vote.logs', ['user_id' => $id, 'rows' => $rows])
</div>

@stop

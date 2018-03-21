@extends('layouts.master')

@section('main_content')
<div class="container">

    <!-- Static navbar -->
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <a class="navbar-brand" href="#">Chompy</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="/login">Login</a></li>
                </ul>
            </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
    </nav>

    <!-- Main component for a primary marketing message or call to action -->
    <div class="jumbotron">
    <h1>Welcome to Chompy</h1>
    <p>The DoSomething.org Importer app</p>
    <!-- <p>
    <a class="btn btn-lg btn-primary" href="../../components/#navbar" role="button">View navbar docs »</a>
    </p> -->
    </div>

</div> <!-- /container -->

@stop

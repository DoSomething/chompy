<!DOCTYPE html>

<html lang="en">

    <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title') | Chompy</title>
        <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
        <link rel="icon" type="image/png" href="http://twooter.biz/Gifs/tonguecat.png">
    </head>

    <body>
        @if (Session::has('status'))
            <div class="alert alert-success" role="alert">
                @include('components.alert-success', ['data' => Session::get('status')])
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="container-fluid">
            @include('components.nav')
        </div>

        <div class="container">
            @yield('main_content')
        </div>

        <script>
            window.PusherAppKey = '{{ env('PUSHER_APP_KEY') }}';
        </script>

        <script src="{{ mix('/js/app.js') }}"></script>
    </body>

</html>

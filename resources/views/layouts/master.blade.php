<!DOCTYPE html>

<html lang="en">

    <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Chompy</title>
        <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
        <link rel="icon" type="image/png" href="http://twooter.biz/Gifs/tonguecat.png">
    </head>

    <body>
        @if (Session::has('status'))
            <div class="alert alert-success" role="alert">
                {!! Session::get('status') !!}
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
        <div class="container">
            @include('components.nav', [
                'rockTheVotePath' => 'import/'.\Chompy\ImportType::$rockTheVote,
            ])
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

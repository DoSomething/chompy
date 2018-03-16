<!DOCTYPE html>

<html lang="en">

    <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Chompy</title>

        <link rel="icon" type="image/png" href="http://twooter.biz/Gifs/tonguecat.png">
    </head>

    <body>

        @if (Session::has('status'))
            <div class="messages">{{ Session::get('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="messages">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="chrome">
            <div class="wrapper">
                <div class="container">
                    @yield('main_content')
                </div>
            </div>
        </div>

    </body>


</html>

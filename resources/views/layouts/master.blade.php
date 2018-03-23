<!DOCTYPE html>

<html lang="en">

    <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Chompy</title>
        <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
        <link rel="icon" type="image/png" href="http://twooter.biz/Gifs/tonguecat.png">
        <script src="{{ mix('/js/app.js') }}"></script>
    </head>

    <body>
        <div class="container ch-navigation">
            @yield('navigation')
        </div>

        <div class="container ch-main">
            @yield('main_content')
        </div>
    </body>

</html>

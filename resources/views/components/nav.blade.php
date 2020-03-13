<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <a class="navbar-brand">Chompy</a>
            <ul class="nav navbar-nav">
                @if (Auth::user())
                    <li @if (Request::is('import-files*')) class="active" @endif>
                        <a class="nav-item nav-link" href="{{  '/import-files'  }}">
                            Imports
                        </a>
                    </li>
                    <li @if (Request::is('failed-jobs*')) class="active" @endif>
                        <a class="nav-item nav-link" href="{{  '/failed-jobs'  }}">
                            Failed jobs
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                @if (Auth::user())
                    <li @if (Request::path() === $emailSubscriptionPath) class="active" @endif>
                        <a class="nav-item nav-link" href="{{  '/'.$emailSubscriptionPath }}">
                            Email subscription
                        </a>
                    </li>
                    <li @if (strpos(Request::path(), 'rock-the-vote') !== false)) class="active" @endif>
                        <a class="nav-item nav-link" href="{{'/rock-the-vote/reports/create'}}">
                            Rock The Vote
                        </a>
                    </li>
                    <li>
                        <a class="nav-item nav-link" href="/logout">Logout</a>
                    </li>
                @else
                    <li><a class="nav-item nav-link" href="/login">Login</a></li>
                @endif
            </ul>
        </div><!--/.nav-collapse -->
    </div><!--/.container-fluid -->
</nav>

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
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                @if (Auth::user())
                    <li class="{{ Request::path() === $rockTheVotePath ? 'active' : '' }}">
                        <a class="nav-item nav-link" href="{{  '/'.$rockTheVotePath }}">
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

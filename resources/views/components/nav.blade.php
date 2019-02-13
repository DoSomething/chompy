<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <a class="navbar-brand" href="/">Chompy</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                @if (Auth::user())
                    <li class="{{ (app('request')->input('type') === get_import_type_turbovote()) ? 'active' : '' }}">
                        <a class="nav-item nav-link" href="/import?type={{ get_import_type_turbovote() }}">
                            TurboVote
                        </a>
                    </li>
                    <li class="{{ (app('request')->input('type') === get_import_type_rock_the_vote()) ? 'active' : '' }}">
                        <a class="nav-item nav-link" href="/import?type={{ get_import_type_rock_the_vote() }}">
                            Rock The Vote
                        </a>
                    </li>
                    <li class="{{ (app('request')->input('type') === get_import_type_facebook()) ? 'active' : '' }}">
                        <a class="nav-item nav-link" href="/import?type={{ get_import_type_facebook() }}">
                            Facebook Share
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

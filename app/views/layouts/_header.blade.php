<nav class="navbar navbar-default navbar-static-top">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#mainNavbar" aria-expanded="false">
                <span class="sr-only">MENU</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a href="{{ url('/') }}" class="navbar-brand"><img src="{{ asset('assets/img/logo.png') }}" alt="SIGNET" height="54"/></a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <div class="navbar-right">
                <ul class="nav navbar-nav">
                    <li>
                        <p class="navbar-btn">
                            <a href="#" target="_blank" class="btn btn-social btn-facebook"><i class="fa fa-facebook fa-fw" aria-hidden="true"></i></a>
                            <a href="#" target="_blank" class="btn btn-social btn-twitter"><i class="fa fa-twitter fa-fw" aria-hidden="true"></i></a>
                        </p>
                    </li>
                </ul>

                @include('layouts._header-form')

                <ul class="nav navbar-nav">
                    @if (is_null($user = Auth::user()))
                        <li>{{ link_to_route('login', 'Sign in') }}</li>
                    @else
                        <li class="dropdown">
                            <a href="#" onclick="return false" class="dropdown-toggle" data-toggle="dropdown" role="button"
                               aria-haspopup="true" aria-expanded="false"><i class="fa fa-user fa-lg" aria-hidden="true"></i>
                                <span class="caret"></span></a>

                            <ul class="dropdown-menu dropdown-menu-right">
                                <li class="disabled"><a href="#" onclick="return false">{{{ $user->welcome_name }}}</a></li>
                                <li>{{ link_to_route('logout', 'Sign out') }}</li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
            <!-- /.navbar-right -->
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container -->
</nav>

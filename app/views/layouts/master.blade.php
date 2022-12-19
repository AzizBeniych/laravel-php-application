<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <meta charset="utf-8"/>
    <title>Signature Travel Network :: @yield('title')</title>

    <!--suppress JSUnresolvedVariable -->
    <script>
        function loadJS(s,c){var t=document.createElement("script");t.defer=!0;t.onload=c;t.src=s;document.body.appendChild(t)}
        function loadCSS(){try{if(this.sheet&&this.sheet.cssRules&&!this.sheet.cssRules.length)this.onerror.call(this)}catch(e){}}
    </script>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" onload="loadCSS()"
          onerror="this.href.indexOf('cdn')>0&&(this.href='{{ asset('assets/vendor/bootstrap-3.3.7/css/bootstrap.min.css') }}')"/>
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,900&amp;subset=latin-ext"/>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" onload="loadCSS()"
          onerror="this.href.indexOf('cdn')>0&&(this.href='{{ asset('assets/vendor/font-awesome-4.7.0/css/font-awesome.min.css') }}')"/>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap24@3.3/bootstrap24.min.css" onload="loadCSS()"
          onerror="this.href.indexOf('cdn')>0&&(this.href='{{ asset('assets/css/bootstrap24.min.css') }}')"/>
    @section('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}"/>
    @show

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
        <script type="text/javascript" src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script type="text/javascript" src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="@yield('page-css')">
@yield('page-body')

<!-- jQuery and Bootstrap -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script>typeof window.jQuery=="undefined"&&document.write('<script src="{{ asset('assets/vendor/jquery-1.12.4/jquery.min.js') }}"><\/script>')</script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>typeof $.fn.modal=="undefined"&&document.write('<script src="{{ asset('assets/vendor/bootstrap-3.3.7/js/bootstrap.min.js') }}"><\/script>')</script>

<!-- HTML5 Placeholder -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-placeholder/2.3.1/jquery.placeholder.min.js"
        async="async" defer="defer" onload="$('input,textarea').placeholder()"
        onerror="loadJS('{{ asset('assets/vendor/jquery-placeholder-2.3.1/jquery.placeholder.min.js') }}',this.onload)"></script>

<!-- jQuery Validation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
<script>typeof $.validator=="undefined"&&document.write('<script src="{{ asset('assets/vendor/jquery-validation-1.17.0/jquery.validate.min.js') }}"><\/script>')</script>

<script src="{{ asset('packages/bllim/laravalid/jquery.validate.laravalid.js') }}" async="async"></script>
@section('scripts')
    <script src="{{ asset('assets/js/site.js') }}" async="async" defer="defer"></script>
@show

<!-- Google Analytics -->
@if (!App::environment('local', 'testing'))
    @include('layouts._js_ga')
@endif
</body>
</html>

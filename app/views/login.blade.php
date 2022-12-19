@extends('layouts.main')
@section('title', 'Login')
@section('page-css', 'main-layout login-page')

@section('content')
    <main>
        <div class="container">
            <div class="row">
                <div class="col-sm-4_5 col-md-4 col-lg-3_5 col-md-offset-0_5 col-lg-offset-1">

                {{ Form::open(['route' => 'login', 'method' => 'post', 'id' => 'loginForm', 'class' => 'login-form']) }}
                    @if (Session::has('error'))
                        <p class="text-danger small">{{{ Session::get('error') }}}</p>
                    @elseif (Session::has('notice'))
                        <p class="text-warning small">{{{ Session::get('notice') }}}</p>
                    @endif

                    <div class="form-group">
                        {{ Form::text('username', null, ['class' => 'form-control', 'placeholder' => 'Username', 'id' => 'username', 'autocomplete' => 'off', 'autofocus' => 'autofocus']) }}
                        @if ($errors->has('username'))
                            <small id="username-error" class="error">{{ $errors->first('username') }}</small>
                        @endif
                    </div>
                    <div class="form-group">
                        {{ Form::password('password', ['class' => 'form-control', 'placeholder' => 'Password', 'id' => 'password', 'autocomplete' => 'off']) }}
                        @if ($errors->has('password'))
                            <small id="password-error" class="error">{{ $errors->first('password') }}</small>
                        @endif
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-default">&nbsp; Submit &nbsp;</button>
                        <p class="help-block smaller">{{ link_to_route('forgot-password', 'I forgot my password', [], ['class' => '']) }}</p>
                    </div>
                {{ Form::close() }}

                </div>
            </div>
            <!--/.row -->
        </div>
    </main>
@stop

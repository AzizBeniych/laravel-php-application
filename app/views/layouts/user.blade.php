@extends('layouts.master')
@section('page-css', 'user-layout')

@section('page-body')
    <header>
        @include('layouts._header')
    </header>

    @yield('content')

    <footer>
        @include('layouts._footer')
    </footer>
@stop

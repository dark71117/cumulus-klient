@extends('layouts.klient')

@section('nav')
    @include('klient.partials.tabs')
@endsection

@section('content')
<div class="main_frame">
    <div id="content"></div>
    <div class="ajax_loader" id="ajax_loader">
        <img src="{{ asset('images/ajax-loader.gif') }}" alt="">
        <span>Wczytywanie danych...</span>
    </div>
</div>
@endsection

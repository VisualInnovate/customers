@extends('slack::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('slack.name') !!}</p>
@endsection

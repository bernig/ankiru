@extends('errors.layout', ['title' => '503'])
@section('slot')
    <x-error-page :status="503" />
@endsection

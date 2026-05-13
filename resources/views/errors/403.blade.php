@extends('errors.layout', ['title' => '403'])
@section('slot')
    <x-error-page :status="403" />
@endsection

@extends('errors.layout', ['title' => '404'])
@section('slot')
    <x-error-page :status="404" />
@endsection

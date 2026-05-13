@extends('errors.layout', ['title' => '419'])
@section('slot')
    <x-error-page :status="419" />
@endsection

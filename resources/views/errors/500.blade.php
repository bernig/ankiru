@extends('errors.layout', ['title' => '500'])
@section('slot')
    <x-error-page :status="500" />
@endsection

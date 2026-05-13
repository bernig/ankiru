@extends('errors.layout', ['title' => '429'])
@section('slot')
    <x-error-page :status="429" />
@endsection

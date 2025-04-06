@extends('layouts.app')

@section('content')
    @include('partials.hero')
    @include('partials.services')
    @include('partials.consultation-form', ['availableSlots' => $availableSlots])
    @include('partials.contact-form')
@endsection
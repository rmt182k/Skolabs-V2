@extends('layouts.app')

@section('title', 'Skolabs Dashboard')
@push('styles')
@endpush
@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb')

        {{-- Load Dashboard --}}
        @includeIf("dashboard.$role.index")
    </div>
@endsection

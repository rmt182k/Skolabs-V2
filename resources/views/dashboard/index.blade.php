@extends('layouts.app')

@section('title', 'Skolabs Dashboard')

@section('content')
    <div class="container-fluid">
        @include('layouts.components.breadcrumb')

        {{-- Load Dashboard --}}
        @includeIf('dashboard.' . $globalRoles[0]->name . '.index')
    </div>
@endsection

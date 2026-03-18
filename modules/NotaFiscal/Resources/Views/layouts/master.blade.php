@extends('layout.dashboard')

@section('layout.dashboard.body')
<div>
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div id="dashboard-content" class="px-4">
        @yield('content')
    </div>
</div>
@endsection

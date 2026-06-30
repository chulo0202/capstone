@extends('layouts.app')

@section('content')
@include('layouts.partials.dashboard-shell', [
    'sidebarTitle' => 'FAMS Portal',
    'sidebarSubtitle' => 'Farmer Assistance Program',
    'sidebarNav' => 'layouts.partials.farmer-sidebar-nav',
])
@endsection

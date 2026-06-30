@extends('layouts.app')

@section('content')
@include('layouts.partials.dashboard-shell', [
    'sidebarTitle' => 'FAMS Admin',
    'sidebarSubtitle' => 'Municipal Agriculture Office',
    'sidebarNav' => 'layouts.partials.admin-sidebar-nav',
])
@endsection

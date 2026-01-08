@extends('layouts.tv')

@section('content')
    @livewire(\App\Filament\Admin\Resources\Servers\Widgets\ServerMonitoringBoard::class)
@endsection

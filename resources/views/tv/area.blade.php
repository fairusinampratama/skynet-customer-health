@extends('layouts.tv')

@section('content')
    @livewire(\App\Filament\Admin\Widgets\AreaDetailBoard::class, ['areaId' => $area->id])
@endsection

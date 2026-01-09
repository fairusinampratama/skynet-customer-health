<?php

namespace App\Filament\Admin\Resources\Servers\Widgets;

use Filament\Widgets\Widget;
use App\Models\Server;
use Livewire\Attributes\On;

class ServerMonitoringBoard extends Widget
{
    protected static ?int $sort = 2; // Show below AreaMonitoringBoard
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected string $view = 'filament.admin.widgets.server-monitoring-board';
    
    public string $displayMode = 'table';

    #[On('set-server-view-mode')]
    public function setMode(string $mode): void
    {
        $this->displayMode = $mode;
    }

    public function getServers()
    {
        return Server::orderBy('name')->get()->map(function ($server) {
            return (object) [
                'id' => $server->id,
                'name' => $server->name,
                'ip_address' => $server->ip_address,
                'status' => $server->status,
                'last_seen' => $server->last_seen,
                'latency' => $server->healthChecks()->latest('checked_at')->value('latency_ms'),
            ];
        });
    }
}

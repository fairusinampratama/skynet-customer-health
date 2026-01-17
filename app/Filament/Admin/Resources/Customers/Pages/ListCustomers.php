<?php

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
            \Filament\Actions\Action::make('sendDailyReport')
                ->label('Send Daily Report')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Send Daily Error Report')
                ->modalDescription('Are you sure you want to generate and send the daily error report immediately? The title (Morning/Afternoon/Evening) will be based on the current time.')
                ->modalSubmitActionLabel('Yes, Send it')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('app:send-daily-error-report');
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Report Sent')
                        ->body('The daily error report has been generated and sent to WhatsApp.')
                        ->success()
                        ->send();
                }),
        ];
    }
}

<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Setting;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.admin.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'daily_report_enabled' => Setting::getValue('daily_report_enabled', true),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Automated Reporting')
                    ->description('Manage automated reporting settings for WhatsApp notifications.')
                    ->schema([
                        Toggle::make('daily_report_enabled')
                            ->label('Enable Daily Error Reports')
                            ->helperText('If disabled, the automated daily reports (8:00, 12:30, 19:00) will not be sent to WhatsApp.')
                            ->default(true),
                        
                        Actions::make([
                             Action::make('sendNow')
                                ->label('Send Daily Report Now')
                                ->color('success')
                                ->icon('heroicon-o-paper-airplane')
                                ->requiresConfirmation()
                                ->modalHeading('Send Daily Error Report')
                                ->modalDescription('Are you sure you want to generate and send the daily error report immediately? The title (Morning/Afternoon/Evening) will be based on the current time.')
                                ->modalSubmitActionLabel('Yes, Send it')
                                ->action(function () {
                                    \Illuminate\Support\Facades\Artisan::call('app:send-daily-error-report');
                                    
                                    Notification::make()
                                        ->title('Report Sent')
                                        ->body('The daily error report has been generated and sent to WhatsApp.')
                                        ->success()
                                        ->send();
                                }),
                        ]),
                    ]),
                Actions::make([
                    Action::make('save')
                        ->label('Save changes')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ])->alignEnd(),
            ])
            ->statePath('data');
    } 

    public function save(): void
    {
        $state = $this->form->getState();
        
        Setting::setValue('daily_report_enabled', $state['daily_report_enabled']);

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}

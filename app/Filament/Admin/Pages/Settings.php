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
            'whatsapp_test_number' => Setting::getValue('whatsapp_test_number', ''),
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
                                ->modalDescription('Generate and send a real-time snapshot of currently down customers? Only customers with > 5 minutes of active downtime will be included.')
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

                        \Filament\Forms\Components\TextInput::make('whatsapp_test_number')
                            ->label('Test Phone Number')
                            ->placeholder('628...')
                            ->helperText('Enter a phone number (with country code) to test the report delivery.'),

                        Actions::make([
                            Action::make('sendTestReport')
                                ->label('Send Test Report to Number')
                                ->color('gray')
                                ->icon('heroicon-o-beaker')
                                ->requiresConfirmation()
                                ->modalHeading('Send Test Report')
                                ->modalDescription('This will generate the PDF and send it to the specified "Test Phone Number". Ensure the number is saved or entered.')
                                ->action(function ($livewire) {
                                    $data = $livewire->form->getState();
                                    $number = $data['whatsapp_test_number'] ?? null;

                                    if (!$number) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Please enter a Test Phone Number first.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Trigger the command but override the recipient logic in the command? 
                                    // Actually, the command expects a Group ID generally.
                                    // We need a way to invoke the logic programmatically without the command OR modify the command to accept a direct number.
                                    // OR, we just reuse the logic here since we are in a closure.

                                    // Let's create the PDF here to ensure it works for testing
                                    $reportTitle = "TEST Report - " . now()->format('H:i');
                                    $customers = \App\Models\Customer::criticallyDown()->with('area')->get();
                                    
                                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily_errors', [
                                        'reportTitle' => $reportTitle,
                                        'date' => now()->format('l, d F Y'),
                                        'affectedCustomers' => $customers,
                                    ]);

                                    $fileName = "TEST_REPORT_" . now()->format('Y-m-d_H-i-s') . ".pdf";
                                    $disk = \Illuminate\Support\Facades\Storage::disk('public');
                                    $disk->put("reports/{$fileName}", $pdf->output());
                                    $fileUrl = route('reports.download', ['filename' => $fileName]);

                                    // Send
                                    $wa = app(\App\Services\WhatsApp\WhatsAppService::class);
                                    $sent = $wa->sendDocument(
                                        $number,
                                        $fileUrl,
                                        "🧪 *TEST REPORT*\n" .
                                        "Generated at: " . now()->format('H:i:s') . "\n" .
                                        "Customers Down: " . $customers->count()
                                    );

                                    if ($sent) {
                                        Notification::make()->title('Test Sent!')->success()->send();
                                    } else {
                                        Notification::make()->title('Failed to send')->body('Check logs.')->danger()->send();
                                    }
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
        if (isset($state['whatsapp_test_number'])) {
            Setting::setValue('whatsapp_test_number', $state['whatsapp_test_number']);
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}

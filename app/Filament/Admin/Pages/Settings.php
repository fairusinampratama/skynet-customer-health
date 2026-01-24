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
                            ->helperText('If disabled, the automated daily reports (every 2 hours from 08:00 to 00:00) will not be sent to WhatsApp.')
                            ->default(true),
                        
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

    public function sendReport(\App\Services\WhatsApp\WhatsAppService $whatsAppService): void
    {
        // Increase time limit to 5 minutes to prevent PHP timeout
        set_time_limit(300);

        try {
            // Check if report sending is enabled
            if (!Setting::getValue('daily_report_enabled', true)) {
                 Notification::make()
                    ->title('Report Disabled')
                    ->body('Please enable Daily Error Reports first.')
                    ->warning()
                    ->send();
                return;
            }

            $date = \Carbon\Carbon::today();
            $dayName = $date->format('l');
            $formattedDate = $date->format('Y-m-d');
            $humanReadableDate = $date->format('l, d F Y');
            $reportTitle = "Error Report - " . now()->format('H:i');

            // Fetch data
            $customers = \App\Models\Customer::criticallyDown()
                ->with('area')
                // CRITICAL FIX: Limit relationship to 1 to prevent loading 100k+ records per customer
                ->with(['healthChecks' => function ($q) {
                    $q->latest('checked_at')->limit(1);
                }])
                // Re-add the count for the PDF "Total Downtime" column
                ->withCount(['healthChecks' => function ($q) {
                    $q->whereDate('checked_at', \Carbon\Carbon::today())
                      ->where('status', 'down');
                }])
                ->get();

            if ($customers->isEmpty()) {
                Notification::make()
                    ->title('No Issues Found')
                    ->body('There are no customers with critical downtime (> 5 mins) right now.')
                    ->info()
                    ->send();
                return; 
            }

            // Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily_errors', [
                'reportTitle' => $reportTitle,
                'date' => $humanReadableDate,
                'affectedCustomers' => $customers,
            ]);

            $safeTitle = \Illuminate\Support\Str::snake($reportTitle);
            $fileName = "{$safeTitle}_{$dayName}_{$formattedDate}_" . now()->format('H-i-s') . ".pdf";
            
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            if (!$disk->put("reports/{$fileName}", $pdf->output())) {
                throw new \Exception("Failed to write PDF to disk!");
            }

            // URL for WhatsApp
            $fileUrl = route('reports.download', ['filename' => $fileName]);
            
            // Send via WhatsApp
            $groupId = config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));

            if ($groupId) {
                $sent = $whatsAppService->sendDocumentToGroup(
                    $groupId,
                    $fileUrl,
                    "📊 *{$reportTitle}*\n" .
                    "📅 {$humanReadableDate}\n" .
                    "📉 *Issues Found:* {$customers->count()} Customers\n\n" .
                    "📎 _See attached PDF for details._\n\n" .
                    "🤖 *Sender:* NOC Skynet\n" .
                    "⚠️ _Disclaimer: This is an automatic message._",
                    $fileName
                );

                if ($sent) {
                    Notification::make()
                        ->title('Report Sent Successfully')
                        ->body("Sent to Group ID: $groupId")
                        ->success()
                        ->send();
                } else {
                    throw new \Exception("Failed to send report via WhatsApp API.");
                }
            } else {
                 Notification::make()
                    ->title('Configuration Error')
                    ->body("No WhatsApp Group ID found in configuration.")
                    ->danger()
                    ->send();
            }

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Manual Report Failed', ['error' => $e->getMessage()]);
            
            Notification::make()
                ->title('Failed to Send Report')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}

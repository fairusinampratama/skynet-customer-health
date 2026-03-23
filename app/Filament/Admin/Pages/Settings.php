<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use App\Services\Reports\CustomerStatusImageReportService;
use App\Services\WhatsApp\WhatsAppService;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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

                        Actions::make([
                            Action::make('sendNow')
                                ->label('Send Daily Report Now')
                                ->color('success')
                                ->icon('heroicon-o-paper-airplane')
                                ->requiresConfirmation()
                                ->modalHeading('Send Daily Error Report')
                                ->modalDescription('Generate and send current customer status report in JPG format to WhatsApp group.')
                                ->modalSubmitActionLabel('Yes, Send it')
                                ->action(function () {
                                    $this->sendReport(
                                        app(WhatsAppService::class),
                                        app(CustomerStatusImageReportService::class)
                                    );
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

    public function sendReport(
        WhatsAppService $whatsAppService,
        CustomerStatusImageReportService $reportService
    ): void {
        set_time_limit(300);

        try {
            if (!Setting::getValue('daily_report_enabled', true)) {
                Notification::make()
                    ->title('Report Disabled')
                    ->body('Please enable Daily Error Reports first.')
                    ->warning()
                    ->send();
                return;
            }

            $report = $reportService->generateAndStoreReport();
            $groupId = config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));

            if (!$groupId) {
                Notification::make()
                    ->title('Configuration Error')
                    ->body('No WhatsApp Group ID found in configuration.')
                    ->danger()
                    ->send();
                return;
            }

            $sent = $whatsAppService->sendDocumentToGroup(
                $groupId,
                $report['file_url'],
                $reportService->buildWhatsAppCaption($report),
                $report['file_name']
            );

            if (!$sent) {
                throw new \RuntimeException('Failed to send report via WhatsApp API.');
            }

            Notification::make()
                ->title('Report Sent Successfully')
                ->body("Sent to Group ID: {$groupId}")
                ->success()
                ->send();
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

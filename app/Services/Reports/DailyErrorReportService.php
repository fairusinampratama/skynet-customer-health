<?php

namespace App\Services\Reports;

use App\Models\Area;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DailyErrorReportService
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function send(?string $groupId = null, bool $useFixture = false): DailyErrorReportResult
    {
        if (!$useFixture && !Setting::getValue('daily_report_enabled', true)) {
            return new DailyErrorReportResult(
                status: 'skipped',
                message: 'Daily Error Report is disabled in settings.',
            );
        }

        $date = Carbon::today();
        $reportTitle = 'Error Report - ' . now()->format('H-i');

        Log::info('Daily report: fetching critical customers.');
        $customers = $useFixture ? $this->fixtureCustomers() : $this->criticalCustomers();
        Log::info("Daily report: found {$customers->count()} critical issues.");

        if ($customers->isEmpty()) {
            return new DailyErrorReportResult(
                status: 'skipped',
                message: 'No customers with significant downtime.',
            );
        }

        Log::info('Daily report: generating PDF.');
        $pdf = Pdf::loadView('reports.daily_errors', [
            'reportTitle' => $reportTitle,
            'date' => $date->format('l, d F Y'),
            'affectedCustomers' => $customers,
        ]);

        $fileName = sprintf(
            '%s_%s_%s_%s.pdf',
            Str::snake($reportTitle),
            $date->format('l'),
            $date->format('Y-m-d'),
            now()->format('H-i-s'),
        );

        Log::info('Daily report: saving PDF to disk.');
        $disk = Storage::disk('public');
        if (!$disk->put("reports/{$fileName}", $pdf->output())) {
            throw new RuntimeException('Failed to write PDF to disk.');
        }

        $filePath = $disk->path("reports/{$fileName}");
        Log::info("Daily report: PDF saved to {$filePath}.");

        $groupId ??= config('services.whatsapp.audit_group_id');
        if (!$groupId) {
            return new DailyErrorReportResult(
                status: 'skipped',
                message: 'No WhatsApp Group ID configured.',
                issueCount: $customers->count(),
                fileName: $fileName,
                filePath: $filePath,
            );
        }

        Log::info("Daily report: sending to WhatsApp Group ID {$groupId}.");
        $sent = $this->whatsAppService->sendDocumentToGroup(
            $groupId,
            $this->caption($reportTitle, $date, $customers->count()),
            $filePath,
        );

        if (!$sent) {
            throw new RuntimeException('Failed to send report via WhatsApp API.');
        }

        return new DailyErrorReportResult(
            status: 'sent',
            message: 'Report sent successfully.',
            issueCount: $customers->count(),
            fileName: $fileName,
            filePath: $filePath,
            groupId: $groupId,
        );
    }

    private function criticalCustomers(): Collection
    {
        return Customer::criticallyDown()
            ->with('area')
            ->get();
    }

    private function fixtureCustomers(): Collection
    {
        return collect([
            (new Customer([
                'name' => 'Local Test Customer',
                'ip_address' => '192.0.2.10',
                'status' => 'down',
                'is_isolated' => false,
            ]))
                ->setRelation('area', new Area(['name' => 'Local Test Area']))
                ->setCreatedAt(now()->subHour())
                ->setUpdatedAt(now()->subMinutes(17)),
        ]);
    }

    private function caption(string $reportTitle, Carbon $date, int $issueCount): string
    {
        return "📊 *{$reportTitle}*\n" .
            "📅 {$date->format('l, d F Y')}\n" .
            "📉 *Issues Found:* {$issueCount} Customers\n\n" .
            "📎 _See attached PDF for details._\n\n" .
            "🤖 *Sender:* NOC Skynet\n" .
            "⚠️ _Disclaimer: This is an automatic message._";
    }
}

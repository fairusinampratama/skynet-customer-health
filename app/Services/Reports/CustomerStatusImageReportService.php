<?php

namespace App\Services\Reports;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerStatusImageReportService
{
    public function generateAndStoreReport(): array
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('PHP GD extension is required to generate JPG reports.');
        }

        $reportTime = now();
        $date = Carbon::today();
        $dayName = $date->format('l');
        $formattedDate = $date->format('Y-m-d');
        $humanReadableDate = $date->format('l, d F Y');
        $reportTitle = 'Customer Status Report - ' . $reportTime->format('H:i');

        $customers = Customer::query()
            ->with('area')
            ->orderBy('name')
            ->get();

        $downNoIsolir = $customers
            ->filter(fn (Customer $customer) => $customer->status === 'down' && !$customer->is_isolated)
            ->values();

        $upCustomers = $customers
            ->filter(fn (Customer $customer) => $customer->status === 'up')
            ->values();

        $downCountByArea = $downNoIsolir
            ->groupBy(fn (Customer $customer) => $customer->area->name ?? 'Tanpa Area')
            ->map(fn (Collection $group) => $group->count())
            ->sortKeys()
            ->all();

        $imageBinary = $this->buildImageBinary(
            $reportTitle,
            $humanReadableDate,
            $customers->count(),
            $downNoIsolir,
            $upCustomers,
            $downCountByArea
        );

        $safeTitle = Str::snake($reportTitle);
        $safeTitle = preg_replace('/[^a-z0-9_\\-]/', '_', $safeTitle) ?? 'customer_status_report';
        $fileName = "{$safeTitle}_{$dayName}_{$formattedDate}_" . $reportTime->format('H-i-s') . '.jpg';

        $disk = Storage::disk('public');
        if (!$disk->put("reports/{$fileName}", $imageBinary)) {
            throw new RuntimeException('Failed to write JPG report to disk.');
        }

        return [
            'file_name' => $fileName,
            'file_url' => route('reports.download', ['filename' => $fileName]),
            'report_title' => $reportTitle,
            'human_date' => $humanReadableDate,
            'total_count' => $customers->count(),
            'down_count' => $downNoIsolir->count(),
            'up_count' => $upCustomers->count(),
            'down_count_by_area' => $downCountByArea,
        ];
    }

    public function buildWhatsAppCaption(array $report): string
    {
        $lines = [
            '*'.$report['report_title'].'*',
            $report['human_date'],
            "Total User: {$report['total_count']}",
            "Down (No Isolir): {$report['down_count']}",
            "Up: {$report['up_count']}",
            '',
            'Keterangan Down per Area:',
        ];

        if (empty($report['down_count_by_area'])) {
            $lines[] = '- Tidak ada user down (no isolir).';
        } else {
            foreach ($report['down_count_by_area'] as $area => $count) {
                $lines[] = "- {$area}: {$count}";
            }
        }

        return implode("\n", $lines);
    }

    private function buildImageBinary(
        string $reportTitle,
        string $humanDate,
        int $totalUsers,
        Collection $downNoIsolir,
        Collection $upCustomers,
        array $downCountByArea
    ): string {
        $width = 1600;
        $padding = 40;
        $font = 5;
        $lineHeight = imagefontheight($font) + 8;
        $maxChars = max(30, intdiv($width - ($padding * 2), imagefontwidth($font)));

        $rows = [];
        $rows = array_merge($rows, $this->wrapWithType('title', $reportTitle, $maxChars));
        $rows = array_merge($rows, $this->wrapWithType('normal', "Tanggal: {$humanDate}", $maxChars));
        $rows[] = ['type' => 'normal', 'text' => ''];
        $rows = array_merge($rows, $this->wrapWithType('summary', "Total User: {$totalUsers}", $maxChars));
        $rows = array_merge($rows, $this->wrapWithType('down', "Down (No Isolir): {$downNoIsolir->count()}", $maxChars));
        $rows = array_merge($rows, $this->wrapWithType('up', "Up: {$upCustomers->count()}", $maxChars));
        $rows[] = ['type' => 'normal', 'text' => ''];

        $rows = array_merge($rows, $this->wrapWithType('section', 'KETERANGAN DOWN PER AREA', $maxChars));
        if (empty($downCountByArea)) {
            $rows = array_merge($rows, $this->wrapWithType('normal', '- Tidak ada user down (no isolir).', $maxChars));
        } else {
            foreach ($downCountByArea as $area => $count) {
                $rows = array_merge($rows, $this->wrapWithType('normal', "- {$area}: {$count}", $maxChars));
            }
        }

        $rows[] = ['type' => 'normal', 'text' => ''];
        $rows = array_merge($rows, $this->wrapWithType('section', 'USER DOWN (NO ISOLIR)', $maxChars));
        if ($downNoIsolir->isEmpty()) {
            $rows = array_merge($rows, $this->wrapWithType('normal', '- Tidak ada.', $maxChars));
        } else {
            foreach ($downNoIsolir as $index => $customer) {
                $area = $customer->area->name ?? 'Tanpa Area';
                $rows = array_merge(
                    $rows,
                    $this->wrapWithType('down', ($index + 1).". [{$area}] {$customer->name} ({$customer->ip_address})", $maxChars)
                );
            }
        }

        $rows[] = ['type' => 'normal', 'text' => ''];
        $rows = array_merge($rows, $this->wrapWithType('section', 'USER UP (SEMUA AREA)', $maxChars));
        if ($upCustomers->isEmpty()) {
            $rows = array_merge($rows, $this->wrapWithType('normal', '- Tidak ada.', $maxChars));
        } else {
            foreach ($upCustomers as $index => $customer) {
                $area = $customer->area->name ?? 'Tanpa Area';
                $rows = array_merge(
                    $rows,
                    $this->wrapWithType('up', ($index + 1).". [{$area}] {$customer->name} ({$customer->ip_address})", $maxChars)
                );
            }
        }

        $maxHeight = 14000;
        $height = ($padding * 2) + (count($rows) * $lineHeight) + 10;
        if ($height > $maxHeight) {
            $maxRows = intdiv($maxHeight - ($padding * 2) - 20, $lineHeight);
            $rows = array_slice($rows, 0, max(0, $maxRows - 2));
            $rows[] = ['type' => 'normal', 'text' => ''];
            $rows = array_merge($rows, $this->wrapWithType('normal', '... Daftar dipotong karena terlalu panjang.', $maxChars));
            $height = $maxHeight;
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new RuntimeException('Failed to create report image resource.');
        }

        $background = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 15, 76, 129);
        $red = imagecolorallocate($image, 180, 35, 35);
        $green = imagecolorallocate($image, 26, 120, 64);
        $gray = imagecolorallocate($image, 90, 90, 90);

        imagefill($image, 0, 0, $background);

        $y = $padding;
        foreach ($rows as $row) {
            $color = match ($row['type']) {
                'title' => $blue,
                'section' => $blue,
                'down' => $red,
                'up' => $green,
                'summary' => $gray,
                default => $black,
            };

            imagestring($image, $font, $padding, $y, $row['text'], $color);
            $y += $lineHeight;
        }

        ob_start();
        imagejpeg($image, null, 90);
        $binary = ob_get_clean();
        imagedestroy($image);

        if ($binary === false) {
            throw new RuntimeException('Failed to encode JPG report.');
        }

        return $binary;
    }

    private function wrapWithType(string $type, string $text, int $maxChars): array
    {
        if ($text === '') {
            return [['type' => $type, 'text' => '']];
        }

        $wrapped = wordwrap($text, $maxChars, "\n", true);
        $lines = explode("\n", $wrapped);

        return array_map(
            fn (string $line) => ['type' => $type, 'text' => $line],
            $lines
        );
    }
}

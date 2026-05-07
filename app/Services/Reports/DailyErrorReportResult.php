<?php

namespace App\Services\Reports;

class DailyErrorReportResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly int $issueCount = 0,
        public readonly ?string $fileName = null,
        public readonly ?string $filePath = null,
        public readonly ?string $groupId = null,
    ) {}

    public function wasSent(): bool
    {
        return $this->status === 'sent';
    }

    public function wasSkipped(): bool
    {
        return $this->status === 'skipped';
    }
}

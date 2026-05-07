<?php

use App\Services\Reports\DailyErrorReportService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Storage;

test('daily error report service sends fixture reports through whatsapp', function () {
    Storage::fake('public');

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp
        ->shouldReceive('sendDocumentToGroup')
        ->once()
        ->with('24831', Mockery::type('string'), Mockery::on(fn (string $path): bool => str_ends_with($path, '.pdf')))
        ->andReturnTrue();

    $result = (new DailyErrorReportService($whatsApp))->send(
        groupId: '24831',
        useFixture: true,
    );

    expect($result->wasSent())->toBeTrue()
        ->and($result->issueCount)->toBe(1)
        ->and($result->fileName)->toEndWith('.pdf');

    Storage::disk('public')->assertExists("reports/{$result->fileName}");
});

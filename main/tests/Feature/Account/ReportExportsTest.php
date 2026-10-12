<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);
afterEach(function () {
    $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
});

test('G07 report exports are private formatted PDF CSV and XLSX with platform insights', function (string $format) {
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $response = $this->get(route('system-reports', ['type' => 'overview', 'format' => $format]))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $bytes = $response->getContent();
    if ($format === 'pdf') {
        expect($bytes)->toStartWith('%PDF-');
    } elseif ($format === 'csv') {
        expect($bytes)->toContain('Account growth', 'Evaluation backlog', 'Registered accounts by current status');
    } else {
        $path = tempnam(sys_get_temp_dir(), 'kody-xlsx-test-');
        file_put_contents($path, $bytes);
        try {
            $zip = new ZipArchive;
            expect($zip->open($path))->toBeTrue();
            expect($zip->getFromName('xl/worksheets/sheet1.xml'))->toContain('Account growth')->not->toContain('<f>');
            $zip->close();
        } finally {
            unlink($path);
        }
    }
})->with(['pdf', 'csv', 'xlsx']);

test('G07 export routes enforce Administrator access and validate formats and windows', function () {
    moduleSignIn($this, moduleAccount(Role::Moderator));
    $this->get(route('system-reports', ['format' => 'pdf']))->assertForbidden();
    moduleSignIn($this, moduleAccount(Role::Administrator));
    $this->getJson(route('system-reports', ['format' => 'html-script']))->assertUnprocessable();
    $this->getJson(route('system-reports', ['format' => 'csv', 'from' => '2020-01-01', 'to' => '2026-10-12']))->assertUnprocessable();
});

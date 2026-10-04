<?php

use App\Services\Account\PrivateObjectErasure;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

function erasureS3(array $responses): MockHandler
{
    $handler = new MockHandler(array_map(fn ($response) => new Result($response), $responses));
    $client = new S3Client(['version' => 'latest', 'region' => 'ap-southeast-1',
        'credentials' => ['key' => 'test-key', 'secret' => 'test-secret'], 'handler' => $handler]);
    $disk = Mockery::mock(AwsS3V3Adapter::class);
    $disk->shouldReceive('getClient')->andReturn($client);
    $disk->shouldReceive('path')->with('credentials/file.pdf')->andReturn('private/credentials/file.pdf');
    config(['filesystems.disks.erasure-s3.driver' => 's3', 'filesystems.disks.erasure-s3.bucket' => 'test-private-bucket']);
    Storage::set('erasure-s3', $disk);

    return $handler;
}

test('A08 private versioned S3 erasure removes only the exact key across paginated versions and markers', function () {
    $key = 'private/credentials/file.pdf';
    $commands = [];
    $handler = erasureS3([
        ['Status' => 'Enabled'],
        ['Versions' => [['Key' => $key, 'VersionId' => 'v1'], ['Key' => $key.'.other', 'VersionId' => 'neighbor']],
            'DeleteMarkers' => [['Key' => $key, 'VersionId' => 'marker']], 'IsTruncated' => true, 'NextKeyMarker' => $key, 'NextVersionIdMarker' => 'v1'],
        [],
        ['Versions' => [['Key' => $key, 'VersionId' => 'v2']], 'IsTruncated' => false],
        [],
        ['Versions' => [['Key' => $key.'.other', 'VersionId' => 'neighbor']], 'IsTruncated' => false],
    ]);
    $disk = Storage::disk('erasure-s3');
    $disk->getClient()->getHandlerList()->appendInit(function ($next) use (&$commands) {
        return function ($command, $request = null) use ($next, &$commands) {
            $commands[] = ['name' => $command->getName(), 'parameters' => $command->toArray()];

            return $next($command, $request);
        };
    });
    app(PrivateObjectErasure::class)->erase('erasure-s3', 'credentials/file.pdf');
    expect(count($handler))->toBe(0);
    $deletes = array_values(array_filter($commands, fn ($command) => $command['name'] === 'DeleteObjects'));
    expect($deletes[0]['parameters']['Delete']['Objects'])->toBe([
        ['Key' => $key, 'VersionId' => 'v1'], ['Key' => $key, 'VersionId' => 'marker'],
    ])->and($deletes[1]['parameters']['Delete']['Objects'])->toBe([['Key' => $key, 'VersionId' => 'v2']]);
    $lists = array_values(array_filter($commands, fn ($command) => $command['name'] === 'ListObjectVersions'));
    expect($lists[1]['parameters']['KeyMarker'])->toBe($key)->and($lists[1]['parameters']['VersionIdMarker'])->toBe('v1');
});

test('A08 version deletion errors and remaining private versions cannot claim completed erasure', function (bool $error) {
    $key = 'private/credentials/file.pdf';
    erasureS3($error ? [['Status' => 'Suspended'], ['Versions' => [['Key' => $key, 'VersionId' => 'v1']]],
        ['Errors' => [['Key' => $key, 'VersionId' => 'v1', 'Code' => 'AccessDenied']]]] : [
            ['Status' => 'Enabled'], ['IsTruncated' => false], ['DeleteMarkers' => [['Key' => $key, 'VersionId' => 'retained']]],
        ]);
    expect(fn () => app(PrivateObjectErasure::class)->erase('erasure-s3', 'credentials/file.pdf'))->toThrow(RuntimeException::class);
})->with([true, false]);

test('A08 local private erasure verifies generated object removal', function () {
    Storage::fake('erasure-local');
    Storage::disk('erasure-local')->put('credentials/file.pdf', 'test-only');
    app(PrivateObjectErasure::class)->erase('erasure-local', 'credentials/file.pdf');
    Storage::disk('erasure-local')->assertMissing('credentials/file.pdf');
});

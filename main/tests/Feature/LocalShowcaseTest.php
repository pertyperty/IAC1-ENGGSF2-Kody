<?php

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\LocalRoleAccountsSeeder;
use Database\Seeders\LocalShowcaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('local showcase publishes full courses media games quizzes help and challenges without changing account sessions', function () {
    Storage::fake('local');
    $this->seed(LocalRoleAccountsSeeder::class);
    $before = User::orderBy('id')->get()->map->getRawOriginal()->all();
    $this->seed(LocalShowcaseSeeder::class);
    expect(User::orderBy('id')->get()->map->getRawOriginal()->all())->toBe($before);
    $this->assertDatabaseCount('learning_courses', 3);
    $this->assertDatabaseCount('learning_modules', 14);
    $this->assertDatabaseCount('coding_challenges', 9);
    $this->assertDatabaseCount('faq_entries', 8);
    $this->assertDatabaseCount('learning_progress', 0);
    $this->assertDatabaseCount('xp_awards', 0);
    expect(DB::table('module_revisions')->where('video_url', '!=', null)->count())->toBe(2);
    $media = DB::table('module_revisions')->where('title', 'What is a program?')->sole();
    $assets = json_decode($media->attachments, true);
    expect(array_column($assets, 'extension'))->toBe(['pdf', 'docx', 'pptx']);
    foreach ($assets as $asset) {
        Storage::disk('local')->assertExists($asset['path']);
    }
    $beforeContent = DB::table('module_revisions')->orderBy('id')->get()->toArray();
    $this->seed(LocalShowcaseSeeder::class);
    expect(DB::table('module_revisions')->orderBy('id')->get()->toArray())->toEqual($beforeContent);
    $this->assertDatabaseCount('learning_courses', 3);
    $this->assertDatabaseCount('coding_challenges', 9);
});

test('showcase rejects deployment environments and never reactivates existing fixture accounts', function () {
    $this->seed(LocalRoleAccountsSeeder::class);
    User::where('email', 'instructor@kody.local')->update(['account_status' => AccountStatus::Suspended]);
    expect(fn () => $this->seed(LocalShowcaseSeeder::class))->toThrow(RuntimeException::class, 'never promoted');
    $this->assertDatabaseCount('learning_modules', 0);
    app()['env'] = 'production';
    expect(fn () => (new LocalShowcaseSeeder)->run())->toThrow(RuntimeException::class, 'local/testing');
});

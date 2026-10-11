<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Jobs\Account\EraseAccountFile;
use App\Models\AccountFileErasure;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Account\ProfilePictures;
use App\Services\Administration\AuditRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('local'));

test('pictures are cropped and reencoded privately for every verified role', function (Role $role) {
    $user = User::factory()->create(['account_role' => $role]);
    profileSignIn($this, $user);
    $this->post(route('account.picture-update'), ['profile_version' => 1, 'picture' => UploadedFile::fake()->image('photo.png', 640, 320)])
        ->assertRedirect(route('account.edit'))->assertSessionHas('status');
    $asset = $user->fresh()->profile_picture;
    expect($asset['path'])->toMatch('~^profile-pictures/[0-9a-f-]{36}\.jpg$~')->and($user->fresh()->profile_version)->toBe(2)
        ->and($user->fresh()->toArray())->not->toHaveKey('profile_picture');
    $bytes = Storage::disk('local')->get($asset['path']);
    expect(getimagesizefromstring($bytes)[0])->toBe(256)->and(getimagesizefromstring($bytes)[1])->toBe(256)
        ->and(getimagesizefromstring($bytes)['mime'])->toBe('image/jpeg');
    $this->get(route('account.picture', $user))->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
    $this->assertDatabaseHas('audit_events', ['event' => 'account.picture-updated', 'actor_id' => $user->id]);
})->with(Role::cases());

test('replacement and removal queue durable erasure and preserve other accounts', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    profileSignIn($this, $user);
    $pictures = app(ProfilePictures::class);
    $pictures->update($user, session()->getId(), 1, UploadedFile::fake()->image('first.jpg'));
    $first = $user->fresh()->profile_picture;
    $pictures->update($user, session()->getId(), 2, UploadedFile::fake()->image('second.jpg'));
    expect(AccountFileErasure::count())->toBe(1)->and(Storage::disk('local')->exists($first['path']))->toBeTrue();
    (new EraseAccountFile(AccountFileErasure::sole()->id))->handle();
    Storage::disk('local')->assertMissing($first['path']);
    $this->post(route('account.picture-update'), ['profile_version' => 3, 'remove_picture' => 1])->assertRedirect(route('account.edit'));
    expect($user->fresh()->profile_picture)->toBeNull()->and($other->fresh()->profile_version)->toBe(1);
    $this->get(route('account.picture', $user))->assertNotFound();
});

test('pictures reject invalid files and stale writes without storing objects', function () {
    $user = User::factory()->create();
    profileSignIn($this, $user);
    $this->postJson(route('account.picture-update'), ['profile_version' => 1, 'picture' => UploadedFile::fake()->create('payload.svg', 1, 'image/svg+xml')])->assertJsonValidationErrors('picture');
    $this->postJson(route('account.picture-update'), ['profile_version' => 0, 'picture' => UploadedFile::fake()->image('photo.jpg')])->assertJsonValidationErrors('profile_version');
    expect(Storage::disk('local')->allFiles())->toBeEmpty()->and($user->fresh()->profile_picture)->toBeNull();
});

test('private pictures need a current verified session and disappear after account withdrawal', function () {
    $user = User::factory()->create();
    $this->get(route('account.picture', $user))->assertRedirect(route('login'));
    profileSignIn($this, $user);
    app(ProfilePictures::class)->update($user, session()->getId(), 1, UploadedFile::fake()->image('photo.jpg'));
    $other = User::factory()->create(['account_status' => AccountStatus::Suspended, 'profile_picture' => $user->fresh()->profile_picture]);
    $this->get(route('account.picture', $other))->assertNotFound();
});

test('an audit failure rolls back the picture and retains durable cleanup for the orphan', function () {
    $user = User::factory()->create();
    profileSignIn($this, $user);
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('synthetic failure'));
    expect(fn () => app(ProfilePictures::class)->update($user, session()->getId(), 1, UploadedFile::fake()->image('photo.jpg')))
        ->toThrow(RuntimeException::class, 'could not be saved');
    expect($user->fresh()->profile_picture)->toBeNull()->and($user->fresh()->profile_version)->toBe(1)->and(AccountFileErasure::count())->toBe(1);
    (new EraseAccountFile(AccountFileErasure::sole()->id))->handle();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('picture updates reject ownership and storage overrides without changing any profile', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    profileSignIn($this, $user);
    $this->postJson(route('account.picture-update'), ['profile_version' => 1, 'user_id' => $other->id, 'disk' => 'public', 'path' => '../secret',
        'picture' => UploadedFile::fake()->image('photo.jpg')])->assertJsonValidationErrors(['user_id', 'disk', 'path']);
    expect($user->fresh()->profile_picture)->toBeNull()->and($other->fresh()->profile_picture)->toBeNull()->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('picture dimensions are bounded before decoding and corrupt PNG payloads are rejected', function () {
    $user = User::factory()->create();
    profileSignIn($this, $user);
    $this->postJson(route('account.picture-update'), ['profile_version' => 1, 'picture' => UploadedFile::fake()->image('wide.png', 4097, 1)])
        ->assertJsonValidationErrors('picture');
    $corrupt = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jZ3sAAAAASUVORK5CYII=');
    $this->postJson(route('account.picture-update'), ['profile_version' => 1, 'picture' => UploadedFile::fake()->createWithContent('corrupt.png', $corrupt)])
        ->assertJsonValidationErrors('picture');
    expect($user->fresh()->profile_version)->toBe(1)->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('account deletion anonymizes picture metadata and durably erases its private bytes', function () {
    $user = moduleAccount(Role::Learner);
    app(ProfilePictures::class)->update($user, 'module-test-session', 1, UploadedFile::fake()->image('photo.jpg'));
    $asset = $user->fresh()->profile_picture;
    app(AccountDeletion::class)->delete($user, 'module-test-session', deletionData($user));
    expect($user->fresh()->profile_picture)->toBeNull()->and($user->fresh()->account_status)->toBe(AccountStatus::Deleted);
    $erasure = AccountFileErasure::sole();
    expect($erasure->path)->toBe($asset['path']);
    (new EraseAccountFile($erasure->id))->handle();
    Storage::disk('local')->assertMissing($asset['path']);
});

test('PostgreSQL rejects public or traversal picture metadata', function () {
    $user = User::factory()->create();
    expect(fn () => DB::transaction(fn () => $user->forceFill(['profile_picture' => ['disk' => 'public', 'path' => '../private.jpg']])->save()))
        ->toThrow(QueryException::class);
    expect($user->fresh()->profile_picture)->toBeNull();
});

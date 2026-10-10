<?php

use App\Enums\Role;
use App\Jobs\Account\EraseAccountFile;
use App\Models\AccountFileErasure;
use App\Models\User;
use App\Services\Content\ModulePublishing;
use App\Services\Content\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function mediaOfficeFile(string $extension, bool $unsafe = false): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'kody-media-test-');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<Types/>');
    $prefix = $unsafe ? '<!DOCTYPE doc [<!ENTITY leak SYSTEM "file:///etc/passwd">]>' : '';
    $zip->addFromString($extension === 'docx' ? 'word/document.xml' : 'ppt/slides/slide1.xml', $prefix.'<doc xmlns:t="http://schemas.openxmlformats.org/'.($extension === 'docx' ? 'wordprocessingml/2006/main' : 'drawingml/2006/main').'"><t:p><t:r><t:t>&lt;script&gt;unsafe()&lt;/script&gt;</t:t></t:r></t:p></doc>');
    $zip->close();
    $bytes = file_get_contents($path);
    unlink($path);

    return UploadedFile::fake()->createWithContent('lesson.'.$extension, $bytes);
}

function mediaPdf(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('notes.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");
}

test('D01 module files stay private and preserve published media during draft edits', function (string $extension) {
    Storage::fake('local');
    $file = $extension === 'pdf' ? mediaPdf() : mediaOfficeFile($extension);
    $module = moduleFixture(true, ['type' => 'Article', 'attachments' => [$file], 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    $revision = $module->publishedRevision;
    $asset = $revision->attachments[0];
    Storage::disk('local')->assertExists($asset['path']);
    moduleSignIn($this, User::find($module->created_by));
    $this->put(route('studio.update', $module), moduleData(['record_version' => 3, 'retain_attachments' => [0]]))->assertRedirect();
    expect($module->fresh()->latestRevision->attachments[0]['path'])->toBe($asset['path']);
    expect($module->fresh()->published_revision_id)->toBe($revision->id);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('modules.show', $module))->assertOk()->assertSee('Download')->assertSee('youtube-nocookie.com', false);
    $url = route('module-media.show', [$module, $revision->id, 0]);
    $preview = $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    if ($extension !== 'pdf') {
        $preview->assertSee('&lt;script&gt;', false)->assertDontSee('<script>unsafe()', false);
    }
    $this->get($url.'?download=1')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get(route('module-media.show', [$module, $module->fresh()->latestRevision->id, 0]))->assertForbidden();
})->with(['pdf', 'docx', 'pptx']);

test('D01 draft resources reject guest and unrelated learner access', function () {
    Storage::fake('local');
    $module = moduleFixture(false, ['attachments' => [mediaPdf()]]);
    $url = route('module-media.show', [$module, $module->latestRevision->id, 0]);
    $this->get($url)->assertRedirect(route('login'));
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get($url)->assertNotFound();
    moduleSignIn($this, User::find($module->created_by));
    $this->get($url)->assertOk();
});

test('D01 rejects unsafe office XML and mismatched PDF content without a draft', function (string $kind) {
    Storage::fake('local');
    moduleSignIn($this, moduleAccount(Role::Instructor));
    $file = $kind === 'office' ? mediaOfficeFile('docx', true) : UploadedFile::fake()->createWithContent('fake.pdf', '<script>danger()</script>');
    $this->post(route('studio.store'), moduleData(['attachments' => [$file]]))->assertSessionHasErrors('attachments');
    $this->assertDatabaseCount('learning_modules', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with(['office', 'pdf']);

test('D03 staff withdrawal blocks even previously opened resources', function () {
    Storage::fake('local');
    $module = moduleFixture(true, ['attachments' => [mediaPdf()]]);
    moduleSignIn($this, moduleAccount(Role::Learner));
    $url = route('module-media.show', [$module, $module->published_revision_id, 0]);
    $this->get($url)->assertOk();
    $module->forceFill(['staff_withdrawn_at' => now()])->save();
    $this->get($url.'?download=1')->assertNotFound();
});

test('B04 pinned course resources retain their exact revision and reject forged lesson contexts', function () {
    Storage::fake('local');
    $module = moduleFixture(true, ['attachments' => [mediaPdf()]]);
    $course = courseFixture(true, $module);
    $user = moduleAccount(Role::Learner);
    joinCourse($course, $user);
    $slot = $course->publishedRevision->modules->sole();
    $old = $module->published_revision_id;
    $service = app(ModulePublishing::class);
    $owner = User::find($module->created_by);
    $service->save($owner, 'module-test-session', moduleData(['record_version' => 3]), $module);
    $service->submit($owner, 'module-test-session', $module->fresh(), 4);
    $service->review(moduleAccount(Role::Moderator), 'module-test-session', $module->fresh(), 5, 'Approved', null);
    moduleSignIn($this, $user);
    $this->get(route('module-media.show', [$module, $old, 0, 'course' => $course->id, 'slot' => $slot->id]))->assertOk();
    $this->get(route('module-media.show', [$module, $old, 0, 'course' => $course->id, 'slot' => $slot->id + 999]))->assertNotFound();
    $this->get(route('module-media.show', [$module, $old, 0]))->assertForbidden();
});

test('video embeds allow exact providers and reject host spoofing or arbitrary iframe URLs', function (string $url, ?string $expected) {
    expect(app(VideoEmbed::class)->url($url))->toBe($expected);
})->with([
    ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    ['https://vimeo.com/123456', 'https://player.vimeo.com/video/123456'],
    ['https://youtube.com.evil.example/embed/dQw4w9WgXcQ', null],
    ['https://youtube.com@evil.example/embed/dQw4w9WgXcQ', null],
    ['javascript:alert(1)', null], ['https://127.0.0.1/admin', null], ['https://youtube.com/watch?v[]=oops', null],
]);

test('D01 failed revision saves queue only new uploads and the private erasure job safely removes them', function () {
    Storage::fake('local');
    $owner = moduleAccount(Role::Instructor);
    expect(fn () => app(ModulePublishing::class)->save($owner, 'module-test-session', moduleData([
        'attachments' => [mediaPdf()], 'game_layout' => '{invalid',
    ])))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('learning_modules', 0);
    $this->assertDatabaseCount('module_revisions', 0);
    $erasure = AccountFileErasure::sole();
    $path = $erasure->path;
    Storage::disk('local')->assertExists($path);
    expect($erasure->queued_job_id)->not->toBeNull();
    (new EraseAccountFile($erasure->id))->handle();
    (new EraseAccountFile($erasure->id))->handle();
    Storage::disk('local')->assertMissing($path);
    expect($erasure->fresh()->completed_at)->not->toBeNull()->and($erasure->fresh()->attempts)->toBe(1);
});

test('D01 a document upload cannot exceed the combined retained and new resource limit', function () {
    Storage::fake('local');
    $module = moduleFixture(false, ['attachments' => array_map(fn () => mediaPdf(), range(1, 5))]);
    moduleSignIn($this, User::find($module->created_by));
    $this->put(route('studio.update', $module), moduleData(['retain_attachments' => [0, 1, 2, 3, 4], 'attachments' => [mediaPdf()]]))
        ->assertSessionHasErrors('attachments');
    $this->assertDatabaseCount('module_revisions', 1);
    expect(count(Storage::disk('local')->allFiles()))->toBe(5);
});

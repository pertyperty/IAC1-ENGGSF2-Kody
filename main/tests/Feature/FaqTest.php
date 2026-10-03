<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\FaqEntry;
use App\Models\User;
use App\Services\Administration\AuditRecorder;
use App\Services\Administration\FaqManagement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function faqData(array $overrides = []): array
{
    return array_replace(['question' => 'How do I start playing?', 'answer' => "Open Play.\nTry the garden.", 'category' => 'getting-started', 'record_version' => 1], $overrides);
}

function faqFixture(array $data = []): FaqEntry
{
    return app(FaqManagement::class)->save(moduleAccount(Role::Administrator), 'module-test-session', faqData($data));
}

test('G11 G12 administrator publishes validated plain text FAQ and audited updates', function () {
    $admin = User::factory()->create(['account_role' => Role::Administrator]);
    moduleSignIn($this, $admin);
    $this->get(route('faq-management.create'))->assertOk();
    $this->post(route('faq-management.store'), faqData(['answer' => '<script>bad()</script>']))->assertRedirect();
    $entry = FaqEntry::sole();
    expect($entry->created_by)->toBe($admin->id)->and($entry->status)->toBe('Active');
    $this->get(route('faq-management.edit', $entry))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>bad()', false);
    $this->put(route('faq-management.update', $entry), faqData(['question' => 'Where is Play?', 'answer' => 'Start on the landing page.', 'category' => 'playing-learning']))->assertRedirect();
    expect($entry->fresh()->record_version)->toBe(2)->and($entry->fresh()->answer)->toBe('Start on the landing page.');
    $this->get(route('help.show', $entry))->assertOk()->assertSee('Where is Play?')->assertSee('Start on the landing page.');
    $this->get(route('faq-management.index'))->assertOk()->assertSee('Where is Play?');
    $this->assertDatabaseHas('audit_events', ['event' => 'faq.created', 'actor_id' => $admin->id]);
    $this->assertDatabaseHas('audit_events', ['event' => 'faq.updated', 'actor_id' => $admin->id]);
});

test('B11 guests read search and filter Active FAQ entries with escaped text and literal keywords', function () {
    $first = faqFixture(['question' => 'What is 100% progress?', 'answer' => '<script>plain()</script>', 'category' => 'playing-learning']);
    faqFixture(['question' => 'What is 1000 progress?', 'category' => 'accounts']);
    $archived = faqFixture(['question' => 'Archived secret']);
    $archived->update(['status' => 'Archived']);
    $deleted = faqFixture(['question' => 'Deleted secret']);
    $deleted->update(['status' => 'Deleted']);
    $this->get(route('help.index'))->assertOk()->assertSee('Getting started')->assertSee('Creating content')->assertSee('What is 100% progress?')
        ->assertDontSee('Archived secret')->assertDontSee('Deleted secret')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>plain()', false);
    $this->get(route('help.index', ['q' => '%']))->assertOk()->assertSee('100% progress')->assertDontSee('1000 progress');
    $this->get(route('help.index', ['category' => 'accounts']))->assertOk()->assertSee('1000 progress')->assertDontSee('100% progress');
    $this->get(route('help.show', $first))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('help.show', $archived))->assertNotFound();
    $this->get(route('help.show', $deleted))->assertNotFound();
    $this->get(route('help.show', 999))->assertNotFound();
    $this->getJson(route('help.index', ['category' => 'invented']))->assertUnprocessable();
    $this->getJson(route('help.index', ['q' => str_repeat('x', 81)]))->assertUnprocessable();
});

test('B11 answer keyword search and pagination preserve query filters', function () {
    $admin = moduleAccount(Role::Administrator);
    foreach (range(1, 14) as $number) {
        app(FaqManagement::class)->save($admin, 'module-test-session', faqData(['question' => 'Tip '.$number, 'answer' => 'Useful keyword guide.', 'category' => 'accounts']));
    }
    $this->get(route('help.index', ['q' => 'KEYWORD', 'category' => 'accounts']))->assertOk()->assertSee('Tip 12')->assertDontSee('Tip 13')
        ->assertSee('category=accounts', false)->assertSee('q=KEYWORD', false);
    $this->get(route('help.index', ['q' => 'KEYWORD', 'category' => 'accounts', 'page' => 2]))->assertOk()->assertSee('Tip 13')->assertDontSee('Tip 1</a>', false);
});

test('G11 G12 G13 nonadministrator roles can read Help but cannot manage or mutate FAQ entries', function (Role $role) {
    $entry = faqFixture();
    $user = moduleAccount($role);
    moduleSignIn($this, $user);
    $this->get(route('help.show', $entry))->assertOk();
    $this->get(route('faq-management.index'))->assertForbidden();
    $this->get(route('faq-management.create'))->assertForbidden();
    $this->get(route('faq-management.edit', $entry))->assertForbidden();
    $this->postJson(route('faq-management.store'), faqData())->assertForbidden();
    $this->putJson(route('faq-management.update', $entry), faqData())->assertForbidden();
    $this->postJson(route('faq-management.delete', $entry), ['record_version' => 1, 'confirmed' => true])->assertForbidden();
    $user->forceFill(['active_session_hash' => hash('sha256', 'module-test-session'), 'active_session_expires_at' => now()->addHour()])->save();
    expect(fn () => app(FaqManagement::class)->save($user, 'module-test-session', faqData()))->toThrow(AuthorizationException::class);
    expect(fn () => app(FaqManagement::class)->delete($user, 'module-test-session', $entry, 1, true))->toThrow(AuthorizationException::class);
})->with([Role::Learner, Role::Contributor, Role::Instructor, Role::Moderator]);

test('G11 field bounds categories and protected input are enforced', function (array $overrides, string $field) {
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Administrator]));
    $this->postJson(route('faq-management.store'), faqData($overrides))->assertUnprocessable()->assertJsonValidationErrors($field);
    $this->assertDatabaseCount('faq_entries', 0);
})->with([
    [['question' => '  '], 'question'], [['answer' => ''], 'answer'], [['question' => str_repeat('x', 256)], 'question'],
    [['answer' => str_repeat('x', 10001)], 'answer'], [['category' => 'injected'], 'category'],
    [['status' => 'Archived'], 'status'], [['created_by' => 999], 'created_by'],
]);

test('G11 G12 duplicate question warnings and stale writes preserve committed state', function () {
    $admin = moduleAccount(Role::Administrator);
    $entry = faqFixture();
    expect(fn () => app(FaqManagement::class)->save($admin, 'module-test-session', faqData(['question' => ' HOW DO I START PLAYING? '])))->toThrow(ValidationException::class);
    $second = faqFixture(['question' => 'Another question']);
    expect(fn () => app(FaqManagement::class)->save($admin, 'module-test-session', faqData(), $second))->toThrow(ValidationException::class);
    app(FaqManagement::class)->save($admin, 'module-test-session', faqData(['answer' => 'Updated answer']), $entry);
    expect(fn () => app(FaqManagement::class)->save($admin, 'module-test-session', faqData(['answer' => 'Stale answer']), $entry))->toThrow(ValidationException::class);
    expect($entry->fresh()->answer)->toBe('Updated answer')->and($second->fresh()->question)->toBe('Another question');
    $this->assertDatabaseCount('faq_entries', 2);
});

test('G13 confirmed deletion hides entries everywhere preserves audits and permits a replacement question', function () {
    $admin = User::factory()->create(['account_role' => Role::Administrator]);
    $entry = faqFixture();
    moduleSignIn($this, $admin);
    $this->postJson(route('faq-management.delete', $entry), ['record_version' => 1])->assertUnprocessable();
    $this->postJson(route('faq-management.delete', $entry), ['record_version' => 99, 'confirmed' => true])->assertUnprocessable();
    $this->post(route('faq-management.delete', $entry), ['record_version' => 1, 'confirmed' => true])->assertRedirect(route('faq-management.index'));
    expect($entry->fresh()->status)->toBe('Deleted')->and($entry->fresh()->record_version)->toBe(2);
    $this->get(route('help.show', $entry))->assertNotFound();
    $this->get(route('faq-management.edit', $entry))->assertNotFound();
    $this->get(route('faq-management.index'))->assertOk()->assertDontSee('How do I start playing?');
    $this->get(route('help.index'))->assertOk()->assertDontSee('How do I start playing?');
    $this->putJson(route('faq-management.update', $entry), faqData(['record_version' => 2]))->assertForbidden();
    $this->assertDatabaseHas('audit_events', ['event' => 'faq.deleted']);
    $this->post(route('faq-management.store'), faqData())->assertRedirect();
    $this->assertDatabaseCount('faq_entries', 2);
});

test('G11 G12 G13 audit failures roll back content and deletion state', function (string $action) {
    $admin = moduleAccount(Role::Administrator);
    $entry = $action === 'create' ? null : faqFixture();
    $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Audit unavailable'));
    expect(function () use ($admin, $entry, $action) {
        if ($action === 'delete') {
            app(FaqManagement::class)->delete($admin, 'module-test-session', $entry, 1, true);
        } else {
            app(FaqManagement::class)->save($admin, 'module-test-session', faqData(['answer' => 'Changed answer']), $entry);
        }
    })->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('faq_entries', $entry === null ? 0 : 1);
    if ($entry !== null) {
        expect($entry->fresh()->status)->toBe('Active')->and($entry->fresh()->record_version)->toBe(1)
            ->and($entry->fresh()->answer)->toBe("Open Play.\nTry the garden.");
    }
})->with(['create', 'update', 'delete']);

test('G11 restricted and expired staff cannot mutate while public Help remains available', function (string $restriction) {
    $admin = moduleAccount(Role::Administrator);
    $admin->forceFill(match ($restriction) {
        'expired' => ['active_session_expires_at' => now()->subMinute()],
        'suspended' => ['account_status' => AccountStatus::Suspended],
        default => ['account_status' => AccountStatus::Unverified, 'email_verified_at' => null],
    })->save();
    expect(fn () => app(FaqManagement::class)->save($admin, 'module-test-session', faqData()))->toThrow(AuthorizationException::class);
    $this->get(route('help.index'))->assertOk();
    $this->assertDatabaseCount('faq_entries', 0);
})->with(['expired', 'suspended', 'unverified']);

test('G11 G13 admin routes require sign in csrf and bounded mutation requests', function () {
    $this->get(route('faq-management.index'))->assertRedirect(route('login'));
    moduleSignIn($this, User::factory()->create(['account_role' => Role::Administrator]));
    foreach (range(1, 10) as $attempt) {
        $this->postJson(route('faq-management.store'), [])->assertUnprocessable();
    }
    $this->postJson(route('faq-management.store'), [])->assertTooManyRequests();
    $this->app->detectEnvironment(fn () => 'local');
    $this->post(route('faq-management.store'), faqData())->assertStatus(419);
});

test('G11 PostgreSQL enforces question uniqueness categories nonempty text and versions', function (string $violation) {
    $entry = faqFixture();
    expect(fn () => match ($violation) {
        'duplicate' => FaqEntry::create(faqData(['question' => strtoupper($entry->question)]) + ['created_by' => $entry->created_by]),
        'category' => $entry->update(['category' => 'unknown']),
        'empty' => $entry->update(['answer' => '   ']),
        'version' => $entry->update(['record_version' => 0]),
    })->toThrow(QueryException::class);
})->with(['duplicate', 'category', 'empty', 'version']);

test('G13 migration rollback preserves FAQ authoring and audit references', function () {
    faqFixture();
    $migration = require database_path('migrations/2026_10_03_000024_create_faq_entries.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
});

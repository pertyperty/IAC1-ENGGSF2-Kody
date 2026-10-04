<?php

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\Account\AccountDeletion;
use App\Services\Account\CreatorErasure;
use App\Services\Content\CourseLearning;
use App\Services\Content\ModulePublishing;
use App\Services\Transactions\ContentAccess;
use App\Services\Transactions\Payments;
use App\Services\Transactions\ProviderWebhooks;
use App\Services\Transactions\PublisherSettlements;
use App\Services\Transactions\Refunds;
use App\Services\Transactions\WalletLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

test('B03 retained Deleted-creator courses admit new learners freely and audit the effective access price', function () {
    $course = courseFixture(true, overrides: ['price_kb' => 20]);
    User::findOrFail($course->created_by)->forceFill(['account_status' => AccountStatus::Deleted])->save();
    $learner = moduleAccount(Role::Learner);
    $enrollment = app(CourseLearning::class)->enroll($learner, 'module-test-session', $course->id, $course->published_revision_id);
    expect($enrollment->user_id)->toBe($learner->id);
    $this->assertDatabaseCount('content_purchases', 0);
    $this->assertDatabaseHas('content_entitlements', ['user_id' => $learner->id, 'content_type' => 'course', 'content_id' => $course->id, 'source' => 'Free']);
    $context = json_decode(DB::table('audit_events')->where('event', 'course.enrolled')->sole()->context, true);
    expect($context['access'])->toBe('free');
});

function economyProvider(): void
{
    config(['operations.staging_verified' => true, 'operations.budget_enforced' => true, 'operations.operations_owner' => 'Test operator', 'operations.backup_responder' => 'Test responder', 'xendit.enabled' => true, 'xendit.contract_verified' => true, 'xendit.secret_key' => str_repeat('test-key', 4),
        'xendit.callback_token' => str_repeat('test-token', 4), 'xendit.business_id' => 'test-merchant', 'app.url' => 'https://kody.example.test',
        'xendit.payment_fee_basis_points' => 300, 'xendit.payment_fee_fixed_minor' => 50, 'xendit.payout_fee_minor' => 100, 'xendit.refund_fee_minor' => 50]);
    DB::table('operations_budget_reports')->insertOrIgnore(['month' => now('Asia/Manila')->startOfMonth()->toDateString(), 'reported_minor' => 0, 'reported_by' => moduleAccount(Role::Administrator)->id, 'record_version' => 1, 'updated_at' => now()]);
    Queue::fake();
}

function economyConfirm(array $overrides = []): array
{
    return array_replace(['current_password' => 'password', 'confirmed' => true, 'confirmation_id' => (string) Str::uuid()], $overrides);
}

function economyCredit(User $user, int $kb = 1000, int $minor = 100000): void
{
    DB::transaction(fn () => app(WalletLedger::class)->credit($user->id, 'test-funding:'.$user->id, 'Purchase', $kb, $minor));
}

function economyPaymentProof(string $reference, string $status = 'SUCCEEDED'): array
{
    return ['payment_request_id' => 'pr-00000000-0000-4000-8000-000000000001', 'reference_id' => $reference, 'business_id' => 'test-merchant',
        'type' => 'PAY', 'country' => 'PH', 'currency' => 'PHP', 'request_amount' => 100, 'capture_method' => 'AUTOMATIC', 'channel_code' => 'GCASH',
        'status' => $status, 'latest_payment_id' => 'py-00000000-0000-4000-8000-000000000002',
        'captures' => $status === 'SUCCEEDED' ? [['capture_id' => 'cptr-00000000-0000-4000-8000-000000000003', 'capture_amount' => 100]] : [],
        'actions' => [['type' => 'REDIRECT_CUSTOMER', 'descriptor' => 'WEB_URL', 'value' => 'https://checkout.xendit.co/test']]];
}

function economyCallback(string $event, array $data, string $header = 'test-event'): void
{
    app(ProviderWebhooks::class)->receive(config('xendit.callback_token'), json_encode(['event' => $event, 'business_id' => 'test-merchant', 'data' => $data], JSON_THROW_ON_ERROR), $header);
}

test('F01 browser purchase requires password confirmation and participant authorization before durable queued work', function () {
    economyProvider();
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $this->get(route('wallet.index'))->assertOk()->assertSee('current_password', false);
    $data = economyConfirm(['package' => 'starter']);
    $this->post(route('wallet.purchase'), array_replace($data, ['current_password' => 'incorrect']))->assertSessionHasErrors('current_password');
    $this->assertDatabaseCount('payment_purchases', 0);
    $this->post(route('wallet.purchase'), $data)->assertRedirect(route('wallet.index'));
    $this->assertDatabaseHas('payment_purchases', ['user_id' => $user->id, 'state' => 'Queued', 'gross_minor' => 10000]);
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(0);
    moduleSignIn($this, moduleAccount(Role::Moderator));
    $this->post(route('wallet.purchase'), economyConfirm(['package' => 'starter']))->assertForbidden();
    $this->assertDatabaseCount('payment_purchases', 1);
});

test('D01 shared access settings remain outside assessment-specific hidden controls and quiz pricing is saved', function () {
    $author = moduleAccount(Role::Instructor);
    moduleSignIn($this, $author);
    $response = $this->get(route('studio.create', ['example' => 'choice-quiz']))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query("//*[@name='price_kb' or @name='minimum_xp' or @id='prerequisite_modules']")->length)->toBe(3)
        ->and($xpath->query("//*[@name='price_kb' or @name='minimum_xp' or @id='prerequisite_modules']/ancestor::*[@data-game-fields or @data-quiz-fields or @data-preset-fields]")->length)->toBe(0);
    $this->post(route('studio.store'), moduleData(['assessment_kind' => 'quiz', 'price_kb' => 5]))->assertRedirect();
    $this->assertDatabaseHas('module_revisions', ['price_kb' => 5, 'review_status' => 'Draft']);
});

test('B02 staff previews cannot persist module activity or claim participant XP', function (Role $role) {
    $module = moduleFixture(true);
    $staff = moduleAccount($role);
    moduleSignIn($this, $staff);
    $this->get(route('modules.show', $module))->assertOk()->assertSee('Staff preview')->assertDontSee('data-completion-url', false);
    $this->postJson(route('modules.game', [$module, $module->published_revision_id]), sequenceSolution())->assertForbidden();
    $this->assertDatabaseCount('learning_activity_days', 0);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('content_entitlements', 0);
})->with([Role::Moderator, Role::Administrator]);

test('D01 D02 reviewed prerequisites reject direct and transitive cycles and recheck availability before publication', function () {
    Queue::fake();
    $first = moduleFixture(true);
    $owner = User::find($first->created_by);
    $service = app(ModulePublishing::class);
    $second = $service->save($owner, 'module-test-session', moduleData(['title' => 'Second', 'prerequisite_modules' => [$first->id]]));
    $service->submit($owner, 'module-test-session', $second, 1);
    $reviewer = moduleAccount(Role::Moderator);
    $service->review($reviewer, 'module-test-session', $second->fresh(), 2, 'Approved', null);
    expect(fn () => $service->save($owner, 'module-test-session', moduleData(['record_version' => $first->record_version,
        'prerequisite_modules' => [$second->id]]), $first))->toThrow(ValidationException::class);
    $third = $service->save($owner, 'module-test-session', moduleData(['title' => 'Third', 'prerequisite_modules' => [$second->id]]));
    $service->submit($owner, 'module-test-session', $third, 1);
    $service->review($reviewer, 'module-test-session', $third->fresh(), 2, 'Approved', null);
    expect(fn () => $service->save($owner, 'module-test-session', moduleData(['record_version' => $first->record_version,
        'prerequisite_modules' => [$third->id]]), $first))->toThrow(ValidationException::class);
    $fourth = $service->save($owner, 'module-test-session', moduleData(['title' => 'Fourth', 'prerequisite_modules' => [$first->id]]));
    $service->submit($owner, 'module-test-session', $fourth, 1);
    $service->archive($owner, 'module-test-session', $first, $first->record_version);
    expect(fn () => $service->review($reviewer, 'module-test-session', $fourth->fresh(), 2, 'Approved', null))->toThrow(ValidationException::class);
    expect($fourth->fresh()->latestRevision->review_status)->toBe('Pending');
    $service->review($reviewer, 'module-test-session', $fourth->fresh(), 2, 'Rejected', 'Prerequisite is archived.');
    expect($fourth->fresh()->latestRevision->review_status)->toBe('Rejected');
});

test('F01 confirmed package is immutable and only an authenticated matching capture plus retrieval credits once', function () {
    economyProvider();
    $user = moduleAccount(Role::Learner);
    $data = economyConfirm(['package' => 'starter']);
    $service = app(Payments::class);
    $id = $service->purchase($user, 'module-test-session', $data);
    expect($service->purchase($user, 'module-test-session', $data))->toBe($id);
    expect(fn () => $service->purchase($user, 'module-test-session', array_replace($data, ['package' => 'builder'])))->toThrow(ValidationException::class);
    Http::fake(['https://api.xendit.co/v3/payment_requests' => Http::response(economyPaymentProof($id, 'REQUIRES_ACTION'))]);
    $service->create($id);
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(0);
    $proof = economyPaymentProof($id);
    economyCallback('payment.capture', $proof);
    Http::fake(['https://api.xendit.co/v3/payment_requests/*' => Http::response($proof)]);
    app(ProviderWebhooks::class)->reconcile('test-event');
    economyCallback('payment.capture', $proof);
    app(ProviderWebhooks::class)->reconcile('test-event');
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(105);
    expect(DB::table('wallet_lots')->sole()->initial_minor)->toBe(9650);
    $this->assertDatabaseCount('wallet_operations', 1);
    $this->assertDatabaseCount('financial_deliveries', 1);
    $this->assertDatabaseHas('payment_purchases', ['id' => $id, 'state' => 'Succeeded']);
    $this->assertDatabaseHas('provider_webhooks', ['id' => 'test-event', 'state' => 'Processed']);
});

test('F01 disabled providers forged callbacks wrong amounts and uncertain POST responses never credit or resend', function () {
    $user = moduleAccount(Role::Learner);
    Http::preventStrayRequests();
    expect(fn () => app(Payments::class)->purchase($user, 'module-test-session', economyConfirm(['package' => 'starter'])))->toThrow(HttpException::class);
    economyProvider();
    $id = app(Payments::class)->purchase($user, 'module-test-session', economyConfirm(['package' => 'starter']));
    $proof = economyPaymentProof($id);
    expect(fn () => app(ProviderWebhooks::class)->receive('forged', '{}', null))->toThrow(HttpException::class);
    economyCallback('payment.capture', array_replace($proof, ['request_amount' => 101]));
    Http::fake(['*' => Http::response($proof)]);
    expect(fn () => app(ProviderWebhooks::class)->reconcile('test-event'))->toThrow(RuntimeException::class);
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(0);
    Http::swap(new Factory);
    Http::fake(['*' => Http::response(['error_code' => 'DO_NOT_LOG_SECRET'], 503)]);
    expect(fn () => app(Payments::class)->create($id))->toThrow(RuntimeException::class);
    app(Payments::class)->create($id);
    Http::assertSentCount(1);
    $this->assertDatabaseHas('payment_purchases', ['id' => $id, 'state' => 'Review']);
});

test('F02 paid course access covers paid lessons and reviewed price changes never recharge an existing enrollment', function () {
    Queue::fake();
    $module = moduleFixture(true, ['price_kb' => 100]);
    $course = courseFixture(true, $module, ['price_kb' => 20]);
    $user = moduleAccount(Role::Learner);
    economyCredit($user, 40, 4000);
    $learning = app(CourseLearning::class);
    $enrollment = $learning->enroll($user, 'module-test-session', $course->id, $course->published_revision_id, true);
    $slot = $course->publishedRevision->modules->sole();
    $learning->lesson($user, 'module-test-session', $course->id, $slot->id);
    $learning->complete($user, 'module-test-session', $course->id, $slot->id, 'game', courseWin());
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(20);
    expect($learning->enroll($user, 'module-test-session', $course->id, $course->published_revision_id)->id)->toBe($enrollment->id);
    $this->assertDatabaseCount('content_purchases', 1);
    $this->assertDatabaseHas('xp_totals', ['user_id' => $user->id, 'xp' => 40]);
    expect(fn () => app(ContentAccess::class)->assertAccessible($user, 'module', $module, $module->publishedRevision))->toThrow(HttpException::class);
});

test('F02 locked previews disclose no lesson or assessment and unlocking uses server price and atomic authorization', function () {
    Queue::fake();
    $module = moduleFixture(true, ['price_kb' => 5, 'content' => 'SECRET_PAID_LESSON']);
    $user = moduleAccount(Role::Learner);
    economyCredit($user, 10, 1000);
    moduleSignIn($this, $user);
    $this->get(route('modules.show', $module))->assertOk()->assertDontSee('SECRET_PAID_LESSON')->assertSee('5 KodeBits');
    $this->assertDatabaseCount('content_accesses', 0);
    $this->post(route('content-access.store', ['module', $module->id]), ['revision_id' => $module->published_revision_id, 'confirmed' => true, 'price_kb' => 0])->assertRedirect();
    $this->get(route('modules.show', $module))->assertOk()->assertSee('SECRET_PAID_LESSON');
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(5);
    $this->post(route('content-access.store', ['module', $module->id]), ['revision_id' => $module->published_revision_id, 'confirmed' => true])->assertRedirect();
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(5);
    $this->get(route('wallet.index'))->assertOk();
    $this->get(route('earnings.index'))->assertOk();
    $this->get(route('finance.index'))->assertForbidden();
});

test('F03 Contributor fractional earnings carry backing and mature before an idempotent whole KB claim', function () {
    Queue::fake();
    $creator = moduleAccount(Role::Contributor);
    $buyer = moduleAccount(Role::Learner);
    economyCredit($buyer, 10, 1000);
    DB::transaction(fn () => app(WalletLedger::class)->buy($buyer->id, $creator->id, 'challenge', 1, 1, 5, 'KodeBits'));
    $data = economyConfirm();
    expect(fn () => app(PublisherSettlements::class)->claim($creator, 'module-test-session', $data))->toThrow(ValidationException::class);
    $this->travel(15)->days();
    $creator->forceFill(['active_session_expires_at' => now()->addHour()])->save();
    expect(app(PublisherSettlements::class)->claim($creator, 'module-test-session', $data))->toBe(3);
    expect(app(PublisherSettlements::class)->claim($creator, 'module-test-session', $data))->toBe(3);
    $earning = DB::table('publisher_earnings')->sole();
    expect($earning->kb_milli - $earning->claimed_kb_milli)->toBe(250);
    expect(DB::table('wallet_lots')->where('user_id', $creator->id)->sole()->initial_minor)->toBe(300);
});

test('F02 approved unused access refund reverses net shares and access while keeping financial and visit history', function () {
    Queue::fake();
    $module = moduleFixture(true, ['price_kb' => 5]);
    $user = moduleAccount(Role::Learner);
    economyCredit($user, 10, 1000);
    app(ContentAccess::class)->unlock($user, 'module-test-session', 'module', $module->id, $module->published_revision_id);
    $sale = DB::table('content_purchases')->sole();
    $id = app(Refunds::class)->request($user, 'module-test-session', 'Access', $sale->id, economyConfirm(['reason' => 'Chose the wrong adventure.']));
    app(Refunds::class)->review(moduleAccount(Role::Administrator), 'module-test-session', $id, economyConfirm(['decision' => 'Approved', 'review_notes' => 'Unused within courtesy period.']));
    expect(app(WalletLedger::class)->snapshot($user->id)['balance'])->toBe(10);
    expect(app(ContentAccess::class)->has($user->id, 'module', $module->id))->toBeFalse();
    $this->assertDatabaseHas('content_purchases', ['id' => $sale->id, 'status' => 'Refunded']);
    $this->assertDatabaseHas('publisher_earnings', ['purchase_id' => $sale->id, 'status' => 'Reversed']);
    expect(DB::table('economy_locks')->value('platform_minor'))->toBe(0);
    $this->assertDatabaseCount('wallet_operations', 3);
});

test('A08 creator erasure needs genuine consent manual review unchanged inventory and settled finances', function () {
    Queue::fake();
    $module = moduleFixture(true, ['price_kb' => 5]);
    $user = User::find($module->created_by);
    $data = economyConfirm(['profile_version' => $user->profile_version, 'confirmation_phrase' => 'DELETE MY ACCOUNT']);
    expect(fn () => app(AccountDeletion::class)->delete($user, 'module-test-session', $data))->toThrow(ValidationException::class);
    $review = app(CreatorErasure::class)->request($user, 'module-test-session', economyConfirm(['retention_consent' => true]));
    app(CreatorErasure::class)->review(moduleAccount(Role::Moderator), 'module-test-session', $review, economyConfirm(['decision' => 'Approved', 'privacy_reviewed' => true, 'review_notes' => 'Inspected every retained revision and scenario.']));
    app(AccountDeletion::class)->delete($user, 'module-test-session', $data + ['retention_consent' => true]);
    expect($user->fresh()->account_status->value)->toBe('Deleted');
    expect(app(ContentAccess::class)->price($module->publishedRevision, $user->id))->toBe(0);
    $this->assertDatabaseHas('learning_modules', ['id' => $module->id, 'status' => 'Published']);
    $this->assertDatabaseHas('creator_erasure_reviews', ['id' => $review, 'state' => 'Consumed']);
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('marking a notification preserves the list filter and a populated page', function (string $state, int $total, int $page, int $expectedPage) {
    $user = User::factory()->create();
    $ids = [];
    for ($i = 0; $i < $total; $i++) {
        $ids[] = $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id, 'type' => 'account.notice', 'notifiable_type' => User::class,
            'notifiable_id' => $user->id, 'data' => json_encode(['title' => "Notice $i"]),
            'read_at' => null, 'created_at' => now()->subSeconds($i), 'updated_at' => now(),
        ]);
    }
    moduleSignIn($this, $user);
    $this->get(route('notifications.index', ['state' => $state, 'page' => $page]))
        ->assertOk()->assertSee('name="state" value="'.$state.'"', false)
        ->assertSee('name="page" value="'.$page.'"', false);

    $expected = array_filter(['state' => $state === 'all' ? null : $state, 'page' => $expectedPage > 1 ? $expectedPage : null]);
    $target = $ids[($page - 1) * 15];
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $this->post(route('notifications.read', $target), ['state' => $state, 'page' => $page])
            ->assertRedirect(route('notifications.index', $expected));
    }
    expect(DB::table('notifications')->whereNotNull('read_at')->count())->toBe(1);
    $expectedCount = min(15, $total - ($state === 'unread' ? 1 : 0) - ($expectedPage - 1) * 15);
    $this->get(route('notifications.index', $expected))->assertOk()
        ->assertViewHas('notifications', fn ($items) => $items->currentPage() === $expectedPage && $items->count() === $expectedCount);
})->with([
    'all page two' => ['all', 31, 2, 2],
    'unread page two' => ['unread', 31, 2, 2],
    'last item on unread page' => ['unread', 16, 2, 1],
    'last unread notification' => ['unread', 1, 1, 1],
]);

test('notification navigation rejects invalid context before changing read state and cannot read another account notice', function () {
    $owner = User::factory()->create();
    $id = (string) Str::uuid();
    DB::table('notifications')->insert([
        'id' => $id, 'type' => 'account.notice', 'notifiable_type' => User::class,
        'notifiable_id' => $owner->id, 'data' => json_encode(['title' => 'Private notice']),
        'read_at' => null, 'created_at' => now(), 'updated_at' => now(),
    ]);
    moduleSignIn($this, $owner);
    foreach ([['state' => 'invalid'], ['page' => 0], ['page' => 'invalid'], ['page' => 2147483648]] as $context) {
        $this->postJson(route('notifications.read', $id), $context)->assertUnprocessable();
    }
    expect(DB::table('notifications')->where('id', $id)->value('read_at'))->toBeNull();
    moduleSignIn($this, User::factory()->create());
    $this->post(route('notifications.read', $id), ['state' => 'unread', 'page' => 2])->assertNotFound();
    expect(DB::table('notifications')->where('id', $id)->value('read_at'))->toBeNull();
});

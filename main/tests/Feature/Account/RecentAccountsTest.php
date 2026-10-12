<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('G01 defaults to the newest ten accounts and expands only through explicit filters or settings', function () {
    $admin = moduleAccount(Role::Administrator);
    User::factory()->count(22)->create(['created_at' => now()->subDay()]);
    $newest = User::factory()->create(['created_at' => now()->addMinute()]);
    moduleSignIn($this, $admin);
    $page = $this->get(route('account-governance.index'))->assertOk();
    expect($page->viewData('accounts')->count())->toBe(10)->and($page->viewData('accounts')->first()->id)->toBe($newest->id);
    expect($page->viewData('accounts')->hasMorePages())->toBeFalse();
    $this->get(route('account-governance.index', ['page' => 2]))->assertViewHas('accounts', fn ($rows) => $rows->count() === 10 && ! $rows->hasMorePages());
    $this->get(route('account-governance.index', ['scope' => 'all', 'per_page' => 20]))->assertViewHas('accounts', fn ($rows) => $rows->count() === 20 && $rows->hasMorePages());
    $this->get(route('account-governance.index', ['reset' => 1]))->assertViewHas('expanded', false);
});

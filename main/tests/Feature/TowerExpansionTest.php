<?php

use App\Enums\Role;
use App\Models\TowerLevel;
use App\Services\Games\QuizAuthoring;
use App\Services\Games\QuizQuestions;
use App\Services\Games\TowerAuthoring;
use App\Services\Games\TowerCurriculumAudit;
use App\Services\Gamification\TowerProgression;
use App\Services\Gamification\TowerTracks;
use Database\Seeders\TowerLevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('three tracks preserve historical classification and order appended floors within their own track', function () {
    $this->seed(TowerLevelSeeder::class);
    $tracks = app(TowerTracks::class);
    expect($tracks->query('beginner')->count())->toBe(35)->and($tracks->query('intermediate')->count())->toBe(35)->and($tracks->query('expert')->count())->toBe(10);
    $user = moduleAccount(Role::Learner);
    moduleSignIn($this, $user);
    $expert = TowerLevel::where('position', 71)->sole();
    $second = TowerLevel::where('position', 72)->sole();
    $this->get(route('tower.show', $expert))->assertOk()->assertSee('Loop invariants');
    $this->get(route('tower.show', $second))->assertForbidden();
    $this->postJson(route('tower.complete', [$second, $second->current_revision_id, 0]), app(TowerCurriculumAudit::class)->solution($second->currentRevision->stages[0]))->assertForbidden();
    $stage = $expert->currentRevision->stages[0];
    $this->postJson(route('tower.complete', [$expert, $expert->current_revision_id, 0]), app(TowerCurriculumAudit::class)->solution($stage))
        ->assertOk()->assertJsonPath('tower.next', route('tower.show', $second));
    $this->assertDatabaseCount('tower_clearances', 1);
    $this->assertDatabaseCount('xp_awards', 0);
    $this->assertDatabaseCount('learning_activity_days', 1);
    $this->get(route('tower.show', $second))->assertOk();
    $this->get(route('tower.show', TowerLevel::where('position', 51)->sole()))->assertForbidden();
    $snapshot = app(TowerProgression::class)->snapshot($user->id, 'expert');
    expect($snapshot['next']->position)->toBe(72)->and($snapshot['track_counts']['expert'])->toBe(['total' => 10, 'completed' => 1]);
});

test('track choice survives map and level navigation without granting admission or changing another account', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Instructor);
    moduleSignIn($this, $user);
    $this->get(route('home', ['track' => 'expert']))->assertOk()->assertSee('Start level 71')->assertSee('0 / 10 cleared');
    $this->get(route('home'))->assertOk()->assertSee('Start level 71');
    $this->get(route('home', ['track' => ['expert']]))->assertOk()->assertSee('Start level 71');
    $this->get(route('tower.show', TowerLevel::where('position', 26)->sole()))->assertOk();
    $this->get(route('home'))->assertOk()->assertSee('Start level 26');
    moduleSignIn($this, moduleAccount(Role::Learner));
    // A reused test session may retain a cosmetic preference; no clearance is transferred.
    $this->assertDatabaseCount('tower_clearances', 0);
    $this->assertDatabaseCount('learning_activity_days', 0);
});

test('guests cannot select a saved advanced track or bypass their three trials', function () {
    $this->seed(TowerLevelSeeder::class);
    $this->get(route('home', ['track' => 'expert']))->assertOk()->assertSee('Start level 1');
    $this->get(route('tower.show', TowerLevel::where('position', 71)->sole()))->assertRedirect(route('welcome'));
    $this->assertDatabaseCount('tower_clearances', 0);
});

test('track boundaries continue to their appended floors and a finished track returns to its own map', function () {
    $this->seed(TowerLevelSeeder::class);
    $user = moduleAccount(Role::Learner);
    $tracks = app(TowerTracks::class);
    foreach (['beginner' => [25, 51], 'intermediate' => [50, 61], 'expert' => [80, null]] as $track => [$position, $next]) {
        foreach ($tracks->query($track)->where('position', '<', $position)->get() as $prior) {
            DB::table('tower_clearances')->insert(['user_id' => $user->id, 'level_id' => $prior->id, 'revision_id' => $prior->current_revision_id, 'completed_at' => now()]);
        }
        $level = TowerLevel::where('position', $position)->sole();
        foreach ($level->currentRevision->stages as $index => $stage) {
            $result = app(TowerProgression::class)->complete($user, 'module-test-session', $level, $level->current_revision_id, $index, app(TowerCurriculumAudit::class)->solution($stage));
        }
        expect($result['tower']['next'])->toBe($next ? route('tower.show', TowerLevel::where('position', $next)->sole()) : route('home', ['track' => $track]));
    }
    moduleSignIn($this, $user);
    $this->get(route('home', ['track' => 'expert']))->assertOk()->assertSee('Track cleared. Choose another adventure.');
});

test('a track without published floors is described as unavailable rather than completed', function () {
    moduleSignIn($this, moduleAccount(Role::Learner));
    $this->get(route('home', ['track' => 'expert']))->assertOk()->assertSee('This track is being prepared.')->assertDontSee('Track cleared.');
});

test('new mixed quizzes preserve teaching notes through editing and reject every plausible wrong answer', function () {
    $entries = array_slice((new TowerLevelSeeder)->curriculum(), 50);
    foreach ($entries as $entry) {
        foreach ($entry['stages'] as $stage) {
            $typed = app(TowerAuthoring::class)->stages([$stage])[0];
            if ($stage['template'] !== 'choice-quiz') {
                continue;
            }
            expect($typed['briefing'])->toBe($stage['briefing']);
            $input = app(TowerCurriculumAudit::class)->solution($typed);
            expect(app(QuizAuthoring::class)->succeeds($typed, $input))->toBeTrue();
            foreach (app(QuizAuthoring::class)->questions($typed) as $question) {
                $wrong = match ($question['type'] ?? 'choice') {
                    'blank' => 'not a valid answer',
                    'matching' => array_fill_keys(array_column($question['pairs'], 'id'), $question['pairs'][0]['id']),
                    default => collect($question['options'])->firstWhere('id', '!=', $question['answer'])['id'],
                };
                expect(app(QuizQuestions::class)->succeeds($question, $wrong))->toBeFalse();
                $badInput = $typed['version'] === 1 ? ['answer' => $wrong] : ['answers' => array_replace($input['answers'], [$question['id'] => $wrong])];
                expect(app(QuizAuthoring::class)->succeeds($typed, $badInput))->toBeFalse();
            }
        }
    }
    $stage = $entries[0]['stages'][0];
    $stage['briefing']['script'] = 'execute me';
    expect(fn () => app(TowerAuthoring::class)->stages([$stage]))->toThrow(ValidationException::class);
});

test('algorithm examples agree with independent numeric traces rather than only the saved answer key', function () {
    $entries = (new TowerLevelSeeder)->curriculum();
    $answer = function (int $floor, string $id) use ($entries): string {
        $questions = app(QuizAuthoring::class)->questions($entries[$floor - 1]['stages'][0]);

        return collect($questions)->firstWhere('id', $id)['accepted'][0];
    };
    $a = [2, 4, 4, 4, 9];
    foreach ([4 => 'bound', 10 => 'absent'] as $target => $id) {
        $lo = 0;
        $hi = count($a);
        while ($lo < $hi) {
            $mid = $lo + intdiv($hi - $lo, 2);
            if ($a[$mid] < $target) {
                $lo = $mid + 1;
            } else {
                $hi = $mid;
            }
        }
        expect($answer(71, $id))->toBe((string) $lo);
    }
    $capacity = 1;
    $copies = 0;
    for ($size = 0; $size < 8; $size++) {
        if ($size === $capacity) {
            $copies += $size;
            $capacity *= 2;
        }
    }
    expect($answer(72, 'copies'))->toBe((string) $copies);
    $dp = array_fill(0, 7, 100);
    $dp[0] = 0;
    for ($sum = 1; $sum <= 6; $sum++) {
        foreach ([1, 3, 4] as $coin) {
            if ($coin <= $sum) {
                $dp[$sum] = min($dp[$sum], 1 + $dp[$sum - $coin]);
            }
        }
    }
    expect($answer(76, 'minimum'))->toBe((string) $dp[6]);
    $best = array_fill(0, 5, 0);
    for ($c = 4; $c >= 2; $c--) {
        $best[$c] = max($best[$c], $best[$c - 2] + 3);
    }
    expect($answer(77, 'once'))->toBe((string) $best[4]);
    $best = array_fill(0, 5, 0);
    for ($c = 2; $c <= 4; $c++) {
        $best[$c] = max($best[$c], $best[$c - 2] + 3);
    }
    expect($answer(77, 'ascending'))->toBe((string) $best[4]);
    $edges = [['S', 'A', 7], ['S', 'B', 2], ['B', 'A', 3], ['A', 'T', 1], ['B', 'T', 9]];
    $dist = ['S' => 0, 'A' => 100, 'B' => 100, 'T' => 100];
    for ($i = 0; $i < 3; $i++) {
        foreach ($edges as [$u, $v, $w]) {
            $dist[$v] = min($dist[$v], $dist[$u] + $w);
        }
    }
    expect($answer(75, 'weighted'))->toBe((string) $dist['T']);
});

test('additive track migration and seeding preserve customized floors history and historical fallback classification', function () {
    $this->seed(TowerLevelSeeder::class);
    $level = TowerLevel::where('position', 51)->sole();
    $level->forceFill(['skill_track' => null])->save(); // Reproduce a pre-expansion custom floor.
    $level->currentRevision->update(['title' => 'An existing owner-created floor']);
    $before = $level->fresh()->toArray();
    $revision = $level->currentRevision->toArray();
    $this->seed(TowerLevelSeeder::class);
    expect($level->fresh()->toArray())->toBe($before)->and($level->currentRevision->fresh()->toArray())->toBe($revision);
    expect(app(TowerTracks::class)->key($level))->toBe('intermediate');
    expect(app(TowerTracks::class)->query('intermediate')->whereKey($level->id)->exists())->toBeTrue();
    expect(app(TowerTracks::class)->query('beginner')->whereKey($level->id)->exists())->toBeFalse();
    $migration = require database_path('migrations/2026_10_18_000002_add_tower_skill_tracks.php');
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'Explicit Tower tracks exist');
});

test('private curriculum audit fails when a retained revision is unverified even if every seed proposal passes', function () {
    $this->seed(TowerLevelSeeder::class);
    $revision = TowerLevel::where('position', 71)->sole()->currentRevision;
    $stage = config('arcade.string-forge');
    $stage['scenario'] = ['start' => 'cannot', 'target' => 'reach this'];
    $revision->update(['stages' => [$stage]]);
    $this->artisan('kody:tower-curriculum-audit')->assertFailed();
    $this->artisan('kody:tower-curriculum-audit', ['--seed-only' => true])->assertSuccessful();
});

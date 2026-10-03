<?php

use App\Actions\Account\LoginAccount;
use App\Actions\Account\RegisterAccount;
use App\Models\User;
use App\Services\Account\EmailVerificationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $input = json_decode(Crypt::decryptString(file_get_contents($argv[2])), true, flags: JSON_THROW_ON_ERROR);
    DB::statement("SET application_name = 'kody-account-concurrency'");
    $session = app('session')->driver();
    $session->start();

    $result = match ($argv[1]) {
        'register' => app(RegisterAccount::class)->handle($input) ? 'created' : 'error',
        'resend' => app(EmailVerificationService::class)->request(User::where('email', $input['email'])->sole()) ? 'queued' : 'limited',
        'verify' => app(EmailVerificationService::class)->verify($input['token']) ? 'verified' : 'invalid',
        'login' => strtolower(app(LoginAccount::class)->attempt($input['email'], $input['password'], $session)->name),
        'confirm' => (function () use ($input, $session): string {
            $session->put('login_confirmation', $input['confirmation']);

            return strtolower(app(LoginAccount::class)->confirm($session)->name);
        })(),
    };
    echo $result."\n";
} catch (ValidationException) {
    echo "duplicate\n";
} catch (Throwable) {
    // Test diagnostics must never print input tokens, credentials or provider details.
    echo "error\n";
    exit(1);
}

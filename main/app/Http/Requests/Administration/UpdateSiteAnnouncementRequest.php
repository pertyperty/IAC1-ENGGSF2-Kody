<?php

namespace App\Http\Requests\Administration;

use App\Models\User;
use App\Services\Administration\SiteManagement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSiteAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manageSite', User::class);
    }

    public function rules(): array
    {
        return app(SiteManagement::class)->rules();
    }
}

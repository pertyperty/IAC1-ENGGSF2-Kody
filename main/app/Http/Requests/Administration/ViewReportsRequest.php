<?php

namespace App\Http\Requests\Administration;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ViewReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewReports', User::class);
    }

    protected function prepareForValidation(): void
    {
        $defaults = ['format' => 'html', 'type' => 'accounts', 'from' => CarbonImmutable::now('Asia/Manila')->subDays(29)->toDateString(),
            'to' => CarbonImmutable::now('Asia/Manila')->toDateString()];
        $this->merge(array_replace($defaults, $this->session()->get('system_report_filters', []), $this->query->all()));
    }

    public function rules(): array
    {
        return ['format' => ['required', Rule::in(['html', 'pdf', 'csv', 'xlsx'])], 'type' => ['required', Rule::in(['overview', 'accounts', 'content', 'learning', 'execution', 'economy', 'rewards', 'engagement', 'governance', 'operations'])],
            'from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'group' => ['sometimes', Rule::in(['day', 'month', 'year'])]];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['from', 'to'])) {
                return;
            }
            if (CarbonImmutable::parse($this->input('from'))->diffInDays(CarbonImmutable::parse($this->input('to'))) > 365) {
                $validator->errors()->add('to', 'Choose a report window of at most 366 days.');
            }
        }];
    }
}

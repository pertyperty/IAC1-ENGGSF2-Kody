<?php

namespace App\Http\Requests\Challenges;

use App\Http\Requests\Content\ReviewPublicationRequest;
use App\Models\CodingChallenge;

class ReviewChallengeRequest extends ReviewPublicationRequest
{
    public function authorize(): bool
    {
        $challenge = $this->route('challenge');

        return $challenge instanceof CodingChallenge && $this->user()?->can('review', $challenge);
    }

    public function rules(): array
    {
        return parent::rules() + ['confirmed' => ['required', 'accepted']];
    }
}

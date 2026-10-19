# Form guidance inventory
Reviewed 18 October 2026. This inventory links to the actual Form Requests; request validation and domain authorization remain authoritative. Rule excerpts are source indexes, not complete descriptions of closures, custom rules, inherited rules or dynamic role/session eligibility.
Public identity is the username. Registration collects username/email/password/confirmation only; profile names are optional and separately editable. Instructor credentials belong to the verified-account application flow.
| Surface | Guidance and enforcement |
| --- | --- |
| Signup and login | Explicit associated help explains exact-case username versus case-insensitive email, 6–30 / 100 character limits and 12–32 mixed-character passwords. Password confirmation must match. Existing usernames remain valid; no new restrictive charset or canonicalization. |
| Profile / support corrections | Names are optional, maximum 50 characters, Unicode letters/marks with spaces, apostrophes and hyphens. Email/password changes keep password re-authentication; support corrections retain current password, audit, version and session rules. |
| Creator studios | Native bounds produce associated character/range help. Existing template, quiz, file MIME/size and scenario guidance remains; executable uploads never become accepted through UI help. |
| Staff / finance forms | Server policies, current-password/version/confirmation checks remain. Native-bound hints do not imply a user is authorized. Shared error summaries associate matching fields with aria-invalid/describedby and focusable correction links. |
| Games and quizzes | Dynamic interpreter controls are excluded from automatic form hints. Their briefings, bounded controls and inline feedback explain the task; preview results are not admissions or saved progress. |
Implementation follows [W3C form instructions](https://www.w3.org/WAI/tutorials/forms/instructions/): visible help associated with its field; placeholders are not labels. Server errors remain visible after toast dismissal. No blanket client-side validator replaces server rules.
## Request source index

### ApplyContributorRequest

[Source](../app/Http/Requests/Account/ApplyContributorRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `request_message` | `['required', 'string', 'max:500', 'not_regex:/\x00/'],` |
| `portfolio_link` | `['nullable', 'string', 'url:http,https', 'max:255'],` |
| `credential_document` | `['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('account.credentials.max_kilobytes')],` |
| `confirmed` | `['required', 'accepted'], 'user_id' => ['prohibited'], 'account_role' => ['prohibited'],` |
| `approval_status` | `['prohibited'], 'credential_path' => ['prohibited'],` |
| `account_age_days` | `['prohibited'], 'completed_modules_count' => ['prohibited'], 'completed_challenges_count' => ['prohibited']];` |

### ApplyInstructorRequest

[Source](../app/Http/Requests/Account/ApplyInstructorRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `institution_name` | `['required', 'string', 'max:100', 'not_regex:/\x00/'],` |
| `specialization` | `['required', 'string', 'max:100', 'not_regex:/\x00/'],` |
| `credential_document` | `['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('account.credentials.max_kilobytes')],` |
| `confirmed` | `['required', 'accepted'], 'user_id' => ['prohibited'], 'account_role' => ['prohibited'],` |
| `verification_status` | `['prohibited'], 'credential_path' => ['prohibited']];` |

### ArchiveAccountRequest

[Source](../app/Http/Requests/Account/ArchiveAccountRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `profile_version` | `['required', 'integer', 'min:1'], 'user_id' => ['prohibited'], 'account_status' => ['prohibited']];` |

### DeleteAccountRequest

[Source](../app/Http/Requests/Account/DeleteAccountRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `confirmation_phrase` | `['required', 'string', Rule::in(['DELETE MY ACCOUNT'])], 'confirmed' => ['required', 'accepted'],` |
| `retention_consent` | `['sometimes', 'accepted'], 'user_id' => ['prohibited'], 'account_status' => ['prohibited']];` |

### GoogleLinkRequest

[Source](../app/Http/Requests/Account/GoogleLinkRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `profile_version` | `['required', 'integer', 'min:1'], 'identity_version' => ['required', 'integer', 'min:0']];` |

### LoginRequest

[Source](../app/Http/Requests/Account/LoginRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `email` | `['required', 'string', 'max:100'],` |
| `password` | `['required', 'string', 'max:1024'],` |

### ProfilePictureRequest

[Source](../app/Http/Requests/Account/ProfilePictureRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `remove_picture` | `['sometimes', 'boolean'],` |
| `picture` | `['required_unless:remove_picture,1', 'prohibited_if:remove_picture,1', 'file', 'mimetypes:image/jpeg,image/png', 'max:2048']];` |

### RegisterAccountRequest

[Source](../app/Http/Requests/Account/RegisterAccountRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `username` | `['required', 'string', 'min:6', 'max:30', Rule::unique('users', 'username')],` |
| `email` | `['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')],` |
| `first_name` | `['prohibited'], 'last_name' => ['prohibited'], 'name' => ['prohibited'],` |
| `password` | `AccountPasswords::rules(),` |
| `account_type` | `['sometimes', Rule::in(['learner'])],` |
| `account_role` | `['prohibited'],` |
| `account_status` | `['prohibited'],` |
| `email_verified_at` | `['prohibited'],` |
| `role` | `['prohibited'],` |
| `permissions` | `['prohibited'],` |
| `institution_name` | `['prohibited'], 'specialization' => ['prohibited'], 'credential_document' => ['prohibited'],` |

### ReviewContributorRequest

[Source](../app/Http/Requests/Account/ReviewContributorRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `moderator_feedback` | `['nullable', 'string', 'max:500', 'not_regex:/\x00/'], 'confirmed' => ['required', 'accepted'],` |
| `user_id` | `['prohibited'], 'account_role' => ['prohibited']];` |

### ReviewInstructorRequest

[Source](../app/Http/Requests/Account/ReviewInstructorRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `verification_notes` | `['required_if:decision,Rejected', 'nullable', 'string', 'max:255'],` |
| `credibility_reviewed` | `['required', 'accepted']];` |

### UpdateProfileRequest

[Source](../app/Http/Requests/Account/UpdateProfileRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `profile_version` | `['required', 'integer', 'min:1'],` |
| `username` | `['required', 'string', 'min:6', 'max:30', Rule::unique('users', 'username')->ignore($this->user()->id)],` |
| `first_name` | `['nullable', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],` |
| `last_name` | `['nullable', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],` |
| `email` | `['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($this->user()->id)],` |
| `current_password` | `['nullable', 'string', 'max:1024'],` |
| `password` | `['nullable', ...array_filter(AccountPasswords::rules(), fn ($rule) => $rule !== 'required')],` |
| `account_role` | `['prohibited'], 'account_status' => ['prohibited'], 'role' => ['prohibited'],` |
| `permissions` | `['prohibited'], 'email_verified_at' => ['prohibited'], 'user_id' => ['prohibited'],` |

### BrowseAccountsRequest

[Source](../app/Http/Requests/Administration/BrowseAccountsRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `status` | `['nullable', Rule::enum(AccountStatus::class)], 'scope' => ['nullable', Rule::in(['recent', 'all'])],` |
| `per_page` | `['nullable', Rule::in([10, 20, 50])], 'reset' => ['sometimes', 'boolean']];` |

### ChangeModeratorRequest

[Source](../app/Http/Requests/Administration/ChangeModeratorRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `current_password` | `['required', 'string', 'max:1024'], 'confirmed' => ['required', 'accepted'],` |
| `account_role` | `['prohibited'], 'account_status' => ['prohibited'], 'moderator_prior_role' => ['prohibited'],` |
| `resulting_role` | `['prohibited'], 'user_id' => ['prohibited']];` |

### CorrectProfileRequest

[Source](../app/Http/Requests/Administration/CorrectProfileRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `profile_version` | `['required', 'integer', 'min:1'],` |
| `username` | `['required', 'string', 'min:6', 'max:30', Rule::unique('users', 'username')->ignore($this->route('account')->id)],` |
| `first_name` | `['nullable', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],` |
| `last_name` | `['nullable', 'string', 'max:50', 'regex:/\A\p{L}[\p{L}\p{M}]*(?:[ \x{0027}\x{2019}-]\p{L}[\p{L}\p{M}]*)*\z/u'],` |
| `current_password` | `['required', 'string', 'max:1024'],` |
| `confirmed` | `['required', 'accepted'], 'support_requested' => ['required', 'accepted'],` |
| `email` | `['prohibited'], 'password' => ['prohibited'], 'name' => ['prohibited'],` |
| `account_role` | `['prohibited'], 'account_status' => ['prohibited'], 'role' => ['prohibited'],` |
| `permissions` | `['prohibited'], 'email_verified_at' => ['prohibited'], 'user_id' => ['prohibited'],` |
| `moderator_prior_role` | `['prohibited'],` |

### EnforceAccountRequest

[Source](../app/Http/Requests/Administration/EnforceAccountRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `profile_version` | `['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted'],` |
| `account_role` | `['prohibited'], 'account_status' => ['prohibited'], 'user_id' => ['prohibited']];` |

### ModerateContentRequest

[Source](../app/Http/Requests/Administration/ModerateContentRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `confirmed` | `['required', 'accepted'], 'flagged' => $this->input('action') === 'Withdrawn' ? ['required', 'accepted'] : ['sometimes', 'accepted'],` |
| `status` | `['prohibited'], 'staff_withdrawn_at' => ['prohibited'], 'created_by' => ['prohibited'],` |
| `published_revision_id` | `['prohibited'], 'content_id' => ['prohibited']];` |

### SaveTowerRequest

[Source](../app/Http/Requests/Administration/SaveTowerRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `concept` | `['required', 'string', 'max:100'], 'description' => ['required', 'string', 'max:1000'],` |
| `source_notes` | `['required', 'string', 'max:5000'], 'stages_json' => ['required', 'string', 'json', 'max:30000']];` |

### ViewReportsRequest

[Source](../app/Http/Requests/Administration/ViewReportsRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `to` | `CarbonImmutable::now('Asia/Manila')->toDateString()];` |
| `from` | `['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],` |
| `group` | `['sometimes', Rule::in(['day', 'month', 'year'])]];` |

### BrowseModulesRequest

[Source](../app/Http/Requests/BrowseModulesRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `q` | `['nullable', 'string', 'max:80'],` |
| `template` | `['nullable', 'string', Rule::in(array_keys($this->templates()))],` |

### SaveChallengeRequest

[Source](../app/Http/Requests/Challenges/SaveChallengeRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `tags` | `$this->input('tags', [])]);` |
| `title` | `['required', 'string', 'max:150', 'not_regex:/\x00/'],` |
| `description` | `['required', 'string', 'max:50000', 'not_regex:/\x00/'],` |
| `language` | `['required', Rule::in(array_keys(config('challenges.languages')))],` |
| `difficulty` | `['required', Rule::in(['Easy', 'Medium', 'Hard'])],` |
| `rules` | `['required', 'string', 'max:20000', 'not_regex:/\x00/'],` |
| `input_format` | `['required', 'string', 'max:5000', 'not_regex:/\x00/'],` |
| `output_format` | `['required', 'string', 'max:5000', 'not_regex:/\x00/'],` |
| `cpu_time_ms` | `['required', 'integer', 'between:'.$limits['min_cpu_time_ms'].','.$limits['max_cpu_time_ms']],` |
| `memory_kib` | `['required', 'integer', 'between:'.$limits['min_memory_kib'].','.$limits['max_memory_kib']],` |
| `test_cases` | `['required', 'array', 'list', 'min:1', 'max:'.$limits['max_test_cases']],` |
| `test_cases.*` | `['required', 'array:input,expected_output,hidden'],` |
| `test_cases.*.input` | `['present', 'nullable', 'string', 'max:'.$limits['max_case_characters'], 'not_regex:/\x00/'],` |
| `test_cases.*.expected_output` | `['present', 'nullable', 'string', 'max:'.$limits['max_case_characters'], 'not_regex:/\x00/'],` |
| `test_cases.*.hidden` | `['required', 'boolean']] + app(AccessSettings::class)->rules('challenge') + app(ChallengeMetadata::class)->rules();` |

### SubmitCodeRequest

[Source](../app/Http/Requests/Challenges/SubmitCodeRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `language` | `['required', Rule::in(array_keys(config('challenges.languages')))],` |
| `confirmed` | `['required', 'accepted'], 'source_code' => ['bail', 'required', 'string', function ($attribute, $value, $fail): void {` |

### DeleteOwnedContentRequest

[Source](../app/Http/Requests/Content/DeleteOwnedContentRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `created_by` | `['prohibited'], 'force' => ['prohibited']];` |

### ReviewPublicationRequest

[Source](../app/Http/Requests/Content/ReviewPublicationRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `review_notes` | `['nullable', 'required_if:decision,Rejected', 'string', 'max:255']];` |

### SaveCourseRequest

[Source](../app/Http/Requests/Content/SaveCourseRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `description` | `['required', 'string', 'max:5000'], 'category' => ['required', 'string', 'max:50'],` |
| `difficulty` | `['required', Rule::in(['Beginner', 'Intermediate', 'Advanced'])],` |
| `estimated_duration` | `['required', 'integer', 'min:1', 'max:10000'],` |
| `sequential` | `['sometimes', 'boolean'],` |
| `module_ids` | `['present', 'array', 'list', 'max:100'], 'module_ids.*' => ['required', 'integer', 'min:1', 'distinct']] + app(AccessSettings::class)->rules('course');` |

### SaveModuleRequest

[Source](../app/Http/Requests/Content/SaveModuleRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `record_version` | `['required', 'integer', 'min:1'],` |
| `title` | `['required', 'string', 'max:150'],` |
| `description` | `['required', 'string', 'max:5000'],` |
| `content` | `['required', 'string', 'max:50000'],` |
| `type` | `['required', Rule::in(['Article', 'Interactive', 'Video'])],` |
| `video_url` | `['nullable', 'required_if:type,Video', 'url:https', 'max:2000'],` |
| `attachments` | `['sometimes', 'array', 'list', 'max:5'],` |
| `attachments.*` | `['required', 'file', 'max:20480', 'extensions:pdf,docx,pptx'],` |
| `retain_attachments` | `['sometimes', 'array', 'list', 'max:5'],` |
| `retain_attachments.*` | `['required', 'integer', 'between:0,4', 'distinct'],` |
| `assessment_kind` | `['required', Rule::in($this->input('type') === 'Interactive' ? ['game', 'quiz', 'preset'] : ['none', 'game', 'quiz', 'preset'])],` |
| `managed_preset` | `['exclude_unless:assessment_kind,preset', 'required', 'integer', 'min:1'],` |
| `preset_title` | `['exclude_unless:assessment_kind,preset', 'required', 'string', 'max:100'],` |
| `game_preset` | `['exclude_unless:assessment_kind,game', 'required', Rule::in(array_keys(config('learning.instances') + config('arcade')))],` |
| `game_scenario` | `['exclude_unless:assessment_kind,game', 'nullable', 'string', 'max:4000'],` |
| `game_layout` | `['exclude_unless:assessment_kind,game', 'nullable', 'string', 'max:4000'],` |
| `game_title` | `['exclude_unless:assessment_kind,game', 'required', 'string', 'max:100'],` |
| `game_instructions` | `['exclude_unless:assessment_kind,game', 'required', 'string', 'max:1000'],` |
| `game_hint` | `['exclude_unless:assessment_kind,game', 'required', 'string', 'max:1000'],` |
| `game_learning_idea` | `['exclude_unless:assessment_kind,game', 'required', 'string', 'max:1000'],` |
| `quiz_title` | `['exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:100'],` |
| `quiz_question` | `['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:500'],` |
| `quiz_a` | `['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:300', 'different:quiz_b'],` |
| `quiz_b` | `['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:300'],` |
| `quiz_answer` | `['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', Rule::in(['a', 'b'])],` |
| `quiz_explanation` | `['exclude_with:quiz_questions', 'exclude_unless:assessment_kind,quiz', 'required', 'string', 'max:1000'],` |

### ReactToContentRequest

[Source](../app/Http/Requests/Engagement/ReactToContentRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `user_id` | `['prohibited'], 'content_id' => ['prohibited'], 'count' => ['prohibited'], 'opened' => ['prohibited']];` |

### ConfigureWeeklyEventRequest

[Source](../app/Http/Requests/Gamification/ConfigureWeeklyEventRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `revision_id` | `['required', 'integer', 'min:1'], 'record_version' => ['required', 'integer', 'min:0'],` |
| `rules` | `['required', 'string', 'max:20000', 'not_regex:/\x00/'], 'confirmed' => ['required', 'accepted']];` |

### CompleteGameRequest

[Source](../app/Http/Requests/Learning/CompleteGameRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `program.*` | `$this->routeIs('play.game') ? ['required', 'string', Rule::in(['up', 'down', 'left', 'right'])] : ['required', 'string', 'max:100'],` |
| `repeat` | `['sometimes', 'boolean'], 'conditional' => ['sometimes', 'boolean']];` |

### CompleteQuizRequest

[Source](../app/Http/Requests/Learning/CompleteQuizRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `answers` | `['required_without:answer', Rule::prohibitedIf($this->has('answer')), 'array', 'min:1', 'max:10'],` |
| `answers.*` | `app(QuizQuestions::class)->answerRules()];` |

### CompleteTowerRequest

[Source](../app/Http/Requests/Learning/CompleteTowerRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `repeat` | `['sometimes', 'boolean'], 'conditional' => ['sometimes', 'boolean'],` |
| `answer` | `['sometimes', 'string', 'max:30'], 'answers' => ['sometimes', 'array', 'min:1', 'max:10'], 'answers.*' => app(QuizQuestions::class)->answerRules()];` |

### PurchaseKodeBitsRequest

[Source](../app/Http/Requests/Transactions/PurchaseKodeBitsRequest.php)

| Field | Source rule excerpt |
| --- | --- |
| `current_password` | `['required', 'string', 'max:32'], 'confirmed' => ['required', 'accepted']];` |

# Public profiles, role badges, catalog card tags, and bookmark toggles — 25 November 2026

Implements three batched sitewide UI and UX improvements across all public, learner, creator, and staff views on top of `origin/main` (`099e7c3`).

## Batch 1: Public profiles, qualifications/bio/socials, role & verified badges, paginated work grid

- **Additive schema & validation:**
  - Added nullable `qualifications` (bounded to 1,000 characters via CHECK constraint) and nullable `social_links` (JSON) to `creator_profiles` (`database/migrations/2026_11_25_000001_add_qualifications_and_socials_to_creator_profiles.php`).
  - `SaveCreatorProfileRequest` and `CreatorProfiles` validate strict `https://` social links (`website`, `github`, `linkedin`, `x`), escape all public fields, preserve private credential isolation, and wipe automatically on account deletion (`AccountDeletion` deletes the `creator_profiles` row).
- **Distinct role & verification badges (`resources/views/components/role-badge.blade.php`, `creator-byline.blade.php`):**
  - Icon + semantic-token pills differentiate `Instructor` (teal/cap), `Contributor` (violet/code), `Moderator` (amber/shield), `Administrator` (rose/crown), and `Learner`, with a distinct emerald staff-reviewed `Verified <Role> · Qualifications reviewed` badge shown only when `$user->hasCreatorBadge()` is true.
  - Applied across uploaded/managed content bylines (Modules, Courses, Challenges, Lessons, Access previews), creator profiles, personal account profiles, and staff account governance.
- **Scalable creator profile (`CreatorProfileController::show`, `resources/views/creators/show.blade.php`, `creators/edit.blade.php`):**
  - Hero card with avatar, role/verified badges, headline, publication count strip, social links, and owner/staff actions.
  - Structured info cards surfacing Qualifications, Bio & experience, and Socials prominently on Instructor profiles (and whenever supplied on other creator profiles).
  - Tabbed (`All`, `Modules`, `Courses`, `Challenges`), searchable, paginated `.creator-work-grid` card layout so profiles scale smoothly to 1,000+ publications while keeping narrow revision projections (`id`, `title`).

## Batch 2: Definite catalog card tag containers & `+N more` overflow indicators

- **`resources/views/components/catalog-entry.blade.php` + `resources/css/design-system.css`:**
  - Added a definite, bordered `.catalog-entry-meta` container with consistent `min-height` and uniform heading/description tracks so cards with fewer tags or shorter copy never pull lower content upward.
  - Added `:tags` / `:max-tags` support rendering a dashed `+N more` pill (with full tooltip) whenever a module, course, or coding challenge has additional tags/topics/contents inside.
  - Applied across Modules (`learning/catalog.blade.php`), Courses (`learning/courses.blade.php` with `withCount('modules')`), Coding Challenges (`challenges/catalog.blade.php`), and Library (`learning/my-courses.blade.php`).

## Batch 3: Clear, unmistakable bookmark toggles & saved indicators

- **SVG sprite & component (`public/images/navigation.svg`, `resources/views/components/bookmark.blade.php`):**
  - Added `#bookmark` ribbon and `#link` icons to `navigation.svg`.
  - Detail-page `<x-bookmark>` toggles show an outline ribbon (`Bookmark`) when unsaved and a solid filled ribbon + highlighted accent pill (`Bookmarked`, `aria-pressed="true"`) plus a `Saved in Library` link when bookmarked.
  - Compact card bookmark toggles (preloaded in one query in `LearningController::catalog` and `CourseLearningController::catalog`, zero N+1) let authorized users see `Saved` status and toggle bookmarks directly from Module and Course catalog cards; Library cards display `Saved` badges; course lessons (`learning/course-lesson.blade.php`) and Tower levels (`tower/level.blade.php`) share the same filled active toggle styling.

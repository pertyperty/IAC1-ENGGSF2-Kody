# Challenge discovery — B06 / C01 / C05

Reviewed 2026-10-05. The acceptance audit found that B06's predefined category
and tag filters were missing. Challenges now use four educational topics and six
concept tags, alongside existing language, difficulty and literal title search.
Creators choose a topic and up to five distinct tags; these are discovery metadata,
not new pricing, prerequisite, rank or reward rules.

Content ownership and publication still belong to ChallengePublishing. Metadata
is stored on each immutable challenge revision, visible to reviewers and learners.
Pending replacements cannot change the catalog until staff approve publication.
Only the current Approved revision of Published, non-withdrawn challenges appears.
Catalog queries select public summary fields and never load test cases.

Migration `2026_10_05_000038_add_challenge_discovery_metadata` adds category and
JSONB tags, a category index and GIN tag index, plus PostgreSQL vocabulary/type/
size constraints. Vocabulary changes require matching schema migrations, not only
a config edit. Existing revisions default to Programming basics and no tags.
HTTP and application-service validation reject unknown categories, unbounded or
duplicate tags and malformed lists. No provider setup or production publication
is required; normal deployment migrations apply the additive schema.
Its down migration refuses to discard nondefault retained metadata. Application
rollback should preserve the additive columns and use a compatible previous release.

`ChallengeDiscoveryTest` covers combined/literal filters, old defaults, invalid
metadata, database constraints, pending-revision isolation, staff withdrawal and
hidden-test exclusion. Existing authoring and access tests remain applicable.

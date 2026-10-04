# Game-based module discovery

Supports B01/B02 and the approved game-first direction. Learning owns discovery;
Content Management remains the source of approved published module snapshots.

The public Learning catalog combines a bounded literal title/description search
with an optional assessment-template filter: Logic Garden, quizzes, Pixel Studio,
Number Machine, Sort Lab or Terminal Quest. Starter trails include both their
garden and quiz activities. Creator results match the current approved published
revision's assessment, including instances created from managed presets.

Each playground trial links to lessons using its game. Creator cards identify
their assessment style. Pagination retains search and template selections. Empty
results offer a return to the full catalog; these links grant no access.

Draft/replacement revisions, archived modules and staff-withdrawn modules remain
excluded. Guests can browse metadata and practice, but lesson routes still require
sign-in and the existing account/session authorization. Filter values are
allowlisted; PostgreSQL JSON filtering and escaped literal ILIKE search use bound
parameters. No schema, progression, reward, provider or deployment configuration
changes are required. This does not complete every original B01/B02 requirement.

`ModuleDiscoveryTest` covers combined search/filtering, literal wildcard input,
publication visibility, starter activities, invalid input, guest access and
pagination. Existing game and publication suites provide regression coverage.

Local verification (2026-10-04): 949 PostgreSQL tests / 6,818 assertions passed;
39 catalog/learning tests / 344 assertions passed with cached config, routes and
views; 32 frontend tests passed. Pint, Vite build, workflow YAML and diff checks
passed. Desktop and 390px mobile browser checks confirmed usable controls and no
horizontal overflow. These checks do not claim remote CI or production readiness.

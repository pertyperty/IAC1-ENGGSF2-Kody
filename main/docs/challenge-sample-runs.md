# Coding challenge sample runs

The internal web endpoint is POST /challenges/{challenge}/sample-runs. It requires the current verified, signed-in participant session, a valid CSRF token, an Accept: application/json header, and existing access to the challenge. The request contains revision_id (the currently published revision), language (the revision's configured language), and source_code (nonblank UTF-8, up to 64 KiB). Requests are limited to five per minute per account.

A successful response is JSON such as {"samples":[{"position":1,"passed":true}]}. It covers up to the first three public sample cases in revision order. It does not return execution output, create submissions or graded attempts, or award progress. Hidden cases are never selected. A changed revision returns 409; missing access returns 403; absent samples or invalid fields return 422; a disabled or unusable runner returns 503. Responses containing results are private and not cached.

There is no production runner binding or sample-run button in this release. Both the existing disabled Judge0 setting and an explicit runner binding must permit execution before the endpoint can dispatch. Tests substitute an in-process fake; they do not establish provider readiness, isolation, latency or capacity. Before a live binding is added, verify provider limits, budget admission, execution isolation and how slow or unavailable work is handled.

Creators can add optional starter code to a challenge revision. Reviewers can read it before publication. Learners with access can read the approved revision's starter code even while live execution is disabled. Publishing a replacement cannot edit the earlier starter code.

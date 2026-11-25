# Coding challenge feedback

The problem and public examples scroll independently beside the wider editor on desktop. On narrow screens, **Go to editor** jumps to the solution. Published starter code takes precedence over the language scaffold. **Add hint comment** inserts a comment without replacing saved work. Retry links still load the exact submitted source, and diagnostic links select the reported line when editing that attempt is still allowed.

The result page compares expected and submitted output for failed public examples when Judge0 supplies it. Compiler and runtime locations are limited to recognized filenames and valid lines in publicly tested source; unrecognized diagnostics still show the verdict and a troubleshooting hint. Hidden cases contribute verdict counts and accepted-suite aggregate measurements only. The worker never requests output or diagnostics for a hidden case. Stored public output previews are bounded, encrypted and rendered as text. Existing submissions retain their original verdicts and are not backfilled.

Apply the additive result-details migration before deploying or restarting a new code-execution worker. Provider settings, attempt limits, and readiness checks are unchanged. Ungraded sample execution still requires an enabled sample runner; this change does not enable one.

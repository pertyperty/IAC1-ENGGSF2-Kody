# Coding challenge workbench

The catalog, standard and weekly workspaces, and result pages share a visual language for difficulty, attempt status, and code. On desktop, the problem scrolls independently beside the editor. On narrow screens, a jump link goes directly to the editor. Public samples can be expanded with a keyboard or pointer; the first is open by default.

The attempt page distinguishes judge availability from an evaluated failure. It shows a test-case progress meter for pending and graded attempts, explains reported failures, and displays expected versus actual output only for public cases. Performance reports the slowest CPU time and peak memory when the judge captured those values for the full passing suite. The submitted code remains available beside the result on wide screens, and eligible retries reuse that code.

The workflow takes inspiration from HackerRank's documented test-result panel and LeetCode's editor toolbar and last-submission recovery. There is no new ungraded run and no change to attempt accounting, judging, or hidden-case handling. The browser checks use isolated seeded data; feature tests use HTTP fakes for judge responses.

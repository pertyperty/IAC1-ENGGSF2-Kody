# Email verification and Tower onboarding

The verification page uses a single six-digit field with a numeric keyboard and one-time code autofill. It accepts pasted spaces or hyphens and keeps leading zeroes. An invalid code keeps the email address filled in without repeating the code. Resending returns to the entry form, and a new email replaces the previous code and link. Code expiry, attempt limits and the secure link fallback are unchanged.

After verifying by code or link, sign in to choose Beginner, Intermediate or Expert. Read the short Tower tutorial, then open the selected Tower map. The first floor of that track is available without any earlier-track clears. Onboarding does not start a game or award progress. If sign-in is interrupted, the account resumes onboarding from its saved step. Existing learners who already signed in before this update keep their current entry flow.

Run the additive user-table migration with the normal release procedure before serving the new code. The migration defaults existing accounts to no pending onboarding. New first sign-ins set the flag as needed, and opening the map from the tutorial clears it.

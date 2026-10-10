# Authoring reliable tower levels

Use this guide for a human editor or a future content-research agent. Tower levels
are original teaching scenarios composed from typed games/quizzes, not arbitrary
uploaded programs. Current implementation and approved access/reward rules are in
[tower and media implementation](tower-and-media-implementation.md).

## Editorial workflow

1. Choose one objective, target language/version and prerequisite concept. Keep
   levels 1–3 accessible to guests and people new to programming. Use coherent
   chapter themes and increase reasoning difficulty rather than adding jargon.
2. Verify the concept against primary documentation, and record the exact source
   URL, section, version/revision or access date. Repository sources need their
   pinned commit, license and asset notices. Record provenance in source notes.
3. Write original prompts, hints, feedback and scenarios. Never assume a free
   course or public repository permits redistribution in a monetized platform.
   Keep attribution where required; do not use institution logos or imply endorsement.
4. Select a supported template and edit its labelled fields. Garden coordinates
   in advanced data are zero-based; pixel/array UI positions start at one.
   The virtual terminal teaches inspecting/copying files; language runtime code
   belongs to the separately isolated Judge0 challenge workflow.
5. Create a working solution, a plausible wrong answer and a useful hint. For
   quizzes, vary correct-answer positions, explain each objective and use 2–6
   distinct choices. Multiquestion completion requires every answer to be correct.
6. Test each stage in unsaved preview, then test ordered progression through the
   level. Boss levels 10/20 require at least two complementary stages. Reuse a
   concept across game/quiz/terminal formats so the boss checks understanding.
7. Check desktop/mobile, keyboard, color-independent instructions and concise
   plain language. Peer-review technical accuracy and provenance before an
   Administrator publishes. Reload stale edits; never overwrite another editor.

## Primary source starting points

Use the [Python tutorial](https://docs.python.org/3/tutorial/controlflow.html)
for control-flow concepts and pin the intended Python version. Use
[Oracle's Java learning documentation](https://dev.java/learn/) for Java and
[the C++ standards committee](https://www.open-std.org/jtc1/sc22/wg21/) for standard
references; distinguish beginner explanations from normative language details.

[MIT OpenCourseWare's terms](https://ocw.mit.edu/pages/privacy-and-terms-of-use/)
and [Harvard CS50's license](https://cs50.harvard.edu/x/license/) describe
Attribution–NonCommercial–ShareAlike licensing. Kody's monetization plan means
their availability is not permission to adapt and redistribute their exercises
commercially. Use links/references and independently authored examples, or obtain
applicable permission before importing material. Each repository/third-party
asset still needs its own license check; a repository's popularity is not evidence
of correctness or license suitability. No such course content was imported into
the initial Kody tower.

## Typed configuration reference

The Administrator editor produces a list of 1–4 stages. Templates:

| Template | Editable scenario | Limits |
| --- | --- | --- |
| `command-garden` | Start, goal, path and crystals; sequence/loop/conditional basis | Existing 5×4 board; 12 instructions or the loop basis's 3 repeated twice; reachable goal, up to 4 crystals |
| `pixel-studio` | `pixels`: `column row color` strings | 1–9 unique cells on a 3×3 board; mint/peach/lavender; 12 instructions |
| `number-machine` | Integer `start`, `target` | −100 to 100; supported bounded arithmetic; 12 instructions |
| `sort-lab` | Integer `items` | 2–6 items, each 0–99; swaps by 1-based position; 12 instructions |
| `terminal-quest` | `files`, `destination`, required `content` | 1–8 files; safe flat names; content up to 300 characters; objective content must exist in a source file; 12 instructions |
| `choice-quiz` | Version 1 single question or version 2 questions | 1–10 questions, 2–6 choices; stable choice/question IDs; explicit correct answer and explanation |

Game prompts are `title`, `concept`, `instructions`, `hint`, `learningIdea`.
The publisher canonicalizes dimensions/command limits from the chosen basis;
changing these fields in raw JSON cannot create a new engine. Unknown templates
and executable HTML/JavaScript/PHP are not supported. Text is escaped in the UI.
Quiz answer keys are public practice data; these are not secret graded exams.

Example quiz stage:

```json
{
  "template": "choice-quiz", "version": 1, "title": "Follow the value",
  "question": "Python starts with x = 2, then runs x = x + 3. What is x?",
  "options": [{"id": "a", "label": "2"}, {"id": "b", "label": "5"}],
  "answer": "b", "explanation": "The assignment stores the computed value 5."
}
```

For more than one question use version 2 and a `questions` list whose items have
`id`, `question`, `options`, `answer`, `explanation`; keep `title` at stage level.
For one question retain version 1. Source notes should explain what the reference
supports and why Kody's scenario is original or legitimately adapted.

## Prompt for a future content agent

> Research an original Kody tower lesson for [position/topic/language/version].
> Read AGENTS.md, this guide and the tower implementation record first. Verify
> concepts against primary sources and record exact URLs/version or repository
> commit, license and attribution. Do not copy noncommercial course exercises
> into monetized content or import executable game code/assets without permission
> and a license/security review. Produce a 1–4-stage typed configuration, concise
> instructions, meaningful hint, objective explanation, working solution, wrong
> attempt and editorial source notes. Boss positions divisible by 10 need at least
> two ordered stages. Keep current free access and no additional XP/KB rules.
> Preview all stages, run server-validation tests, check mobile/keyboard usability
> and prepare a reviewable revision. Only an authorized Administrator publishes.

This prompt does not schedule work, authorize external publishing or activate
payments. Adding new level positions/engine contracts is a separate code change
requiring migrations/validation and review; this release's editor edits existing
seeded positions while retaining historical clearances.

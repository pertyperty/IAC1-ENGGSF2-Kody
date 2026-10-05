# Launch curriculum review pack

This pack turns the existing original Kody examples into a concrete editorial
review queue. It creates no records, changes no prices and publishes nothing.
Designated Instructors customize/save lessons in `/create/curriculum`; staff
review each module before the Instructor composes and submits its course.
Two plans, eleven lessons and twelve starters remain in `config/curriculum.php`
and `config/creator-examples.php`. Estimated hours are editable planning values.

## First programming adventures

For people with no coding background. Suggested sequential order:

| Lesson | Learning objective | Activity / reviewer check |
| --- | --- | --- |
| What is a program? | Explain precise instructions using a familiar recipe or route | Reading: opening then Mark as read advances course progress only |
| A robot takes its first steps | Predict how ordered instructions change a destination | Garden: right, right, up, right, right; an incomplete path must not save a win |
| Spot the repeating trail | Recognize and repeat a short pattern | Garden loop: right, up, right with repeat enabled; no repeat must fail the objective |
| A robot makes a choice | Explain conditional crystal collection | Garden: first path plus conditional rule; reaching the flag without crystals must fail |
| Connect your first coding ideas | Distinguish sequence, loop and condition in a new situation | Three-question capstone: different destination, loop around steps, condition before collecting; all must be correct |

## Make data do something

An independent beginner plan; no invented prerequisite or payment gate. Creators
may choose sequential access for new enrollments.

| Lesson | Learning objective | Activity / reviewer check |
| --- | --- | --- |
| Meet the data your code uses | Identify numbers, coordinates, lists and files | Reading: explicitly explain that game coordinates start at 1 and language conventions vary |
| Paint a tiny picture with code | Locate cells with column/row coordinates | Pixel Studio: `paint 1 1 mint`, `paint 2 2 peach`; extra colored cells must fail |
| Transform a number | Predict successive updates to a stored variable | Number Machine: `add 3`, `multiply 2`; reversing these gives 7 rather than target 10 |
| Put a list in order | Compare indexed positions and preserve values during swaps | Sort Lab: `swap 1 2`, `swap 2 3`; explain how each swap changes order |
| Deliver a message in the terminal | Inspect, copy and verify named virtual files | Terminal Quest: `ls`, `cat hello.txt`, `cp hello.txt release.txt`, `cat release.txt`; copying without final inspection must fail |
| Connect values lists and files | Transfer arithmetic, list and terminal concepts | Capstone: 10, positions of two values, cat destination; a single wrong answer must not save completion |

The CLI is a virtual filesystem. Do not describe it as a host terminal or promise
Python/Java/C++ execution there. Those languages belong to the deferred Judge0
integration. Game commands and expected solutions must change together whenever
a creator customizes a scenario; these checks describe the supplied defaults.

## Coding quest follow-on

`sum-two`, `even-or-odd` and `countdown` provide original problem sets in Python,
Java and C++. Suggested topics: Numbers and arithmetic; concept tags: Variables,
Conditions and Loops respectively. Contributors or Instructors customize sample
and hidden edge cases, then staff review. Authoring can be prepared now; public
live evaluation waits for Judge0 setup. No browser practice result grants a code
challenge pass or Contributor eligibility.

## Publication acceptance

Review goal/instructions/hints together, try a wrong and correct attempt, and
check each capstone's feedback. Read text aloud for a beginner, define unfamiliar
terms and avoid color-only instructions. Check keyboard operation and 320/390px
layouts in both themes. Keep examples editable and clearly disclose saved versus
practice outcomes. Select the approved versions in the table order; confirm any
sequential policy before staff course approval. Existing enrollments retain their
original policy and pinned revisions after later edits.

The browser harness publishes synthetic copies through domain services solely
inside a disposable database, then verifies the actual UI and server persistence.
That evidence does not replace creator attribution, editorial approval or actual
publication in the main/staging application.

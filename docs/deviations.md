# Deviations and methodological notes

Where the implemented system differs from the midpoint proposal or the Software
Requirements Specification, each difference is recorded here with its reason.
Everything else is implemented as specified.

## Deviations from the specification

### FR5: language models

The SRS specifies GPT-4o-mini, Gemini 1.5 Flash and Llama 3 via Groq. Two of the
three were no longer available when the engine was implemented.

| Specified | Implemented | Reason |
|---|---|---|
| GPT-4o-mini | `gpt-4o-mini` | Unchanged |
| Gemini 1.5 Flash | `gemini-3.1-flash-lite` | Gemini 1.5 Flash has been withdrawn for new API consumers. Of the Flash models offered, the larger ones returned HTTP 503 "experiencing high demand" on every request when measured on 26 September 2026 (Gemini 3.6 and 3.7 Flash failed 3 of 3, 3.5 Flash 2 of 3), while 3.1 Flash Lite served 3 of 3. It is also the closer tier match to GPT-4o-mini. |
| Llama 3 via Groq | `qwen/qwen3.8-27b` via Groq | Groq has retired every Llama 3 chat model and retains only Llama safety classifiers. Qwen 3.8 27B preserves the original selection criterion of open-weight accessibility, and keeps the three models in three distinct model families. |

Model availability was verified directly against each provider's API rather than
inferred from documentation. Model identifiers are pinned to explicit versions,
never to moving aliases such as `gemini-flash-latest`, so evaluation runs remain
reproducible. Availability should be re-checked immediately before an
evaluation run.

### NFR3: authentication tokens

The SRS specifies JWT authentication tokens with a maximum expiry of 24 hours.
The system uses Laravel's session authentication instead. Passwords are hashed
with bcrypt as specified.

JWT is designed for stateless APIs, where a client holds a signed token and
presents it on each request. RegRadar is a server-rendered web application with
no public API, for which server-side sessions are the conventional and more
secure mechanism: a session can be revoked immediately, as account deactivation
(FR14) relies on, whereas a JWT remains valid until it expires. The intent of the
requirement, a bounded login lifetime, is exceeded: sessions expire after 120
minutes of inactivity, against the 24-hour ceiling.

### Framework version

The proposal (section 5.2.3) specifies Laravel 11. The system is built on
**Laravel 12** (12.71 at the time of writing). Laravel 12 was the current
release when development began and offers the same architecture and features
the proposal relies on.

## Scope decisions

### NFR8: evaluation output

NFR8 limits how long pull request data and LLM results are stored without
consent. It is enforced for analyses submitted through the application, which is
what the requirement addresses. Evaluation output written to
`storage/app/evaluation` is excluded: it is research data derived entirely from
public open-source repositories, contains no personal data, and must be retained
for the results to be reproducible.

### Could Have requirements not targeted

- **NFR9**, 95% uptime, is a property of a hosted deployment rather than of the
  prototype.
- **NFR10**, WCAG 2.1 AA, was a design target throughout (contrast, focus states,
  keyboard operation) but has not been formally audited.

## Methodological notes

### Prompt versions

`LLMService::PROMPT_VERSION` identifies the exact prompt text, and is written
into every evaluation row. Results produced under different versions are not
comparable and must not be pooled.

| Version | Change |
|---|---|
| 1 | Proof of concept, zero-shot only |
| 2 | Three strategies, richer summary, testing focus |
| 3 | Production and test files separated; rule for non-production changes; third worked example |
| 4 | Per-feature confidence; registry entries sent in name order |

Analyses run before version 4, including the midpoint demonstration, used
earlier versions.

### Pilot finding

Pilot testing before the evaluation found that every prompting strategy rated
test-only pull requests as High risk. Two defects caused this: the preprocessor
reported endpoints found inside test assertions as changed endpoints, and the
risk rubric had no rule for changes that never reach production. Both were
corrected in prompt version 3, before any measurement was taken. The keyword
baseline was given the same production and test awareness, so that it was not
handicapped relative to the language models.

### Confidence scores

FR13 confidence values are reported by the model itself. Self-reported LLM
confidence is not a calibrated probability. Each value is recorded per
prediction in the evaluation output so that its calibration can be measured
rather than assumed.

### Ground truth

Proposal section 5.3 specifies that ground-truth labels are established manually
by the researcher. Each entry in `database/evaluation/ground_truth.json` carries
a `label_status`, and the evaluation command warns while any label is not yet
`verified`. Figures should not be reported until every label has been checked
against its linked issue and fix commit.

### Evaluation registries

The three registries in `EvaluationRegistrySeeder` were constructed from each
repository's documentation and structure, as a QA engineer would know it, not
from the evaluation pull requests. Code areas are deliberately not exhaustive,
and each registry includes decoy features that no evaluation pull request
touches, so that precision measures something.

# Feature Registry

The Feature Registry tells RegRadar what your product's features are and which
code implements each one. When you analyse a pull request, RegRadar sends the
registry entries that apply to it to the language model, alongside the diff, and
the model rates the regression risk to each feature.

You maintain it from the **Feature Registry** page. No developer involvement or
code access is needed: everything here is functional knowledge a QA engineer
already has.

The formal definition is [`feature-registry.schema.json`](feature-registry.schema.json)
(JSON Schema, draft 2020-12). This page explains the same rules in plain terms.

## Fields

| Field | Required | What to enter |
|---|---|---|
| **Repository** | No | The GitHub repository the feature belongs to, as `owner/name`, for example `laravel/framework`. Leave blank if the feature applies to pull requests from any repository. |
| **Feature name** | Yes | A feature someone using or testing the product would recognise, such as `Password Reset`. Plain language, not a class name. |
| **Code areas** | Yes | Comma-separated class, file or directory names that implement the feature, such as `PasswordResetController, ResetPasswordMail`. At least one. |
| **API endpoints** | No | Comma-separated routes the feature exposes, such as `/api/password/forgot`. Usually blank for framework repositories. |
| **Description** | No | One sentence on what the feature covers. The model reads it, so a clear description helps it reason about indirect effects. |

## How entries are chosen for an analysis

RegRadar reads the repository from the pull request URL, then uses:

- every entry scoped to that repository, and
- every entry with no repository set.

Entries scoped to a different repository are ignored. A pull request from
`rails/rails` is never scored against `laravel/framework` features.

The evaluation (`php artisan regradar:evaluate`) is stricter: it uses **only**
entries scoped to each pull request's repository, so that general entries cannot
add noise to the measured results.

## Writing good entries

**Name code areas the way a QA engineer knows them.** List the classes and files
you would recognise from bug reports, logs and test plans. Do not try to list
every internal class. The registry is meant to reflect functional knowledge, and
part of what RegRadar is evaluated on is whether the model can connect a change
to a feature when the exact class name is not listed.

**Be specific.** A broad code area such as `Service` matches almost every diff,
and produces correspondingly broad results. `OrderService` is far more useful.

**Keep names stable.** The evaluation compares feature names as exact strings, so
renaming a feature after its ground truth has been written will turn correct
predictions into apparent errors.

**One feature per entry.** If a single entry covers two unrelated areas of the
product, the model cannot tell you which of them is at risk.

## What the model receives

Each applicable entry is sent in this shape, ordered by feature name:

```json
{
  "feature": "Form Request Validation",
  "code_areas": ["FormRequest", "ValidatesWhenResolved", "Validator", "rules"],
  "endpoints": ["/api/register", "/api/profile/update", "/api/login"],
  "description": "All form-based request validation using Laravel's validation layer"
}
```

The repository is used to select entries and is not itself sent to the model.

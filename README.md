# RegRadar

An LLM-powered regression risk analysis platform for QA engineers.

RegRadar reads a GitHub pull request diff, maps the change onto a QA-maintained
**Feature Registry**, and returns a ranked report of which product features are at
risk of regression, each with a stated reason, a confidence score and concrete
regression tests to carry out.

Final Year Project, Kubra Aslam (CB012428). Supervisor: Adshayani Pirapaharan.

## Requirements

- PHP 8.2 or later, with the `sqlite3` or `pdo_mysql` extension
- Composer 2
- Node.js 20 or later
- API keys for GitHub, OpenAI, Google Gemini and Groq

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Add your API keys to `.env`:

```
GITHUB_TOKEN=
OPENAI_API_KEY=
GEMINI_API_KEY=
GROQ_API_KEY=
```

SQLite works with no further configuration. To use MySQL instead, set the `DB_*`
values in `.env`. Then create the database, seed it, and build the interface:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Open <http://localhost:8000>.

## Demonstration accounts

Seeding creates one account per role. The password for each is set in
`database/seeders/UserSeeder.php`.

| Email | Role | Can |
|---|---|---|
| `qa@regradar.com` | QA Engineer | Run analyses, maintain the Feature Registry |
| `dev@regradar.com` | Developer | View reports, history and the registry (read-only) |
| `admin@regradar.com` | Admin | Everything, plus manage user accounts and roles |

Seeding also loads a demonstration registry and the three evaluation registries
for `laravel/framework`, `rails/rails` and `django/django`. Every seeder is
idempotent, so `php artisan db:seed` is safe to run again.

## Using it

1. **Feature Registry.** Map each product feature to the classes, files and
   endpoints that implement it. Set a repository to scope an entry to one
   codebase; see [docs/feature-registry.md](docs/feature-registry.md).
2. **New Analysis.** Paste a GitHub pull request URL and choose a model and a
   prompting strategy.
3. **Report.** Review the verdict, summary, findings by severity, and what to
   test. Filter by risk level, or export the report as a PDF.

## Models and prompting strategies

| Model | Provider |
|---|---|
| GPT-6 Luna (reasoning off) | OpenAI |
| Gemini 3.1 Flash Lite | Google |
| Qwen 3.8 27B | Groq |

Each can be run with zero-shot, few-shot or chain-of-thought prompting, giving
nine combinations. The models differ from those named in the proposal; see
[docs/deviations.md](docs/deviations.md).

## Evaluation (RO4)

The ablation study runs every model and strategy combination, plus a
deterministic keyword-matching baseline, over the curated pull requests in
`database/evaluation/ground_truth.json`:

```bash
# Baseline only. Makes no model API calls.
php artisan regradar:evaluate --baseline-only

# The full nine-condition ablation.
php artisan regradar:evaluate
```

Each pull request is scored only against its own repository's registry. Results
are written as one row per prediction to `storage/app/evaluation/*.csv`, ready
for scoring with pandas and scikit-learn.

Useful options: `--limit=3`, `--models=gpt-6-luna`, `--strategies=few-shot`,
`--skip-baseline`.

## Data retention (NFR8)

Analyses older than 90 days are deleted unless their owner has consented to keep
them under **Profile, Data retention**. To see what would be removed:

```bash
php artisan regradar:prune-analyses
```

This is a dry run. Deletion happens only with `--force`, which the scheduler
applies daily. The scheduler runs only when a cron entry calls
`php artisan schedule:run`, or while `php artisan schedule:work` is running.
The period is set by `REGRADAR_RETENTION_DAYS`.

## Tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database and make no API calls.

## Requirements coverage

| | Requirement | Status |
|---|---|---|
| FR1 | Register and sign in | Implemented |
| FR2 | QA Engineer, Developer (read-only) and Admin roles | Implemented |
| FR3 | Retrieve the pull request diff from GitHub | Implemented |
| FR4 | Preprocess the diff | Implemented |
| FR5 | Three LLM models | Implemented, with substituted models |
| FR6 | Three prompting strategies | Implemented |
| FR7 | Cross-reference against the Feature Registry | Implemented |
| FR8 | Ranked risk report with explanations | Implemented |
| FR9 | Feature Registry create, read, update, delete | Implemented |
| FR10 | Store results and registry in a database | Implemented |
| FR11 | Export the report as PDF | Implemented |
| FR12 | Filter the report by risk level | Implemented |
| FR13 | Confidence score per feature | Implemented |
| FR14 | Admin manages accounts and roles | Implemented |
| FR15 | Analysis history | Implemented |
| NFR1 | Report within 60 seconds | Met under normal conditions |
| NFR3 | bcrypt passwords, bounded sessions | Implemented, with sessions in place of JWT |
| NFR4 | Graceful handling of API failures | Implemented, with retry and backoff |
| NFR5 | New models without core changes | Implemented |
| NFR6 | Documented registry schema | [Implemented](docs/feature-registry.schema.json) |
| NFR7 | Modern browsers, no plugins | Implemented |
| NFR8 | 90-day retention without consent | Implemented |

NFR2 (SUS score) is measured in the user study. Deviations, scope decisions and
methodological notes are in [docs/deviations.md](docs/deviations.md).

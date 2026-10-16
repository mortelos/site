# Agent Instructions

## Laravel Vet

`laravel/vet` guards `composer install`, `require` and `update`: a package version that `vet.json` does not trust fails the command. Only a new dependency is a reason to stop and ask; clear everything else yourself.

- **New dependency: ask first.** Before you `composer require` a package that `composer.json` does not list yet, tell the user which package, what it is for and why the installed packages do not cover it, and wait for a yes. That yes covers the packages it pulls in. Installing or updating a package that is already listed, including `mortelos/*` and `laravel/framework`, needs no question.
- **Clear it with `bin/vet-agent`.** It runs `./vendor/bin/vet` with "Automatically, with my coding agent reading the changes first" and trusts the packages that review passes. Vet has no non-interactive option for that review; `bin/vet-agent` answers its prompts in a pseudo-terminal.
- **Non-zero exit: read the verdicts.** `WARN` means the review could not finish (files over the budget, not text, no answer): run `bin/vet-agent --trust-all` yourself and list those packages in the pull request. If the reviewing agent itself failed (rate limit, timeout), run `bin/vet-agent` once more first. `FAIL` means the review found a risk: stop, tell the user the package and what the review saw in plain words, and run `--trust-all` only after the user says to trust it. `--trust-all` trusts every package left in the list, so with a `FAIL` in it, do not run it for the `WARN`s either.
- Then run `composer install` again and commit `vet.json` with `composer.lock`. Never edit `vet.json` or `vendor/composer/installed.json` by hand, and never run `vet --init` or `vet --fresh` to get past a failure.

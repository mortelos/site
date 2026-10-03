# Agent Instructions

## Laravel Vet

`laravel/vet` guards `composer install`, `require` and `update`: a package version that `vet.json` does not trust fails the command. Clear it yourself with `bin/vet-agent`: it runs `./vendor/bin/vet` with "Automatically, with my coding agent reading the changes first" and records the packages that review cleared. When it exits non-zero, explain each flagged package and its warning to the user in plain words, and run `bin/vet-agent --trust-all` only after the user explicitly says to trust them. Then run `composer install` again and commit `vet.json` with `composer.lock`. Never edit `vet.json` by hand and never run `vet --init` or `vet --fresh` to clear a failure.

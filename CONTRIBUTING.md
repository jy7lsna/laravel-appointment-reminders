# Contributing to laravel-appointment-reminders

Thanks for taking the time to contribute. Bug reports, ideas, documentation fixes and code are all welcome. This guide takes you from cloning the repo to an opened pull request.

## Ways to help

- Report a bug or suggest a feature by opening an issue. Say what you expected, what happened, and the steps to reproduce it.
- Improve the README or the docs.
- Pick up an open issue. Issues labelled `good first issue` are a good place to start.

## Picking up an issue

1. Browse the open issues and choose one.
2. Comment on it to say you'd like to work on it, and wait for a maintainer to reply before you start.
3. Work on one issue at a time.
4. If you can't finish, say so in the issue so someone else can take it.

If you want to work on something that has no issue yet, open one first and describe your idea, so you don't spend time on a change that doesn't fit.

## Set up

You need PHP 8.3 or newer and Composer.

```bash
git clone https://github.com/<your-username>/laravel-appointment-reminders.git && cd laravel-appointment-reminders
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

To send real emails you need a `MAILTRAP_API_KEY` and a verified sender in `.env`. **The tests need none of that**: mail goes to `Mail::fake()` or Laravel's in-memory mailer.

## Run it

Keep these three running, each in its own terminal:

```bash
php artisan serve          # the booking site
php artisan queue:work     # sends the queued emails
php artisan schedule:work  # runs the reminder command every 5 minutes
```

Open http://localhost:8000, not `127.0.0.1`. The links in emails are signed for `APP_URL`, and the host is part of the signature.

## Tests and checks

```bash
./vendor/bin/pest      # or: php artisan test
composer lint          # Laravel Pint in test mode
vendor/bin/pint        # fixes style problems
composer audit:deps    # known vulnerabilities in Composer dependencies
```

Tests control time with `$this->travelTo()` and check emails with `Mail::fake()`.

Please add or update tests for the behavior you change, and run all the checks above before you open a pull request.

## Making your change

1. Fork the repository and create a branch from `main`. Use a short name that says what it does, such as `fix/expired-code-message`, `feature/dark-theme` or `docs/readme-typo`.
2. Keep each pull request focused on one change. Don't mix refactoring or formatting changes into it.
3. Write commit messages as a short, imperative summary of what changed (for example `Fix wrong time in reminder email`), and add a few lines of explanation if the reason isn't obvious.
4. Push your branch and open a pull request against `main`.

A good pull request description says:

- what the change does and why it is needed;
- how you tested it;
- which issue it closes, written as `Closes #123`;
- screenshots for any change to the user interface.

## Project layout

- `app/Services/`: booking, availability and calendar invite logic.
- `app/Console/Commands/`: the reminder command.
- `app/Mail/`: the email classes, with views in `resources/views/emails/`.
- `app/Http/`: controllers and form requests.
- `public/css/app.css`: the only stylesheet.
- `tests/Feature/`: the test suite.

## Keep secrets out

Never put API tokens, `.env` contents or real customer data in an issue, a pull request or a commit. `.env` is git-ignored, and the example file only contains placeholders.

## License

By contributing, you agree that your contributions are licensed under the project's [MIT license](LICENSE).

# Changelog

All notable changes to `laravel-sendables` are documented here.
This project follows [Semantic Versioning](https://semver.org/).

## [3.3.0] - 2026-11-10

### Added

- `canResend()` on `OtpVerifier` and the providers: whether the user can ask for another code at all. `false` for testers (the code is fixed) and passwords; when it is `false`, clients show neither a countdown nor a resend button.

### Fixed

- `getResendAfter()` now always tells the truth: it is exactly the time until `send()` accepts the next code, and `0` means now. `send()` refuses while it is above `0`, so the two can no longer disagree.
  - Testers, Firebase (the phone sends it) and passwords send nothing from the server, so they report `0`. Testers used to report the 60-second wait, so apps showed a countdown to tester numbers.
  - With no code sent in the last hour it reports `0`. It reported the first wait (60 seconds) while `send()` would have accepted, e.g. after a send that failed.
  - When more codes are in the last hour than `maxSendsPerHour` allows (the cap was lowered meanwhile), the wait runs until enough of them leave the hour, not just the oldest one.
- A `maxSendsPerHour` below 1 counts as 1.
- Two overlapping sends for one mobile no longer both go out: `SystemOtpProvider::send()` holds a cache lock per mobile; a send while another runs gets the `too-many-requests` error.
- Confirm picks the newest code even when two share a second.

### Security

- Tester numbers now get `maxWrongAttempts` too. Their code never changes, so wrong guesses are counted per number for a code's lifetime (`Otp::EXPIRE_AFTER`, 2 minutes) instead of per code. They were unlimited, so a fixed tester code could be guessed.

### Notes

- Send limits (`resendAfterSeconds`, `maxSendsPerHour`) and `verifiedForMinutes` only concern codes the server sends; entries for other providers under `limits.providers` are ignored, as before.
- Codes the server sends need a cache store with locks (file, database, redis, memcached, dynamodb, array).

## [3.2.0] - 2026-09-23

### Added

- **Limits for codes the server sends** (WhatsApp, SMS), per mobile number, configured under `otp.limits`:
  - `resendAfterSeconds` — the wait after the 1st, 2nd, 3rd... code of the last hour; the last value repeats. A single number means the same wait every time. Default `[60, 120, 300]`.
  - `maxSendsPerHour` — codes per hour per number. Default `10`.
  - `maxWrongAttempts` — wrong guesses allowed per code. Default `5`.
  - `verifiedForMinutes` — how long a verified code proves the number. Default `60`.
- **Per-provider limits**: a provider reads its own value first, then the default one, then `OtpProvider::DEFAULT_LIMITS`, so apps that already published their config keep working.
- **`getResendAfter()`** on `OtpVerifier` and the providers, returning the seconds until another code is allowed, so clients can show a countdown that matches the server.
- Errors `too-many-requests` and `too-many-attempts`, with Arabic and English messages.
- README section covering the limits and `getResendAfter()`.

### Changed

- Codes are generated with `random_int()` instead of `rand()`.

### Security

- A verified code now proves the number for `verifiedForMinutes` only. It used to be accepted forever and could be replayed.
- Wrong guesses are capped per code, and codes per number are capped per hour, which closes brute-force and cost-abuse paths.

### Notes

- **No database changes.** Send limits count rows already in the `otps` table, and wrong guesses are counted in the cache.
- Limits apply to providers that send from the server. Firebase (the phone sends it), testers and password logins are untouched.

## [3.1.0] - 2026-09-07

- Support Laravel 11, 12 and 13, and Guzzle 8.

## [3.0.1] - 2024-08-22

- Add the password provider.

## [3.0.0] - 2023-11-05

- Add the WhatsApp OTP provider, using an `otp_code` authentication template.
- **Breaking:** provider credentials moved to `sendables.services`.

## [2.0.1] - 2023-11-02

- Pass the OTP code to `smsProvider()` in `SystemOtpProvider`.

## [2.0.0] - 2023-03-28

- Support Laravel 9 and 10.

## [1.0.1] - 2022-12-12

- Add the OurSMS version 2 OTP provider.

## [1.0.0] - 2022-12-12

- Add the OurSMS version 2 SMS provider.

## [0.2.0] - 2022-06-26

- SMS: add the enable/disable sending option.

## [0.1.0] - 2021-09-23

- First release.

[3.2.0]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v3.1.0...v3.2.0
[3.1.0]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v2.0.1...v3.0.0
[2.0.1]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v1.0.1...v2.0.0
[1.0.1]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/HasanAlyazidi/laravel-sendables/compare/v0.2.0...v1.0.0
[0.2.0]: https://github.com/HasanAlyazidi/laravel-sendables/compare/0.1.0...v0.2.0
[0.1.0]: https://github.com/HasanAlyazidi/laravel-sendables/releases/tag/0.1.0

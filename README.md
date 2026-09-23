# laravel-sendables

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Travis](https://img.shields.io/travis/hasanalyazidi/laravel-sendables.svg?style=flat-square)]()
[![Total Downloads](https://img.shields.io/packagist/dt/hasanalyazidi/laravel-sendables.svg?style=flat-square)](https://packagist.org/packages/hasanalyazidi/laravel-sendables)


## Install

1. Add package to your `composer.json`

```bash
composer require hasanalyazidi/laravel-sendables
```

2. Migrate package tables or publish migrations first then migrate

```bash
php artisan migrate
```

---

## Publish package files

### Publish config file (Required)

```bash
php artisan vendor:publish --provider="HasanAlyazidi\Sendables\Providers\SendablesServiceProvider" --tag="config"
```

---

### Publish migrations (Optional)

```bash
php artisan vendor:publish --provider="HasanAlyazidi\Sendables\Providers\SendablesServiceProvider" --tag="migrations"
```

---

### Publish language resources (Optional)

#### English

```bash
php artisan vendor:publish --provider="HasanAlyazidi\Sendables\Providers\SendablesServiceProvider" --tag="resources-lang-en"
```

#### Arabic

```bash
php artisan vendor:publish --provider="HasanAlyazidi\Sendables\Providers\SendablesServiceProvider" --tag="resources-lang-ar"
```

#### All supported languages

```bash
php artisan vendor:publish --provider="HasanAlyazidi\Sendables\Providers\SendablesServiceProvider" --tag="resources-lang-all"
```

---

## Usage

### SMS

```php
$sms = new SMSNotification('SMS Message', '966000000000');
$sms->send();
```

### OTP

```php
$otp = new OtpVerifier('966000000000');
$otp->send();
```

- WhatsApp:\
    Add a message template named `otp_code`, type (`Authentication`) with your app supported languages.

### OTP limits

Codes sent by the server (WhatsApp, SMS) are limited per mobile number. Set them in
`config/sendables.php` under `otp.limits`; a provider takes its own value first, then the
default one, then `OtpProvider::DEFAULT_LIMITS`.

| Limit | Default | Meaning |
| --- | --- | --- |
| `resendAfterSeconds` | `[60, 120, 300]` | Wait after the 1st, 2nd, 3rd... code of the last hour; the last value repeats. A single number means the same wait every time. |
| `maxSendsPerHour` | `10` | Codes per hour per mobile number. |
| `maxWrongAttempts` | `5` | Wrong guesses allowed per code, then a new code is needed. |
| `verifiedForMinutes` | `60` | How long a verified code proves the number to `isAuthenticated()`. |

```php
'limits' => [
    'default' => OtpProvider::DEFAULT_LIMITS,

    'providers' => [
        OurSMSV2OtpProvider::class => [
            'resendAfterSeconds' => [120, 300],
            'maxSendsPerHour'    => 5,
        ],
    ],
],
```

Tell the client when it may ask for another code, and pass it in your send response:

```php
$otp->getResendAfter(); // seconds
```

A refused send returns `false` with the error `too-many-requests`, and too many wrong
guesses return `too-many-attempts`; both have messages in the package language files.
Limits apply to providers that send from the server, not to Firebase (the phone sends
it), testers or password logins.

---

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Security

If you discover any security-related issues, please email hassanx220@gmail.com instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.

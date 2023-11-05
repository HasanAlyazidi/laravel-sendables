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

---

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Security

If you discover any security-related issues, please email hassanx220@gmail.com instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.

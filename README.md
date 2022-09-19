# Digest

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/blomstra/digest.svg)](https://packagist.org/packages/blomstra/digest) [![Total Downloads](https://img.shields.io/packagist/dt/blomstra/digest.svg)](https://packagist.org/packages/blomstra/digest)

A [Flarum](https://flarum.org/) extension. Email digests for your forum users.

2 frequencies are included by default: daily and weekly.
The sending is implemented into the Flarum scheduler, so [it's the only thing you need to configure](https://docs.flarum.org/console#schedulerun).

You can optionally enable the "Single Digest" feature, which will bundle immediate notifications into a template similar to the digest for users who didn't configure a digest frequency.
This feature will group all different notifications sent during a single Flarum request lifecycle together, even if some are processed on an asynchronous queue.
If some jobs are asynchronous, all notifications for that request will be held on a queue until all jobs have finished processing so that a single email can be generated for each user.

## Installation

Install with composer:

```sh
composer require blomstra/digest:"*"
```

## Updating

```sh
composer update blomstra/digest
php flarum migrate
php flarum cache:clear
```

## Excluded Blueprints

A list of blueprints are excluded from both the Scheduled and Single Digests.
These blueprints will always use the built-in Flarum email template and send immediately in their own email.

The following blueprints are in the excluded list:

- `flarum/suspend`: all notifications
- `fof/byobu`: all notifications
- `fof/subscribed`: post flagged

The excluded list can be customized by developers via the container binding `blomstra.digest.excludedBlueprints`.

## Links

- [Packagist](https://packagist.org/packages/blomstra/digest)
- [GitHub](https://github.com/blomstra/flarum-ext-digest)
- [Discuss](https://discuss.flarum.org/d/PUT_DISCUSS_SLUG_HERE)

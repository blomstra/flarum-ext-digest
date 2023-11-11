<?php

/*
 * This file is part of blomstra/digest.
 *
 * Copyright (c) 2022 Team Blomstra.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Digest\Tests\integration\Console;

use Blomstra\Digest\Tests\integration\RunsConsoleTests;
use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Factory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Mail\MailQueue;
use PHPUnit\Framework\Assert;

class SendDigestsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RunsConsoleTests;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('blomstra-digest', 'flarum-subscriptions', 'flarum-mentions');

        $this->setting('mail_driver', 'log');
        $this->setting('flarum-mentions.allow_username_format', '1');
        $this->setting('blomstra-digest.singleDigest', '1');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'lightsail in title', 'created_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'user_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'not in title', 'created_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'user_id' => 2, 'comment_count' => 1],
                ['id' => 4, 'title' => 'hidden', 'created_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'last_posted_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::createFromDate(1975, 5, 21)->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::createFromDate(1985, 5, 21)->toDateTimeString(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>', 'number' => 1],
                ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::createFromDate(1995, 5, 21)->toDateTimeString(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>', 'number' => 1],
                ['id' => 4, 'discussion_id' => 4, 'created_at' => Carbon::createFromDate(2005, 5, 21)->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>', 'number' => 1],
            ],
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'receiver', 'email' => 'receiver@machine.local', 'is_email_confirmed' => 1, 'preferences' => $prefs = json_encode([
                    'notify_newPost_email'                      => true,
                    'notify_postMentioned_email'                => true,
                    'notify_userMentioned_email'                => true,
                    'flarum-subscriptions.notify_for_all_posts' => true,
                ])],
                ['id' => 4, 'username' => 'receiver2', 'email' => 'receiver2@machine.local', 'is_email_confirmed' => 1, 'preferences' => $prefs],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 3, 'subscription' => 'follow'],
                ['discussion_id' => 4, 'user_id' => 3, 'subscription' => 'follow'],
                ['discussion_id' => 4, 'user_id' => 4, 'subscription' => 'follow'],
            ],
        ]);
    }

    public function test_notifications_are_not_aggregated_then_sent_when_no_frequency_set()
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime(12, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => null, 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        Carbon::setTestNow($now->addDay());

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount(0);
    }

    public function test_notifications_are_aggregated_then_sent_daily_for_daily_frequency_when_no_hour_is_set_defaulting_to_midnight_time()
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime(12, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'daily', 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addDays(2)->setTime(00, 00));

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount(2);
    }

    public function test_notifications_are_aggregated_then_sent_weekly_for_weekly_frequency_when_no_hour_is_set_defaulting_to_midnight_time()
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime(12, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'weekly', 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addWeek()->addDay()->setTime(00, 00));

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount(2);
    }

    /** @dataProvider hours */
    public function test_notifications_are_aggregated_then_sent_daily_for_daily_frequency_when_hour_is_set(int $hour)
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime($hour, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'daily', 'digest_hour' => $hour, 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addDay());

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount(2);
    }

    /** @dataProvider hours */
    public function test_notifications_are_aggregated_then_sent_weekly_for_weekly_frequency_when_hour_is_set(int $hour)
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime($hour, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'weekly', 'digest_hour' => $hour, 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addWeek());

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount(2);
    }

    /** @dataProvider hours */
    public function test_notifications_are_aggregated_then_sent_daily_for_daily_frequency_when_hour_is_set_and_matches(int $hour)
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime($hour, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'daily', 'digest_hour' => 15, 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addDay());

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount($hour === 15 ? 2 : 0);
    }

    /** @dataProvider hours */
    public function test_notifications_are_aggregated_then_sent_weekly_for_weekly_frequency_when_hour_is_set_and_matches(int $hour)
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime($hour, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'weekly', 'digest_hour' => 20, 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addWeek());

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount($hour === 20 ? 2 : 0);
    }

    public function test_notifications_are_aggregated_but_not_sent_less_than_a_day_before_for_daily_frequency()
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime(12, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'daily', 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addDay()->subHours(2));

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount(0);
    }

    public function test_notifications_are_aggregated_but_not_sent_less_than_a_week_before_for_weekly_frequency()
    {
        $this->app();

        Carbon::setTestNow($now = Carbon::createFromDate(2023, 7, 21)->setTime(12, 00));

        User::query()
            ->whereIn('id', [3, 4])
            ->update(['digest_frequency' => 'weekly', 'last_digest_sent_at' => $now]);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(0, $queuedBlueprints);

        $this->mimicUserMentionNotification(3);
        $this->mimicSubscriptionsNotification(2, 1);
        $this->mimicSubscriptionsNotification(1, 1);
        $this->mimicSubscriptionsNotification(1, 4);

        $queuedBlueprints = $this->database()->table('digest_queued_blueprints')->count();
        $this->assertEquals(5, $queuedBlueprints);

        Carbon::setTestNow($now->addWeek()->subDay());

        $this->app()->getContainer()->instance(Mailer::class, $mailer = new FakeMailer());

        $this->runCommand([
            'command' => 'digest:send',
        ]);

        $mailer->assertSentCount(0);
    }

    private function mimicUserMentionNotification(int $userId): void
    {
        $this->app();

        $user = User::query()->findOrFail($userId);

        $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'title'   => 'foo bar',
                            'content' => "@$user->username foo bar",
                        ],
                    ],
                ],
            ])
        );
    }

    private function mimicSubscriptionsNotification(int $userId, int $discussionId)
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => $userId,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'content' => 'foo bar',
                        ],
                        'relationships' => [
                            'discussion' => [
                                'data' => [
                                    'id' => $discussionId,
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );
    }

    public function hours(): array
    {
        return array_map(function ($hour) {
            return [$hour];
        }, range(0, 23));
    }
}

class FakeMailer implements Factory, Mailer, MailQueue
{
    public $sent = [];

    public function mailer($name = null)
    {
        // TODO: Implement mailer() method.
    }

    public function queue($view, $queue = null)
    {
        // TODO: Implement queue() method.
    }

    public function later($delay, $view, $queue = null)
    {
        // TODO: Implement later() method.
    }

    public function to($users)
    {
        // TODO: Implement to() method.
    }

    public function bcc($users)
    {
        // TODO: Implement bcc() method.
    }

    public function raw($text, $callback)
    {
        // TODO: Implement raw() method.
    }

    public function send($view, array $data = [], $callback = null)
    {
        $this->sent[] = [$view, $data, $callback];
    }

    public function failures()
    {
        // TODO: Implement failures() method.
    }

    public function assertSentCount(int $count)
    {
        Assert::assertCount($count, $this->sent);
    }
}

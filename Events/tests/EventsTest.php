<?php

use Carbon\Carbon;
use Statamic\Testing\TestCase;
use Statamic\Addons\Events\Events;

class EventsTest extends TestCase
{
    /** @var Events */
    private $events;

    public function setUp()
    {
        parent::setUp();

        $this->events = new Events();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Carbon::setTestNow();
    }
}

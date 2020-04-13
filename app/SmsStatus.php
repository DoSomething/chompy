<?php

namespace Chompy;

class SmsStatus
{
    /**
     * @var string
     */
    public static $activeStatus = 'active';

    /**
     * @var string
     */
    public static $lessStatus = 'less';

    /**
     * @var string
     */
    public static $pendingStatus = 'pending';

    /**
     * @var string
     */
    public static $stopStatus = 'stop';

    /**
     * @var string
     */
    public static $undeliverableStatus = 'undeliverable';
}

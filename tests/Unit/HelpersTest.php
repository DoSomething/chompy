<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    /**
     * Test expected results for the minimalist is_valid_mobile helper.
     *
     * @return void
     */
    public function testIsValidMobile()
    {
        $this->assertEquals(is_valid_mobile('212-254-2390'), true);
        $this->assertEquals(is_valid_mobile('000-000-0000'), false);
        $this->assertEquals(is_valid_mobile('787 249 13'), false);
        $this->assertEquals(is_valid_mobile('302367890'), false);
    }
}

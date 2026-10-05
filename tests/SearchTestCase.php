<?php

namespace Asyntai\Search\Tests;

use Asyntai\Search\Tests\Concerns\SearchTestBench;
use Tests\TestCase;
use Webkul\Admin\Tests\Concerns\AdminTestBench;
use Webkul\Core\Tests\Concerns\CoreAssertions;

class SearchTestCase extends TestCase
{
    use AdminTestBench, CoreAssertions, SearchTestBench;
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Base;

use App\Tests\Support\FunctionalTester;
use Codeception\Util\HttpCode;

final class ExampleTestCest
{
    public function seeBaseResponse(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendGet('/api/example');

        $I->seeResponseCodeIs(HttpCode::OK);
    }
}

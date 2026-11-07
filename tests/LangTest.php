<?php

namespace Ardakilic\Mutlucell\Tests;

use PHPUnit\Framework\Attributes\Test;

class LangTest extends TestCase
{
    #[Test]
    public function en_and_tr_have_identical_keys(): void
    {
        $en = require __DIR__ . '/../src/lang/en/en.php';
        $tr = require __DIR__ . '/../src/lang/tr/tr.php';

        $this->assertSame(array_keys($en), array_keys($tr));

        foreach (['reports', 'sms', 'app', 'exceptions'] as $group) {
            $this->assertArrayHasKey($group, $en);
            $this->assertArrayHasKey($group, $tr);
            $this->assertSame(array_keys($en[$group]), array_keys($tr[$group]), "Sub-keys differ for group [{$group}]");
        }
    }
}

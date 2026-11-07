<?php

namespace Ardakilic\Mutlucell\Tests;

use Ardakilic\Mutlucell\Facades\Mutlucell as MutlucellFacade;
use Ardakilic\Mutlucell\Mutlucell;
use Ardakilic\Mutlucell\MutlucellServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function resolves_mutlucell_singleton(): void
    {
        $instance = $this->app->make('mutlucell');

        $this->assertInstanceOf(Mutlucell::class, $instance);
        $this->assertSame($instance, $this->app->make('mutlucell'));
    }

    #[Test]
    public function provides_mutlucell(): void
    {
        $provides = (new MutlucellServiceProvider($this->app))->provides();

        $this->assertContains('mutlucell', $provides);
    }

    #[Test]
    public function translations_are_loaded(): void
    {
        $lines = $this->app['translator']->get('mutlucell::en');

        $this->assertIsArray($lines);
        $this->assertArrayHasKey('reports', $lines);
        $this->assertArrayHasKey('sms', $lines);
        $this->assertArrayHasKey('app', $lines);
        $this->assertArrayHasKey('exceptions', $lines);
    }

    #[Test]
    public function facade_accessor_is_mutlucell(): void
    {
        $method = new ReflectionMethod(MutlucellFacade::class, 'getFacadeAccessor');
        $method->setAccessible(true);

        $this->assertSame('mutlucell', $method->invoke(null));
    }
}

<?php

namespace Ardakilic\Mutlucell\Tests;

use Ardakilic\Mutlucell\Mutlucell;
use Ardakilic\Mutlucell\MutlucellServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [MutlucellServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.locale', 'en');
        $app['config']->set('mutlucell', [
            'auth' => ['username' => 'testuser', 'password' => 'testpass'],
            'default_sender' => 'TEST',
            'queue' => false,
            'charset' => 'default',
            'append_unsubscribe_link' => false,
        ]);
    }

    protected function makeClient(array $responses, array &$history = []): Client
    {
        $mock = new MockHandler(array_map(
            fn ($body) => new Response(200, [], $body),
            $responses
        ));
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        return new Client(['handler' => $stack]);
    }

    // Pass $history explicitly and reuse the same variable on return
    // ([$sut, $history] = $this->makeSut([], [...], $history)) to keep the live log.
    protected function makeSut(array $configOverrides = [], array $responses = [], array &$history = []): array
    {
        $sut = new Mutlucell($this->app);
        $sut->setHttpClient($this->makeClient($responses, $history));
        $sut->setConfig(array_merge($this->app['config']->get('mutlucell'), $configOverrides));

        return [$sut, $history];
    }
}

<?php

declare(strict_types=1);

namespace SmartframeTest\Cdn\Factory\Fastly;

use Fastly\Fastly;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Smartframe\Cdn\ConfigProvider;
use Smartframe\Cdn\Exception\Fastly\FastlyApiTokenNotDefinedException;
use Smartframe\Cdn\Factory\Fastly\FastlyClientFactory;

class FastlyClientFactoryTest extends TestCase
{
    /**
     * @dataProvider configDataProvider
     */
    public function testInvoke(array $config, ?string $expectedExceptionClass = null): void
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('get')->willReturn($config);

        $factory = new FastlyClientFactory();

        if (isset($expectedExceptionClass)) {
            $this->expectException($expectedExceptionClass);
        }

        $object = $factory($container, Fastly::class);

        self::assertInstanceOf(Fastly::class, $object);
    }

    public function configDataProvider(): Generator
    {
        yield 'Correct configuration' => [
            'config' => [
                'cdn' => [
                    'fastly' => [
                        'apiToken' => 'some-test-token'
                    ]
                ]
            ],
            'expectedExceptionClass' => null
        ];

        yield 'Fastly API token has a placeholder value' => [
            'config' => [
                'cdn' => [
                    'fastly' => [
                        'apiToken' => ConfigProvider::FASTLY_API_TOKEN_PLACEHOLDER
                    ]
                ]
            ],
            'expectedExceptionClass' => FastlyApiTokenNotDefinedException::class
        ];

        yield 'Missing Fastly API token in configuration' => [
            'config' => [
                'cdn' => [
                    'fastly' => [
                    ]
                ]
            ],
            'expectedExceptionClass' => FastlyApiTokenNotDefinedException::class
        ];
    }
}
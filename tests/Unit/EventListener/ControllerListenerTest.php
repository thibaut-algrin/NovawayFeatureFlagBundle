<?php

declare(strict_types=1);

/*
 * This file is part of the NovawayFeatureFlagBundle package.
 * (c) Novaway <https://github.com/novaway/NovawayFeatureFlagBundle>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Novaway\Bundle\FeatureFlagBundle\Tests\Unit\EventListener;

use Novaway\Bundle\FeatureFlagBundle\EventListener\ControllerListener;
use Novaway\Bundle\FeatureFlagBundle\Manager\ChainedFeatureManager;
use Novaway\Bundle\FeatureFlagBundle\Tests\Fixtures\App\TestBundle\Controller\AttributeClassDisabledController;
use Novaway\Bundle\FeatureFlagBundle\Tests\Fixtures\App\TestBundle\Controller\DefaultController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class ControllerListenerTest extends TestCase
{
    public function testShouldValidateEvent(): void
    {
        static::assertSame(['kernel.controller' => 'onKernelController'], ControllerListener::getSubscribedEvents());
    }

    public function testShouldNotResolveFeatureBecauseFeatureNotExist(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Feature "foo" is defined more than once in Novaway\Bundle\FeatureFlagBundle\Tests\Fixtures\App\TestBundle\Controller\DefaultController::attributeFooError');

        $attributes = $this->createMock(ParameterBag::class);
        $attributes->expects($this->never())->method('set');
        $attributes->expects($this->never())->method('get');

        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = $this->createMock(Request::class);
        $request->attributes = $attributes;

        $listener = new ControllerListener();
        $listener->onKernelController(
            new ControllerEvent(
                $kernel,
                [new DefaultController(new ChainedFeatureManager(new \ArrayObject())), 'attributeFooError'],
                $request,
                null
            )
        );
    }

    public function testShouldResolveFeatureWithClass(): void
    {
        $attributes = $this->createMock(ParameterBag::class);
        $attributes
            ->expects($this->once())
            ->method('set')
            ->with(
                '_features',
                ['foo' => ['feature' => 'foo', 'enabled' => false], 'bar' => ['feature' => 'bar', 'enabled' => true]]
            )
        ;
        $attributes
            ->expects($this->once())
            ->method('get')
            ->with('_features', [])
            ->willReturn(['bar' => ['feature' => 'bar', 'enabled' => true]])
        ;

        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = $this->createMock(Request::class);
        $request->attributes = $attributes;

        $listener = new ControllerListener();
        $listener->onKernelController(
            new ControllerEvent(
                $kernel,
                new AttributeClassDisabledController(),
                $request,
                null
            )
        );
    }

    public function testShouldResolveFeatureWithMethod(): void
    {
        $attributes = $this->createMock(ParameterBag::class);
        $attributes
            ->expects($this->once())
            ->method('set')
            ->with(
                '_features',
                ['foo' => ['feature' => 'foo', 'enabled' => true], 'bar' => ['feature' => 'bar', 'enabled' => true]]
            )
        ;
        $attributes
            ->expects($this->once())
            ->method('get')
            ->with('_features', [])
            ->willReturn(['bar' => ['feature' => 'bar', 'enabled' => true]])
        ;

        $kernel = $this->createMock(HttpKernelInterface::class);

        $request = $this->createMock(Request::class);
        $request->attributes = $attributes;

        $listener = new ControllerListener();
        $listener->onKernelController(
            new ControllerEvent(
                $kernel,
                [new DefaultController(new ChainedFeatureManager(new \ArrayObject())), 'attributeFooEnabled'],
                $request,
                null
            )
        );
    }
}

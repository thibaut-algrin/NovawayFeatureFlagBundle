<?php

declare(strict_types=1);

/*
 * This file is part of the NovawayFeatureFlagBundle package.
 * (c) Novaway <https://github.com/novaway/NovawayFeatureFlagBundle>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Novaway\Bundle\FeatureFlagBundle\Tests\Unit\EventListener;

use Novaway\Bundle\FeatureFlagBundle\EventListener\FeatureListener;
use Novaway\Bundle\FeatureFlagBundle\Manager\ChainedFeatureManager;
use Novaway\Bundle\FeatureFlagBundle\Model\Feature;
use Novaway\Bundle\FeatureFlagBundle\Tests\Fixtures\App\TestBundle\Controller\AttributeClassDisabledController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @codingStandardsIgnoreFile
 *
 * @SuppressWarnings(PHPMD)
 */
class FeatureListenerTest extends TestCase
{
    private Feature $fooFeature;
    private Feature $barFeature;

    protected function setUp(): void
    {
        $this->fooFeature = $this->createMock(Feature::class);
        $this->barFeature = $this->createMock(Feature::class);
    }

    public function testShouldValidateEvent(): void
    {
        static::assertSame(['kernel.controller' => 'onKernelController'], FeatureListener::getSubscribedEvents());
    }

    public function testShouldNotCheckFeaturesBecauseFeaturesIsEmpty(): void
    {
        $this->fooFeature->expects($this->never())->method('isEnabled');
        $this->barFeature->expects($this->never())->method('isEnabled');

        $kernel = $this->createMock(HttpKernelInterface::class);

        $attributes = $this->createMock(ParameterBag::class);
        $attributes->expects($this->once())->method('get')->with('_features', null)->willReturn(null);

        $request = $this->createMock(Request::class);
        $request->attributes = $attributes;

        $event = new ControllerEvent($kernel, new AttributeClassDisabledController(), $request, null);
        $controller = new FeatureListener(
            new ChainedFeatureManager(new \ArrayObject([$this->fooFeature, $this->barFeature]))
        );
        $controller->onKernelController($event);
    }

    public function testShouldNotCheckFeaturesBecauseFeatureNotExist(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->fooFeature->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->barFeature->expects($this->once())->method('isEnabled')->willReturn(false);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $attributes = $this->createMock(ParameterBag::class);
        $attributes
            ->expects($this->once())
            ->method('get')
            ->with('_features', null)
            ->willReturn(['bar' => ['feature' => 'bar', 'enabled' => true]])
        ;

        $request = $this->createMock(Request::class);
        $request->attributes = $attributes;

        $event = new ControllerEvent($kernel, new AttributeClassDisabledController(), $request, null);
        $controller = new FeatureListener(
            new ChainedFeatureManager(new \ArrayObject([$this->fooFeature, $this->barFeature]))
        );
        $controller->onKernelController($event);
    }

    public function testShouldCheckFeatures(): void
    {
        $this->fooFeature->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->barFeature->expects($this->once())->method('isEnabled')->willReturn(true);

        $kernel = $this->createMock(HttpKernelInterface::class);

        $attributes = $this->createMock(ParameterBag::class);
        $attributes
            ->expects($this->once())
            ->method('get')
            ->with('_features', null)
            ->willReturn(['bar' => ['feature' => 'bar', 'enabled' => true]])
        ;

        $request = $this->createMock(Request::class);
        $request->attributes = $attributes;

        $event = new ControllerEvent($kernel, new AttributeClassDisabledController(), $request, null);
        $controller = new FeatureListener(
            new ChainedFeatureManager(new \ArrayObject([$this->fooFeature, $this->barFeature]))
        );
        $controller->onKernelController($event);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of the NovawayFeatureFlagBundle package.
 * (c) Novaway <https://github.com/novaway/NovawayFeatureFlagBundle>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Novaway\Bundle\FeatureFlagBundle\Tests\Unit\DataCollector;

use Novaway\Bundle\FeatureFlagBundle\DataCollector\FeatureCollector;
use Novaway\Bundle\FeatureFlagBundle\Manager\ChainedFeatureManager;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Novaway\Bundle\FeatureFlagBundle\Model\Feature;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureCollectorTest extends TestCase
{
    private FeatureManager $emptyManager;
    private FeatureManager $fooManager;
    private FeatureManager $barManager;

    protected function setUp(): void
    {
        $this->emptyManager = $this->createMock(FeatureManager::class);
        $this->fooManager = $this->createMock(FeatureManager::class);
        $this->barManager = $this->createMock(FeatureManager::class);
    }

    public function testShouldCollectData(): void
    {
        $feature1 = $this->createMock(Feature::class);
        $feature1
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'feature1', 'enabled' => false, 'description' => ''])
        ;
        $feature1->expects($this->once())->method('isEnabled')->willReturn(false);

        $feature2 = $this->createMock(Feature::class);
        $feature2
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'feature2', 'enabled' => true, 'description' => ''])
        ;
        $feature2->expects($this->once())->method('isEnabled')->willReturn(true);

        $feature3 = $this->createMock(Feature::class);
        $feature3
            ->expects($this->once())
            ->method('toArray')
            ->willReturn(['key' => 'feature3', 'enabled' => true, 'description' => ''])
        ;
        $feature3->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->emptyManager->expects($this->exactly(2))->method('getName')->willReturn('baz');
        $this->emptyManager->expects($this->once())->method('all')->willReturn([]);

        $this->fooManager->expects($this->exactly(4))->method('getName')->willReturn('foo');
        $this->fooManager->expects($this->once())->method('all')->willReturn([$feature1, $feature2]);

        $this->barManager->expects($this->exactly(3))->method('getName')->willReturn('bar');
        $this->barManager->expects($this->once())->method('all')->willReturn([$feature3]);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $collector = $this->getCollector();
        $collector->reset();
        $collector->collect($request, $response);
        static::assertSame(
            [
                'baz' => [],
                'foo' => [
                    ['key' => 'feature1', 'enabled' => false, 'description' => ''],
                    ['key' => 'feature2', 'enabled' => true, 'description' => ''],
                ],
                'bar' => [
                    ['key' => 'feature3', 'enabled' => true, 'description' => ''],
                ],
            ],
            $collector->getFeatures()
        );
        static::assertSame(3, $collector->getFeatureCount());
        static::assertSame(2, $collector->getActiveFeatureCount());
        static::assertSame('novaway_feature_flag.feature_collector', $collector->getName());
    }

    private function getCollector(): FeatureCollector
    {
        return new FeatureCollector(new ChainedFeatureManager(new \ArrayObject([$this->emptyManager, $this->fooManager, $this->barManager])));
    }
}

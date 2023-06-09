<?php

declare(strict_types=1);

/*
 * This file is part of the NovawayFeatureFlagBundle package.
 * (c) Novaway <https://github.com/novaway/NovawayFeatureFlagBundle>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Novaway\Bundle\FeatureFlagBundle\Tests\Unit\Storage;

use Novaway\Bundle\FeatureFlagBundle\Model\FeatureFlag;
use Novaway\Bundle\FeatureFlagBundle\Storage\ArrayStorage;
use Novaway\Bundle\FeatureFlagBundle\Storage\FeatureUndefinedException;
use PHPUnit\Framework\TestCase;

final class ArrayStorageTest extends TestCase
{
    public function testAllReturnEmptyArrayIfNoFeatureDefined(): void
    {
        $storage = new ArrayStorage();

        static::assertEmpty($storage->all());
    }

    public function testAllReturnDefinedFeatures(): void
    {
        $storage = ArrayStorage::fromArray([
            'foo' => ['enabled' => false],
            'bar' => ['enabled' => true, 'description' => 'Feature bar description'],
        ]);

        $features = $storage->all();

        static::assertCount(2, $features);
        static::assertEquals(
            new FeatureFlag('foo', false),
            $features['foo'],
        );
        static::assertEquals(
            new FeatureFlag('bar', true, 'Feature bar description'),
            $features['bar'],
        );
    }

    public function testAnExceptionThrowsIfAccessUndefinedFeature(): void
    {
        $storage = new ArrayStorage();

        $this->expectException(FeatureUndefinedException::class);

        $storage->get('unknown-feature');
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of the NovawayFeatureFlagBundle package.
 * (c) Novaway <https://github.com/novaway/NovawayFeatureFlagBundle>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Novaway\Bundle\FeatureFlagBundle\Tests\Unit\Manager;

use Novaway\Bundle\FeatureFlagBundle\Checker\ExpressionLanguageChecker;
use Novaway\Bundle\FeatureFlagBundle\Manager\DefaultFeatureManager;
use Novaway\Bundle\FeatureFlagBundle\Storage\ArrayStorage;
use Novaway\Bundle\FeatureFlagBundle\Storage\Storage;
use PHPUnit\Framework\TestCase;

final class DefaultFeatureManagerTest extends TestCase
{
    private const FEATURES = [
        'features' => [
            'feature_1' => ['name' => 'feature_1', 'enabled' => true],
            'feature_2' => ['name' => 'feature_2', 'enabled' => false],
            'feature_3' => ['name' => 'feature_3', 'enabled' => true, 'expression' => 'is_granted(\'ROLE_ADMIN\')'],
        ],
    ];

    private DefaultFeatureManager $manager;
    private Storage $storage;
    private ExpressionLanguageChecker $elc;

    protected function setUp(): void
    {
        $this->elc = $this->createMock(ExpressionLanguageChecker::class);

        $this->storage = new ArrayStorage(self::FEATURES);
        $this->manager = new DefaultFeatureManager('foo', $this->storage, $this->elc);
    }

    public function testAllFeaturesCanBeRetrieved(): void
    {
        $this->elc->expects($this->never())->method('isGranted');

        static::assertEquals($this->storage->all(), $this->manager->all());
    }

    public function testIsFeatureEnabled(): void
    {
        $this->elc->expects($this->once())->method('isGranted')->willReturn(true);

        static::assertTrue($this->manager->isEnabled('feature_1'));
        static::assertFalse($this->manager->isEnabled('feature_2'));
        static::assertTrue($this->manager->isEnabled('feature_3'));
    }

    public function testIsFeatureDisabled(): void
    {
        static::assertTrue($this->manager->isDisabled('feature_2'));
        static::assertFalse($this->manager->isDisabled('feature_1'));
        static::assertTrue($this->manager->isDisabled('feature_3'));
    }
}

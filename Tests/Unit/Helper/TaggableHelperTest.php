<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\TestEntity;

class TaggableHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaggableHelper */
    protected $helper;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $configProvider;

    protected function setUp()
    {
        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new TaggableHelper($this->configProvider);
    }

    /**
     * @dataProvider getEntityIdDataProvider
     *
     * @param object $object
     * @param bool   $expectedId
     */
    public function testGetEntityId($object, $expectedId)
    {
        $this->assertEquals($expectedId, TaggableHelper::getEntityId($object));
    }

    /**
     * @dataProvider isImplementsTaggableDataProvider
     *
     * @param object $object
     * @param bool   $result
     */
    public function testIsImplementsTaggable($object, $result)
    {
        $this->assertEquals($result, TaggableHelper::isImplementsTaggable($object));
    }

    /**
     * @dataProvider isTaggableDataProvider
     *
     * @param object $object
     * @param bool   $result
     * @param bool   $needSetConfig
     * @param bool   $hasConfig
     * @param bool   $isEnabled
     */
    public function testIsTaggable($object, $result, $needSetConfig = false, $hasConfig = false, $isEnabled = false)
    {
        if ($needSetConfig) {
            $this->setConfigProvider($object, $hasConfig, $isEnabled);
        }
        $this->assertEquals($result, $this->helper->isTaggable($object));
    }

    /** @return array */
    public function isTaggableDataProvider()
    {
        return [
            'implements Taggable' => [new Taggable(), true],
            'enabled in config'   => [new \StdClass(), true, true, true, true],
            'has no config'       => [new \StdClass(), false, true, false],
            'disabled in config'  => [new \StdClass(), false, true, true, false]
        ];
    }

    /** @return array */
    public function isImplementsTaggableDataProvider()
    {
        return [
            'implements Taggable'     => [new Taggable(), true],
            'not implements Taggable' => [new \StdClass(), false]
        ];
    }

    /** @return array */
    public function getEntityIdDataProvider()
    {
        return [
            'from Taggable interface method' => [new Taggable(['id' => 100]), 100],
            'from getId method'              => [new TestEntity(200), 200]
        ];
    }

    protected function setConfigProvider($object, $hasConfig, $isEnabled)
    {
        $this->configProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with($object)
            ->willReturn($hasConfig);

        if ($hasConfig) {
            $config = $this
                ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
                ->getMock();
            $config
                ->expects($this->once())
                ->method('is')
                ->willReturn($isEnabled);

            $this->configProvider
                ->expects($this->once())
                ->method('getConfig')
                ->with($object)
                ->willReturn($config);
        }
    }
}
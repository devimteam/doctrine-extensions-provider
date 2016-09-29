<?php

use Devimteam\Provider\DoctrineExtensionsServiceProvider\DoctrineExtendServiceProvider;
use Pimple\Container;

class DoctrineExtendServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $pimple = new Container();
        $pimple['db.event_manager'] = true;
        $service = new DoctrineExtendServiceProvider();
        $service->register($pimple);

        $this->assertEquals($pimple['orm.extend.subscribers'], []);
        $this->assertEquals($pimple['orm.extend.listeners'], []);
        $this->assertEquals($pimple['orm.extend.filters'], []);
        $this->assertEquals($pimple['orm.extend.mapping_types'], []);
    }

    public function testBoot()
    {

    }
}

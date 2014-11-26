<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace Thorr\OAuth2\CLI\Test;

use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Thorr\OAuth2\CLI\ClientController;
use Thorr\OAuth2\Entity\Client;
use Thorr\Persistence\DataMapper\DataMapperInterface;
use \Zend\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;
use Zend\Crypt\Password\PasswordInterface;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Console\RouteMatch;

class ClientControllerTest extends TestCase
{
    /**
     * @var DataMapperInterface|MockObject
     */
    protected $clientMapper;

    /**
     * @var ClientController
     */
    protected $controller;

    /**
     * @var MvcEvent
     */
    protected $mvcEvent;

    public function setUp()
    {
        $this->clientMapper = $this->getMock(DataMapperInterface::class);
        $password = $this->getMock(PasswordInterface::class);
        $password->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($arg) {
                return $arg;
            });

        $this->mvcEvent = new MvcEvent();
        $this->mvcEvent->setRouteMatch(new RouteMatch([]));
        $this->controller = new ClientController($this->clientMapper, $password);
        $this->controller->setEvent($this->mvcEvent);
        $this->controller->setConsole($this->getMock(ConsoleAdapterInterface::class));
    }

    public function testCreate()
    {
        $this->mvcEvent->getRouteMatch()->setParam('description', 'foo');
        $this->mvcEvent->getRouteMatch()->setParam('grant-types', 'bar, baz,bat , man ,asd');

        $this->clientMapper->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($client) {

                $this->assertInstanceOf(Client::class, $client);
                /** @var Client $client */
                $this->assertSame('foo', $client->getDescription());
                $this->assertSame(['bar', 'baz', 'bat', 'man', 'asd'], $client->getGrantTypes());
                $this->assertNotEmpty($client->getUuid());
                $this->assertNotEmpty($client->getSecret());

                return true;
            }));

        $this->controller->createAction();
    }

    public function testRemove()
    {
        $this->mvcEvent->getRouteMatch()->setParam('uuid', 'foo');

        $this->clientMapper->expects($this->once())
            ->method('removeByUuid')
            ->with('foo');

        $this->controller->deleteAction();
    }
}

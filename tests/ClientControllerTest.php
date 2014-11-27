<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace Thorr\OAuth2\CLI\Test;

use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use ReflectionProperty;
use Thorr\OAuth2\CLI\ClientController;
use Thorr\OAuth2\Entity\Client;
use Thorr\Persistence\DataMapper\DataMapperInterface;
use \Zend\Console\Adapter as ConsoleAdapter;
use Zend\Console\Prompt\PromptInterface;
use Zend\Console\Request;
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

    /**
     * @var ConsoleAdapter\AdapterInterface
     */
    protected $consoleMock;

    /**
     * @var array
     */
    protected $prompts;

    /**
     * @var string
     */
    protected $output;

    public function setUp()
    {
        $this->clientMapper = $this->getMock(DataMapperInterface::class);
        $password = $this->getMock(PasswordInterface::class);
        $password->expects($this->any())
            ->method('create')
            ->willReturnCallback(function ($arg) {
                return $arg;
            });

        $this->prompts = [
            'public'       => $this->getMock(PromptInterface::class),
            'description'  => $this->getMock(PromptInterface::class),
            'grant-types'  => $this->getMock(PromptInterface::class),
            'redirect-uri' => $this->getMock(PromptInterface::class),
        ];

        $this->mvcEvent = new MvcEvent();
        $this->mvcEvent->setRequest(new Request());
        $this->mvcEvent->setRouteMatch(new RouteMatch([]));

        $this->consoleMock = $this->getMock(ConsoleAdapter\AbstractAdapter::class, ['write']);
        $this->consoleMock->expects($this->any())
            ->method('write')
            ->willReturnCallback(function ($text) {
                $this->output .= $text;
        });

        $this->controller = new ClientController($this->clientMapper, $password);
        $this->controller->setEvent($this->mvcEvent);
        $this->controller->setConsole($this->consoleMock);

        $refl = new ReflectionProperty($this->controller, 'prompts');
        $refl->setAccessible(true);
        $refl->setValue($this->controller, $this->prompts);
    }

    /**
     * @param array $routeParams
     * @param array $promptParams
     * @dataProvider createProvider
     */
    public function testCreate($routeParams, $promptParams, $expectedValue)
    {
        $this->mvcEvent->setRouteMatch(new RouteMatch($routeParams));
        $this->mvcEvent->getRouteMatch()->setParam('action', 'create');

        /** @var Client $client */
        $client = null;

        $this->clientMapper->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Client $createdDlient) use (&$client) {
                $client = $createdDlient;

                return true;
            }));

        foreach ($promptParams as $key => $promptValue) {
            $this->prompts[$key]->expects($this->any())
                ->method('show')
                ->willReturn($promptValue);
        }

        $this->controller->dispatch($this->mvcEvent->getRequest());

        $this->assertSame($expectedValue['public'], empty($client->getSecret()));
        $this->assertSame($expectedValue['public'], $client->isPublic());
        $this->assertEquals($expectedValue['description'], $client->getDescription());
        $this->assertEquals($expectedValue['grant-types'], implode(',', $client->getGrantTypes()));
        $this->assertEquals($expectedValue['redirect-uri'], $client->getRedirectUri());

        $this->assertContains('Client created', $this->output);
        if (! $expectedValue['public']) {
            $this->assertContains("Secret: \t".$client->getSecret(), $this->output);
        }
        $this->assertContains("UUID: \t\t".$client->getUuid()->toString(), $this->output);
        $this->assertContains("Description: \t".$client->getDescription(), $this->output);
        $this->assertContains("Grant types: \t".implode(',', $client->getGrantTypes()), $this->output);
        $this->assertContains("Redirect URI: \t".$client->getRedirectUri(), $this->output);
    }

    public function createProvider()
    {
        return [
            [
                [
                    'public' => null,
                    'description' => null,
                    'grant-types' => null,
                    'redirect-uri' => null,
                ],
                [
                    'public' => null,
                    'description' => null,
                    'grant-types' => null,
                    'redirect-uri' => null,
                ],
                [
                    'public' => false,
                    'description' => null,
                    'grant-types' => null,
                    'redirect-uri' => null,
                ],
            ],
            [
                [
                    'public' => true,
                    'description' => 'asd',
                    'grant-types' => 'asd',
                    'redirect-uri' => 'asd',
                ],
                [
                    'public' => null,
                    'description' => null,
                    'grant-types' => null,
                    'redirect-uri' => null,
                ],
                [
                    'public' => true,
                    'description' => 'asd',
                    'grant-types' => 'asd',
                    'redirect-uri' => 'asd',
                ],
            ],
            [
                [
                    'public' => null,
                    'description' => null,
                    'grant-types' => null,
                    'redirect-uri' => null,
                ],
                [
                    'public' => 'y',
                    'description' => 'asd',
                    'grant-types' => 'asd',
                    'redirect-uri' => 'asd',
                ],
                [
                    'public' => true,
                    'description' => 'asd',
                    'grant-types' => 'asd',
                    'redirect-uri' => 'asd',
                ],
            ],
            [
                [
                    'public' => null,
                    'description' => null,
                    'grant-types' => null,
                    'redirect-uri' => null,
                ],
                [
                    'public' => false,
                    'description' => 'asd',
                    'grant-types' => 'asd',
                    'redirect-uri' => 'asd',
                ],
                [
                    'public' => false,
                    'description' => 'asd',
                    'grant-types' => 'asd',
                    'redirect-uri' => 'asd',
                ],
            ],
        ];
    }

    public function testRemove()
    {
        $this->mvcEvent->getRouteMatch()->setParam('action', 'delete');
        $this->mvcEvent->getRouteMatch()->setParam('uuid', 'foo');

        $this->clientMapper->expects($this->once())
            ->method('removeByUuid')
            ->with('foo');

        $this->controller->dispatch($this->mvcEvent->getRequest());
    }
}

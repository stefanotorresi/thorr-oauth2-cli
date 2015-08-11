<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace Thorr\OAuth2\CLI;

use Thorr\OAuth2;
use Thorr\Persistence\DataMapper\Manager\DataMapperManager;
use Zend\Console\Adapter\AdapterInterface;
use Zend\ModuleManager\Feature;
use Zend\Mvc\Controller\ControllerManager;

class Module implements
    Feature\ConfigProviderInterface,
    Feature\ConsoleUsageProviderInterface,
    Feature\ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'console' => [
                'router' => [
                    'routes' => [
                        'client-create' => [
                            'options' => [
                                'route'    => 'oauth2 create client [--public] [--description=] [--grant-types=] [--redirect-uri=]',
                                'defaults' => [
                                    'controller' => ClientController::class,
                                    'action'     => 'create',
                                ],
                            ],
                        ],
                        'client-delete' => [
                            'options' => [
                                'route'    => 'oauth2 delete client <uuid>',
                                'defaults' => [
                                    'controller' => ClientController::class,
                                    'action'     => 'delete',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConsoleUsage(AdapterInterface $console)
    {
        return [
            'oauth2 create client [--public] [--description=] [--grant-types=] [--redirect-uri=]' => 'Create a new OAuth2 client',
            'oauth2 delete client <uuid>'                                                         => 'Delete an OAuth2 client',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getControllerConfig()
    {
        return [
            'factories' => [
                ClientController::class => function (ControllerManager $controllerManager) {
                    $serviceManager = $controllerManager->getServiceLocator();

                    /** @var DataMapperManager $dmm */
                    $dmm = $serviceManager->get(DataMapperManager::class);

                    $clientMapper = $dmm->getDataMapperForEntity(OAuth2\Entity\Client::class);
                    $password = $serviceManager->get(OAuth2\Module::DEFAULT_PASSWORD_SERVICE);

                    return new ClientController($clientMapper, $password);
                },
            ],
        ];
    }
}

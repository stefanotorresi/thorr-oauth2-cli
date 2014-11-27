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
use Zend\Crypt\Password\Bcrypt;
use Zend\ModuleManager\Feature;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module implements
    Feature\ConfigProviderInterface,
    Feature\ConsoleUsageProviderInterface,
    Feature\ServiceProviderInterface
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
                                'route' => 'client create [--public] [--description=] [--grant-types=] [--redirect-uri=]',
                                'defaults' => [
                                    'controller' => ClientController::class,
                                    'action' => 'create',
                                ],
                            ],
                        ],
                        'client-delete' => [
                            'options' => [
                                'route' => 'client delete <uuid>',
                                'defaults' => [
                                    'controller' => ClientController::class,
                                    'action' => 'delete',
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
            'client create [--public] [--description=] [--grant-types=] [--redirect-uri=]' => 'Create a new OAuth2 client',
            'client delete <uuid>' => 'Delete an OAuth2 client',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                ClientController::class => function (ServiceLocatorInterface $serviceLocator) {
                    /** @var DataMapperManager $dmm */
                    $dmm = $serviceLocator->get(DataMapperManager::class);
                    /** @var OAuth2\Options\ModuleOptions $oauth2Options */
                    $oauth2Options = $serviceLocator->get(OAuth2\Options\ModuleOptions::class);

                    $clientMapper = $dmm->getDataMapperForEntity(OAuth2\Entity\Client::class);
                    $password = new Bcrypt(['cost' => $oauth2Options->getBcryptCost()]);

                    return new ClientController($clientMapper, $password);
                }
            ],
        ];
    }
}

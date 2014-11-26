<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace Thorr\OAuth2\CLI;

use Rhumsaa\Uuid\Uuid;
use Thorr\OAuth2\Entity\Client;
use Thorr\Persistence\DataMapper\DataMapperInterface;
use Zend\Console\Prompt;
use Zend\Console\ColorInterface as Color;
use Zend\Crypt\Password\PasswordInterface;
use Zend\Math\Rand;
use Zend\Mvc\Controller\AbstractConsoleController;

class ClientController extends AbstractConsoleController
{
    /**
     * @var DataMapperInterface
     */
    protected $clientMapper;

    /**
     * @var PasswordInterface
     */
    private $password;

    /**
     * @param DataMapperInterface $clientMapper
     * @param PasswordInterface   $password
     */
    public function __construct(DataMapperInterface $clientMapper, PasswordInterface $password)
    {
        $this->clientMapper = $clientMapper;
        $this->password = $password;
    }

    /**
     *
     */
    public function createAction()
    {
        $description = $this->params('description') ?:
            Prompt\Line::prompt('Please enter a client description: []', true, 255);

        $grantTypes = $this->params('grant-types') ?:
            Prompt\Line::prompt('Please enter a comma separated list of client grant types (leave empty to allow any): []', true, 255);

        $redirectUri = $this->params('description') ?:
            Prompt\Line::prompt('Please enter a redirect URI: []', true, 2000);

        $secret = Rand::getString(32);
        $encryptedSecret = $this->password->create($secret);

        if ($grantTypes) {
            $grantTypes = explode(',', $grantTypes);
            array_walk($grantTypes, function (&$grant) {
                $grant = trim($grant);
            });
        }

        $client = new Client(null, $encryptedSecret, null, $grantTypes, $redirectUri, $description);

        $this->clientMapper->save($client);

        $this->getConsole()->writeLine();
        $this->getConsole()->writeLine("* Client created *", Color::GREEN);
        $this->getConsole()->writeLine("The client secret was auto-generated and encrypted. Please store it safely.");
        $this->getConsole()->writeLine("Don't ever disclose the client secret publicly", Color::YELLOW);
        $this->getConsole()->writeLine();
        $this->getConsole()->writeLine("UUID: \t\t".$client->getUuid());
        $this->getConsole()->writeLine("Secret: \t".$secret);
        $this->getConsole()->writeLine("Grant types: \t".implode(', ', $client->getGrantTypes()));
        $this->getConsole()->writeLine("Description: \t".$client->getDescription());
    }

    /**
     *
     */
    public function deleteAction()
    {
        $this->clientMapper->removeByUuid($this->params('uuid'));

        $this->getConsole()->writeLine();
        $this->getConsole()->writeLine("* Client removed *", Color::GREEN);
    }
}

<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace Thorr\OAuth2\CLI;

use InvalidArgumentException;
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
    protected $password;

    /**
     * @var array
     */
    protected $prompts = [];

    /**
     * @param DataMapperInterface $clientMapper
     * @param PasswordInterface $password
     * @param array $prompts
     */
    public function __construct(DataMapperInterface $clientMapper, PasswordInterface $password, array $prompts = [])
    {
        $this->clientMapper = $clientMapper;
        $this->password = $password;

        $this->prompts = [
            'public'       => new Prompt\Confirm('Is the client public?'),
            'description'  => new Prompt\Line('Please enter a client description: []', true, 255),
            'grant-types'  => new Prompt\Line('Please enter a comma separated list of client grant types (leave empty to allow any): []', true, 255),
            'redirect-uri' => new Prompt\Line('Please enter a redirect URI: []', true, 2000),
        ];

        foreach ($prompts as $key => $prompt) {
            if (! $prompt instanceof Prompt\PromptInterface) {
                throw new InvalidArgumentException(sprintf(
                    "Invalid prompt type: got '%s', expected '%s'",
                    is_object($prompt) ? get_class($prompt) : gettype($prompt),
                    Prompt\PromptInterface::class
                ));
            }
            $this->prompts[$key] = $prompt;
        }
    }

    /**
     *
     */
    public function createAction()
    {
        $isPublic    = (bool) ($this->params('public') ?: $this->getPrompt('public')->show());
        $description = $this->params('description') ?: $this->getPrompt('description')->show();
        $grantTypes  = $this->params('grant-types') ?: $this->getPrompt('grant-types')->show();
        $redirectUri = $this->params('redirect-uri') ?: $this->getPrompt('redirect-uri')->show();

        $secret = null;
        $encryptedSecret = null;

        if (! $isPublic) {
            $secret = Rand::getString(32);
            $encryptedSecret = $this->password->create($secret);
        }

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
        if (! $isPublic) {
            $this->getConsole()->writeLine("The client secret was auto-generated and encrypted. Please store it safely.");
            $this->getConsole()->writeLine("Don't ever disclose the client secret publicly", Color::YELLOW);
            $this->getConsole()->writeLine();
        }
        $this->getConsole()->writeLine("UUID: \t\t".$client->getUuid());
        if (! $isPublic) {
            $this->getConsole()->writeLine("Secret: \t" . $secret);
        }
        $this->getConsole()->writeLine("Grant types: \t".implode(', ', $client->getGrantTypes()));
        $this->getConsole()->writeLine("Description: \t".$client->getDescription());
        $this->getConsole()->writeLine("Redirect URI: \t".$client->getRedirectUri());
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

    /**
     * @param string $key
     * @return Prompt\PromptInterface
     */
    protected function getPrompt($key)
    {
        return $this->prompts[$key];
    }
}

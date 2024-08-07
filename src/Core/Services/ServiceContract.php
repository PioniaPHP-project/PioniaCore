<?php

namespace Pionia\Core\Services;

use Pionia\Core\Pionia;
use Pionia\Exceptions\ResourceNotFoundException;
use Pionia\Exceptions\UserUnauthenticatedException;
use Pionia\Exceptions\UserUnauthorizedException;
use Pionia\Request\Request;
use Pionia\Response\BaseResponse;
use ReflectionException;
use ReflectionMethod;

/**
 * This is the main class all other services must extend.
 * It contains the basic methods that all services will need for authentication and request processing
 *
 * @property Request $request The request object
 * @property array $deactivatedActions An array of actions that are deactivated for the current service
 * @property array $actionsRequiringAuth An array of actions that require authentication
 * @property bool $serviceRequiresAuth If true, the entire service requires authentication
 * @property string | null $authMessage This message will be displayed when the entire service requires authentication
 * @internal
 * @author [Jet - ezrajet9@gmail.com](https://www.linkedin.com/in/jetezra/)
 **/
abstract class ServiceContract
{
    use AuthTrait, RequestActionTrait, ValidationTrait;

    /**
     * @var Request $request The request object
     */
    public Request $request;

    /**
     * An array of actions that are deactivated for the current service
     * @var array $deactivatedActions
     */
    public array $deactivatedActions = [];

    /**
     * An associative array of actions and their required permissions.
     * The permissions will be checked on the context user object
     * @example
     * ```php
     * public array $actionPermissions = [
     * 'create' => ['create_article'],
     * 'delete' => ['delete_article'],
     * 'update' => ['update_article'],
     * 'list' => ['list_article'],
     * 'get' => ['get_article'],
     * ]
     * @var array $actionPermissions
     */
    public array $actionPermissions = [];

    /**
     * This array contains the actions that require authentication
     * @example ```php
     * public array $actionsRequiringAuth = ['create', 'delete', 'update'];
     * ```
     *
     * All the actions defined in this will only be access by only authenticated requests based on the user object
     * @var array $actionsRequiringAuth
     */
    public array $actionsRequiringAuth = [];

    /**
     * If true, the entire service requires authentication.
     *
     * No action in the service will be accessible without authentication
     * @var bool $serviceRequiresAuth
     */
    public bool  $serviceRequiresAuth = false;

    /**
     * This message will be displayed when the entire service requires authentication.
     * It is used to inform the user why they cannot access the service.
     * By default, this will return `Service $service requires authentication`
     * @var ?string $authMessage
     */
    public ?string $authMessage = null;

    /**
     * This method is called when the service is called with an action
     *
     * @param string $action The action to be called
     * @param Request $request The request object
     * @return BaseResponse The response object
     * @throws ResourceNotFoundException|ReflectionException
     * @throws UserUnauthenticatedException
     * @throws UserUnauthorizedException
     * @internal
     */
    public function processAction(string $action, Request $request): BaseResponse
    {
        $this->request = $request;

        $data = $request->getData();

        $service = $data['SERVICE']?? $data['service'] ?? throw new ResourceNotFoundException("Service not defined in request data");

        if ($this->serviceRequiresAuth) {
            $this->mustAuthenticate($this->authMessage??"Service $service requires authentication");
        }

        if (in_array($action, $this->deactivatedActions)) {
            throw new ResourceNotFoundException("Action $action is currently deactivated for this service");
        }

        if (in_array($action, $this->actionsRequiringAuth)) {
            $this->mustAuthenticate("Action $action requires authentication");
        }

        $data = $this->request->getData();
        $files = $this->request->files;
        // here we attempt to call the action method on the current class
        if (method_exists($this, $action)) {
            if (isset($this->actionPermissions[$action])) {
                if (is_array($this->actionPermissions[$action])){
                    $this->canAll($this->actionPermissions[$action]);
                }
                // from version 1.1.4, we started checking permissions that are also strings, not arrays
                if (is_string($this->actionPermissions[$action])){
                    $this->can($this->actionPermissions[$action]);
                }
            }
            $reflection = new ReflectionMethod($this, $action);
            $reflection->setAccessible(true);
            return $reflection->invoke($this, $data, $files, $request);
        }
        throw new ResourceNotFoundException("Action $action not found in the $service context");
    }
}

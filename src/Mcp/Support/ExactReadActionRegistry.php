<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Mcp\Support;

use ReflectionMethod;
use XVE\ExactonlineLaravelApi\Exceptions\InvalidActionClass;

/**
 * Registry of readable Exact API actions available to the exact_api_read tool.
 *
 * Only actions whose logical name starts with "get_" are considered reads.
 * Write-path actions (create_*, update_*, sync_*, delete_*, switch_division)
 * are explicitly rejected here as the real enforcement layer; #[IsReadOnly]
 * on the tool is only a client hint.
 */
class ExactReadActionRegistry
{
    /**
     * Logical-name prefixes that are never allowed, regardless of config.
     *
     * @var array<string>
     */
    private const BLOCKED_PREFIXES = [
        'create_',
        'update_',
        'sync_',
        'delete_',
        'switch_',
        'acquire_',
        'refresh_',
        'store_',
        'revoke_',
        'register_',
        'validate_',
        'process_',
        'dispatch_',
        'check_',
        'wait_for_',
        'track_',
        'batch_',
        'monitor_',
    ];

    /**
     * Return all readable entity names (the "get_*" keys from config actions).
     *
     * @return array<string>
     */
    public function readableEntities(): array
    {
        $actions = config('exactonline-laravel-api.actions', []);
        $entities = [];

        if (! is_array($actions)) {
            return $entities;
        }

        foreach ($actions as $name => $_actionClass) {
            if (is_string($name) && $this->isReadable($name)) {
                $entities[] = $name;
            }
        }

        return $entities;
    }

    /**
     * Resolve and validate a readable action instance.
     *
     * @return object The resolved action instance with an execute() method
     *
     * @throws InvalidActionClass when the entity is not known or not a read action
     */
    public function resolve(string $entity): object
    {
        if (! $this->isReadable($entity)) {
            throw new InvalidActionClass(
                "Entity \"{$entity}\" is not a readable action. Only get_* entities are allowed."
            );
        }

        $actionClass = config("exactonline-laravel-api.actions.{$entity}");

        if (! $actionClass || ! class_exists($actionClass)) {
            throw new InvalidActionClass(
                "Entity \"{$entity}\" is not configured or the class does not exist."
            );
        }

        $this->ensureExecutable($actionClass, $entity);

        return app($actionClass);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function execute(object $action, array $arguments): mixed
    {
        try {
            return (new ReflectionMethod($action, 'execute'))->invokeArgs($action, $arguments);
        } catch (\ReflectionException) {
            throw new InvalidActionClass('Resolved action does not expose an execute method.');
        }
    }

    /**
     * @param  class-string  $actionClass
     *
     * @throws InvalidActionClass
     */
    private function ensureExecutable(string $actionClass, string $entity): void
    {
        if (! method_exists($actionClass, 'execute')) {
            throw new InvalidActionClass(
                "Entity \"{$entity}\" is configured with an action class that does not expose execute()."
            );
        }
    }

    /**
     * Determine whether the action's execute() takes a string $id parameter.
     *
     * Collection getters: execute(ExactConnection, array)
     * Single getters:     execute(ExactConnection, string, array)
     */
    public function isSingleGetter(object $action): bool
    {
        try {
            $method = new ReflectionMethod($action, 'execute');
            $params = $method->getParameters();

            // Collection: execute(ExactConnection, array $opts = [])
            // Single:     execute(ExactConnection, string $id, ...)
            // The second parameter's type distinguishes them.
            if (count($params) < 2) {
                return false;
            }

            $secondParam = $params[1];
            $type = $secondParam->getType();

            if ($type === null) {
                return false;
            }

            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type;

            return $typeName === 'string';
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * Determine whether the execute() method accepts an $options array parameter.
     */
    public function acceptsOptions(object $action): bool
    {
        try {
            $method = new ReflectionMethod($action, 'execute');
            $params = $method->getParameters();

            foreach ($params as $param) {
                if ($param->getName() === 'options' || $param->getName() === 'opts') {
                    return true;
                }
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType && $type->getName() === 'array'
                    && $param->isOptional()
                    && $param->getPosition() >= 1
                ) {
                    return true;
                }
            }

            return false;
        } catch (\ReflectionException) {
            return false;
        }
    }

    public function collectionEntityForSingle(string $entity): ?string
    {
        if (! str_starts_with($entity, 'get_')) {
            return null;
        }

        $candidates = [$entity.'s'];

        if (str_ends_with($entity, 'y')) {
            $candidates[] = substr($entity, 0, -1).'ies';
        }

        if (str_ends_with($entity, 'ss')) {
            $candidates[] = $entity.'es';
        }

        foreach ($candidates as $candidate) {
            if ($this->isReadable($candidate) && config("exactonline-laravel-api.actions.{$candidate}")) {
                return $candidate;
            }
        }

        return null;
    }

    private function isReadable(string $name): bool
    {
        foreach (self::BLOCKED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return false;
            }
        }

        return str_starts_with($name, 'get_') || str_starts_with($name, 'download_');
    }
}

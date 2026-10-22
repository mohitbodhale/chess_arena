<?php
declare(strict_types=1);

namespace App\Auth\Identifier;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;

/**
 * Identifies a player by the primary key that is kept in the session.
 *
 * The password identifier is used for the sign in form, but once the player is
 * signed in the session only keeps the identifier. This identifier turns that
 * identifier back into a fresh user record, so a role change takes effect on
 * the next request without signing the player out.
 */
class PrimaryKeyIdentifier extends AbstractIdentifier
{
    use ResolverAwareTrait;

    /**
     * Default configuration.
     *
     * - `resolver` The resolver implementation to use.
     * - `field` The field that holds the primary key.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'resolver' => [
            'className' => 'Authentication.Orm',
            'finder' => 'forAuth',
        ],
        'field' => 'id',
    ];

    /**
     * @inheritDoc
     */
    public function identify(array $credentials): ArrayAccess|array|null
    {
        if (!isset($credentials['key'])) {
            return null;
        }

        $identifier = $credentials['key'];
        if (!is_int($identifier) && !(is_string($identifier) && ctype_digit($identifier))) {
            return null;
        }

        return $this->getResolver()->find([$this->getConfig('field') => (int)$identifier]);
    }
}

<?php
declare(strict_types=1);

namespace App\Authorization;

use App\Model\Entity\ArenaMatch;
use App\Model\Entity\Tournament;
use App\Model\Entity\TournamentRegistration;
use App\Model\Entity\User;
use App\Policy\MatchPolicy;
use App\Policy\TournamentPolicy;
use App\Policy\TournamentRegistrationPolicy;
use App\Policy\UserPolicy;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Policy\MapResolver;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Builds the authorization service and wires the policies to the resources.
 *
 * Every policy of the arena is listed here, so an unmapped resource fails
 * loudly instead of silently allowing the request.
 */
class AuthorizationServiceProvider implements AuthorizationServiceProviderInterface
{
    /**
     * Get the authorization service.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Authorization\AuthorizationServiceInterface
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        $resolver = new MapResolver([
            Tournament::class => TournamentPolicy::class,
            ArenaMatch::class => MatchPolicy::class,
            User::class => UserPolicy::class,
            TournamentRegistration::class => TournamentRegistrationPolicy::class,
        ]);

        return new AuthorizationService($resolver);
    }
}

<?php
declare(strict_types=1);

/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions.
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     */
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        /*
         * The cinematic landing page of the arena.
         */
        $builder->connect('/', ['controller' => 'Users', 'action' => 'welcome'], ['_name' => 'landing']);

        /*
         * Authentication.
         */
        $builder->connect('/login', ['controller' => 'Users', 'action' => 'login'], ['_name' => 'login']);
        $builder->connect('/register', ['controller' => 'Users', 'action' => 'register'], ['_name' => 'register']);
        $builder->connect('/logout', ['controller' => 'Users', 'action' => 'logout'], ['_name' => 'logout']);

        /*
         * The tournament lobby and the leaderboards.
         */
        $builder->connect('/lobby', ['controller' => 'Tournaments', 'action' => 'lobby'], ['_name' => 'lobby']);
        $builder->connect(
            '/my-contests',
            ['controller' => 'Tournaments', 'action' => 'mine'],
            ['_name' => 'my_contests'],
        );
        $builder->connect(
            '/leaderboard',
            ['controller' => 'Leaderboard', 'action' => 'index'],
            ['_name' => 'leaderboard'],
        );

        /*
         * The live arena.
         */
        $builder->connect(
            '/matches/play/{id}',
            ['controller' => 'Matches', 'action' => 'play'],
            ['_name' => 'match_play'],
        )->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect(
            '/matches/move/{id}',
            ['controller' => 'Matches', 'action' => 'move'],
            ['_name' => 'match_move'],
        )->setPass(['id'])->setPatterns(['id' => '\d+']);
        $builder->connect(
            '/matches/state/{id}',
            ['controller' => 'Matches', 'action' => 'state'],
            ['_name' => 'match_state'],
        )->setPass(['id'])->setPatterns(['id' => '\d+']);

        /*
         * The control rooms.
         */
        $builder->connect('/admin', ['controller' => 'Admin', 'action' => 'dashboard'], ['_name' => 'admin_dashboard']);
        $builder->connect(
            '/organizer',
            ['controller' => 'Organizer', 'action' => 'dashboard'],
            ['_name' => 'organizer_dashboard'],
        );

        /*
         * Static pages such as the arena rules. The route is declared before
         * the fallbacks so that /pages/rules never looks for a controller
         * action with that name.
         */
        $builder->connect(
            '/pages/*',
            ['controller' => 'Pages', 'action' => 'display'],
            ['_name' => 'static_page'],
        );

        /*
         * Connect catchall routes for all controllers.
         */
        $builder->fallbacks();
    });
};

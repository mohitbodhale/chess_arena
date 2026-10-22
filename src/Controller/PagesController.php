<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Serves the static pages of the skeleton.
 */
class PagesController extends AppController
{
    /**
     * The landing page of the arena.
     *
     * @param string ...$path Path segments.
     * @return \Cake\Http\Response|null
     */
    public function display(string ...$path): ?\Cake\Http\Response
    {
        if ($path === []) {
            return $this->redirect(['controller' => 'Users', 'action' => 'welcome']);
        }

        $this->set('page', $path);

        return null;
    }
}

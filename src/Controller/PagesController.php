<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Serves the static pages of the arena.
 */
class PagesController extends AppController
{
    /**
     * The static pages are open to everybody.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->addUnauthenticatedActions(['display']);
    }

    /**
     * Render a static page from templates/Pages.
     *
     * @param string ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When the page does not exist.
     */
    public function display(string ...$path): ?\Cake\Http\Response
    {
        if ($path === []) {
            return $this->redirect(['controller' => 'Users', 'action' => 'welcome']);
        }

        $name = implode('/', array_map('basename', $path));
        if (!preg_match('#^[a-z0-9\-/]+$#', $name)) {
            throw new NotFoundException('The page "' . $name . '" does not exist.');
        }

        $template = ROOT . DS . 'templates' . DS . 'Pages' . DS . str_replace('/', DS, $name) . '.php';
        if (!is_file($template)) {
            throw new NotFoundException('The page "' . $name . '" does not exist.');
        }

        $this->set('page', $path);
        $this->viewBuilder()->setTemplate(implode('/', $path));

        return null;
    }
}

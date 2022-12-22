<?php

namespace App\EventSubscriber;

use App\Controller\Admin\UserGroupCrudController;
use App\Entity\UserGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class RestrictNonEmptyUserGroupDeleteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected RouterInterface $router,
        protected RequestStack    $requestStack,
    ) {
    }

    public function onBeforeEntityDeletedEvent(BeforeEntityDeletedEvent $event): void
    {
        if (!$event->getEntityInstance() instanceof UserGroup) {
            return;
        }

        /** @var UserGroup $userGroup */
        $userGroup = $event->getEntityInstance();

        if ($userGroup->getUsers()->count() === 0) {
            return;
        }

        // Restricting delete non-empty user group

        $this->requestStack
            ->getSession()
            ->getFlashbag()
            ->add('danger', 'This group can not be deleted, because it has users');

        $response = new RedirectResponse($this->router->generate('admin', [
            'crudAction' => Crud::PAGE_DETAIL,
            'crudControllerFqcn' => UserGroupCrudController::class,
            'entityId' => $userGroup->getId(),
        ]));

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityDeletedEvent::class => 'onBeforeEntityDeletedEvent',
        ];
    }
}

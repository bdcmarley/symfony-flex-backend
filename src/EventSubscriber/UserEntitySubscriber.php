<?php
declare(strict_types = 1);
/**
 * /src/EventSubscriber/UserEntitySubscriber.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserEntitySubscriber
 *
 * @package App\EventSubscriber
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UserEntitySubscriber implements EventSubscriber
{
    /**
     * Used password encoder
     *
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    /**
     * Constructor
     *
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    /**
     * Doctrine lifecycle event for 'prePersist' event.
     *
     * @param LifecycleEventArgs $event
     *
     * @return void
     *
     * @throws \LengthException
     * @throws \RuntimeException
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->process($event);
    }

    /**
     * Doctrine lifecycle event for 'preUpdate' event.
     *
     * @param PreUpdateEventArgs $event
     *
     * @return void
     *
     * @throws \LengthException
     * @throws \RuntimeException
     */
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $this->process($event);
    }

    /**
     * @param LifecycleEventArgs $event
     *
     * @throws \RuntimeException
     * @throws \LengthException
     */
    private function process(LifecycleEventArgs $event): void
    {
        // Get user entity object
        $user = $event->getEntity();

        // Valid user so lets change password
        if ($user instanceof User) {
            $this->changePassword($user);
        }
    }

    /**
     * Method to change user password whenever it's needed.
     *
     * @param User $user
     *
     * @return void
     *
     * @throws \LengthException
     * @throws \RuntimeException
     */
    private function changePassword(User $user): void
    {
        // Get plain password from user entity
        $plainPassword = $user->getPlainPassword();

        // Yeah, we have new plain password set, so we need to encode it
        if (!empty($plainPassword)) {
            if (\strlen($plainPassword) < 8) {
                throw new \LengthException('Too short password');
            }

            // Password hash callback
            $callback = function ($plainPassword) use ($user) {
                return $this->userPasswordEncoder->encodePassword($user, $plainPassword);
            };

            // Set new password and encode it with user encoder
            $user->setPassword($callback, $plainPassword);

            // And clean up plain password from entity
            $user->eraseCredentials();
        }
    }
}

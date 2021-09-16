<?php

declare(strict_types=1);

namespace NGSOFT\ORM\Events;

use Psr\EventDispatcher\{
    EventDispatcherInterface, ListenerProviderInterface
};

/**
 * Simple Event Listener
 */
final class Fuse implements ListenerProviderInterface {

    const FUSE_EVENTS = [
        'update' => Update::class,
        'open' => Open::class,
        'delete' => Delete::class,
        'after_delete' => AfterDelete::class,
        'after_update' => AfterUpdate::class,
        'dispense' => Dispense::class,
        'validate' => Validate::class,
        'loadEntity' => Load::class,
    ];

    /** @var array */
    private $listeners = [];

    /** @var callable[] */
    private $sorted = [];

    /** {@inheritdoc} */
    public function getListenersForEvent(object $event): iterable {
        foreach ($this->sorted as $type => $listeners) {
            if ($event instanceof $type) {
                foreach ($listeners as $listener) {
                    yield $listener;
                }
            }
        }
    }

    /**
     * Adds a listener to a fuse Event
     * @param string $eventType
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    public function addListener(string $eventType, callable $listener, int $priority = 0): void {
        $priority = max(0, $priority);
        $this->listeners[$eventType] = $this->listeners[$eventType] ?? [];
        $this->listeners[$eventType][$priority] = $this->listeners[$eventType][$priority] ?? [];
        $this->listeners[$eventType][$priority][] = $listener;
        $this->sortListeners($eventType);
    }

    /**
     * Remove a registered listener
     *
     * @param string $eventType The Event Class to listen to
     * @param callable $listener The listener
     * @return void
     */
    public function removeListener(string $eventType, callable $listener): void {
        if (!isset($this->listeners[$eventType])) return;
        foreach ($this->listeners[$eventType] as $priority => &$listeners) {
            $id = array_search($listener, $listeners, true);
            if (false !== $id) unset($listeners[$id]);
            if (count($listeners) == 0) unset($this->listeners[$eventType][$priority]);
        }
        $this->sortListeners($eventType);
    }

    /**
     * Sort listeners by priority
     */
    private function sortListeners(string $eventType) {
        if (!isset($this->listeners[$eventType])) {
            unset($this->sorted[$eventType]);
            return;
        }
        krsort($this->listeners[$eventType]);
        $this->sorted[$eventType] = [];

        foreach ($this->listeners[$eventType] as $listeners) {
            foreach ($listeners as $listener) {
                $this->sorted[$eventType][] = $listener;
            }
        }
    }

}

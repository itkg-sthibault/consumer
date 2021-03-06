<?php

namespace Itkg\Consumer\Listener;

use Itkg\Consumer\Event\ServiceEvent;
use Itkg\Consumer\Event\ServiceEvents;
use Itkg\Consumer\Service\ServiceLoggableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LoggerListener
 *
 * Event listener for service logging (handler for ServiceLoggableInterface)
 *
 * @package Itkg\Consumer\Listener
 */
class LoggerListener implements EventSubscriberInterface
{
    /**
     * @param ServiceEvent $event
     */
    public function onServiceRequest(ServiceEvent $event)
    {
        $service = $event->getService();
        if ($service instanceof ServiceLoggableInterface && null !== $logger = $service->getLogger()) {
            $logger->info('Request will be send', array(
                'service' => $service
            ));
        }
    }

    /**
     * @param ServiceEvent $event
     */
    public function onServiceResponse(ServiceEvent $event)
    {
        $service = $event->getService();

        if ($service instanceof ServiceLoggableInterface && null !== $logger = $service->getLogger()) {
            $logger->info('Response success', array(
                'service' => $service
            ));
        }
    }

    /**
     * @param ServiceEvent $event
     */
    public function onServiceException(ServiceEvent $event)
    {
        $service = $event->getService();
        if ($service instanceof ServiceLoggableInterface && null !== $logger = $service->getLogger()) {
            $logger->error('Response KO', array(
                'service' => $service
            ));
        }

    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            ServiceEvents::REQUEST   => array('onServiceRequest', -1),
            ServiceEvents::RESPONSE  => array('onServiceResponse', -1),
            ServiceEvents::EXCEPTION => array('onServiceException', -1)
        );
    }
}

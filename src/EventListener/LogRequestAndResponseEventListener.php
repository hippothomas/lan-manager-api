<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class LogRequestAndResponseEventListener
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelTerminate(TerminateEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $this->logger->info('data', [
            'route' => $request->getMethod() . ' ' . $request->getRequestUri(),
            'status' => $response->getStatusCode(),
            'request_body' => $request->request->all(),
            'response' => json_decode($response->getContent(), true),
            'headers' => $request->headers->all(),
        ]);
    }
}

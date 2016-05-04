<?php
namespace TijsVerkoyen\Bpost;

use Psr\Log\LoggerInterface;

class Logger
{
    /** @var  LoggerInterface */
    private $logger;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function debug($message, array $context = [ ])
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }
}

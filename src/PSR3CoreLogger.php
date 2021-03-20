<?php

declare(strict_types=1);

namespace dokuwiki\plugin\yearbox\src;

use dokuwiki\Logger;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

final class PSR3CoreLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        // PSR-3 states that $message should be a string
        $message = (string)$message;

        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                $this->logDokuWikiSeverity(Logger::LOG_ERROR, $message, $context);
                break;
            case LogLevel::WARNING:
                $this->logDokuWikiSeverity(LogLevel::WARNING, $message, $context);
                break;
            case LogLevel::NOTICE:
                $this->logDokuWikiSeverity(LogLevel::NOTICE, $message, $context);
                break;
            case LogLevel::INFO:
                $this->logDokuWikiSeverity(LogLevel::INFO, $message, $context);
                break;
            case LogLevel::DEBUG:
                $this->logDokuWikiSeverity(Logger::LOG_DEBUG, $message, $context);
                break;
            default:
                // PSR-3 states that we must throw a
                // PsrLogInvalidArgumentException if we don't
                // recognize the level
                throw new InvalidArgumentException(
                    'Unknown severity level'
                );
        }
    }

    private function logDokuWikiSeverity($level, $message, array $context): void
    {
        if (!class_exists(Logger::class)) {
            if ($context) {
                $message .= "\n" . json_encode($context, JSON_PRETTY_PRINT);
            }
            dbglog($message, $level);
            return;
        }

        Logger::getInstance($level)->log($message, $context);
    }
}

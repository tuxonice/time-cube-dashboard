<?php

namespace App\Core;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Logger
{
    private static ?MonologLogger $instance = null;

    public static function getInstance(): MonologLogger
    {
        if (self::$instance === null) {
            self::$instance = self::createLogger();
        }
        return self::$instance;
    }

    private static function createLogger(): MonologLogger
    {
        $logger = new MonologLogger('app');

        $level = self::resolveLevel($_ENV['LOG_LEVEL'] ?? 'warning');
        $logPath = dirname(__DIR__, 2) . '/storage/logs/app.log';

        $logger->pushHandler(new RotatingFileHandler($logPath, maxFiles: 30, level: $level));

        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            $logger->pushHandler(new StreamHandler('php://stderr', Level::Debug));
        }

        return $logger;
    }

    private static function resolveLevel(string $level): Level
    {
        return match (strtolower($level)) {
            'debug'     => Level::Debug,
            'info'      => Level::Info,
            'notice'    => Level::Notice,
            'warning'   => Level::Warning,
            'error'     => Level::Error,
            'critical'  => Level::Critical,
            'alert'     => Level::Alert,
            'emergency' => Level::Emergency,
            default     => Level::Warning,
        };
    }

    public static function registerHandlers(): void
    {
        set_exception_handler(function (\Throwable $e): void {
            self::critical('Uncaught exception', [
                'class'   => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            self::renderErrorPage();
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            $level = match (true) {
                ($severity & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) !== 0 => Level::Critical,
                ($severity & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING))    !== 0 => Level::Warning,
                ($severity & E_NOTICE)                                             !== 0 => Level::Notice,
                ($severity & E_DEPRECATED)                                         !== 0 => Level::Info,
                default                                                                  => Level::Warning,
            };
            self::getInstance()->log($level, $message, ['file' => $file, 'line' => $line]);
            return true;
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
                self::critical('Fatal error', [
                    'message' => $error['message'],
                    'file'    => $error['file'],
                    'line'    => $error['line'],
                ]);
                self::renderErrorPage();
            }
        });
    }

    private static function renderErrorPage(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        try {
            $loader = new FilesystemLoader(dirname(__DIR__, 2) . '/templates');
            $twig = new Environment($loader, ['cache' => false]);
            echo $twig->render('errors/500.twig');
        } catch (\Throwable) {
            echo '<!DOCTYPE html><html><body><h1>500 Internal Server Error</h1></body></html>';
        }

        exit(1);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getInstance()->critical($message, $context);
    }
}
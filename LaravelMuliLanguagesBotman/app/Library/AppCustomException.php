<?php

namespace App\Library;

use App;
use App\Library\Services\Interfaces\LogInterface;
use App\Notifications\DevelopersDebuggerChannelSlackNotification;
use App\Notifications\DevelopersDebuggerChannelTelegramNotification;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Notification;

class AppCustomException
{
    protected static $instance;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new AppCustomException;
        }

        return self::$instance;
    }

    public static function raiseChannelError(
        string $errorMsg,
        string $exceptionClass,
        string $file = '',
        string $line = ''
    ) {
        // If run from console - show error in console
        if (App::runningInConsole()) {
            $logger = app(LogInterface::class);
            $logger->writeError($errorMsg . ' of ' . $exceptionClass . ' class(FROM AppCustomException) ', $file,
                $line, isDie: false);
            $logger->writeInfo('');
        }
        $channelLoggedCustomExceptions  = config('app.channel_logging_custom_exceptions', []);
        $slackDevelopersDebuggerChannel = config('services.slack-developers-debugger-channel', []);
        $telegramBotApi                 = config('services.telegram-bot-api');
        $telegramChatId                 = $telegramBotApi['developers-debugger-channel-user-id'] ?? null;

        Notification::route('telegram', $telegramChatId)
            ->notify(new DevelopersDebuggerChannelTelegramNotification(
                errorMsg: $errorMsg,
                exceptionClass: $exceptionClass,
                file: $file,
                line: $line
            ));

        // some custom_exceptions must be sent into slack channel
        if (count($channelLoggedCustomExceptions) > 0 and ! empty($slackDevelopersDebuggerChannel)) {
            if (in_array($exceptionClass, $channelLoggedCustomExceptions)) {
                Notification::route('slack', $slackDevelopersDebuggerChannel)
                    ->notify(new DevelopersDebuggerChannelSlackNotification(
                        errorMsg: $errorMsg,
                        exceptionClass: $exceptionClass,
                        file: $file,
                        line: $line
                    ));
            }

        }

        if (class_exists(Debugbar::class)) {
            Debugbar::error('errorMsg : ' . $errorMsg . ', exceptionClass : ' . $exceptionClass);
            Debugbar::info(' file : ' . $file . ', line : ' . $line);
            Debugbar::info('');
        }

        return new \Illuminate\Support\MessageBag(['error_message' => $errorMsg]);
    }

}

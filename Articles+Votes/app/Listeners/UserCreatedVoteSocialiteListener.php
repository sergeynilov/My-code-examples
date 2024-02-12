<?php

namespace App\Listeners;

use App\Library\Services\TelegramWrapper;

use App\Events\VoteCreated;
use App\Library\Services\Interfaces\WriteMessageIntoSocialiteInterface;
use App\Models\User;
use Illuminate\Support\Facades\File;
use App\Notifications\TelegramVoteCreated\SendMessageWithReviewLink;
use App\Notifications\TelegramVoteCreated\AttachImage;
use App\Notifications\TelegramVoteCreated\AttachDocument;
use Illuminate\Support\Facades\Notification;

class UserCreatedVoteSocialiteListener
{
    protected $telegram;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct( TelegramWrapper $telegramWrapper = null)
    {
        $this->telegramWrapper = $telegramWrapper;
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\VoteCreated $event
     *
     * @return void
     */
    public function handle(VoteCreated $event)
    {
        $voteCreator = User::find($event->vote->creator_id);
        if ( ! empty($voteCreator)) {
            $writeMessageInterface = app(WriteMessageIntoSocialiteInterface::class);
            $writeMessageInterface->writeMessage("You have as new vote to with name {$event->vote->name} created by " . $voteCreator->username . " !");
        }

        $votesChannelTelegramUserId = config('services.telegram-bot-api.votes-channel-user-id');
        Notification::route('telegram', $votesChannelTelegramUserId)
            ->notify(new SendMessageWithReviewLink($event->vote, $voteCreator));
        $voteMedia = $event->vote->getFirstMedia(config('app.media_app_name'));
        if ( ! empty($voteMedia) and File::exists($voteMedia->getPath())) {
            Notification::route('telegram', new AttachImage($event->vote, $voteCreator, $voteMedia->getPath()));
        }

        $siteRulesFile = config('app.site_rules_file');
        if ( ! empty($siteRulesFile)) {
            Notification::route('telegram', new AttachDocument($event->vote, $voteCreator, $siteRulesFile));
        }
    }
}

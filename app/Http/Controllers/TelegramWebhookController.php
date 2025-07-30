<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BotInteractionService;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    protected $botService;

    public function __construct(BotInteractionService $botService) // Inject BotInteractionService
    {
        $this->botService = $botService;
    }

    public function handle(Request $request)
    {
        $update = $request->all();

        // Log the API request metadata
        $this->logApiRequest($request, 'telegram_webhook');

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];

            if (isset($message['text'])) {
                $this->botService->handleTextCommand($chatId, $message['text']);
            } elseif (isset($message['location'])) {
                $this->botService->handleLocationMessage($chatId, $message['location']['latitude'], $message['location']['longitude']);
            } elseif (isset($message['contact'])) {
                // You can add specific logic here if needed, or pass to botService
                $this->botService->sendMessage($chatId, "Received contact information. Thank you!");
            } elseif (isset($message['photo'])) {
                // You can add specific logic here if needed, or pass to botService
                $this->botService->sendMessage($chatId, "Received your photo! I can't process images for restaurant searches yet.");
            } elseif (isset($message['video'])) {
                // You can add specific logic here if needed, or pass to botService
                $this->botService->sendMessage($chatId, "Received your video! I can't process videos for restaurant searches yet.");
            }
            // Add more handlers for other message types as needed
        } elseif (isset($update['callback_query'])) {
            $callbackQuery = $update['callback_query'];
            $chatId = $callbackQuery['message']['chat']['id'];
            $data = $callbackQuery['data'];

            // Acknowledge the callback query to remove the loading state on the button
            // This is important for user experience
            Http::post("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/answerCallbackQuery", [
                'callback_query_id' => $callbackQuery['id']
            ]);

            $this->botService->handleCallbackQuery($chatId, $data);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Logs API request metadata.
     * This method is part of the ApiRequestLogger middleware but kept here for clarity on webhook handling.
     *
     * @param Request $request
     * @param string $source
     * @return void
     */
    protected function logApiRequest(Request $request, string $source)
    {
        // This method's implementation is handled by the ApiRequestLogger middleware.
        // We're keeping the method signature for consistency, but the actual logging
        // will occur when the middleware processes the incoming request.
        // No direct logging code is needed here.
    }
}
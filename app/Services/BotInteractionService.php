<?php

namespace App\Services;
// curl -F "url=https://onlnxqbqjo.sharedwithexpose.com/api/telegram/webhook" https://api.telegram.org/bot7643626346:AAFttOsRyolKBKBfLOzeGDXC9lqAu3xGtD8/setWebhook
use App\Services\GooglePlacesService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class BotInteractionService
{
    protected $placesService;
    protected $telegramBotToken;

    public function __construct(GooglePlacesService $placesService)
    {
        $this->placesService = $placesService;
        $this->telegramBotToken = env('TELEGRAM_BOT_TOKEN');
    }

    /**
     * Sends a text message to a Telegram chat.
     *
     * @param int $chatId
     * @param string $text
     * @param array $options Optional parameters for sendMessage (e.g., reply_markup)
     * @return void
     */
    public function sendMessage(int $chatId, string $text, array $options = []): void
    {
        $defaultOptions = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];
        $mergedOptions = array_merge($defaultOptions, $options);

        try {
            Http::post("https://api.telegram.org/bot{$this->telegramBotToken}/sendMessage", $mergedOptions)->throw();
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage(), ['chat_id' => $chatId, 'text' => $text]);
        }
    }

    /**
     * Handles text commands from the user (e.g., /start, /search, /details).
     *
     * @param int $chatId
     * @param string $text
     * @return void
     */
    public function handleTextCommand(int $chatId, string $text): void
    {
        if (strtolower($text) == '/start') {
            $this->sendMessage($chatId, "Welcome to the Culinary Bot! You can search for restaurants, share your location, or ask for help.");
        } elseif (str_starts_with(strtolower($text), '/search')) {
            $query = trim(substr($text, 7));
            if (!empty($query)) {
                $this->searchAndSendPlaces($chatId, $query);
            } else {
                $this->sendMessage($chatId, "Please provide a query after /search (e.g., /search pizza Jakarta).");
            }
        } elseif (str_starts_with(strtolower($text), '/details')) {
            $placeId = trim(substr($text, 8));
            if (!empty($placeId)) {
                $this->sendPlaceDetails($chatId, $placeId);
            } else {
                $this->sendMessage($chatId, "Please provide a valid place ID (e.g., /details ChIJN1_u_... ).");
            }
        } elseif (strtolower($text) == '/help') {
            $this->sendMessage($chatId, "Available commands:\n"
                                      . "/start - Start the bot\n"
                                      . "/search [query] - Search for restaurants (e.g., /search Sushi Tokyo)\n"
                                      . "/details [place_id] - Get restaurant details by Place ID (e.g., /details ChIJN1_u_...)\n"
                                      . "Send your location to find nearby restaurants.");
        } else {
            $this->sendMessage($chatId, "I received your message: \"$text\". Try /help for available commands or send your location!");
        }
    }

    /**
     * Handles location messages from the user.
     *
     * @param int $chatId
     * @param float $latitude
     * @param float $longitude
     * @return void
     */
    public function handleLocationMessage(int $chatId, float $latitude, float $longitude): void
    {
        $this->sendMessage($chatId, "Thank you for your location: Latitude $latitude, Longitude $longitude. Searching for nearby restaurants...");
        $this->searchAndSendPlaces($chatId, null, $latitude, $longitude);
    }

    /**
     * Handles callback queries from inline keyboards.
     *
     * @param int $chatId
     * @param string $callbackData
     * @return void
     */
    public function handleCallbackQuery(int $chatId, string $callbackData): void
    {
        // Example: If callbackData is 'details_PLACE_ID'
        if (str_starts_with($callbackData, 'details_')) {
            $placeId = substr($callbackData, strlen('details_'));
            $this->sendPlaceDetails($chatId, $placeId);
        } else {
            $this->sendMessage($chatId, "Unknown action.");
        }
    }


    /**
     * Searches for places (restaurants) using Google Places API and sends the results to Telegram.
     *
     * @param int $chatId
     * @param string|null $query
     * @param float|null $latitude
     * @param float|null $longitude
     * @return void
     */
    protected function searchAndSendPlaces(int $chatId, ?string $query = null, ?float $latitude = null, ?float $longitude = null): void
    {
        $results = $this->placesService->searchPlaces($query, $latitude, $longitude);

        if (empty($results)) {
            $this->sendMessage($chatId, "No restaurants found for your query/location.");
            return;
        }

        $message = "Here are some restaurants:\n\n";
        $inlineKeyboardButtons = [];

        foreach (array_slice($results, 0, 5) as $index => $place) { // Limit to 5 results
            $message .= "*Name:* " . ($place['name'] ?? 'N/A') . "\n";
            $message .= "*Address:* " . ($place['formatted_address'] ?? 'N/A') . "\n";
            $message .= "*Rating:* " . ($place['rating'] ?? 'N/A') . " ⭐ (" . ($place['user_ratings_total'] ?? 0) . " reviews)\n";
            $message .= "\n"; // Add extra line for spacing

            // Prepare button for inline keyboard
            $inlineKeyboardButtons[] = [
                ['text' => 'Details for ' . ($place['name'] ?? 'Restaurant ' . ($index + 1)), 'callback_data' => 'details_' . ($place['place_id'] ?? '')]
            ];
        }

        $replyMarkup = [
            'inline_keyboard' => $inlineKeyboardButtons
        ];

        $this->sendMessage($chatId, $message, ['reply_markup' => json_encode($replyMarkup)]);
    }

    /**
     * Sends detailed place information (restaurant details) to Telegram.
     *
     * @param int $chatId
     * @param string $placeId
     * @return void
     */
    protected function sendPlaceDetails(int $chatId, string $placeId): void
    {
        $details = $this->placesService->getPlaceDetails($placeId);

        if (empty($details)) {
            $this->sendMessage($chatId, "Could not find details for Place ID: $placeId.");
            return;
        }

        $message = "*Restaurant Details:*\n\n";
        $message .= "*Name:* " . ($details['name'] ?? 'N/A') . "\n";
        $message .= "*Address:* " . ($details['formatted_address'] ?? 'N/A') . "\n";
        $message .= "*Phone:* " . ($details['formatted_phone_number'] ?? 'N/A') . "\n";
        $message .= "*Website:* " . ($details['website'] ?? 'N/A') . "\n";
        $message .= "*Rating:* " . ($details['rating'] ?? 'N/A') . " ⭐ (" . ($details['user_ratings_total'] ?? 0) . " reviews)\n";
        $message .= "*Open Now:* " . (($details['opening_hours']['open_now'] ?? false) ? 'Yes' : 'No') . "\n";

        if (!empty($details['reviews'])) {
            $message .= "\n*Recent Reviews:*\n";
            foreach (array_slice($details['reviews'], 0, 3) as $review) { // Limit to 3 reviews
                $message .= "- *" . ($review['author_name'] ?? 'Anonymous') . "* (" . ($review['rating'] ?? 'N/A') . "/5): " . ($review['text'] ?? 'N/A') . "\n";
            }
        } else {
            $message .= "\nNo reviews available.\n";
        }

        $this->sendMessage($chatId, $message);
    }

    // You can add other handlers for different message types (contact, photo, video)
    // if they require specific bot logic beyond a simple text reply.
}
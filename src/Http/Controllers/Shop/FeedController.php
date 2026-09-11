<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto: the feed's HTTP face.
 */

namespace Asyntai\Search\Http\Controllers\Shop;

use Asyntai\Search\Feed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FeedController extends Controller
{
    public function index(Request $request, Feed $feed): JsonResponse
    {
        try {
            $answer = $feed->handle($request->query());
        } catch (\Throwable $e) {
            // The trace goes to the store's log, where its owner can see it.
            // Asyntai gets a plain sentence and tries again later.
            report($e);

            $answer = [
                'status' => 500,
                'body'   => ['ok' => false, 'error' => 'The store could not build the feed.'],
            ];
        }

        return response()
            ->json($answer['body'], $answer['status'], [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Cache-Control', 'no-store');
    }
}

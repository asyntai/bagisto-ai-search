<?php

/**
 * Asyntai AI Search for Bagisto: English strings.
 */

return [
    'admin' => [
        'menu' => [
            'title' => 'AI Search',
        ],

        'title' => 'Asyntai AI Search',

        'hero' => [
            'title'   => 'Connect your Asyntai account',
            'text'    => 'Sign in, or create a free account. We read your catalogue and your pages, and the bar switches on by itself when they are ready.',
            'button'  => 'Connect Asyntai',
            'point_1' => 'Products, categories and pages in one search box.',
            'point_2' => 'Live prices and stock, straight from your store.',
            'point_3' => 'If your allowance runs out, your usual search takes over.',
        ],

        'headings' => [
            'live'       => 'Live on your store',
            'setting_up' => 'Setting up your store',
            'blocked'    => 'Connected, but not on your store yet',
            'unknown'    => 'We could not reach Asyntai just now',
        ],

        'status' => [
            'not_connected'  => 'Not connected yet.',
            'live_pages'     => 'Searching your pages.',
            'live_products'  => 'Searching :count products, plus your pages.',
            'plan'           => 'Your plan does not include the AI Search Bar yet. Your usual store search is still running, so nothing on your store has changed.',
            'limit'          => 'You have used all your replies for this month. Your usual store search is still running, and the bar comes back when your allowance resets.',
            'reading'        => 'We are reading your store. This takes a few minutes, then the bar switches on by itself.',
            'unknown_widget' => 'This store is not connected to Asyntai any more. Connect it again above.',
            'unreachable'    => 'Your store is still connected. We will check again shortly, and nothing on your store has changed in the meantime.',
            'off'            => 'The search bar is not running.',
        ],

        'allowance'  => ':left of your :limit monthly replies left. Searches and chat replies share this allowance.',
        'connected_as' => 'Connected as :email.',
        'dashboard'  => 'Open your Asyntai dashboard',
        'analytics'  => 'See what shoppers searched for',
        'check_now'  => 'Check again',
        'disconnect' => 'Disconnect this store',

        'preview' => [
            'title'  => 'Try it on your own catalogue',
            'text'   => 'Open your real search bar and type something a shopper would look for. Searches you run there are not counted against your monthly allowance.',
            'button' => 'Try your search bar',
        ],

        'settings' => [
            'title'             => 'Settings',
            'placement'         => 'Where the bar goes',
            'placement_help'    => 'Replace mode takes the place of the search box your theme already shows. Pressing Enter without picking a result still opens your usual search page.',
            'placement_replace' => 'In place of my search box',
            'placement_manual'  => 'Only where my theme puts it',
            'selector'          => 'Search box to replace',
            'selector_help'     => 'Leave empty and we find it. Fill in a CSS selector only if we pick the wrong one.',
            'placeholder'       => 'Placeholder text',
            'placeholder_help'  => 'Leave empty and each shopper sees it in their own language.',
            'accent'            => 'Accent colour',
            'accent_help'       => 'Leave empty to use the colour set in your Asyntai dashboard.',
            'feed'              => 'Share my catalogue with Asyntai',
            'feed_help'         => 'Prices, stock and product links come straight from your store, so results are never out of date. Switch this off and the bar searches your pages only.',
            'save'              => 'Save',
            'saved'             => 'Settings saved.',
        ],

        'theme' => [
            'title' => 'In your theme',
            'text'  => 'To put the bar somewhere of your own choosing, add this where it should appear. It stays empty while the bar is off.',
        ],

        'js' => [
            'preparing'   => 'Preparing...',
            'waiting'     => 'Waiting for you to finish in the Asyntai window...',
            'saving'      => 'Connected. Saving...',
            'blocked'     => 'Your browser blocked the pop-up. Allow pop-ups and try again, or open the sign-in page with the link below.',
            'open_link'   => 'Open the Asyntai sign-in page',
            'failed'      => 'Could not connect. Please try again.',
            'timeout'     => 'Timed out waiting for the Asyntai window. Please try again.',
            'signed_out'  => 'Bagisto signed you out. Please log in again, then try once more.',
            'confirm'     => 'Disconnect this store from Asyntai? The search bar stops, and your usual store search takes over.',
            'unreachable' => 'Could not reach Asyntai. Check that this store can make outgoing HTTPS requests.',
            'expired'     => 'This connection attempt has expired. Please press Connect again.',
        ],
    ],
];

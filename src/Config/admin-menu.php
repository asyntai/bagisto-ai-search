<?php

/**
 * Asyntai AI Search for Bagisto: the admin menu entry.
 *
 * One top-level item. The settings screen is the whole product on the admin
 * side, so there is nothing to put under it.
 */

return [
    [
        'key'   => 'asyntai-search',
        'name'  => 'asyntai-search::app.admin.menu.title',
        'route' => 'admin.asyntai_search.index',
        'sort'  => 9,
        'icon'  => 'icon-search',
    ],
];

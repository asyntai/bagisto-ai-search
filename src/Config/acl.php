<?php

/**
 * Asyntai AI Search for Bagisto: the permissions a role can be given.
 *
 * They appear under Settings > Roles like every core screen's. The key of
 * the first one is also the admin menu key, so a role without it does not
 * see the menu entry. Bagisto's `admin` middleware checks the route named
 * here on each request; SearchController checks every action as well,
 * because an entry here can only name one route.
 */

return [
    [
        'key'   => 'asyntai-search',
        'name'  => 'asyntai-search::app.admin.menu.title',
        'route' => 'admin.asyntai_search.index',
        'sort'  => 9,
    ], [
        'key'   => 'asyntai-search.connection',
        'name'  => 'asyntai-search::app.admin.acl.connection',
        'route' => 'admin.asyntai_search.prepare',
        'sort'  => 1,
    ], [
        'key'   => 'asyntai-search.settings',
        'name'  => 'asyntai-search::app.admin.acl.settings',
        'route' => 'admin.asyntai_search.settings',
        'sort'  => 2,
    ],
];

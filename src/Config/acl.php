<?php

/**
 * Asyntai AI Search for Bagisto: the permissions a role can be given.
 *
 * They appear under Settings > Roles like every core screen's. The key of
 * the first one is also the admin menu key, so a role without it does not
 * see the menu entry. SearchController checks the right permission on every
 * action as well.
 *
 * Newer Bagisto refuses any admin route that no permission names, for every
 * role without full access, and accepts a list of routes per permission.
 * Older Bagisto takes exactly one route per permission and lets unnamed
 * routes through. The list is given only where Bagisto reads it.
 */

$routeLists = defined('Webkul\User\Http\Middleware\Bouncer::UNRESTRICTED_ROUTES');

$routes = function (array $names) use ($routeLists) {
    return $routeLists ? $names : $names[0];
};

return [
    [
        'key'   => 'asyntai-search',
        'name'  => 'asyntai-search::app.admin.menu.title',
        'route' => $routes([
            'admin.asyntai_search.index',
            'admin.asyntai_search.refresh',
        ]),
        'sort'  => 9,
    ], [
        'key'   => 'asyntai-search.connection',
        'name'  => 'asyntai-search::app.admin.acl.connection',
        'route' => $routes([
            'admin.asyntai_search.prepare',
            'admin.asyntai_search.poll',
            'admin.asyntai_search.finish',
            'admin.asyntai_search.disconnect',
        ]),
        'sort'  => 1,
    ], [
        'key'   => 'asyntai-search.settings',
        'name'  => 'asyntai-search::app.admin.acl.settings',
        'route' => 'admin.asyntai_search.settings',
        'sort'  => 2,
    ],
];

<?php

/**
 * Asyntai AI Search for Bagisto: Simplified Chinese strings.
 *
 * Generated from translations.json by render_lang.py. Edit the JSON.
 */

return [
    'admin' => [
        'menu' => [
            'title' => 'AI 搜索',
        ],

        'acl' => [
            'connection' => '连接和断开连接',
            'settings'   => '更改设置',
        ],

        'title' => 'Asyntai AI 搜索',

        'hero' => [
            'title'   => '连接您的 Asyntai 账户',
            'text'    => '登录或创建一个免费账户。我们会读取您的商品目录和页面，准备就绪后搜索栏会自动启用。',
            'button'  => '连接 Asyntai',
            'point_1' => '商品、分类和页面，尽在一个搜索框。',
            'point_2' => '价格和库存实时来自您的店铺。',
            'point_3' => '额度用完后，您原本的搜索会继续工作。',
        ],

        'headings' => [
            'live'       => '已在您的店铺运行',
            'setting_up' => '正在准备您的店铺',
            'blocked'    => '已连接，但尚未出现在您的店铺',
            'unknown'    => '暂时无法连接到 Asyntai',
        ],

        'status' => [
            'not_connected'  => '尚未连接。',
            'live_pages'     => '正在搜索您的页面。',
            'live_products'  => '正在搜索 :count 件商品以及您的页面。',
            'plan'           => '您的套餐尚未包含 AI 搜索栏。您原本的店铺搜索仍在运行，因此店铺没有任何变化。',
            'limit'          => '本月的回复次数已用完。您原本的店铺搜索仍在运行，额度重置后搜索栏会恢复。',
            'reading'        => '我们正在读取您的店铺。这需要几分钟，之后搜索栏会自动启用。',
            'unknown_widget' => '该店铺已不再连接到 Asyntai。请在上方重新连接。',
            'unreachable'    => '您的店铺仍处于连接状态。我们稍后会再次检查，在此期间店铺没有任何变化。',
            'off'            => '搜索栏未在运行。',
        ],

        'allowance'    => '本月 :limit 次回复中还剩 :left 次。搜索和聊天回复共用同一额度。',
        'connected_as' => '已以 :email 身份连接。',
        'dashboard'    => '打开您的 Asyntai 控制台',
        'analytics'    => '查看顾客搜索了什么',
        'check_now'    => '重新检查',
        'disconnect'   => '断开此店铺的连接',

        'preview' => [
            'title'  => '用您自己的商品目录试一试',
            'text'   => '打开真实的搜索栏，输入顾客可能会搜索的内容。在那里进行的搜索不计入您的每月额度。',
            'button' => '试用您的搜索栏',
        ],

        'settings' => [
            'title'             => '设置',
            'placement'         => '搜索栏的位置',
            'placement_help'    => '替换模式会取代主题已显示的搜索框。未选择结果直接按回车，仍会打开您原本的搜索页面。',
            'placement_replace' => '替换我的搜索框',
            'placement_manual'  => '仅显示在我的主题指定的位置',
            'selector'          => '要替换的搜索框',
            'selector_help'     => '留空则由我们自动查找。只有当我们选错时才填写 CSS 选择器。',
            'placeholder'       => '占位文字',
            'placeholder_help'  => '留空则每位顾客会看到自己语言的文字。',
            'accent'            => '强调色',
            'accent_help'       => '留空则使用您在 Asyntai 控制台中设置的颜色。',
            'feed'              => '与 Asyntai 共享我的商品目录',
            'feed_help'         => '价格、库存和商品链接直接来自您的店铺，因此结果永远不会过时。关闭后，搜索栏只会搜索您的页面。',
            'save'              => '保存',
            'saved'             => '设置已保存。',
        ],

        'theme' => [
            'title' => '在您的主题中',
            'text'  => '若想把搜索栏放在自己选定的位置，请在该处加入这段代码。搜索栏关闭时，该位置保持为空。',
        ],

        'js' => [
            'preparing'   => '正在准备...',
            'waiting'     => '正在等待您在 Asyntai 窗口中完成操作...',
            'saving'      => '已连接，正在保存...',
            'blocked'     => '浏览器拦截了弹出窗口。请允许弹出窗口后重试，或使用下方链接打开登录页面。',
            'open_link'   => '打开 Asyntai 登录页面',
            'failed'      => '连接失败，请重试。',
            'timeout'     => '等待 Asyntai 窗口超时，请重试。',
            'signed_out'  => 'Bagisto 已将您登出。请重新登录后再试一次。',
            'confirm'     => '要断开此店铺与 Asyntai 的连接吗？搜索栏将停止，您原本的店铺搜索会恢复。',
            'unreachable' => '无法连接到 Asyntai。请检查该店铺是否可以发出对外的 HTTPS 请求。',
            'expired'     => '本次连接已过期，请重新点击“连接”。',
            'forbidden'   => '您的角色无权执行此操作。请让店主为您的角色授予 AI 搜索 权限。',
        ],

    ],
];

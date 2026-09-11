{{--
    Asyntai AI Search for Bagisto: the settings screen.

    Every call from this page lands on this store's own controller. Nothing is
    ever fetched from Asyntai as a script, so no code from another server runs
    inside the admin.
--}}

@php
    $connected = $siteId !== '';
    $dots = ['live' => 'bg-green-600', 'setting_up' => 'bg-amber-500', 'blocked' => 'bg-amber-500', 'unknown' => 'bg-gray-400'];
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('asyntai-search::app.admin.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('asyntai-search::app.admin.title')
        </p>
    </div>

    <div class="mt-3.5 max-w-[820px]">
        <div id="asyntai-alert" class="mb-4 hidden rounded border border-l-4 border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-white"></div>

        @if (! $connected)
            <div class="box-shadow mb-4 rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    @lang('asyntai-search::app.admin.hero.title')
                </p>

                <p class="mt-2 max-w-[94ch] text-sm text-gray-600 dark:text-gray-300">
                    @lang('asyntai-search::app.admin.hero.text')
                </p>

                <p class="mt-4">
                    <button type="button" id="asyntai-connect" class="primary-button">
                        @lang('asyntai-search::app.admin.hero.button')
                    </button>
                </p>

                <p id="asyntai-open-link" class="mt-3 hidden text-sm">
                    <a href="#" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                        @lang('asyntai-search::app.admin.js.open_link')
                    </a>
                </p>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-2.5 md:grid-cols-3">
                @foreach (['point_1', 'point_2', 'point_3'] as $point)
                    <div class="box-shadow rounded bg-white px-4 py-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300">
                        @lang('asyntai-search::app.admin.hero.' . $point)
                    </div>
                @endforeach
            </div>
        @else
            <div class="box-shadow mb-4 rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    <span class="mr-2 inline-block h-2 w-2 rounded-full align-middle {{ $dots[$state] }}"></span>
                    @lang('asyntai-search::app.admin.headings.' . $state)
                </p>

                <p id="asyntai-message" class="mt-1.5 max-w-[68ch] text-sm text-gray-600 dark:text-gray-300">
                    {{ $message }}
                </p>

                @if ($state === 'live' && ! empty($status['monthly_limit']))
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        @lang('asyntai-search::app.admin.allowance', [
                            'left'  => number_format((int) ($status['searches_left'] ?? 0)),
                            'limit' => number_format((int) $status['monthly_limit']),
                        ])
                    </p>
                @endif

                @if ($accountEmail)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @lang('asyntai-search::app.admin.connected_as', ['email' => $accountEmail])
                    </p>
                @endif

                <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                    <a href="{{ $status['dashboard_url'] ?? 'https://asyntai.com/dashboard' }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                        @lang('asyntai-search::app.admin.dashboard')
                    </a>

                    @if ($state === 'live')
                        <a href="{{ $status['analytics_url'] ?? 'https://asyntai.com/ai-search-analytics/' }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                            @lang('asyntai-search::app.admin.analytics')
                        </a>
                    @endif

                    <a href="#" id="asyntai-refresh" class="text-blue-600 hover:underline">
                        @lang('asyntai-search::app.admin.check_now')
                    </a>

                    <a href="#" id="asyntai-disconnect" class="text-red-600 hover:underline">
                        @lang('asyntai-search::app.admin.disconnect')
                    </a>
                </p>
            </div>

            @if ($previewUrl)
                <div class="box-shadow mb-4 rounded bg-white p-5 dark:bg-gray-900">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        @lang('asyntai-search::app.admin.preview.title')
                    </p>

                    <p class="mt-2 max-w-[68ch] text-sm text-gray-600 dark:text-gray-300">
                        @lang('asyntai-search::app.admin.preview.text')
                    </p>

                    <p class="mt-3">
                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="primary-button">
                            @lang('asyntai-search::app.admin.preview.button')
                        </a>
                    </p>
                </div>
            @endif

            <div class="box-shadow mb-4 rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    @lang('asyntai-search::app.admin.settings.title')
                </p>

                <form id="asyntai-settings" class="mt-3" onsubmit="return false;">
                    <div class="mb-4">
                        <label class="mb-1.5 block text-xs font-medium text-gray-800 dark:text-white" for="asyntai-placement">
                            @lang('asyntai-search::app.admin.settings.placement')
                        </label>

                        <select id="asyntai-placement" name="placement" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                            <option value="replace" {{ $placement === 'replace' ? 'selected' : '' }}>@lang('asyntai-search::app.admin.settings.placement_replace')</option>
                            <option value="manual" {{ $placement === 'manual' ? 'selected' : '' }}>@lang('asyntai-search::app.admin.settings.placement_manual')</option>
                        </select>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('asyntai-search::app.admin.settings.placement_help')
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="mb-1.5 block text-xs font-medium text-gray-800 dark:text-white" for="asyntai-selector">
                            @lang('asyntai-search::app.admin.settings.selector')
                        </label>

                        <input type="text" id="asyntai-selector" name="selector" value="{{ $selector }}" placeholder="form[role=search]" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('asyntai-search::app.admin.settings.selector_help')
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="mb-1.5 block text-xs font-medium text-gray-800 dark:text-white" for="asyntai-placeholder">
                            @lang('asyntai-search::app.admin.settings.placeholder')
                        </label>

                        <input type="text" id="asyntai-placeholder" name="placeholder" value="{{ $placeholder }}" class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('asyntai-search::app.admin.settings.placeholder_help')
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="mb-1.5 block text-xs font-medium text-gray-800 dark:text-white" for="asyntai-accent">
                            @lang('asyntai-search::app.admin.settings.accent')
                        </label>

                        <input type="text" id="asyntai-accent" name="accent" value="{{ $accent }}" placeholder="#111111" class="w-40 rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('asyntai-search::app.admin.settings.accent_help')
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="flex cursor-pointer items-start gap-2.5 text-sm text-gray-800 dark:text-white">
                            <input type="checkbox" id="asyntai-feed" name="feed_enabled" value="1" {{ $feedEnabled ? 'checked' : '' }} class="mt-0.5">
                            <span>
                                @lang('asyntai-search::app.admin.settings.feed')
                                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                    @lang('asyntai-search::app.admin.settings.feed_help')
                                </span>
                            </span>
                        </label>
                    </div>

                    <button type="button" id="asyntai-save" class="primary-button">
                        @lang('asyntai-search::app.admin.settings.save')
                    </button>
                </form>
            </div>

            <div class="box-shadow mb-4 rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    @lang('asyntai-search::app.admin.theme.title')
                </p>

                <p class="mt-2 max-w-[68ch] text-sm text-gray-600 dark:text-gray-300">
                    @lang('asyntai-search::app.admin.theme.text')
                </p>

                <p class="mt-2"><code class="text-xs">&lt;div data-asyntai-search&gt;&lt;/div&gt;</code></p>
            </div>
        @endif
    </div>

    @pushOnce('scripts')
        <script>
        (function () {
            'use strict';

            var urls = {
                prepare: @json(route('admin.asyntai_search.prepare')),
                poll: @json(route('admin.asyntai_search.poll')),
                finish: @json(route('admin.asyntai_search.finish')),
                disconnect: @json(route('admin.asyntai_search.disconnect')),
                refresh: @json(route('admin.asyntai_search.refresh')),
                settings: @json(route('admin.asyntai_search.settings'))
            };
            var token = @json(csrf_token());
            var strings = {
                preparing: @json(trans('asyntai-search::app.admin.js.preparing')),
                waiting: @json(trans('asyntai-search::app.admin.js.waiting')),
                saving: @json(trans('asyntai-search::app.admin.js.saving')),
                blocked: @json(trans('asyntai-search::app.admin.js.blocked')),
                failed: @json(trans('asyntai-search::app.admin.js.failed')),
                timeout: @json(trans('asyntai-search::app.admin.js.timeout')),
                signedOut: @json(trans('asyntai-search::app.admin.js.signed_out')),
                confirm: @json(trans('asyntai-search::app.admin.js.confirm')),
                saved: @json(trans('asyntai-search::app.admin.settings.saved'))
            };
            var popup = null;

            function say(message, ok) {
                var box = document.getElementById('asyntai-alert');
                if (!box) { return; }
                box.classList.remove('hidden');
                box.style.borderLeftColor = ok ? '#16a34a' : '#dc2626';
                box.textContent = message;
            }

            // Every call carries the CSRF token and asks for JSON. A session
            // that ended mid-handshake answers with the login page instead,
            // which is not JSON, and no amount of waiting brings it back.
            function call(url, data) {
                return fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data || {})
                }).then(function (response) {
                    if (response.status === 401 || response.status === 403 || response.status === 419) {
                        var lost = new Error(strings.signedOut);
                        lost.signedOut = true;
                        throw lost;
                    }
                    return response.text().then(function (text) {
                        var parsed;
                        try { parsed = JSON.parse(text); }
                        catch (e) {
                            var gone = new Error(strings.signedOut);
                            gone.signedOut = true;
                            throw gone;
                        }
                        if (!response.ok) {
                            throw new Error((parsed && (parsed.error || parsed.message)) || strings.failed);
                        }
                        return parsed || {};
                    });
                });
            }

            // The window must be opened inside the click, before any network
            // call, or the browser treats it as unrequested and blocks it.
            function connect() {
                popup = window.open('about:blank', 'asyntai_connect', 'width=820,height=740,scrollbars=yes,resizable=yes');
                say(strings.preparing, true);

                call(urls.prepare).then(function (data) {
                    if (popup && !popup.closed) {
                        popup.location = data.url;
                        say(strings.waiting, true);
                    } else {
                        // Blocked. The handshake runs anyway: the owner opens
                        // the sign-in page from the link and we keep polling.
                        var wrap = document.getElementById('asyntai-open-link');
                        if (wrap) {
                            wrap.classList.remove('hidden');
                            wrap.querySelector('a').href = data.url;
                        }
                        say(strings.blocked, false);
                    }
                    poll(data.state, 0);
                }).catch(function (error) {
                    if (popup) { try { popup.close(); } catch (e) {} }
                    say(error.message || strings.failed, false);
                });
            }

            // A session that really ended answers 401, 403 or 419 on every
            // call from then on. One such answer on its own can also be a
            // torn read of the session file under two requests at once, so
            // a single one is retried and only a run of them is believed.
            var authFailures = 0;

            function poll(state, attempt) {
                if (attempt > 150) { say(strings.timeout, false); return; }

                call(urls.poll, { state: state }).then(function (data) {
                    authFailures = 0;
                    if (data && data.ready && data.site_id) {
                        say(strings.saving, true);
                        return call(urls.finish, { site_id: data.site_id, account_email: data.account_email || '' })
                            .then(function () { window.location.reload(); });
                    }
                    setTimeout(function () { poll(state, attempt + 1); }, 2000);
                }).catch(function (error) {
                    if (error && error.signedOut && ++authFailures >= 3) {
                        if (popup) { try { popup.close(); } catch (e) {} }
                        say(error.message, false);
                        return;
                    }
                    setTimeout(function () { poll(state, attempt + 1); }, 3000);
                });
            }

            function saveSettings() {
                var form = document.getElementById('asyntai-settings');
                if (!form) { return; }
                var data = {
                    placement: form.placement.value,
                    selector: form.selector.value,
                    placeholder: form.placeholder.value,
                    accent: form.accent.value,
                    feed_enabled: form.feed_enabled.checked ? 1 : 0
                };
                call(urls.settings, data).then(function () {
                    say(strings.saved, true);
                }).catch(function (error) {
                    say(error.message || strings.failed, false);
                });
            }

            document.addEventListener('click', function (event) {
                var target = event.target;
                if (!target || !target.id) { return; }

                if (target.id === 'asyntai-connect') {
                    event.preventDefault();
                    connect();
                } else if (target.id === 'asyntai-save') {
                    event.preventDefault();
                    saveSettings();
                } else if (target.id === 'asyntai-refresh') {
                    event.preventDefault();
                    call(urls.refresh).then(function () {
                        window.location.reload();
                    }).catch(function (error) {
                        say(error.message || strings.failed, false);
                    });
                } else if (target.id === 'asyntai-disconnect') {
                    event.preventDefault();
                    if (!window.confirm(strings.confirm)) { return; }
                    call(urls.disconnect).then(function () {
                        window.location.reload();
                    }).catch(function (error) {
                        say(error.message || strings.failed, false);
                    });
                }
            });
        })();
        </script>
    @endPushOnce
</x-admin::layouts>

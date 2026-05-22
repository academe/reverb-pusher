<x-filament-panels::page>
    {{-- Pusher.js loaded from CDN — Filament's panel does not bundle the app's Vite assets --}}
    <script src="https://js.pusher.com/8.4/pusher.min.js"></script>

    @php
        $words = ['apple','river','cloud','sunset','forest','ocean','candle','amber','silver','copper','hollow','gentle','cosmic','serene','velvet','mossy','crisp','radiant'];
        $wsScheme = $forceTLS ? 'wss' : 'ws';
        $wsUrl = "{$wsScheme}://{$wsHost}:{$wsPort}/app/{$loopbackKey}";
    @endphp

    <div
        x-data="reverbDiagnostics()"
        x-init="init()"
        class="space-y-4"
    >
        {{-- ── Restart notice ─────────────────────────────────────────────── --}}
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-800 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-300">
            <x-filament::icon icon="heroicon-o-arrow-path" class="mt-0.5 h-4 w-4 shrink-0" />
            <span>If this is the first time you have opened this page, <strong>restart Reverb</strong> so it picks up the loopback app: <code class="rounded bg-amber-100 px-1.5 py-0.5 text-xs dark:bg-amber-500/20">php artisan reverb:restart</code></span>
        </div>

        {{-- ── Proxy reachability check ──────────────────────────────────── --}}
        @if ($proxyCheck['ok'])
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                <x-filament::icon icon="heroicon-o-check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                <div class="space-y-1">
                    <p><strong>Transport check passed.</strong> {{ $proxyCheck['message'] }}</p>
                    <p class="text-xs opacity-75">Probed <code class="rounded bg-emerald-100 px-1 py-0.5 dark:bg-emerald-500/20">{{ $proxyCheck['url'] }}</code> &mdash; HTTP {{ $proxyCheck['status'] }}</p>
                </div>
            </div>
        @else
            <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-300">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
                <div class="space-y-1">
                    <p><strong>Transport check failed.</strong> {{ $proxyCheck['message'] }}</p>
                    <p class="text-xs opacity-75">
                        Probed <code class="rounded bg-red-100 px-1 py-0.5 dark:bg-red-500/20">{{ $proxyCheck['url'] }}</code>
                        @if ($proxyCheck['status']) &mdash; HTTP {{ $proxyCheck['status'] }} @endif
                    </p>
                </div>
            </div>
        @endif

        {{-- ── Connection status bar ──────────────────────────────────────── --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm dark:border-white/10 dark:bg-gray-900">
            <span class="font-medium text-gray-500 dark:text-gray-400">Loopback app</span>
            <code class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-white/5">{{ $loopbackKey }}</code>
            <span class="font-medium text-gray-500 dark:text-gray-400">Channel</span>
            <code class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-white/5">private-loopback</code>
            <span class="font-medium text-gray-500 dark:text-gray-400">WebSocket URL</span>
            <code class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-white/5">{{ $wsUrl }}</code>
            <span class="font-medium text-gray-500 dark:text-gray-400">TLS</span>
            <span class="text-gray-700 dark:text-gray-300">{{ $forceTLS ? 'yes' : 'no' }}</span>
        </div>

        {{-- ── Pusher.js not loaded warning ───────────────────────────────── --}}
        <div x-show="!pusherLoaded" class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-300">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
            <span><strong>Pusher.js failed to load.</strong> Check your internet connection or browser content blocker — this page requires the Pusher.js CDN script.</span>
        </div>

        {{-- ── Three-panel layout ─────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_auto_1fr_auto_1fr]">

            {{-- ── Panel 1: SOURCES ──────────────────────────────────────── --}}
            <div class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Sources</h3>

                {{-- PHP Backend --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                    <div class="mb-3 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-server" class="h-5 w-5 text-indigo-500" />
                        <span class="font-semibold text-gray-900 dark:text-white">PHP Backend</span>
                    </div>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        PHP calls <code class="text-xs">broadcast()</code> → Reverb HTTP API → WebSocket push
                    </p>

                    <x-filament::button
                        wire:click="sendFromBackend"
                        wire:target="sendFromBackend"
                        wire:loading.attr="disabled"
                        color="primary"
                        icon="heroicon-m-paper-airplane"
                    >
                        <span wire:loading.remove wire:target="sendFromBackend">Send from PHP</span>
                        <span wire:loading wire:target="sendFromBackend">Sending&hellip;</span>
                    </x-filament::button>

                    <div x-show="backendLastSent" class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Sent <span class="font-medium text-gray-800 dark:text-gray-200" x-text="backendLastSent?.word"></span>
                        at <span x-text="backendLastSent?.timestamp"></span>
                    </div>
                    <div x-show="backendError" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400" x-text="backendError"></div>
                </div>

                {{-- Browser (client event) --}}
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                    <div class="mb-3 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5 text-emerald-500" />
                        <span class="font-semibold text-gray-900 dark:text-white">Browser</span>
                    </div>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        Pusher.js sends a client event directly → Reverb → other subscribers
                    </p>

                    <x-filament::button
                        x-on:click="sendFromBrowser()"
                        x-bind:disabled="senderStatus !== 'connected'"
                        color="success"
                        icon="heroicon-m-paper-airplane"
                    >
                        Send from Browser
                    </x-filament::button>

                    <div x-show="browserLastSent" class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Sent <span class="font-medium text-gray-800 dark:text-gray-200" x-text="browserLastSent?.word"></span>
                        at <span x-text="browserLastSent?.timestamp"></span>
                    </div>
                    <div x-show="senderStatus !== 'connected' && senderStatus !== 'connecting'" class="mt-3 text-sm text-amber-600 dark:text-amber-400">
                        Browser sender must be connected to send client events.
                    </div>
                </div>
            </div>

            {{-- ── Arrow 1 ───────────────────────────────────────────────── --}}
            <div class="hidden items-center justify-center lg:flex">
                <x-filament::icon icon="heroicon-o-arrow-right" class="h-6 w-6 text-gray-300 dark:text-gray-600" />
            </div>

            {{-- ── Panel 2: REVERB ───────────────────────────────────────── --}}
            <div class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Reverb Server</h3>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
                    {{-- Sender connection --}}
                    <div class="mb-4">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Sender (browser → Reverb)</p>
                        <div class="flex items-center gap-2">
                            <span
                                class="h-2.5 w-2.5 rounded-full"
                                :class="{
                                    'bg-emerald-500': senderStatus === 'connected',
                                    'bg-amber-400 animate-pulse': senderStatus === 'connecting',
                                    'bg-red-500': senderStatus === 'failed' || senderStatus === 'auth-error',
                                    'bg-gray-300 dark:bg-gray-600': senderStatus === 'disconnected',
                                }"
                            ></span>
                            <span class="text-sm capitalize text-gray-700 dark:text-gray-300" x-text="senderStatus"></span>
                        </div>
                        <div x-show="senderError" class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-400" x-text="senderError"></div>
                    </div>

                    {{-- Receiver connection --}}
                    <div class="mb-4">
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Receiver (Reverb → browser)</p>
                        <div class="flex items-center gap-2">
                            <span
                                class="h-2.5 w-2.5 rounded-full"
                                :class="{
                                    'bg-emerald-500': receiverStatus === 'connected',
                                    'bg-amber-400 animate-pulse': receiverStatus === 'connecting',
                                    'bg-red-500': receiverStatus === 'failed' || receiverStatus === 'auth-error',
                                    'bg-gray-300 dark:bg-gray-600': receiverStatus === 'disconnected',
                                }"
                            ></span>
                            <span class="text-sm capitalize text-gray-700 dark:text-gray-300" x-text="receiverStatus"></span>
                        </div>
                        <div x-show="receiverError" class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-400" x-text="receiverError"></div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 text-xs text-gray-400 dark:border-white/5 dark:text-gray-500 space-y-1">
                        <div>Messages received: <span class="font-medium text-gray-600 dark:text-gray-300" x-text="messages.length"></span></div>
                    </div>
                </div>

                {{-- ── Troubleshooting hints ──────────────────────────────── --}}
                <div x-show="senderError || receiverError" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900 space-y-2 text-xs text-gray-500 dark:text-gray-400">
                    <p class="font-semibold text-gray-700 dark:text-gray-300">Troubleshooting</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Restart Reverb: <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/5">php artisan reverb:restart</code></li>
                        <li>Check Reverb is running on <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/5">{{ $wsHost }}:{{ $wsPort }}</code></li>
                        <li x-show="@js($forceTLS)">TLS is enabled — ensure Reverb is behind a TLS terminating proxy on port <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/5">{{ $wssPort }}</code>, or set <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/5">REVERB_SCHEME=http</code></li>
                        <li x-show="!@js($forceTLS)">TLS is disabled — if you are on HTTPS, set <code class="rounded bg-gray-100 px-1.5 py-0.5 dark:bg-white/5">REVERB_SCHEME=https</code> and configure a TLS proxy</li>
                        <li>Check your browser console (F12) for WebSocket connection errors</li>
                    </ul>
                </div>
            </div>

            {{-- ── Arrow 2 ───────────────────────────────────────────────── --}}
            <div class="hidden items-center justify-center lg:flex">
                <x-filament::icon icon="heroicon-o-arrow-right" class="h-6 w-6 text-gray-300 dark:text-gray-600" />
            </div>

            {{-- ── Panel 3: LISTENER ─────────────────────────────────────── --}}
            <div class="space-y-4">
                <div class="flex items-baseline justify-between">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Listener</h3>
                    <button
                        @click="messages = []"
                        x-show="messages.length > 0"
                        class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >Clear</button>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900" style="min-height: 16rem;">
                    <div x-show="messages.length === 0" class="flex h-64 flex-col items-center justify-center gap-2 text-gray-300 dark:text-gray-600">
                        <x-filament::icon icon="heroicon-o-signal-slash" class="h-10 w-10" />
                        <p class="text-sm">Waiting for messages…</p>
                    </div>

                    <ul x-show="messages.length > 0" class="divide-y divide-gray-100 dark:divide-white/5">
                        <template x-for="(msg, i) in messages" :key="i">
                            <li class="flex items-center gap-3 px-5 py-3">
                                {{-- Source badge --}}
                                <span
                                    class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="msg.source === 'backend'
                                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300'
                                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'"
                                    x-text="msg.source === 'backend' ? 'PHP' : 'Browser'"
                                ></span>

                                {{-- Word --}}
                                <span class="flex-1 font-medium text-gray-800 dark:text-gray-200" x-text="msg.word"></span>

                                {{-- Timestamp --}}
                                <span class="shrink-0 font-mono text-xs text-gray-400" x-text="msg.timestamp"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function reverbDiagnostics() {
            const pusherLoaded = typeof Pusher !== 'undefined';

            const config = {
                key:      @js($loopbackKey),
                wsHost:   @js($wsHost),
                wsPort:   @js($wsPort),
                wssPort:  @js($wssPort),
                forceTLS: @js($forceTLS),
            };

            const words = @js($words);

            function randomWord() {
                return words[Math.floor(Math.random() * words.length)];
            }

            function connectionError(error) {
                if (!error) { return null; }
                const type = error.type ?? error.error?.type ?? '';
                const data = error.error?.data ?? error.data ?? error;
                const code = data?.code ?? '';
                const msg  = data?.message ?? (typeof data === 'string' ? data : '');
                const parts = [type, code ? `(${code})` : '', msg].filter(Boolean);
                return parts.join(' ') || JSON.stringify(error);
            }

            function makePusher() {
                const p = new Pusher(config.key, {
                    wsHost:            config.wsHost,
                    wsPort:            config.wsPort,
                    wssPort:           config.wssPort,
                    forceTLS:          config.forceTLS,
                    cluster:           'mt1',
                    enabledTransports: ['ws', 'wss'],
                    authEndpoint:      '/reverb/loopback-auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                });

                Pusher.logToConsole = true;

                return p;
            }

            return {
                pusherLoaded,
                senderStatus:    'disconnected',
                receiverStatus:  'disconnected',
                senderError:     null,
                receiverError:   null,
                messages:        [],
                backendLastSent: null,
                backendError:    null,
                browserLastSent: null,
                _senderChannel:  null,

                init() {
                    if (!pusherLoaded) { return; }

                    // Sender — used only to trigger client events
                    const sender = makePusher();
                    sender.connection.bind('connecting',    () => { this.senderStatus = 'connecting'; this.senderError = null; });
                    sender.connection.bind('connected',     () => { this.senderStatus = 'connected';  this.senderError = null; });
                    sender.connection.bind('disconnected',  () => this.senderStatus = 'disconnected');
                    sender.connection.bind('failed',        () => this.senderStatus = 'failed');
                    sender.connection.bind('error',         (err) => {
                        this.senderStatus = 'failed';
                        this.senderError  = connectionError(err) || 'WebSocket connection failed — check that Reverb is running on ' + config.wsHost + ':' + config.wsPort;
                    });

                    const senderCh = sender.subscribe('private-loopback');
                    senderCh.bind('pusher:subscription_error', (err) => {
                        this.senderStatus = 'auth-error';
                        const status = err?.status ?? err?.error?.status ?? '';
                        this.senderError = 'Channel auth failed' + (status ? ' (HTTP ' + status + ')' : '') + '. Check that the /reverb/loopback-auth endpoint is reachable and you are logged in.';
                    });
                    this._senderChannel = senderCh;

                    // Receiver — separate socket; receives backend events + client events from sender
                    const receiver = makePusher();
                    receiver.connection.bind('connecting',   () => { this.receiverStatus = 'connecting'; this.receiverError = null; });
                    receiver.connection.bind('connected',    () => { this.receiverStatus = 'connected';  this.receiverError = null; });
                    receiver.connection.bind('disconnected', () => this.receiverStatus = 'disconnected');
                    receiver.connection.bind('failed',       () => this.receiverStatus = 'failed');
                    receiver.connection.bind('error',        (err) => {
                        this.receiverStatus = 'failed';
                        this.receiverError  = connectionError(err) || 'WebSocket connection failed — check that Reverb is running on ' + config.wsHost + ':' + config.wsPort;
                    });

                    const receiverCh = receiver.subscribe('private-loopback');
                    receiverCh.bind('pusher:subscription_error', (err) => {
                        this.receiverStatus = 'auth-error';
                        const status = err?.status ?? err?.error?.status ?? '';
                        this.receiverError = 'Channel auth failed' + (status ? ' (HTTP ' + status + ')' : '') + '. Check that the /reverb/loopback-auth endpoint is reachable and you are logged in.';
                    });

                    receiverCh.bind('loopback.message', (data) => {
                        this.messages.unshift(data);
                    });

                    receiverCh.bind('client-loopback-message', (data) => {
                        this.messages.unshift(data);
                    });

                    // Livewire events from PHP actions
                    $wire.on('backend-sent',  ({ word, timestamp }) => {
                        this.backendError   = null;
                        this.backendLastSent = { word, timestamp };
                    });
                    $wire.on('backend-error', ({ message }) => {
                        this.backendError = message;
                    });
                },

                sendFromBrowser() {
                    if (!this._senderChannel || this.senderStatus !== 'connected') { return; }

                    const word      = randomWord();
                    const timestamp = new Date().toTimeString().slice(0, 8);
                    const payload   = { source: 'browser', word, timestamp };

                    const ok = this._senderChannel.trigger('client-loopback-message', payload);

                    if (ok) {
                        this.browserLastSent = payload;
                    }
                },
            };
        }
    </script>
</x-filament-panels::page>
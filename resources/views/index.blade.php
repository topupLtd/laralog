@extends(config('topup-logger.layout'))

@section(config('topup-logger.section'))
    {{-- Scoped under .tl so the page also fits inside a host app's own layout. No web fonts or external assets. --}}
    <style>
        .tl {
            --tl-surface: #ffffff;
            --tl-surface-2: #f9fafb;
            --tl-border: #e3e6ea;
            --tl-text: #1f2328;
            --tl-muted: #656d76;
            --tl-focus: #0969da;
            --tl-warn: #9a6700;
            --tl-slow: #cf222e;
            --tl-get: #0550ae;
            --tl-get-bg: #ddf4ff;
            --tl-post: #116329;
            --tl-post-bg: #dafbe1;
            --tl-put: #7d4e00;
            --tl-put-bg: #fff8c5;
            --tl-delete: #a40e26;
            --tl-delete-bg: #ffebe9;
            --tl-other: #57606a;
            --tl-other-bg: #eaeef2;
            --tl-mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;

            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 16px 40px;
            color: var(--tl-text);
            font-size: 14px;
            line-height: 1.5;
        }

        @media (prefers-color-scheme: dark) {
            .tl-page .tl {
                --tl-surface: #161b22;
                --tl-surface-2: #1c2128;
                --tl-border: #30363d;
                --tl-text: #e6edf3;
                --tl-muted: #8d96a0;
                --tl-focus: #4493f8;
                --tl-warn: #d29922;
                --tl-slow: #ff7b72;
                --tl-get: #79c0ff;
                --tl-get-bg: rgba(56, 139, 253, .15);
                --tl-post: #56d364;
                --tl-post-bg: rgba(46, 160, 67, .15);
                --tl-put: #e3b341;
                --tl-put-bg: rgba(187, 128, 9, .15);
                --tl-delete: #ff7b72;
                --tl-delete-bg: rgba(248, 81, 73, .15);
                --tl-other: #9198a1;
                --tl-other-bg: rgba(110, 118, 129, .2);
            }
        }

        .tl *, .tl *::before, .tl *::after { box-sizing: border-box; }
        .tl [hidden] { display: none !important; }

        .tl-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .tl-title { margin: 0; font-size: 20px; font-weight: 600; letter-spacing: -.01em; line-height: 1.3; }
        .tl-subtitle { margin: 2px 0 0; color: var(--tl-muted); font-size: 13px; }

        .tl-pager { display: flex; gap: 6px; }
        .tl-footer { display: flex; justify-content: flex-end; margin-top: 16px; }

        .tl-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 32px;
            padding: 0 12px;
            border: 1px solid var(--tl-border);
            border-radius: 6px;
            background: var(--tl-surface);
            color: var(--tl-text);
            font: inherit;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .tl-btn-icon { width: 14px; height: 14px; flex: none; }
        .tl-btn:hover { background: var(--tl-surface-2); }
        .tl-btn[aria-disabled="true"] { opacity: .45; pointer-events: none; }
        .tl-btn--ghost { height: 26px; padding: 0 8px; border-color: transparent; background: transparent; color: var(--tl-muted); font-size: 12px; }
        .tl-btn--ghost:hover { color: var(--tl-text); }
        .tl-no-clipboard [data-copy] { display: none; }

        .tl-btn:focus-visible, .tl-summary:focus-visible { outline: 2px solid var(--tl-focus); outline-offset: -2px; }

        .tl-list {
            margin: 0;
            padding: 0;
            list-style: none;
            background: var(--tl-surface);
            border: 1px solid var(--tl-border);
            border-radius: 8px;
            overflow: hidden;
        }

        .tl-log + .tl-log { border-top: 1px solid var(--tl-border); }

        .tl-summary {
            display: grid;
            grid-template-columns: 64px minmax(0, 1fr) 76px 128px 16px;
            grid-template-areas: "method main duration time chevron";
            align-items: center;
            column-gap: 16px;
            width: 100%;
            padding: 10px 16px;
            border: 0;
            background: none;
            color: inherit;
            font: inherit;
            text-align: left;
            cursor: pointer;
        }

        .tl-summary:hover, .tl-summary[aria-expanded="true"] { background: var(--tl-surface-2); }

        .tl-method {
            grid-area: method;
            justify-self: start;
            padding: 1px 8px;
            border-radius: 999px;
            font: 600 11px/1.7 var(--tl-mono);
            letter-spacing: .02em;
        }

        .tl-method--get { color: var(--tl-get); background: var(--tl-get-bg); }
        .tl-method--post { color: var(--tl-post); background: var(--tl-post-bg); }
        .tl-method--put { color: var(--tl-put); background: var(--tl-put-bg); }
        .tl-method--delete { color: var(--tl-delete); background: var(--tl-delete-bg); }
        .tl-method--other { color: var(--tl-other); background: var(--tl-other-bg); }

        .tl-main { grid-area: main; display: flex; flex-direction: column; min-width: 0; }
        .tl-path, .tl-meta { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tl-path { font-family: var(--tl-mono); font-size: 13px; }
        .tl-meta { color: var(--tl-muted); font-size: 12px; }

        .tl-duration, .tl-time { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
        .tl-duration { grid-area: duration; font-family: var(--tl-mono); font-size: 12.5px; }
        .tl-time { grid-area: time; color: var(--tl-muted); font-size: 12.5px; }
        .tl-warn { color: var(--tl-warn); }
        .tl-slow { color: var(--tl-slow); font-weight: 600; }

        .tl-chevron { grid-area: chevron; width: 16px; height: 16px; color: var(--tl-muted); transition: transform .15s ease; }
        .tl-summary[aria-expanded="true"] .tl-chevron { transform: rotate(90deg); }

        .tl-detail { padding: 4px 16px 16px; background: var(--tl-surface-2); border-top: 1px solid var(--tl-border); }

        .tl-urlbar { display: flex; align-items: flex-start; gap: 8px; margin: 12px 0 16px; }
        .tl-fullurl {
            flex: 1;
            min-width: 0;
            padding: 6px 10px;
            border: 1px solid var(--tl-border);
            border-radius: 6px;
            background: var(--tl-surface);
            font: 12.5px/1.5 var(--tl-mono);
            word-break: break-all;
        }

        .tl-panels { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .tl-panel-title {
            margin: 0;
            color: var(--tl-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .tl-block { margin-top: 8px; border: 1px solid var(--tl-border); border-radius: 6px; background: var(--tl-surface); overflow: hidden; }
        .tl-block-head {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 3px 4px 3px 12px;
            border-bottom: 1px solid var(--tl-border);
            font-size: 12px;
            font-weight: 600;
        }

        .tl-size { margin-right: auto; color: var(--tl-muted); font-weight: 400; font-variant-numeric: tabular-nums; }
        .tl-block-body { max-height: 420px; overflow: auto; }

        .tl-code {
            margin: 0;
            padding: 10px 12px;
            font: 12.5px/1.55 var(--tl-mono);
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .tl-headers { width: 100%; border-collapse: collapse; font: 12.5px/1.5 var(--tl-mono); }
        .tl-headers tr + tr { border-top: 1px solid var(--tl-border); }
        .tl-headers th, .tl-headers td { padding: 5px 12px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        .tl-headers th { width: 38%; color: var(--tl-muted); font-weight: 500; }
        .tl-redacted { color: var(--tl-warn); }

        .tl-note { margin: 0; padding: 12px; color: var(--tl-muted); font-size: 12.5px; }
        .tl-note--error { color: var(--tl-slow); }
        .tl-detail > .tl-note { padding: 16px 0 4px; }

        .tl-empty { padding: 48px 16px; text-align: center; color: var(--tl-muted); }
        .tl-empty strong { display: block; margin-bottom: 4px; color: var(--tl-text); font-size: 15px; }

        @media (max-width: 760px) {
            .tl-summary {
                grid-template-columns: auto auto minmax(0, 1fr) 16px;
                grid-template-areas: "method main main chevron" ". duration time chevron";
                row-gap: 2px;
                column-gap: 12px;
            }

            .tl-duration, .tl-time { text-align: left; }
            .tl-panels { grid-template-columns: minmax(0, 1fr); }
        }
    </style>

    <div class="tl" id="tl-root">
        <header class="tl-toolbar">
            <div>
                <h1 class="tl-title">Request logs</h1>
                <p class="tl-subtitle">Newest first. Select a log to see its headers and bodies.</p>
            </div>
            @include('topup-logger::partials.pager')
        </header>

        @if($logs->isEmpty())
            <div class="tl-list tl-empty">
                <strong>No logs on this page</strong>
                @if($newestUrl)
                    <a class="tl-btn" href="{{ $newestUrl }}">Go to newest logs</a>
                @else
                    Requests show up here once something is logged.
                @endif
            </div>
        @else
            <ul class="tl-list">
                @foreach($logs as $log)
                    @php
                        $method = strtoupper((string) $log->method);
                        $methodClass = in_array($method, ['GET', 'POST', 'DELETE'], true)
                            ? strtolower($method)
                            : (in_array($method, ['PUT', 'PATCH'], true) ? 'put' : 'other');

                        $url = (string) $log->url;

                        $seconds = (float) $log->duration;
                        $duration = $seconds < 1 ? round($seconds * 1000) . ' ms' : number_format($seconds, 2) . ' s';
                        $durationClass = $seconds >= 3 ? 'tl-slow' : ($seconds >= 1 ? 'tl-warn' : '');

                        $time = \Illuminate\Support\Carbon::parse($log->request_time);
                        $timeLabel = $time->year === now()->year ? $time->format('M j, H:i:s') : $time->format('M j Y, H:i');

                        $meta = array_filter([
                            ucfirst((string) $log->type),
                            $log->ip,
                            $log->user_id ? 'User ' . $log->user_id : null,
                            '#' . $log->id,
                        ]);
                    @endphp
                    <li class="tl-log">
                        <button type="button" class="tl-summary" aria-expanded="false"
                                aria-controls="tl-detail-{{ $log->id }}"
                                data-url="{{ route('topup.logger.show', ['id' => $log->id]) }}">
                            <span class="tl-method tl-method--{{ $methodClass }}">{{ $method }}</span>
                            <span class="tl-main">
                                <span class="tl-path" title="{{ $url }}">{{ $url }}</span>
                                <span class="tl-meta">{{ implode(' · ', $meta) }}</span>
                            </span>
                            <span class="tl-duration {{ $durationClass }}" title="Duration">{{ $duration }}</span>
                            <time class="tl-time" datetime="{{ $time->toIso8601String() }}"
                                  title="{{ $time->format('Y-m-d H:i:s T') }}">{{ $timeLabel }}</time>
                            <svg class="tl-chevron" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.6"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="tl-detail" id="tl-detail-{{ $log->id }}" hidden></div>
                    </li>
                @endforeach
            </ul>

            <footer class="tl-footer">
                @include('topup-logger::partials.pager')
            </footer>
        @endif
    </div>

    {{-- Built once per expanded log; nothing below is rendered until a log is opened. --}}
    <template id="tl-detail-template">
        <div class="tl-urlbar">
            <code class="tl-fullurl" data-slot="url"></code>
            <button type="button" class="tl-btn tl-btn--ghost" data-copy data-slot="url-copy">Copy URL</button>
        </div>
        <div class="tl-panels">
            <section class="tl-panel">
                <h2 class="tl-panel-title">Request</h2>
                <div class="tl-block" data-field="request_header" data-kind="headers">
                    <div class="tl-block-head">Headers <span class="tl-size"></span>
                        <button type="button" class="tl-btn tl-btn--ghost" data-copy>Copy</button></div>
                    <div class="tl-block-body"></div>
                </div>
                <div class="tl-block" data-field="request_body" data-kind="body">
                    <div class="tl-block-head">Body <span class="tl-size"></span>
                        <button type="button" class="tl-btn tl-btn--ghost" data-copy>Copy</button></div>
                    <div class="tl-block-body"></div>
                </div>
            </section>
            <section class="tl-panel">
                <h2 class="tl-panel-title">Response</h2>
                <div class="tl-block" data-field="response_header" data-kind="headers">
                    <div class="tl-block-head">Headers <span class="tl-size"></span>
                        <button type="button" class="tl-btn tl-btn--ghost" data-copy>Copy</button></div>
                    <div class="tl-block-body"></div>
                </div>
                <div class="tl-block" data-field="response_body" data-kind="body">
                    <div class="tl-block-head">Body <span class="tl-size"></span>
                        <button type="button" class="tl-btn tl-btn--ghost" data-copy>Copy</button></div>
                    <div class="tl-block-body"></div>
                </div>
            </section>
        </div>
    </template>

    <script>
        (function () {
            var root = document.getElementById('tl-root');
            var template = document.getElementById('tl-detail-template');
            if (!root || !template) {
                return;
            }
            if (!navigator.clipboard) {
                root.classList.add('tl-no-clipboard'); // clipboard needs https or localhost
            }

            root.addEventListener('click', function (event) {
                var copy = event.target.closest('[data-copy]');
                if (copy) {
                    copyText(copy);
                    return;
                }
                var summary = event.target.closest('.tl-summary');
                if (summary) {
                    toggle(summary);
                }
            });

            function toggle(summary) {
                var detail = document.getElementById(summary.getAttribute('aria-controls'));
                var open = summary.getAttribute('aria-expanded') !== 'true';
                var state = detail.getAttribute('data-state');

                summary.setAttribute('aria-expanded', open ? 'true' : 'false');
                detail.hidden = !open;

                if (open && state !== 'loaded' && state !== 'loading') {
                    load(summary.getAttribute('data-url'), detail);
                }
            }

            // headers and bodies are fetched on first open, so the list itself stays small
            function load(url, detail) {
                detail.setAttribute('data-state', 'loading');
                detail.replaceChildren(note('Loading…'));

                fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function (log) {
                        detail.replaceChildren(render(log));
                        detail.setAttribute('data-state', 'loaded');
                    })
                    .catch(function (error) {
                        detail.replaceChildren(note('Could not load this log (' + error.message + '). Close and reopen it to try again.', true));
                        detail.setAttribute('data-state', 'error');
                    });
            }

            // logged data is untrusted: it only ever goes in through textContent
            function render(log) {
                var fragment = template.content.cloneNode(true);
                var url = log.url ? String(log.url) : '';

                fragment.querySelector('[data-slot="url"]').textContent = url;
                fragment.querySelector('[data-slot="url-copy"]').tlText = url;

                fragment.querySelectorAll('[data-field]').forEach(function (block) {
                    var raw = log[block.getAttribute('data-field')];
                    var value = raw === null || raw === undefined ? '' : String(raw);
                    var body = block.querySelector('.tl-block-body');
                    var copy = block.querySelector('[data-copy]');

                    if (value === '') {
                        body.appendChild(note('Empty'));
                        copy.hidden = true;
                        return;
                    }

                    block.querySelector('.tl-size').textContent = formatBytes(new Blob([value]).size);
                    copy.tlText = block.getAttribute('data-kind') === 'headers'
                        ? renderHeaders(body, value)
                        : renderBody(body, value);
                });

                return fragment;
            }

            function renderHeaders(container, value) {
                var headers = parseJson(value);
                if (Array.isArray(headers) && headers.length === 0) {
                    container.appendChild(note('None'));
                    return '';
                }
                if (!headers || typeof headers !== 'object' || Array.isArray(headers)) {
                    return renderBody(container, value);
                }

                var tbody = el('tbody');
                var lines = [];
                Object.keys(headers).forEach(function (name) {
                    [].concat(headers[name]).forEach(function (item) {
                        var text = item === null || item === undefined ? '' : String(item);
                        var row = el('tr');
                        var th = el('th', null, name);
                        th.scope = 'row';
                        row.appendChild(th);
                        row.appendChild(el('td', text.indexOf('[REDACTED]') !== -1 ? 'tl-redacted' : null, text));
                        tbody.appendChild(row);
                        lines.push(name + ': ' + text);
                    });
                });

                var table = el('table', 'tl-headers');
                table.appendChild(tbody);
                container.appendChild(table);
                return lines.join('\n');
            }

            function renderBody(container, value) {
                var first = value.trim().charAt(0);
                var parsed = first === '{' || first === '[' ? parseJson(value) : undefined;
                var text = parsed && typeof parsed === 'object' ? JSON.stringify(parsed, null, 2) : value;
                container.appendChild(el('pre', 'tl-code', text));
                return text;
            }

            function copyText(button) {
                if (!navigator.clipboard) {
                    return;
                }
                navigator.clipboard.writeText(button.tlText || '').then(function () {
                    var label = button.getAttribute('data-label') || button.textContent;
                    button.setAttribute('data-label', label);
                    button.textContent = 'Copied';
                    setTimeout(function () {
                        button.textContent = label;
                    }, 1200);
                });
            }

            function parseJson(value) {
                try {
                    return JSON.parse(value);
                } catch (e) {
                    return undefined;
                }
            }

            function formatBytes(bytes) {
                if (bytes < 1024) {
                    return bytes + ' B';
                }
                return bytes < 1048576 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';
            }

            function note(text, isError) {
                return el('p', 'tl-note' + (isError ? ' tl-note--error' : ''), text);
            }

            function el(tag, className, text) {
                var node = document.createElement(tag);
                if (className) {
                    node.className = className;
                }
                if (text !== undefined && text !== null) {
                    node.textContent = text;
                }
                return node;
            }
        })();
    </script>
@endsection

@extends(config('topup-logger.layout'))

@section(config('topup-logger.section'))
    <style>
        .topup-flex {
            display: flex;
        }

        .topup-flex-1 {
            flex: 1;
        }

        .topup-separator {
            border-bottom: 1px dotted #cccccc;
            margin: 5px 0 10px 0;
        }

        .topup-logger-container {
            max-width: 100%;
            width: 100%;
        }

        .topup-log {
            padding: 5px;
        }

        .topup-log-header {
            border: 1px solid #eeeeee;
            padding: 5px;
            background-color: #002b36;
            color: #ffffff;
        }

        .topup-url {
            padding: 5px;
            font-size: .85rem;
            color: #76838f;
            word-break: break-all;
        }

        .topup-log-json {
            width: 100% !important;
            display: block;
            border: none !important;
            background-color: transparent !important;
        }

        .topup-hide {
            display: none;
        }

        .topup-editor {
            background-color: #002b36;
            color: #ffffff;
            padding: 5px;
            margin: 0;
            max-height: 480px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .topup-paginate a {
            margin-right: 10px;
        }
    </style>
    <script>
        // Headers and bodies are not part of the page: they are fetched when a log is first expanded.
        function topupLoggerAction(btn) {
            let element = document.getElementById('topup-logger-content-' + btn.getAttribute('data-log-id'));

            if (element.getAttribute('data-loaded') === '1') {
                element.classList.toggle('topup-hide');
                return;
            }

            btn.disabled = true;
            element.classList.remove('topup-hide');
            topupLoggerFill(element, 'Loading...');

            fetch(btn.getAttribute('data-url'), {
                headers: {'Accept': 'application/json'},
                credentials: 'same-origin'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            }).then(function (log) {
                ['request_header', 'request_body', 'response_header', 'response_body'].forEach(function (field) {
                    element.querySelector('[data-field="' + field + '"]').textContent = topupLoggerPretty(log[field]);
                });
                element.setAttribute('data-loaded', '1');
            }).catch(function (error) {
                topupLoggerFill(element, 'Could not load this log (' + error.message + ')');
            }).finally(function () {
                btn.disabled = false;
            });
        }

        function topupLoggerFill(element, text) {
            element.querySelectorAll('[data-field]').forEach(function (block) {
                block.textContent = text;
            });
        }

        function topupLoggerPretty(value) {
            if (value === null || value === undefined) {
                return '';
            }
            try {
                let parsed = JSON.parse(value);
                if (parsed !== null && typeof parsed === 'object') {
                    return JSON.stringify(parsed, null, 2);
                }
            } catch (e) {
                // not JSON: show as stored
            }
            return value;
        }
    </script>
    <div class="topup-logger-container">
        @forelse($logs as $log)
            <div class="topup-separator topup-log">
                <div class="topup-log-header topup-flex">
                    <div class="topup-flex-1 topup-log-action">
                        <button type="button" data-log-id="{{ $log->id }}"
                                data-url="{{ route('topup.logger.show', ['id' => $log->id]) }}"
                                onclick="topupLoggerAction(this)">Show/Hide</button>
                    </div>
                    <div class="topup-flex-1 topup-log-method">Method: {{ $log->method }}</div>
                    <div class="topup-flex-1 topup-log-type">Type: {{ $log->type }}</div>
                    <div class="topup-flex-1 topup-log-duration">Duration: {{ $log->duration }}</div>
                    <div class="topup-flex-1 topup-log-request_time">Date: {{ $log->request_time }}</div>
                    <div class="topup-flex-1 topup-log-request_ip">IP: {{ $log->ip }}</div>
                </div>

                <div class="topup-url">URL: {{ $log->url }}</div>

                <div id="topup-logger-content-{{ $log->id }}" class="topup-hide">
                    <div class="topup-user">User ID: {{ $log->user_id }}</div>

                    <div class="topup-separator topup-log-json topup-request-header">
                        <div>Request Header:</div>
                        <pre class="topup-editor" data-field="request_header"></pre>
                    </div>
                    <div class="topup-separator topup-log-json topup-request-body">
                        <div>Request Body:</div>
                        <pre class="topup-editor" data-field="request_body"></pre>
                    </div>

                    <div class="topup-separator topup-log-json topup-response-header">
                        <div>Response Header:</div>
                        <pre class="topup-editor" data-field="response_header"></pre>
                    </div>
                    <div class="topup-separator topup-log-json topup-response-body">
                        <div>Response Body:</div>
                        <pre class="topup-editor" data-field="response_body"></pre>
                    </div>
                </div>
            </div>
        @empty
            <p>No logs here.</p>
        @endforelse

        <div class="topup-paginate paginate">
            @if($newestUrl)
                <a href="{{ $newestUrl }}">&laquo; Newest</a>
            @endif
            @if($newerUrl)
                <a href="{{ $newerUrl }}">&lsaquo; Newer</a>
            @endif
            @if($olderUrl)
                <a href="{{ $olderUrl }}">Older &rsaquo;</a>
            @endif
        </div>
    </div>
@endsection

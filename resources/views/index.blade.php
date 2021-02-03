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
        }
    </style>
    <script>
        function topupLoggerAction(btn) {
            let logID = btn.getAttribute('data-log-id');
            let containerID = 'topup-logger-content-' + logID;
            let element = document.getElementById(containerID);
            element.classList.toggle("topup-hide");
        }
    </script>
    <div class="topup-logger-container">
        @foreach($logs as $log)
            <div class="topup-separator topup-log">
                <div class="topup-log-header topup-flex">
                    <div class="topup-flex-1 topup-log-action">
                        <button data-log-id="{{ $log->id }}" onclick="topupLoggerAction(this)">Show/Hide</button>
                    </div>
                    <div class="topup-flex-1 topup-log-method">Method: {{ $log->method }}</div>
                    <div class="topup-flex-1 topup-log-type">Type: {{ $log->type }}</div>
                    <div class="topup-flex-1 topup-log-duration">Duration: {{ $log->duration }}</div>
                    <div class="topup-flex-1 topup-log-request_time">Date: {{ $log->request_time }}</div>
                    <div class="topup-flex-1 topup-log-request_ip">IP: {{ $log->	ip }}</div>
                </div>

                <div class="topup-url">URL: {{ $log->url }}</div>

                <div id="topup-logger-content-{{ $log->id }}" class="topup-hide">
                    <div class="topup-user">User ID: {{ $log->user_id }}</div>

                    <div class="topup-separator topup-log-json topup-request-header">
                        <div>Request Header:</div>
                        <div class="topup-editor">
                            <code>
                                {{ $log->request_header }}
                            </code>
                        </div>
                    </div>
                    <div class="topup-separator topup-log-json topup-request-body">
                        <div>Request Body:</div>
                        <div class="topup-editor">
                            <code>
                                {{ $log->request_body }}
                            </code>
                        </div>
                    </div>

                    <div class="topup-separator topup-log-json topup-response-header">
                        <div>Response Header:</div>
                        <div class="topup-editor">
                            <code>
                                {{ $log->response_header }}
                            </code>
                        </div>
                    </div>
                    <div class="topup-separator topup-log-json topup-response-body">
                        <div>Response Body:</div>
                        <div class="topup-editor">
                            <code>
                                {{ $log->response_body }}
                            </code>
                        </div>
                    </div>

                </div>

            </div>
        @endforeach

        <div class="topup-paginate paginate">
            {{ $logs->links() }}
        </div>
    </div>
@endsection

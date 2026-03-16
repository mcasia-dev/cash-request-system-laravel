<?php

return [
    'ocr_space_api_key'  => env('OCR_SPACE_API_KEY', null),
    'ocr_space_endpoint' => env('OCR_SPACE_ENDPOINT', 'https://api.ocr.space/parse/image'),
    'timeout'            => (int) env('OCR_SPACE_TIMEOUT', 30),
    'connect_timeout'    => (int) env('OCR_SPACE_CONNECT_TIMEOUT', 10),
    'retry_times'        => (int) env('OCR_SPACE_RETRY_TIMES', 2),
    'retry_sleep_ms'     => (int) env('OCR_SPACE_RETRY_SLEEP_MS', 1000),
];

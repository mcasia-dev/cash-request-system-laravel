<x-filament-panels::page>
    <style>
        .tracker-shell {
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
        }

        .tracker-line {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            transform: translateX(-50%);
            background: #dbe5ef;
            border-radius: 999px;
        }

        .tracker-step {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 56px 1fr;
            align-items: center;
            margin-bottom: 28px;
        }

        .tracker-step:last-child {
            margin-bottom: 0;
        }

        .tracker-step--right .tracker-card {
            grid-column: 3;
            grid-row: 1;
        }

        .tracker-step--left .tracker-card {
            grid-column: 1;
            grid-row: 1;
        }

        .tracker-dot-wrap {
            grid-column: 2;
            grid-row: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        .tracker-dot {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 4px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
        }

        .tracker-card {
            border-radius: 12px;
            border: 2px solid;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
        }

        .tracker-card h3 {
            margin: 0;
        }

        .tracker-meta {
            margin-top: 10px;
            display: grid;
            gap: 4px;
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .tracker-line {
                left: 16px;
                transform: none;
            }

            .tracker-step {
                grid-template-columns: 40px 1fr;
            }

            .tracker-dot-wrap {
                grid-column: 1;
            }

            .tracker-step--left .tracker-card,
            .tracker-step--right .tracker-card {
                grid-column: 2;
            }
        }
    </style>

    <div class="space-y-5">
        <div class="tracker-shell">
            <div class="text-sm text-slate-700">
                <span class="font-semibold">Request No:</span> {{ $this->getRecord()->request_no }}
            </div>
            <div class="text-sm text-slate-700">
                <span class="font-semibold">Nature of Request:</span> {{ $this->getRecord()->nature_of_request }}
            </div>
        </div>

        <div class="tracker-shell relative">
            <div class="tracker-line"></div>

            @foreach ($this->getTrackerSteps() as $index => $step)
                @php($styles = $this->getStateStyles($step['status']))
                @php($isRight = $index % 2 === 1)
                @php($dotColor = match($step['status']) {
                    'approved' => '#059669',
                    'rejected' => '#dc2626',
                    'pending' => '#f59e0b',
                    default => '#94a3b8',
                })

                <div class="tracker-step {{ $isRight ? 'tracker-step--right' : 'tracker-step--left' }}">
                    <div class="tracker-dot-wrap">
                        <div class="tracker-dot" style="background: {{ $dotColor }};">
                            {{ $step['status'] === 'approved' ? '✓' : ($step['status'] === 'rejected' ? 'X' : '!') }}
                        </div>
                    </div>

                    <div class="tracker-card {{ $styles['card'] }}">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-xl font-semibold {{ $styles['title'] }}">{{ $step['title'] }}</h3>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $styles['badge'] }}">
                                {{ $step['statusLabel'] }}
                            </span>
                        </div>

                        <div class="tracker-meta text-slate-700">
                            <div><span class="font-semibold">Remarks:</span> {{ $step['remarks'] }}</div>
                            <div><span class="font-semibold">By:</span> {{ $step['by'] }}</div>
                            <div><span class="font-semibold">Date:</span> {{ $step['date'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

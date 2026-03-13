<x-filament-panels::page>
    <style>
        .timeline {
            width: min(100%, 700px);
            margin: 0 auto;
            position: relative;
            padding-left: 4px;
            padding-top: 6px;
            padding-bottom: 10px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 2px;
            bottom: 2px;
            width: 2px;
            background: #e2e8f0;
        }

        .step {
            position: relative;
            display: grid;
            grid-template-columns: 28px 1fr;
            gap: 16px;
            margin-bottom: 30px;
        }

        .step:last-child {
            margin-bottom: 0;
        }

        .step-dot {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid #d7dee8;
            background: #f8fafc;
            margin-top: 12px;
            z-index: 1;
        }

        .step > div:last-child {
            padding-top: 4px;
            padding-bottom: 2px;
        }

        .step-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .step-head {
            margin: 0;
            font-size: 24px;
            line-height: 1.2;
            font-weight: 500;
            color: #1e293b;
        }

        .step-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.01em;
        }

        .step-meta {
            display: grid;
            gap: 8px;
            font-size: 14px;
            color: #475569;
            line-height: 1.62;
        }

        .step-meta span {
            color: #64748b;
            font-weight: 400;
        }

        .s-approved .step-dot {
            background: #047857;
            border-color: #d1fae5;
            box-shadow: 0 0 0 3px #ecfdf5;
        }

        .s-approved .step-pill {
            background: #fef3c7;
            color: #9a6700;
        }

        .s-rejected .step-dot {
            background: #dc2626;
            border-color: #fecaca;
            box-shadow: 0 0 0 3px #fff1f2;
        }

        .s-rejected .step-pill {
            background: #fee2e2;
            color: #b91c1c;
        }

        .s-pending .step-dot {
            background: #d97706;
            border-color: #fde68a;
            box-shadow: 0 0 0 3px #fff7ed;
        }

        .s-pending .step-pill {
            background: #ffedd5;
            color: #9a5a00;
        }

        .s-upcoming .step-dot,
        .s-stopped .step-dot {
            background: #cbd5e1;
            border-color: #e2e8f0;
        }

        .s-upcoming .step-pill,
        .s-stopped .step-pill {
            background: #e2e8f0;
            color: #64748b;
        }

        .intro-pill {
            background: #e2e8f0;
            color: #64748b;
        }
    </style>

    @php($steps = $this->getTrackerSteps())

    <div class="timeline">
        <div class="step">
            <div class="step-dot"></div>
            <div class="step-top">
                <h2 class="step-head">{{ $this->getRecord()->reimbursement_no }}</h2>
                <span class="step-pill intro-pill">{{ $this->getRecord()->reimbursementMode?->name ?? 'N/A' }}</span>
            </div>
        </div>

        @foreach ($steps as $step)
            @php($stateClass = 's-' . ($step['status'] ?? 'upcoming'))
            <div class="step {{ $stateClass }}">
                <div class="step-dot"></div>
                <div>
                    <div class="step-top">
                        <h3 class="step-head">{{ $step['title'] }}</h3>
                        <span class="step-pill">{{ $step['statusLabel'] }}</span>
                    </div>

                    <div class="step-meta">
                        <div><span>remarks:</span> {{ $step['remarks'] }}</div>
                        <div><span>by:</span> {{ $step['by'] }}</div>
                        <div><span>date:</span> {{ $step['date'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>


<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Support\HtmlString;

trait HasDashboardReportLinks
{
    protected function renderReportLinks(string $report, ?string $filter = null): HtmlString
    {
        $query = $filter ? ['filter' => $filter] : [];

        $printUrl = route('reports.dashboard.print', ['report' => $report] + $query);
        $pdfUrl = route('reports.dashboard.pdf', ['report' => $report] + $query);
        $excelUrl = route('reports.dashboard.excel', ['report' => $report] + $query);

        return new HtmlString(
            '<div class="mt-2 flex flex-wrap gap-2">'
            . '<a href="' . e($printUrl) . '" target="_blank" class="inline-flex items-center rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Print</a>'
            . '<a href="' . e($pdfUrl) . '" class="inline-flex items-center rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">PDF</a>'
            . '<a href="' . e($excelUrl) . '" class="inline-flex items-center rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">Excel</a>'
            . '</div>'
        );
    }
}

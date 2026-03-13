<?php

namespace App\Http\Controllers;

use App\Services\Reports\DashboardChartReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardChartReportController extends Controller
{
    public function __construct(
        private readonly DashboardChartReportService $reportService
    ) {
    }

    public function print(Request $request, string $report): Response
    {
        $data = $this->resolveReport($request, $report);

        return response()->view('reports.dashboard-chart', [
            ...$data,
            'isExport' => false,
        ]);
    }

    public function pdf(Request $request, string $report): Response
    {
        $data = $this->resolveReport($request, $report);

        $pdf = Pdf::loadView('reports.dashboard-chart', [
            ...$data,
            'isExport' => true,
        ])->setPaper('a4');

        return $pdf->download($report . '-report.pdf');
    }

    public function excel(Request $request, string $report): BinaryFileResponse
    {
        $data = $this->resolveReport($request, $report);
        $filePath = tempnam(sys_get_temp_dir(), 'dashboard-chart-');

        if ($filePath === false) {
            abort(500, 'Unable to create export file.');
        }

        $xlsxPath = $filePath . '.xlsx';
        $writer = new Writer();
        $writer->openToFile($xlsxPath);
        $writer->getCurrentSheet()->setName(substr($data['title'], 0, 31));
        $writer->addRow(Row::fromValues([$data['title']]));
        $writer->addRow(Row::fromValues([$data['subtitle']]));
        $writer->addRow(Row::fromValues(['Generated At', $data['generatedAt']->format('M d, Y h:i A')]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Label', $data['metricLabel']]));

        foreach ($data['rows'] as $row) {
            $writer->addRow(Row::fromValues([$row['label'], $row['value']]));
        }

        $writer->close();

        return response()->download($xlsxPath, $report . '-report.xlsx')->deleteFileAfterSend(true);
    }

    private function resolveReport(Request $request, string $report): array
    {
        abort_unless(Auth::check(), 403);
        abort_unless($this->canAccessReport($report), 403);

        return $this->reportService->getReport(
            report: $report,
            user: Auth::user(),
            filter: $request->string('filter')->toString() ?: null,
        );
    }

    private function canAccessReport(string $report): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return match ($report) {
            'requests-by-status' => $user->isSuperAdmin(),
            'release-amount-summary', 'release-nature-percentage', 'approval-decision'
                => $user->isSuperAdmin() || $user->hasRole('treasury_staff') || $user->hasRole('treasury_manager'),
            default => false,
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Reports\TopActiveUsersReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopActiveUsersReportController extends Controller
{
    public function __construct(
        private readonly TopActiveUsersReportService $reportService
    ) {
    }

    public function print(): Response
    {
        $this->authorizeAccess();

        return response()->view('reports.top-active-users', [
            ...$this->reportService->getReportData(),
            'isExport' => false,
        ]);
    }

    public function pdf(): Response
    {
        $this->authorizeAccess();

        $pdf = Pdf::loadView('reports.top-active-users', [
            ...$this->reportService->getReportData(),
            'isExport' => true,
            'autoPrint' => false,
            'pdfMode' => true,
        ])->setPaper('a4');

        return $pdf->download('top-active-users-report.pdf');
    }

    public function excel(): BinaryFileResponse
    {
        $this->authorizeAccess();

        $data = $this->reportService->getReportData();
        $filePath = tempnam(sys_get_temp_dir(), 'top-active-users-');

        if ($filePath === false) {
            abort(500, 'Unable to create export file.');
        }

        $xlsxPath = $filePath . '.xlsx';
        $writer = new Writer();
        $writer->openToFile($xlsxPath);

        $writer->getCurrentSheet()->setName('Top Submitters');
        $writer->addRow(Row::fromValues(['Name', 'Position', 'Total Submissions']));
        foreach ($data['topSubmitters'] as $row) {
            $writer->addRow(Row::fromValues([$row['name'], $row['position'], $row['total']]));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Top Approvers');
        $writer->addRow(Row::fromValues(['Name', 'Position', 'Total Approvals']));
        foreach ($data['topApprovers'] as $row) {
            $writer->addRow(Row::fromValues([$row['name'], $row['position'], $row['total']]));
        }

        $writer->close();

        return response()->download($xlsxPath, 'top-active-users-report.xlsx')->deleteFileAfterSend(true);
    }

    private function authorizeAccess(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperAdmin(), 403);
    }
}

<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use App\Services\{ReportBuilder, ReportService};
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public const TYPES = ['sales' => 'Sales', 'profit' => 'Profit', 'inventory' => 'Inventory', 'products' => 'Products', 'customers' => 'Customers', 'staff' => 'Staff'];

    public function index(Request $request)
    {
        $user = $request->user();
        $types = self::TYPES;
        if (!$user->can_do('profit.view')) unset($types['profit']);
        $type = $request->get('type', 'sales');
        abort_unless(isset($types[$type]), 403, 'You do not have access to this report.');

        [$from, $to, $label, $key] = ReportService::range($request->get('range', 'month'), $request->get('from'), $request->get('to'));
        $export = $request->get('export') === 'csv';
        $data = ReportBuilder::build($type, $request, $from, $to, $user, $export);

        if ($export) {
            AuditLog::record('report_exported', null, "{$user->name} exported the {$data['title']} ({$label})");
            return $this->csv($data, $type);
        }
        return view('reports.index', compact('type', 'types', 'data', 'from', 'to', 'label', 'key'));
    }

    private function csv(array $data, string $type)
    {
        $name = $type . '-report-' . now()->format('Y-m-d') . '.csv';
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                       // so Excel reads it correctly
            fputcsv($out, array_column($data['columns'], 'label'));
            foreach ($data['rows'] as $row) {
                $line = [];
                foreach ($data['columns'] as $c) {
                    $v = $row[$c['key']] ?? '';
                    $line[] = $c['type'] === 'money' && $v !== '' ? number_format((float) $v, 2, '.', '') : $v;
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

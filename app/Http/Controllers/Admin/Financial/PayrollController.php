<?php

namespace App\Http\Controllers\Admin\Financial;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\SchoolProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PayrollController extends Controller
{
    public function downloadSlip(Payroll $payroll): Response
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        // Ensure user is authorized
        if (! $user->isAdmin() && ! $user->isBendahara() && ! $user->isKepsek() && $user->id !== $payroll->user_id) {
            abort(403);
        }

        $schoolProfile = SchoolProfile::active();

        $pdf = Pdf::loadView('pdf.financial.payroll-slip', [
            'payroll' => $payroll->load(['user', 'academicYear']),
            'schoolProfile' => $schoolProfile,
        ]);

        $filename = 'Slip_Gaji_'.str_replace(' ', '_', $payroll->user->name).'_'.$payroll->month.'.pdf';

        return $pdf->download($filename);
    }
}

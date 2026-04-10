<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\QrTokenService;
use Illuminate\Contracts\View\View;

class QrController extends Controller
{
    public function __construct(private readonly QrTokenService $qrTokenService)
    {
    }

    public function show(): View
    {
        return view('student.qr.show', [
            'token' => $this->qrTokenService->generateForUser(auth()->user()),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;

class CertificateController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success();
    }

    public function create(): JsonResponse
    {
        return $this->success();
    }

    public function show(Certificate $certificate): JsonResponse
    {
        return $this->success($certificate);
    }
}
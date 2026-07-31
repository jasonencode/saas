<?php

namespace App\Http\Controllers\Chain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chain\StoreCertificateRequest;
use App\Http\Resources\Chain\CertificateResource;
use App\Http\Responses\ApiResponse;
use App\Models\BlockChain\Certificate;
use App\Services\BlockChain\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $certificates = Certificate::with(['parent'])
            ->when($request->filled('type'), function ($builder, string $type) {
                $builder->where('type', $type);
            })
            ->when($request->filled('sign_type'), function ($builder, string $signType) {
                $builder->where('sign_type', $signType);
            })
            ->latest()
            ->paginate(min((int) $request->input('limit', config('custom.pagination.default_per_page')), config('custom.pagination.max_per_page')));

        return ApiResponse::success(CertificateResource::collection($certificates));
    }

    public function create(StoreCertificateRequest $request): JsonResponse
    {
        $certificate = Certificate::create([
            'common_name' => $request->input('common_name'),
            'type' => $request->input('type'),
            'sign_type' => $request->input('sign_type'),
            'country_name' => $request->input('country_name'),
            'state_or_province_name' => $request->input('state_or_province_name'),
            'locality_name' => $request->input('locality_name'),
            'organization_name' => $request->input('organization_name'),
            'organizational_unit_name' => $request->input('organizational_unit_name'),
            'email_address' => $request->input('email_address'),
            'password' => $request->input('password'),
        ]);

        return ApiResponse::created(CertificateResource::make($certificate));
    }

    public function show(Certificate $certificate): JsonResponse
    {
        if ($certificate->isDisabled()) {
            return ApiResponse::notFound();
        }

        $certificate->load(['parent', 'children']);

        return ApiResponse::success(CertificateResource::make($certificate));
    }
}

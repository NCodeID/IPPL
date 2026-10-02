<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceivePurchaseRequestRequest;
use App\Http\Requests\ReviewPurchaseRequestRequest;
use App\Http\Requests\StorePurchaseRequestRequest;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseRequestController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $query = PurchaseRequest::query()->with(['items.product', 'requester', 'approver'])
            ->orderByDesc('created_at');

        return PurchaseRequestResource::collection($query->paginate(10));
    }

    public function show(PurchaseRequest $purchaseRequest): PurchaseRequestResource
    {
        return new PurchaseRequestResource($purchaseRequest->load(['items.product', 'requester', 'approver']));
    }

    public function store(StorePurchaseRequestRequest $request, PurchaseRequestService $service): JsonResponse
    {
        $purchaseRequest = $service->createRequest($request->validated()['items'], $request->user()->id);

        return (new PurchaseRequestResource($purchaseRequest->load(['items.product', 'requester', 'approver'])))->response()->setStatusCode(201);
    }

    public function review(ReviewPurchaseRequestRequest $request, PurchaseRequest $purchaseRequest, PurchaseRequestService $service): PurchaseRequestResource
    {
        $purchaseRequest = $service->reviewRequest($purchaseRequest, $request->user()->id, $request->validated()['status'], $request->validated()['note'] ?? null);

        return new PurchaseRequestResource($purchaseRequest->load(['items.product', 'requester', 'approver']));
    }

    public function receive(ReceivePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest, PurchaseRequestService $service): PurchaseRequestResource
    {
        $purchaseRequest = $service->receiveGoods($purchaseRequest, $request->validated()['items']);

        return new PurchaseRequestResource($purchaseRequest->load(['items.product', 'requester', 'approver']));
    }

    public function settle(PurchaseRequest $purchaseRequest, PurchaseRequestService $service): PurchaseRequestResource
    {
        $purchaseRequest = $service->settle($purchaseRequest);

        return new PurchaseRequestResource($purchaseRequest->load(['items.product', 'requester', 'approver']));
    }
}

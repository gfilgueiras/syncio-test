<?php

namespace App\Http\Controllers;

use Illuminate\Cache\CacheManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayloadController extends Controller
{
    private CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    public function storeFirst(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $this->cache->put('payload:p1', $payload, now()->addMinutes(10));

        return response()->json(['status' => 'ok', 'message' => 'Payload 1 received']);
    }

    public function storeSecond(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $this->cache->put('payload:p2', $payload, now()->addMinutes(10));

        return response()->json(['status' => 'ok', 'message' => 'Payload 2 received']);
    }

    public function diff(): JsonResponse
    {
        $p1 = $this->cache->get('payload:p1');
        $p2 = $this->cache->get('payload:p2');

        if ($p1 === null || $p2 === null) {
            return response()->json([
                'status' => 'pending',
                'message' => 'Waiting for payloads',
                'received' => [
                    'p1' => $p1 !== null,
                    'p2' => $p2 !== null,
                ],
            ]);
        }

        return response()->json([
            'status' => 'done',
            'diff' => $this->recursiveDiff($p1, $p2),
        ]);
    }

    private function recursiveDiff(array $a, array $b): array
    {
        $diff = [];
        $keys = collect(array_keys($a))
            ->merge(array_keys($b))
            ->unique();

        foreach ($keys as $key) {
            $itemA = $a[$key] ?? null;
            $itemB = $b[$key] ?? null;

            if ($itemA === $itemB) {
                continue;
            }

            if (is_array($itemA) && is_array($itemB)) {
                $nested = $this->recursiveDiff($itemA, $itemB);
                if (!empty($nested)) {
                    $diff[$key] = $nested;
                }
            } else {
                $diff[$key] = [
                    'from' => $itemA,
                    'to' => $itemB,
                ];
            }
        }

        return $diff;
    }
}

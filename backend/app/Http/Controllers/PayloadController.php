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
                'message' => 'Waiting for both payloads',
                'received' => [
                    'p1' => $p1 !== null,
                    'p2' => $p2 !== null,
                ],
            ]);
        }

        $diff = $this->computeProductDiff($p1, $p2);

        return response()->json([
            'status' => 'done',
            'diff' => $diff,
        ]);
    }

    private function computeProductDiff(array $p1, array $p2): array
    {
        // Step 1: Compare root-level fields (title, description)
        // Create a Laravel Collection with the fields we want to check
        $rootFields = collect(['title', 'description'])

            // Filter: Keep only fields that are DIFFERENT between p1 and p2
            // The ?? null means "use null if the field doesn't exist"
            // !== compares if values are not identical
            ->filter(fn($field) => ($p1[$field] ?? null) !== ($p2[$field] ?? null))

            // Transform: For each changed field, create a "before and after" structure
            // Example result: ['title' => ['from' => 'Old Title', 'to' => 'New Title']]
            ->mapWithKeys(fn($field) => [
                $field => [
                    'from' => $p1[$field] ?? null,  // Old value
                    'to' => $p2[$field] ?? null,    // New value
                ]
            ])

            // Convert the Collection back to a plain array
            ->toArray();

        // Step 2: Build and return the complete diff structure
        return [
            // Root changes (title, description that changed)
            'rootChanges' => $rootFields,

            // Images comparison: Check which images were added, removed, or modified
            // We pass the fields we care about: 'position' and 'url'
            'images' => $this->diffArrayById(
                $p1['images'] ?? [],  // Old images (or empty array if none)
                $p2['images'] ?? [],  // New images (or empty array if none)
                ['position', 'url']   // Fields to compare for changes
            ),

            // Variants comparison: Check which product variants changed
            // We track changes in SKU, barcode, image reference, and inventory
            'variants' => $this->diffArrayById(
                $p1['variants'] ?? [],  // Old variants
                $p2['variants'] ?? [],  // New variants
                ['sku', 'barcode', 'image_id', 'inventory_quantity']  // Fields to track
            ),
        ];
    }

    private function diffArrayById(array $p1, array $p2, array $fieldsToCheck): array
    {
        // Step 1: Index both arrays by 'id' for fast lookups
        // Transform: [{'id': 1, 'name': 'A'}, {'id': 2, 'name': 'B'}]
        // Into: {1: {'id': 1, 'name': 'A'}, 2: {'id': 2, 'name': 'B'}}
        // This makes it super fast to check if an ID exists!
        $collectionP1 = collect($p1)->keyBy('id');
        $collectionP2 = collect($p2)->keyBy('id');

        // Step 2: Find ADDED items (new items that appear in p2)
        // Logic: Keep items from p2 whose ID doesn't exist in p1
        $added = $collectionP2
            ->reject(fn($item) => $collectionP1->has($item['id']))  // Reject if ID exists in old data
            ->values()  // Reset array keys to 0, 1, 2... (remove the ID keys)
            ->toArray();

        // Step 3: Find REMOVED items (items that disappeared from p1 to p2)
        // Logic: Keep items from p1 whose ID doesn't exist in p2
        $removed = $collectionP1
            ->reject(fn($item) => $collectionP2->has($item['id']))  // Reject if ID exists in new data
            ->values()  // Reset array keys
            ->toArray();

        // Step 4: Find CHANGED items (items that exist in both, but with different values)
        $changed = $collectionP1
            // Filter: Keep only items that exist in BOTH p1 and p2
            ->filter(fn($itemA) => $collectionP2->has($itemA['id']))

            // Map: For each item, check which specific fields changed
            ->map(function ($itemA) use ($collectionP2, $fieldsToCheck) {
                // Get the corresponding item from p2 (same ID)
                $itemB = $collectionP2[$itemA['id']];

                // Check each field we care about (e.g., 'sku', 'barcode', 'price')
                $fieldChanges = collect($fieldsToCheck)
                    // Filter: Keep only fields where the value is DIFFERENT
                    ->filter(fn($field) => ($itemA[$field] ?? null) !== ($itemB[$field] ?? null))

                    // Transform: Create "before and after" for each changed field
                    // Example: ['sku' => ['from' => 'SKU-OLD', 'to' => 'SKU-NEW']]
                    ->mapWithKeys(fn($field) => [
                        $field => [
                            'from' => $itemA[$field] ?? null,  // Old value
                            'to' => $itemB[$field] ?? null,    // New value
                        ]
                    ])
                    ->toArray();

                // Only return if there are actual changes
                // If no fields changed, return null (will be filtered out later)
                return !empty($fieldChanges) ? [
                    'id' => $itemA['id'],           // Which item changed
                    'changes' => $fieldChanges,     // What changed in this item
                ] : null;
            })

            // Remove null values (items with no actual changes)
            ->filter()

            // Reset array keys to 0, 1, 2...
            ->values()
            ->toArray();

        // Step 5: Return the complete diff report
        return [
            'added' => $added,      // Brand new items
            'removed' => $removed,  // Items that were deleted
            'changed' => $changed,  // Items that were modified
        ];
    }
}



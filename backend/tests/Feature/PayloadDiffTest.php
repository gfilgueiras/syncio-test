<?php

namespace Tests\Feature;

use Tests\TestCase;

class PayloadDiffTest extends TestCase
{
    public function test_it_accepts_both_payloads_and_returns_diff(): void
    {
        $p1 = [
            'id' => 432232523,
            'title' => 'Syncio T-Shirt',
            'description' => '<p>Lorem ipsum</p>',
            'images' => [
                ['id' => 1, 'position' => 1, 'url' => 'a.png'],
            ],
            'variants' => [
                ['id' => 10, 'sku' => 'A', 'barcode' => 'B', 'image_id' => 1, 'inventory_quantity' => 1],
            ],
        ];

        $p2 = [
            'id' => 432232523,
            'title' => 'Syncio T-Shirt',
            'description' => '<p>Lorem ipsum changed</p>',
            'images' => [
                ['id' => 1, 'position' => 1, 'url' => 'b.png'],
                ['id' => 2, 'position' => 2, 'url' => 'c.png'],
            ],
            'variants' => [
                ['id' => 10, 'sku' => 'A', 'barcode' => 'B', 'image_id' => 1, 'inventory_quantity' => 2],
            ],
        ];

        $this->postJson('/api/payloads/first', $p1)->assertOk();
        $this->postJson('/api/payloads/second', $p2)->assertOk();

        $res = $this->getJson('/api/diff')->assertOk()->json();

        $this->assertEquals('done', $res['status']);
        $this->assertArrayHasKey('rootChanges', $res['diff']);
        $this->assertArrayHasKey('images', $res['diff']);
        $this->assertArrayHasKey('variants', $res['diff']);

        $this->assertNotEmpty($res['diff']['images']['added']);
        $this->assertNotEmpty($res['diff']['images']['changed']);
        $this->assertNotEmpty($res['diff']['variants']['changed']);
    }

    public function test_diff_pending_when_missing_payload(): void
    {
        $this->getJson('/api/diff')
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
            ]);
    }
}



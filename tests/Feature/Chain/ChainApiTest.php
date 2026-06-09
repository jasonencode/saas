<?php

namespace Tests\Feature\Chain;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChainApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /api/chain/networks ──────────────────────────────────

    public function test_networks_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/chain/networks');

        $response->assertOk();
    }

    // ─── GET /api/chain/contracts ─────────────────────────────────

    public function test_contracts_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/chain/contracts');

        $response->assertOk();
    }

    // ─── GET /api/chain/contracts/{contract} ──────────────────────

    public function test_contract_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/chain/contracts/99999');

        $response->assertNotFound();
    }

    // ─── GET /api/chain/certificates ──────────────────────────────

    public function test_certificates_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/chain/certificates');

        $response->assertOk();
    }

    // ─── POST /api/chain/certificates ─────────────────────────────

    public function test_certificates_create_endpoint_returns_success(): void
    {
        $response = $this->postJson('/api/chain/certificates');

        $response->assertOk();
    }

    // ─── GET /api/chain/certificates/{certificate} ────────────────

    public function test_certificate_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/chain/certificates/99999');

        $response->assertNotFound();
    }

    // ─── GET /api/chain/addresses ─────────────────────────────────

    public function test_addresses_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/chain/addresses');

        $response->assertOk();
    }
}

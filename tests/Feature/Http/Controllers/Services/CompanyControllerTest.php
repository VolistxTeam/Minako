<?php

namespace Tests\Feature\Http\Controllers\Services;

use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Services\CompanyController
 */
class CompanyControllerTest extends TestCase
{
    /**
     * @test
     */
    public function get_company_returns_an_ok_response(): void
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->getJson('minako/company/{id}');

        $response->assertOk();
        $response->assertJsonStructure([
            // TODO: compare expected response data
        ]);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function search_returns_an_ok_response(): void
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->getJson('minako/company/search/{name}');

        $response->assertOk();
        $response->assertJsonStructure([
            // TODO: compare expected response data
        ]);

        // TODO: perform additional assertions
    }

    // test cases...
}
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StreamAPITest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_the_api_returns_unauthorized_response_when_there_is_no_auth_token_in_the_hearder_or_when_there_is_no_code_in_the_request()
    {
        $response = $this->get('http://localhost:9003/api/getStreamData?getuserfollowedstreams=true&gettotalnumberofstreams=true&gettopgamesbyviewercount=true&gettopstreamsbyviewercount=true&gettotalnumberofstreamsbystarttime=true&pageindex=0&pagesize=20');
        $response->assertStatus(401);
    }

    public function test_the_api_returns_a_successful_response_when_code_is_present_in_the_request()
    {
        $response = $this->get('http://localhost:9003/api/getStreamData?getuserfollowedstreams=true&gettotalnumberofstreams=true&gettopgamesbyviewercount=true&gettopstreamsbyviewercount=true&gettotalnumberofstreamsbystarttime=true&pageindex=0&pagesize=20&code=accesscode');
        $response->assertStatus(200);
    }

    public function test_the_api_returns_a_successful_response_when_auth_token_is_present_in_request_header()
    {
        $response = $this->withHeaders([
            'authtoken' => 'Value',
        ])->json('GET', 'http://localhost:9003/api/getStreamData?getuserfollowedstreams=true&gettotalnumberofstreams=true&gettopgamesbyviewercount=true&gettopstreamsbyviewercount=true&gettotalnumberofstreamsbystarttime=true&pageindex=0&pagesize=20');

        $response->assertStatus(200);
    }
}

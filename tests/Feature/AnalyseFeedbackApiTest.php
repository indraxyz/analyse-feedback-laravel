<?php

use App\Exceptions\AnthropicApiKeyMissingException;
use App\Exceptions\AnthropicServiceException;
use App\Services\AnalyseFeedbackService;

test('health endpoint returns ok', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJson(['status' => 'ok']);
});

test('analyse-feedback returns 400 when feedback_text is missing', function () {
    $response = $this->postJson('/api/analyse-feedback', []);

    $response->assertStatus(400)
        ->assertJsonStructure(['message']);
});

test('analyse-feedback returns 400 when feedback_text is empty', function () {
    $response = $this->postJson('/api/analyse-feedback', [
        'feedback_text' => '',
    ]);

    $response->assertStatus(400)
        ->assertJsonStructure(['message']);
});

test('analyse-feedback returns 400 when feedback_text is whitespace only', function () {
    $response = $this->postJson('/api/analyse-feedback', [
        'feedback_text' => "   \n\t  ",
    ]);

    $response->assertStatus(400)
        ->assertJsonStructure(['message']);
});

test('analyse-feedback returns 200 with summary, sentiment and language when service succeeds', function () {
    $this->mock(AnalyseFeedbackService::class, function ($mock) {
        $mock->shouldReceive('analyse')
            ->once()
            ->with('Great product!')
            ->andReturn([
                'summary' => 'Customer is satisfied with the product.',
                'sentiment' => 'positive',
                'language' => 'english',
            ]);
    });

    $response = $this->postJson('/api/analyse-feedback', [
        'feedback_text' => 'Great product!',
    ]);

    $response->assertOk()
        ->assertJsonPath('summary', 'Customer is satisfied with the product.')
        ->assertJsonPath('sentiment', 'positive')
        ->assertJsonPath('language', 'english')
        ->assertJsonStructure(['summary', 'sentiment', 'language']);
});

test('analyse-feedback returns 503 when API key is not configured', function () {
    $this->mock(AnalyseFeedbackService::class, function ($mock) {
        $mock->shouldReceive('analyse')
            ->once()
            ->andThrow(new AnthropicApiKeyMissingException);
    });

    $response = $this->postJson('/api/analyse-feedback', [
        'feedback_text' => 'Some feedback',
    ]);

    $response->assertStatus(503)
        ->assertJson(['message' => 'API_KEY_MISSING']);
});

test('analyse-feedback returns 502 when service throws AnthropicServiceException', function () {
    $this->mock(AnalyseFeedbackService::class, function ($mock) {
        $mock->shouldReceive('analyse')
            ->once()
            ->andThrow(new AnthropicServiceException('AI service error or invalid response'));
    });

    $response = $this->postJson('/api/analyse-feedback', [
        'feedback_text' => 'Some feedback',
    ]);

    $response->assertStatus(502)
        ->assertJsonStructure(['message']);
});

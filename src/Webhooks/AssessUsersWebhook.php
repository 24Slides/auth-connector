<?php

namespace Slides\Connector\Auth\Webhooks;

use Slides\Connector\Auth\Services\Assessment\AssessmentService;

/**
 * Class AssessUsersWebhook
 *
 * @package Slides\Connector\Auth\Webhooks
 */
class AssessUsersWebhook extends Webhook
{
    /**
     * @var AssessmentService
     */
    protected $assessmentService;

    /**
     * AssessUsersWebhook constructor.
     *
     * @param AssessmentService $assessmentService
     */
    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * The payload validation rules.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [];
    }
    
    /**
     * Handle the incoming request.
     *
     * @param array $payload
     *
     * @return array
     */
    public function handle(array $payload)
    {
        return $this->assessmentService->differentiateUsers(
            array_get($payload, 'keys', [])
        );
    }
}
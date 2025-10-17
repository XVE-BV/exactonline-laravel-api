<?php

declare(strict_types=1);

namespace Skylence\ExactonlineLaravelApi\Events\Webhooks;

class ProjectUpdated extends BaseWebhookEvent
{
    public function getProjectId(): ?string { return $this->getEntityId(); }
    public function getCode(): ?string { return $this->getData('Code'); }
    public function getDescription(): ?string { return $this->getData('Description'); }
    public function getModifiedFields(): array { return $this->getData('ModifiedFields', []); }
}

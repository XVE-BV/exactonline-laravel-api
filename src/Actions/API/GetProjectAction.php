<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Project;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class GetProjectAction
{
    use HandlesExactConnection;

    /**
     * Retrieve a single project from Exact Online.
     *
     * @param  string  $projectId  The Exact Online project ID (GUID)
     * @return array<string, mixed>|null
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $projectId): ?array
    {
        $picqerConnection = $this->prepareConnection($connection);

        try {
            $project = new Project($picqerConnection);

            $result = $project->find($projectId);

            $this->completeRequest($connection, $picqerConnection);

            if (! $result) {
                return null;
            }

            Log::info('Retrieved project from Exact Online', [
                'connection_id' => $connection->id,
                'project_id' => $projectId,
            ]);

            return $result->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve project from Exact Online', [
                'connection_id' => $connection->id,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to retrieve project: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}

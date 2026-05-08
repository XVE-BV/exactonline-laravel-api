<?php

declare(strict_types=1);

namespace XVE\ExactonlineLaravelApi\Actions\API;

use Illuminate\Support\Facades\Log;
use Picqer\Financials\Exact\Project;
use XVE\ExactonlineLaravelApi\Concerns\HandlesExactConnection;
use XVE\ExactonlineLaravelApi\Concerns\ValidatesPayload;
use XVE\ExactonlineLaravelApi\Exceptions\ConnectionException;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

class UpdateProjectAction
{
    use HandlesExactConnection;
    use ValidatesPayload;

    /**
     * Update an existing project in Exact Online.
     *
     * @param  string  $projectId  The Exact Online project ID (GUID)
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function execute(ExactConnection $connection, string $projectId, array $data): array
    {
        $this->validateUpdatePayload('Project', $data);

        $picqerConnection = $this->prepareConnection($connection);

        try {
            $project = new Project($picqerConnection);
            $project->ID = $projectId;

            foreach ($data as $key => $value) {
                $project->{$key} = $value;
            }

            $project->save();

            $this->completeRequest($connection, $picqerConnection);

            Log::info('Updated project in Exact Online', [
                'connection_id' => $connection->id,
                'project_id' => $projectId,
            ]);

            return $project->attributes();

        } catch (\Exception $e) {
            Log::error('Failed to update project in Exact Online', [
                'connection_id' => $connection->id,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            throw new ConnectionException(
                'Failed to update project: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use App\Http\Api\Requests\StoreTeamRequest;
use App\Http\Api\Requests\UpdateTeamRequest;
use App\Http\Api\Resources\TeamResource;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

final class TeamController extends Controller
{
    /**
     * List teams
     *
     * Returns one page of teams, newest first. Archived teams are included only when
     * the `status` filter asks for them.
     */
    #[QueryParameter(name: 'page', description: 'Page to fetch.', type: 'int', default: 1, example: 2)]
    #[QueryParameter(name: 'per_page', description: 'How many teams per page, up to 100.', type: 'int', default: 15, example: 25)]
    #[QueryParameter(name: 'status', description: 'Keep only the teams in this lifecycle state.', type: TeamStatus::class)]
    #[QueryParameter(name: 'search', description: 'Matches the team name or slug.', type: 'string', example: 'he4rt')]
    public function index(): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->when(request()->string('status')->value() !== '', fn ($query) => $query->where('status', request()->string('status')))
            ->when(request()->string('search')->value() !== '', function ($query): void {
                $search = '%'.request()->string('search').'%';
                $query->where(fn (Builder $inner) => $inner->where('name', 'ilike', $search)->orWhere('slug', 'ilike', $search));
            })
            ->latest()
            ->paginate(min(request()->integer('per_page', 15), 100));

        return TeamResource::collection($teams);
    }

    /**
     * Show a team
     *
     * Returns one team by its identifier.
     */
    public function show(Team $team): TeamResource
    {
        return new TeamResource($team);
    }

    /**
     * Create a team
     *
     * The team starts as `active` unless the payload says otherwise.
     */
    #[Response(status: 201, description: 'The created team.')]
    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::query()->create([
            ...$request->validated(),
            'status' => $request->enum('status', TeamStatus::class) ?? TeamStatus::Active,
        ]);

        return new TeamResource($team)->response()->setStatusCode(201);
    }

    /**
     * Update a team
     *
     * Only the fields present in the payload change; everything else is left alone.
     */
    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $team->update($request->validated());

        return new TeamResource($team->refresh());
    }

    /**
     * Delete a team
     *
     * Soft deletes the team, so the record can be restored later.
     */
    #[Response(status: 204, description: 'The team was deleted.')]
    public function destroy(Team $team): JsonResponse
    {
        $team->delete();

        return response()->json(status: 204);
    }
}

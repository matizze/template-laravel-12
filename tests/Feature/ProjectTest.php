<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Workspace::forgetCurrent();
        parent::tearDown();
    }

    /** @return array{User, Workspace} */
    private function createWorkspaceWithOwner(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        return [$user, $workspace];
    }

    // T108: test_projects_are_scoped_to_current_workspace
    public function test_projects_are_scoped_to_current_workspace(): void
    {
        [$user1, $workspace1] = $this->createWorkspaceWithOwner();
        [$user2, $workspace2] = $this->createWorkspaceWithOwner();

        $project1 = Project::factory()->create([
            'workspace_id' => $workspace1->id,
            'user_id' => $user1->id,
        ]);
        $project2 = Project::factory()->create([
            'workspace_id' => $workspace1->id,
            'user_id' => $user1->id,
        ]);
        $project3 = Project::factory()->create([
            'workspace_id' => $workspace2->id,
            'user_id' => $user2->id,
        ]);

        Workspace::setCurrent($workspace1->id);

        $projects = Project::all();

        $this->assertCount(2, $projects);
        $this->assertTrue($projects->contains($project1));
        $this->assertTrue($projects->contains($project2));
        $this->assertFalse($projects->contains($project3));
    }

    // T109: test_project_auto_assigns_workspace_id
    public function test_project_auto_assigns_workspace_id(): void
    {
        [$user, $workspace] = $this->createWorkspaceWithOwner();

        Workspace::setCurrent($workspace->id);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workspace_id' => null,
        ]);

        $this->assertEquals($workspace->id, $project->fresh()->workspace_id);
    }

    // T110: test_cannot_access_projects_from_other_workspace
    public function test_cannot_access_projects_from_other_workspace(): void
    {
        [$user1, $workspace1] = $this->createWorkspaceWithOwner();
        [$user2, $workspace2] = $this->createWorkspaceWithOwner();

        $project = Project::factory()->create([
            'workspace_id' => $workspace1->id,
            'user_id' => $user1->id,
        ]);

        Workspace::setCurrent($workspace2->id);

        $projects = Project::all();

        $this->assertCount(0, $projects);
        $this->assertFalse($projects->contains($project));
    }
}

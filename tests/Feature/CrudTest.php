<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $o = []): array
    {
        return array_merge([
            'name' => 'Asha Rai', 'email' => 'asha@example.com', 'phone' => '9800000000',
            'address' => 'Kathmandu', 'date_of_birth' => '2000-01-31',
        ], $o);
    }

    private function course(array $o = []): array
    {
        return array_merge([
            'name' => 'Laravel', 'description' => 'Intro', 'duration' => 8,
            'fee' => '199.50', 'difficulty' => 'Medium', 'is_active' => '1',
        ], $o);
    }

    public function test_student_full_crud(): void
    {
        $this->get('/students/create')->assertOk();
        $this->post('/students', $this->student())->assertRedirect('/students');
        $s = Student::firstOrFail();

        $this->get('/students')->assertOk()->assertSee('Asha Rai')->assertSee('Student Asha Rai created');
        $this->get("/students/{$s->id}")->assertOk()->assertSee('2000-01-31');
        $this->get("/students/{$s->id}/edit")->assertOk()->assertSee('value="2000-01-31"', false);

        // update keeping own email must pass uniqueness
        $this->put("/students/{$s->id}", $this->student(['name' => 'Asha R.']))
            ->assertRedirect("/students/{$s->id}");
        $this->assertSame('Asha R.', $s->fresh()->name);

        $this->delete("/students/{$s->id}")->assertRedirect('/students');
        $this->assertDatabaseCount('students', 0);
    }

    public function test_student_validation(): void
    {
        $this->post('/students', [])->assertSessionHasErrors(['name', 'email', 'phone']);
        $this->post('/students', $this->student());
        $this->post('/students', $this->student())->assertSessionHasErrors('email'); // duplicate
        $other = Student::create($this->student(['email' => 'b@example.com']));
        $this->put("/students/{$other->id}", $this->student())->assertSessionHasErrors('email');
        $this->get('/students/999')->assertNotFound();
    }

    public function test_course_full_crud(): void
    {
        $this->get('/courses/create')->assertOk();
        $this->post('/courses', $this->course())->assertRedirect('/courses');
        $c = Course::firstOrFail();
        $this->assertTrue($c->is_active);

        $this->get('/courses')->assertOk()->assertSee('Laravel')->assertSee('199.50');
        $this->get("/courses/{$c->id}")->assertOk()->assertSee('Medium');
        $this->get("/courses/{$c->id}/edit")->assertOk();

        // unchecked checkbox => inactive
        $this->put("/courses/{$c->id}", $this->course(['is_active' => null, 'difficulty' => 'Hard']))
            ->assertRedirect("/courses/{$c->id}");
        $c->refresh();
        $this->assertFalse($c->is_active);
        $this->assertSame('Hard', $c->difficulty);

        $this->delete("/courses/{$c->id}")->assertRedirect('/courses');
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_course_validation(): void
    {
        $this->post('/courses', [])->assertSessionHasErrors(['name', 'duration', 'fee', 'difficulty']);
        $this->post('/courses', $this->course(['difficulty' => 'Extreme']))->assertSessionHasErrors('difficulty');
        $this->post('/courses', $this->course(['duration' => 0]))->assertSessionHasErrors('duration');
        $this->post('/courses', $this->course(['fee' => -5]))->assertSessionHasErrors('fee');
        $this->assertDatabaseCount('courses', 0);
    }
}

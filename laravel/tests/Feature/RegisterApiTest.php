<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;

class RegisterApiTest extends TestCase
{
	use RefreshDatabase;

	public function testRegisterUser()
	{
		$data = [
			'name' => 'vuesplash user',
			'email' => 'dummy@email.com',
			'password' => 'test1234',
			'password_confirmation' => 'test1234',
		];

		$response = $this->json('POST', route('register'), $data);

		$user = User::first();
		$this->assertEquals($data['name'], $user->name);

		$response
			->assertStatus(201)
			->assertJson(['name' => $user->name]);
	}
}